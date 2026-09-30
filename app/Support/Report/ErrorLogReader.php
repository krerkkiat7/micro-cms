<?php

namespace App\Support\Report;

use Carbon\CarbonImmutable;
use SplFileObject;

/**
 * อ่านไฟล์ error log แบบ JSON (json-error-{front,admin}-YYYY-MM-DD.log — 1 บรรทัด = 1 รายการ, เขียนโดย channel error_*_json)
 * สำหรับหน้า "ตรวจสอบ Error" ในหลังบ้าน (Admin\System\ErrorViewerController) — ไฟล์ text-error-* มีเนื้อหาเดียวกันไว้เปิดอ่านเอง
 *
 * ความปลอดภัย: side / วันที่ / รหัส ถูกตรวจรูปแบบก่อนประกอบชื่อไฟล์เสมอ (ไม่มีทางอ่านไฟล์นอกโฟลเดอร์ log)
 * ประสิทธิภาพ: รายการวันที่ได้จากชื่อไฟล์ (ไม่เปิดไฟล์), อ่านทีละบรรทัด, จำกัด MAX_ENTRIES รายการต่อวัน,
 * ค้นรหัสด้วย str_contains กับบรรทัดดิบก่อน json_decode
 */
final class ErrorLogReader
{
    public const SIDES = ['front', 'admin'];

    /** จำนวนรายการสูงสุดที่อ่านต่อวัน — เกินนี้ตัดทิ้งและแจ้ง truncated */
    public const MAX_ENTRIES = 10000;

    private const DATE_PATTERN = '/^\d{4}-\d{2}-\d{2}$/';

    private const REFERENCE_PATTERN = '/^ERR-[2-9A-Z]{8}$/';

    public static function isSide(mixed $side): bool
    {
        return in_array($side, self::SIDES, true);
    }

    public static function isDate(mixed $date): bool
    {
        return is_string($date) && preg_match(self::DATE_PATTERN, $date) === 1;
    }

    public static function isReference(mixed $reference): bool
    {
        return is_string($reference) && preg_match(self::REFERENCE_PATTERN, $reference) === 1;
    }

    /**
     * วันที่ที่มีไฟล์ log ของฝั่งนั้น เรียงใหม่ → เก่า
     *
     * @return list<string>
     */
    public static function dates(string $side): array
    {
        if (! self::isSide($side)) {
            return [];
        }

        [$directory, $prefix] = self::location($side);
        $dates = [];

        foreach (glob($directory.DIRECTORY_SEPARATOR.$prefix.'-*.log') ?: [] as $file) {
            $date = substr(basename($file, '.log'), strlen($prefix) + 1);

            if (self::isDate($date) && filesize($file) > 0) {
                $dates[] = $date;
            }
        }

        rsort($dates);

        return $dates;
    }

    /**
     * รายการ error ของวันหนึ่ง (ล่าสุดก่อน) — กรองด้วย $q (รหัส / ข้อความ / URL / ประเภท) ไม่สนตัวพิมพ์เล็กใหญ่
     *
     * @return array{rows: list<array<string, mixed>>, truncated: bool}
     */
    public static function entries(string $side, string $date, ?string $q = null): array
    {
        $file = self::file($side, $date);

        if ($file === null) {
            return ['rows' => [], 'truncated' => false];
        }

        $needle = $q !== null && trim($q) !== '' ? mb_strtolower(trim($q)) : null;
        $rows = [];
        $truncated = false;

        foreach (self::lines($file) as $line) {
            if (count($rows) >= self::MAX_ENTRIES) {
                $truncated = true;
                break;
            }

            $entry = self::decode($line);

            if ($entry === null) {
                continue;
            }

            $row = self::row($entry, $side);

            if ($needle !== null && ! str_contains(mb_strtolower($row['reference'].' '.$row['message'].' '.$row['url'].' '.$row['class']), $needle)) {
                continue;
            }

            $rows[] = $row;
        }

        // ไฟล์เขียนต่อท้าย = เก่า → ใหม่ กลับลำดับให้ล่าสุดอยู่บน
        return ['rows' => array_reverse($rows), 'truncated' => $truncated];
    }

    /**
     * กลุ่ม error ที่เกิดซ้ำ (ประเภท + ตำแหน่งในโค้ด) เรียงตามจำนวน
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{class: string, file: string|null, count: int, last_at: string|null, reference: string|null}>
     */
    public static function summary(array $rows, int $limit = 5): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = $row['class'].'|'.($row['file'] ?? '');
            $groups[$key] ??= ['class' => $row['class'], 'file' => $row['file'], 'count' => 0, 'last_at' => $row['datetime'], 'reference' => $row['reference']];
            $groups[$key]['count']++;
        }

        usort($groups, fn (array $a, array $b) => $b['count'] <=> $a['count']);

        return array_slice(array_values($groups), 0, $limit);
    }

    /**
     * หารายละเอียดเต็มจากรหัสอ้างอิง ในไฟล์ของทุกฝั่ง (ใหม่ → เก่า)
     *
     * @return array<string, mixed>|null
     */
    public static function find(string $reference): ?array
    {
        if (! self::isReference($reference)) {
            return null;
        }

        $files = [];

        foreach (self::SIDES as $side) {
            foreach (self::dates($side) as $date) {
                $files[] = [$date, $side];
            }
        }

        usort($files, fn (array $a, array $b) => strcmp($b[0], $a[0]));
        $needle = '"'.$reference.'"';

        foreach ($files as [$date, $side]) {
            foreach (self::lines(self::file($side, $date)) as $line) {
                if (! str_contains($line, $needle)) {
                    continue;
                }

                $entry = self::decode($line);

                if ($entry !== null && ($entry['context']['reference'] ?? null) === $reference) {
                    return self::detail($entry, $side);
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function row(array $entry, string $side): array
    {
        $context = is_array($entry['context'] ?? null) ? $entry['context'] : [];
        [$class, $message] = self::splitMessage((string) ($entry['message'] ?? ''));

        return [
            'side' => $side,
            'datetime' => self::datetime($entry['datetime'] ?? null),
            'reference' => (string) ($context['reference'] ?? ''),
            'class' => $class,
            'class_short' => class_basename($class),
            'message' => mb_substr($message, 0, 300),
            'file' => isset($context['file']) ? (string) $context['file'] : null,
            'url' => (string) ($context['url'] ?? ''),
            'method' => isset($context['method']) ? (string) $context['method'] : null,
            'user_id' => isset($context['user_id']) ? (int) $context['user_id'] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function detail(array $entry, string $side): array
    {
        $context = is_array($entry['context'] ?? null) ? $entry['context'] : [];
        [$class, $message] = self::splitMessage((string) ($entry['message'] ?? ''));
        $userId = isset($context['user_id']) ? (int) $context['user_id'] : null;
        $user = $userId !== null ? (AccessLogReport::userInfo([$userId])[$userId] ?? null) : null;

        return [
            'side' => $side,
            'datetime' => self::datetime($entry['datetime'] ?? null),
            'reference' => (string) ($context['reference'] ?? ''),
            'class' => $class,
            'message' => $message,
            'file' => $context['file'] ?? null,
            'url' => $context['url'] ?? null,
            'method' => $context['method'] ?? null,
            'route' => $context['route'] ?? null,
            'user_id' => $userId,
            'user_name' => $user['name'] ?? null,
            'user_deleted' => (bool) ($user['deleted'] ?? false),
            'front_user_id' => $context['front_user_id'] ?? null,
            'ip' => $context['ip'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'referer' => $context['referer'] ?? null,
            'input_keys' => is_array($context['input_keys'] ?? null) ? array_values($context['input_keys']) : [],
            'trace' => $context['trace'] ?? null,
        ];
    }

    /** "App\Foo\BarException: ข้อความ" → [class, ข้อความ] */
    private static function splitMessage(string $message): array
    {
        if (preg_match('/^([A-Za-z_\\\\][A-Za-z0-9_\\\\]*): (.*)$/s', $message, $m) === 1) {
            return [$m[1], $m[2]];
        }

        return ['', $message];
    }

    private static function datetime(mixed $value): ?string
    {
        try {
            return $value ? CarbonImmutable::parse((string) $value)->toIso8601String() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decode(string $line): ?array
    {
        $line = trim($line);

        if ($line === '' || $line[0] !== '{') {
            return null;
        }

        $entry = json_decode($line, true);

        return is_array($entry) ? $entry : null;
    }

    /**
     * @return iterable<string>
     */
    private static function lines(?string $file): iterable
    {
        if ($file === null) {
            return;
        }

        $handle = new SplFileObject($file, 'r');

        while (! $handle->eof()) {
            $line = $handle->fgets();

            if ($line !== '') {
                yield $line;
            }
        }
    }

    /** path ของไฟล์วันนั้น — null เมื่อ side/วันที่ไม่ถูกรูปแบบ หรือไม่มีไฟล์ */
    private static function file(string $side, string $date): ?string
    {
        if (! self::isSide($side) || ! self::isDate($date)) {
            return null;
        }

        [$directory, $prefix] = self::location($side);
        $file = $directory.DIRECTORY_SEPARATOR."{$prefix}-{$date}.log";

        return is_file($file) ? $file : null;
    }

    /**
     * โฟลเดอร์ + ชื่อไฟล์ฐาน จาก config ของ channel (daily เติม -YYYY-MM-DD ต่อท้ายชื่อฐาน)
     *
     * @return array{0: string, 1: string}
     */
    private static function location(string $side): array
    {
        $path = (string) config("logging.channels.error_{$side}_json.path", storage_path("logs/json-error-{$side}.log"));

        return [dirname($path), basename($path, '.log')];
    }
}
