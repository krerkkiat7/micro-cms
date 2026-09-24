<?php

namespace App\Support\Front;

use App\Support\ClientIp;
use App\Support\Front\Views\ViewBuffer;
use App\Support\UserAgentParser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

use function Illuminate\Support\defer;

/**
 * นับยอดเข้าชมรายละเอียดบทความ / หน้าเพจ — ออกแบบให้ "ไม่ทำให้หน้าเว็บช้า" แม้มีผู้เข้าชมหน้าเดิมพร้อมกันจำนวนมาก
 * (ระบบเดิม insert → select count → update ทุก request ทำให้ล็อกแถวเดิมซ้ำ ๆ จนหน่วงทั้งไซต์)
 *
 * ขั้นตอน:
 * 1. ระหว่าง request: ข้ามบอท + ข้ามการเปิดซ้ำของ session เดิมภายใน front.views.dedupe_minutes (เช็กใน session — ไม่แตะ DB)
 * 2. หลังส่ง response (defer): โหมด redis = RPUSH ลงคิว (ไม่แตะ DB เลย); โหมด database = insert + update ตรง
 * 3. คำสั่ง `front:flush-views` (schedule ทุกนาที) ดึงคิวทีละชุด → insert ประวัติแบบ batch 1 คำสั่ง → update view_amount
 *    1 คำสั่งต่อชุด (บวกเพิ่มจากจำนวนในชุด ไม่ count ตารางประวัติ) — คิวยาวเกิน flush_threshold จะ flush เองทันที
 *
 * view_amount อัปเดตผ่าน query builder (ไม่ผ่าน model event) จึงไม่ทำให้ cache หน้าบ้านถูกล้าง และไม่แตะ updated_at
 */
final class ViewCounter
{
    /** ประเภท → ตารางประวัติ, คอลัมน์ FK, ตาราง info */
    public const TYPES = [
        'article' => ['table' => 'article_item_view', 'fk' => 'article_item_info_id', 'info' => 'article_item_info'],
        'page' => ['table' => 'page_item_view', 'fk' => 'page_item_info_id', 'info' => 'page_item_info'],
    ];

    private const SESSION_KEY = 'front_viewed';

    private const LOCK_KEY = 'front:views:flush';

    /**
     * @param  ViewBuffer|null  $buffer  null = โหมด database (บันทึกตรงหลังส่ง response)
     */
    public function __construct(private readonly ?ViewBuffer $buffer = null) {}

    public function buffered(): bool
    {
        return $this->buffer !== null;
    }

    /**
     * นับการเข้าชม 1 ครั้งของเนื้อหา — คืน true ถ้านับ (false = บอท / เปิดซ้ำในช่วง dedupe)
     */
    public function hit(string $type, int $id, string $lang): bool
    {
        self::assertType($type);
        $request = request();

        if (UserAgentParser::parse((string) $request->userAgent())['robot'] !== null) {
            return false;
        }

        if ($request->hasSession() && ! $this->markSession($request->session(), "{$type}.{$id}")) {
            return false;
        }

        $row = [
            'id' => $id,
            'user_id' => Auth::id(),
            'lang' => $lang,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'remote_ip' => ClientIp::from($request),
            'at' => now()->format('Y-m-d H:i:s'),
        ];

        // หลังส่ง response เฉพาะ request นี้ (defer — ไม่สะสมข้าม request แบบ app()->terminating())
        defer(function () use ($type, $row) {
            try {
                if ($this->buffer === null) {
                    $this->write($type, [$row]);

                    return;
                }

                $this->buffer->push($type, $row);

                if ($this->buffer->size($type) >= (int) config('front.views.flush_threshold', 1000)) {
                    $this->flush($type, 5);
                }
            } catch (Throwable $e) {
                report($e); // ยอดเข้าชมพลาดไม่ควรทำให้ผู้เข้าชมเห็น error (response ส่งไปแล้ว)
            }
        });

        return true;
    }

    /**
     * บันทึกคิวลงฐานข้อมูลเป็นชุด (ใช้ lock กันรันซ้อน) — คืนจำนวนรายการที่บันทึก
     *
     * @param  string|null  $type  null = ทุกประเภท
     */
    public function flush(?string $type = null, int $maxBatches = 50): int
    {
        if ($this->buffer === null) {
            return 0;
        }

        $types = $type !== null ? [$type] : array_keys(self::TYPES);
        $written = 0;

        Cache::lock(self::LOCK_KEY, 120)->get(function () use ($types, $maxBatches, &$written) {
            $batchSize = max(1, (int) config('front.views.batch_size', 1000));

            foreach ($types as $type) {
                self::assertType($type);

                for ($i = 0; $i < $maxBatches; $i++) {
                    $rows = $this->buffer->pull($type, $batchSize);

                    if ($rows === []) {
                        break;
                    }

                    try {
                        $this->write($type, $rows);
                    } catch (Throwable $e) {
                        $this->buffer->restore($type, $rows);

                        throw $e;
                    }

                    $written += count($rows);
                }
            }
        });

        return $written;
    }

    /**
     * insert ประวัติแบบ batch + บวก view_amount ตามจำนวนต่อรายการ (1 คำสั่งต่อชุด) ในทรานแซกชันเดียว
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function write(string $type, array $rows): void
    {
        self::assertType($type);
        ['table' => $table, 'fk' => $fk, 'info' => $info] = self::TYPES[$type];

        $records = [];
        $counts = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $at = (string) ($row['at'] ?? now()->format('Y-m-d H:i:s'));
            $records[] = [
                $fk => $id,
                'user_id' => $row['user_id'] ?? null,
                'lang' => $row['lang'] ?? null,
                'session_id' => $row['session_id'] ?? null,
                'remote_ip' => $row['remote_ip'] ?? null,
                'geo_ip' => null,
                'action_date' => substr($at, 0, 10),
                'status' => 'Y',
                'created_by' => $row['user_id'] ?? null,
                'created_at' => $at,
                'updated_at' => $at,
            ];
            $counts[$id] = ($counts[$id] ?? 0) + 1;
        }

        if ($records === []) {
            return;
        }

        DB::transaction(function () use ($table, $info, $records, $counts) {
            foreach (array_chunk($records, 500) as $chunk) {
                DB::table($table)->insert($chunk);
            }

            $cases = [];
            $bindings = [];

            foreach ($counts as $id => $count) {
                $cases[] = 'when ? then ?';
                array_push($bindings, $id, $count);
            }

            $ids = array_keys($counts);
            DB::update(
                "update {$info} set view_amount = view_amount + (case id ".implode(' ', $cases).' else 0 end) where id in ('.implode(',', array_fill(0, count($ids), '?')).')',
                [...$bindings, ...$ids],
            );
        });
    }

    /**
     * บันทึกใน session ว่าเพิ่งเปิดเนื้อหานี้ — คืน false ถ้าเปิดไปแล้วภายในช่วง dedupe (ไม่นับซ้ำ)
     */
    private function markSession($session, string $key): bool
    {
        $window = max(0, (int) config('front.views.dedupe_minutes', 30)) * 60;
        $now = time();
        $viewed = $session->get(self::SESSION_KEY, []);
        $viewed = is_array($viewed) ? array_filter($viewed, fn ($at) => is_int($at) && $now - $at < $window) : [];

        if (isset($viewed[$key])) {
            $session->put(self::SESSION_KEY, $viewed);

            return false;
        }

        if ($window > 0) {
            $viewed[$key] = $now;
        }

        $session->put(self::SESSION_KEY, $viewed);

        return true;
    }

    private static function assertType(string $type): void
    {
        if (! isset(self::TYPES[$type])) {
            throw new InvalidArgumentException("Unknown view type [{$type}]");
        }
    }
}
