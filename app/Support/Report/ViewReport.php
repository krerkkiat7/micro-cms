<?php

namespace App\Support\Report;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ตัวคำนวณรายงานการเข้าชมจากตารางประวัติที่ ViewCounter เขียน (article_item_view / page_item_view / banner_item_click)
 *
 * ทุก method กรองตามช่วงวันที่ของ filters (คอลัมน์ action_date) + scope ที่ส่งมา (เช่น บทความเดียว / หมวดหมู่เดียว)
 * การจัดกลุ่มตามช่วงเวลาทำใน SQL ด้วยนิพจน์ที่แยกตาม driver (MySQL สำหรับใช้งานจริง / SQLite สำหรับเทส)
 * แล้วเติมช่วงที่ไม่มีข้อมูลเป็น 0 ใน PHP ให้กราฟต่อเนื่อง
 */
final class ViewReport
{
    public const PERIODS = ['day', 'week', 'month', 'year'];

    /** จำกัดช่วงวันที่สูงสุด (ปี) กันการคำนวณหนักเกินไป */
    private const MAX_YEARS = 5;

    private const THAI_MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    private const SEARCH_HOSTS = ['google.', 'bing.', 'yahoo.', 'duckduckgo.', 'baidu.', 'yandex.', 'naver.', 'ecosia.'];

    private const SOCIAL_HOSTS = ['facebook.', 'fb.', 'instagram.', 't.co', 'twitter.', 'x.com', 'line.', 'lin.ee', 'tiktok.', 'youtube.', 'youtu.be', 'linkedin.', 'pinterest.', 'reddit.', 'threads.'];

    /**
     * @param  array{date_from: string, date_to: string, period: string}  $filters
     * @param  Closure(Builder): mixed|null  $scope  จำกัดขอบเขตข้อมูล (รับ query ของตารางประวัติ)
     * @param  string  $uniqueColumn  คอลัมน์ที่นับ "ไม่ซ้ำ" (ค่า sessions ในผลลัพธ์) — ตารางที่ไม่มี session_id ใช้คอลัมน์อื่นแทน
     *                                เช่น log_back_login = username, log_back_action = user_id
     */
    public function __construct(
        private readonly string $table,
        private readonly string $fk,
        private readonly array $filters,
        private readonly ?Closure $scope = null,
        private readonly string $uniqueColumn = 'session_id',
    ) {}

    /**
     * อ่านตัวกรองจาก query string — ช่วงวันที่ (ค่าเริ่มต้น 30 วันล่าสุด) + ช่วงเวลาที่จัดกลุ่ม (ค่าเริ่มต้นเลือกตามความยาวช่วง)
     *
     * @return array{date_from: string, date_to: string, period: string}
     */
    public static function filters(Request $request): array
    {
        $today = CarbonImmutable::today();
        $to = self::parseDate($request->query('date_to')) ?? $today;
        $from = self::parseDate($request->query('date_from')) ?? $to->subDays(29);

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->lessThan($to->subYears(self::MAX_YEARS))) {
            $from = $to->subYears(self::MAX_YEARS)->addDay();
        }

        $period = $request->query('period');

        if (! in_array($period, self::PERIODS, true)) {
            $days = $from->diffInDays($to) + 1;
            $period = match (true) {
                $days <= 62 => 'day',
                $days <= 190 => 'week',
                $days <= 1100 => 'month',
                default => 'year',
            };
        }

        return [
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'period' => $period,
        ];
    }

    /**
     * query ฐานของตารางประวัติ (กรองช่วงวันที่ + scope แล้ว) — ใช้ต่อยอดสรุปแบบเฉพาะโมดูล เช่น join หมวดหมู่
     */
    public function query(?string $from = null, ?string $to = null): Builder
    {
        $query = DB::table($this->table)
            ->whereNull("{$this->table}.deleted_at")
            ->whereBetween("{$this->table}.action_date", [$from ?? $this->filters['date_from'], $to ?? $this->filters['date_to']]);

        if ($this->scope !== null) {
            ($this->scope)($query);
        }

        return $query;
    }

    public function table(): string
    {
        return $this->table;
    }

    public function fk(): string
    {
        return "{$this->table}.{$this->fk}";
    }

    /**
     * ยอดตามช่วงเวลา (เติม 0 ครบทุกช่วง)
     *
     * @return list<array{key: string, label: string, start: string, end: string, views: int, sessions: int, ips: int}>
     */
    public function series(): array
    {
        $bucket = $this->bucketExpression();

        $rows = $this->query()
            ->selectRaw("{$bucket} as bucket, count(*) as views, count(distinct {$this->table}.{$this->uniqueColumn}) as sessions, count(distinct {$this->table}.remote_ip) as ips")
            ->groupByRaw($bucket)
            ->get()
            ->keyBy('bucket');

        return array_map(function (array $bucket) use ($rows) {
            $row = $rows->get($bucket['key']);

            return $bucket + [
                'views' => (int) ($row->views ?? 0),
                'sessions' => (int) ($row->sessions ?? 0),
                'ips' => (int) ($row->ips ?? 0),
            ];
        }, $this->buckets());
    }

    /**
     * ยอดตามช่วงเวลาแยกตามค่าของคอลัมน์ (เช่น หมวดหมู่) — คืน [ค่า => [views ของแต่ละช่วงตามลำดับ buckets()]]
     *
     * @param  Closure(Builder): mixed  $join  เพิ่ม join ที่ต้องใช้กับ $column
     * @param  list<int|string>  $values  ค่าที่ต้องการ
     * @return array<int|string, list<int>>
     */
    public function seriesBy(string $column, Closure $join, array $values): array
    {
        if ($values === []) {
            return [];
        }

        $bucket = $this->bucketExpression();
        $query = $this->query();
        $join($query);

        $rows = $query
            ->whereIn($column, $values)
            ->selectRaw("{$bucket} as bucket, {$column} as grp, count(*) as views")
            ->groupByRaw("{$bucket}, {$column}")
            ->get();

        $keys = array_column($this->buckets(), 'key');
        $result = [];

        foreach ($values as $value) {
            $result[$value] = array_fill_keys($keys, 0);
        }

        foreach ($rows as $row) {
            if (isset($result[$row->grp][$row->bucket])) {
                $result[$row->grp][$row->bucket] = (int) $row->views;
            }
        }

        return array_map('array_values', $result);
    }

    /**
     * สรุปตัวเลขสำคัญ + เปรียบเทียบกับช่วงก่อนหน้าที่ยาวเท่ากัน
     *
     * @param  list<array{views: int, label: string}>|null  $series  ส่งผล series() มาได้ถ้าคำนวณไว้แล้ว (หาช่วงสูงสุด)
     * @return array<string, mixed>
     */
    public function summary(?array $series = null): array
    {
        $from = CarbonImmutable::parse($this->filters['date_from']);
        $to = CarbonImmutable::parse($this->filters['date_to']);
        $days = (int) $from->diffInDays($to) + 1;

        $current = $this->totals();
        $previousFrom = $from->subDays($days)->toDateString();
        $previousTo = $from->subDay()->toDateString();
        $previous = $this->totals($previousFrom, $previousTo);

        $series ??= $this->series();
        $peak = null;

        foreach ($series as $row) {
            if ($row['views'] > 0 && ($peak === null || $row['views'] > $peak['views'])) {
                $peak = ['label' => $row['label'], 'views' => $row['views']];
            }
        }

        $activeDays = (int) $this->query()->distinct()->count("{$this->table}.action_date");

        return [
            'views' => $current['views'],
            'sessions' => $current['sessions'],
            'ips' => $current['ips'],
            'items' => $current['items'],
            'days' => $days,
            'active_days' => $activeDays,
            'avg_per_day' => round($current['views'] / max(1, $days), 2),
            'views_per_session' => $current['sessions'] > 0 ? round($current['views'] / $current['sessions'], 2) : 0,
            'peak' => $peak,
            'previous' => [
                'date_from' => $previousFrom,
                'date_to' => $previousTo,
                'views' => $previous['views'],
                'sessions' => $previous['sessions'],
            ],
            'change' => [
                'views' => self::percentChange($previous['views'], $current['views']),
                'sessions' => self::percentChange($previous['sessions'], $current['sessions']),
            ],
        ];
    }

    /**
     * ยอดแยกตามค่าของคอลัมน์ในตารางประวัติ (lang / device_type / browser / platform) — ค่า null = ไม่ทราบ
     *
     * @return list<array{key: string|null, views: int, sessions: int}>
     */
    public function breakdown(string $column, int $limit = 20): array
    {
        $qualified = "{$this->table}.{$column}";

        return $this->query()
            ->selectRaw("{$qualified} as k, count(*) as views, count(distinct {$this->table}.{$this->uniqueColumn}) as sessions")
            ->groupBy($qualified)
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'key' => $row->k !== null && $row->k !== '' ? (string) $row->k : null,
                'views' => (int) $row->views,
                'sessions' => (int) $row->sessions,
            ])
            ->all();
    }

    /**
     * แหล่งที่มาจาก referrer — จัดเป็นกลุ่ม (เข้าตรง / ภายในเว็บ / เครื่องมือค้นหา / โซเชียล / เว็บไซต์อื่น) + host ภายนอกยอดนิยม
     * + path ภายในเว็บยอดนิยม (banner = หน้าที่มีการคลิก, บทความ/หน้าเพจ = หน้าที่กดลิงก์มา)
     *
     * @return array{sources: list<array{key: string, views: int}>, hosts: list<array{key: string, views: int}>, paths: list<array{key: string, views: int}>}
     */
    public function referrers(string $ownHost, int $hostLimit = 20): array
    {
        $rows = $this->query()
            ->selectRaw("{$this->table}.referrer as ref, count(*) as views")
            ->groupBy("{$this->table}.referrer")
            ->orderByDesc('views')
            ->limit(5000)
            ->get();

        $sources = ['direct' => 0, 'internal' => 0, 'search' => 0, 'social' => 0, 'other' => 0];
        $hosts = [];
        $paths = [];
        $ownHost = self::normalizeHost($ownHost);

        foreach ($rows as $row) {
            $views = (int) $row->views;
            $host = self::normalizeHost((string) parse_url((string) $row->ref, PHP_URL_HOST));

            if ($host === '') {
                $sources['direct'] += $views;

                continue;
            }

            if ($host === $ownHost) {
                $sources['internal'] += $views;
                $path = (string) (parse_url((string) $row->ref, PHP_URL_PATH) ?: '/');
                $paths[$path] = ($paths[$path] ?? 0) + $views;

                continue;
            }

            $sources[self::classifyHost($host)] += $views;
            $hosts[$host] = ($hosts[$host] ?? 0) + $views;
        }

        arsort($hosts);
        arsort($paths);

        $top = fn (array $counts) => array_map(
            fn ($key, $views) => ['key' => (string) $key, 'views' => $views],
            array_keys(array_slice($counts, 0, $hostLimit, true)),
            array_slice($counts, 0, $hostLimit, true),
        );

        return [
            'sources' => array_map(fn ($key) => ['key' => $key, 'views' => $sources[$key]], array_keys($sources)),
            'hosts' => $top($hosts),
            'paths' => $top($paths),
        ];
    }

    /**
     * ยอดตามวันในสัปดาห์ (0 = จันทร์) × ชั่วโมง (0-23) จากเวลาที่เข้าชมจริง (created_at)
     *
     * @return list<list<int>> [7][24]
     */
    public function heatmap(): array
    {
        [$weekday, $hour] = $this->driver() === 'sqlite'
            ? ["((cast(strftime('%w', {$this->table}.created_at) as integer) + 6) % 7)", "cast(strftime('%H', {$this->table}.created_at) as integer)"]
            : ["weekday({$this->table}.created_at)", "hour({$this->table}.created_at)"];

        $grid = array_fill(0, 7, array_fill(0, 24, 0));

        $this->query()
            ->selectRaw("{$weekday} as wd, {$hour} as hr, count(*) as views")
            ->groupByRaw("{$weekday}, {$hour}")
            ->get()
            ->each(function ($row) use (&$grid) {
                $grid[(int) $row->wd][(int) $row->hr] = (int) $row->views;
            });

        return $grid;
    }

    /**
     * รายการที่มียอดเข้าชมสูงสุด — คืน id (ค่าของ FK) พร้อมยอด เรียงมากไปน้อย
     *
     * @return list<array{id: int, views: int, sessions: int, ips: int}>
     */
    public function topIds(int $limit): array
    {
        $fk = $this->fk();

        return $this->query()
            ->selectRaw("{$fk} as item_id, count(*) as views, count(distinct {$this->table}.{$this->uniqueColumn}) as sessions, count(distinct {$this->table}.remote_ip) as ips")
            ->groupBy($fk)
            ->orderByDesc('views')
            ->orderBy($fk)
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->item_id,
                'views' => (int) $row->views,
                'sessions' => (int) $row->sessions,
                'ips' => (int) $row->ips,
            ])
            ->all();
    }

    /**
     * ช่วงเวลาทั้งหมดในช่วงวันที่ (เรียงตามเวลา) — key ตรงกับค่าที่ bucketExpression() คืน
     *
     * @return list<array{key: string, label: string, start: string, end: string}>
     */
    public function buckets(): array
    {
        $from = CarbonImmutable::parse($this->filters['date_from']);
        $to = CarbonImmutable::parse($this->filters['date_to']);
        $period = $this->filters['period'];

        $start = match ($period) {
            'week' => $from->startOfWeek(CarbonImmutable::MONDAY),
            'month' => $from->startOfMonth(),
            'year' => $from->startOfYear(),
            default => $from,
        };

        $interval = match ($period) {
            'week' => '1 week',
            'month' => '1 month',
            'year' => '1 year',
            default => '1 day',
        };

        $buckets = [];

        foreach (CarbonPeriod::create($start, $interval, $to) as $date) {
            $date = CarbonImmutable::instance($date);
            $end = match ($period) {
                'week' => $date->addDays(6),
                'month' => $date->endOfMonth(),
                'year' => $date->endOfYear(),
                default => $date,
            };

            // ช่วงแรก/สุดท้ายอาจเกินขอบของช่วงวันที่ที่เลือก — ตัดให้อยู่ในช่วง
            $clippedStart = $date->lessThan($from) ? $from : $date;
            $clippedEnd = $end->greaterThan($to) ? $to : $end;

            $buckets[] = [
                'key' => match ($period) {
                    'month' => $date->format('Y-m'),
                    'year' => $date->format('Y'),
                    default => $date->toDateString(),
                },
                'label' => self::label($period, $date, $end),
                'start' => $clippedStart->toDateString(),
                'end' => $clippedEnd->toDateString(),
            ];
        }

        return $buckets;
    }

    /**
     * ส่งออก CSV (UTF-8 + BOM ให้ Excel อ่านภาษาไทยได้)
     *
     * @param  list<string>  $headers
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function csv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** ป้ายชื่อช่วงเวลาภาษาไทย (ปี พ.ศ.) */
    public static function label(string $period, CarbonImmutable $start, CarbonImmutable $end): string
    {
        $month = fn (CarbonImmutable $d) => self::THAI_MONTHS[$d->month - 1];
        $year = fn (CarbonImmutable $d) => $d->year + 543;

        return match ($period) {
            'week' => $start->year === $end->year
                ? "{$start->day} {$month($start)} – {$end->day} {$month($end)} {$year($end)}"
                : "{$start->day} {$month($start)} {$year($start)} – {$end->day} {$month($end)} {$year($end)}",
            'month' => "{$month($start)} {$year($start)}",
            'year' => (string) $year($start),
            default => "{$start->day} {$month($start)} {$year($start)}",
        };
    }

    /**
     * @return array{views: int, sessions: int, ips: int, items: int}
     */
    private function totals(?string $from = null, ?string $to = null): array
    {
        $row = $this->query($from, $to)
            ->selectRaw("count(*) as views, count(distinct {$this->table}.{$this->uniqueColumn}) as sessions, count(distinct {$this->table}.remote_ip) as ips, count(distinct {$this->fk()}) as items")
            ->first();

        return [
            'views' => (int) ($row->views ?? 0),
            'sessions' => (int) ($row->sessions ?? 0),
            'ips' => (int) ($row->ips ?? 0),
            'items' => (int) ($row->items ?? 0),
        ];
    }

    /** นิพจน์ SQL ของ key ช่วงเวลาจาก action_date (สัปดาห์ = วันจันทร์ต้นสัปดาห์) */
    private function bucketExpression(): string
    {
        $column = "{$this->table}.action_date";

        if ($this->driver() === 'sqlite') {
            return match ($this->filters['period']) {
                'week' => "date({$column}, '-' || ((cast(strftime('%w', {$column}) as integer) + 6) % 7) || ' days')",
                'month' => "strftime('%Y-%m', {$column})",
                'year' => "strftime('%Y', {$column})",
                default => "strftime('%Y-%m-%d', {$column})",
            };
        }

        return match ($this->filters['period']) {
            'week' => "date_format(date_sub({$column}, interval weekday({$column}) day), '%Y-%m-%d')",
            'month' => "date_format({$column}, '%Y-%m')",
            'year' => "date_format({$column}, '%Y')",
            default => "date_format({$column}, '%Y-%m-%d')",
        };
    }

    private function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    private static function parseDate(mixed $value): ?CarbonImmutable
    {
        $value = trim((string) $value);

        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function percentChange(int $previous, int $current): ?float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : null; // null = ช่วงก่อนหน้าไม่มีข้อมูล เทียบเป็น % ไม่ได้
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    private static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }

    private static function classifyHost(string $host): string
    {
        foreach (self::SEARCH_HOSTS as $needle) {
            if (str_contains($host, $needle)) {
                return 'search';
            }
        }

        foreach (self::SOCIAL_HOSTS as $needle) {
            if ($host === $needle || str_starts_with($host, $needle) || str_contains($host, ".{$needle}")) {
                return 'social';
            }
        }

        return 'other';
    }
}
