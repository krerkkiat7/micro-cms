<?php

namespace App\Support\Report;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * สถิติจากตารางประวัติการเข้าหน้า log_back_access (หลังบ้าน) / log_front_access (หน้าบ้าน) — 1 แถว = การเปิดหน้า 1 ครั้ง
 * ใช้ ViewReport เป็นตัวคำนวณฐาน (FK = user_id → "items" = จำนวนผู้ใช้งาน) แล้วเพิ่ม: ผู้ใช้งาน / หน้า / เวลาที่ใช้ต่อหน้า / IP /
 * หน้าแรกที่เข้า / อัตราการออกทันที (bounce) / บอท
 *
 * เวลาที่ใช้ต่อหน้าจอ = last_visited - created_at (keep-alive จาก useAccessHeartbeat ทุก 45 วินาที + ตอนออกจากหน้า)
 * ตัดค่าแต่ละครั้งไว้ไม่เกิน DURATION_CAP วินาที กันแท็บที่เปิดทิ้งไว้ทั้งวันทำให้ค่าเฉลี่ยเพี้ยน — ดู docs/PRD-system.md
 */
final class AccessLogReport
{
    public const BACK = 'log_back_access';

    public const FRONT = 'log_front_access';

    /** เวลาสูงสุดที่นับต่อการเปิดหน้าจอ 1 ครั้ง (วินาที) */
    public const DURATION_CAP = 1800;

    /**
     * @param  array{date_from: string, date_to: string, period: string}  $filters
     * @param  bool  $excludeRobots  ตัดแถวที่เป็นบอทออก (หน้าบ้าน — log_front_access บันทึกบอทด้วย)
     */
    public static function make(string $table, array $filters, ?int $userId = null, bool $excludeRobots = false): ViewReport
    {
        return new ViewReport($table, 'user_id', $filters, function (Builder $query) use ($table, $userId, $excludeRobots) {
            $query->when($userId !== null, fn ($q) => $q->where("{$table}.user_id", $userId))
                ->when($excludeRobots, fn ($q) => $q->whereNull("{$table}.robot"));
        });
    }

    /**
     * นิพจน์ SQL เวลาที่ใช้ของแต่ละแถว (วินาที, ตัดที่ DURATION_CAP) — null เมื่อไม่มี last_visited
     */
    public static function durationExpression(string $t): string
    {
        $diff = DB::connection()->getDriverName() === 'sqlite'
            ? "((julianday({$t}.last_visited) - julianday({$t}.created_at)) * 86400)"
            : "timestampdiff(second, {$t}.created_at, {$t}.last_visited)";
        $cap = self::DURATION_CAP;

        return "(case when {$t}.last_visited is null then null when {$diff} < 0 then 0 when {$diff} > {$cap} then {$cap} else {$diff} end)";
    }

    /**
     * สรุปเวลาที่ใช้: เฉลี่ยต่อการเปิดหน้าจอ / รวมทั้งหมด / เฉลี่ยต่อ session
     *
     * @return array{avg_seconds: float, total_seconds: int, avg_session_seconds: float}
     */
    public static function durationSummary(ViewReport $report): array
    {
        $t = $report->table();
        $duration = self::durationExpression($t);

        $row = $report->query()
            ->selectRaw("avg({$duration}) as avg_seconds, sum({$duration}) as total_seconds, count(distinct {$t}.session_id) as sessions")
            ->first();

        $total = (int) round((float) ($row->total_seconds ?? 0));

        return [
            'avg_seconds' => round((float) ($row->avg_seconds ?? 0), 1),
            'total_seconds' => $total,
            'avg_session_seconds' => (int) ($row->sessions ?? 0) > 0 ? round($total / (int) $row->sessions, 1) : 0.0,
        ];
    }

    /**
     * สรุปรายผู้ใช้งาน — จำนวนเปิดหน้าจอ / session / วันที่ใช้งาน / จำนวนหน้าจอที่ต่างกัน / เวลาใช้งานรวม / เข้าใช้ครั้งแรก-ล่าสุด
     *
     * @return list<array<string, mixed>>
     */
    public static function users(ViewReport $report, int $limit = 100): array
    {
        $t = $report->table();
        $duration = self::durationExpression($t);

        $rows = $report->query()
            ->selectRaw("{$t}.user_id as user_id, count(*) as views, count(distinct {$t}.session_id) as sessions,
                count(distinct {$t}.action_date) as active_days, count(distinct {$t}.title_name) as pages,
                count(distinct {$t}.remote_ip) as ips, sum({$duration}) as total_seconds, avg({$duration}) as avg_seconds,
                min({$t}.created_at) as first_at, max({$t}.created_at) as last_at")
            ->groupBy("{$t}.user_id")
            ->orderByDesc('views')
            ->limit($limit)
            ->get();

        $users = self::userInfo($rows->pluck('user_id')->filter()->all());

        return $rows->map(fn ($row) => [
            'user_id' => $row->user_id !== null ? (int) $row->user_id : null,
            'name' => $row->user_id !== null ? ($users[$row->user_id]['name'] ?? null) : null,
            'email' => $row->user_id !== null ? ($users[$row->user_id]['email'] ?? null) : null,
            'group' => $row->user_id !== null ? ($users[$row->user_id]['group'] ?? null) : null,
            'deleted' => $row->user_id !== null && ($users[$row->user_id]['deleted'] ?? true),
            'views' => (int) $row->views,
            'sessions' => (int) $row->sessions,
            'active_days' => (int) $row->active_days,
            'pages' => (int) $row->pages,
            'ips' => (int) $row->ips,
            'total_seconds' => (int) round((float) $row->total_seconds),
            'avg_seconds' => round((float) $row->avg_seconds, 1),
            'first_at' => $row->first_at,
            'last_at' => $row->last_at,
        ])->values()->all();
    }

    /**
     * สรุปรายหน้าจอ (ตาม title_name) — จำนวนเปิด / ผู้ใช้งานไม่ซ้ำ / เวลาที่ใช้เฉลี่ย-รวม
     *
     * @return list<array<string, mixed>>
     */
    public static function pages(ViewReport $report, int $limit = 100): array
    {
        $t = $report->table();
        $duration = self::durationExpression($t);

        return $report->query()
            ->selectRaw("{$t}.title_name as title, count(*) as views, count(distinct {$t}.user_id) as users,
                count(distinct {$t}.session_id) as sessions, avg({$duration}) as avg_seconds, sum({$duration}) as total_seconds,
                max({$t}.created_at) as last_at")
            ->groupBy("{$t}.title_name")
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'title' => $row->title,
                'views' => (int) $row->views,
                'users' => (int) $row->users,
                'sessions' => (int) $row->sessions,
                'avg_seconds' => round((float) $row->avg_seconds, 1),
                'total_seconds' => (int) round((float) $row->total_seconds),
                'last_at' => $row->last_at,
            ])
            ->all();
    }

    /**
     * IP ที่เข้าใช้งานบ่อย พร้อมจำนวนผู้ใช้งานต่อ IP (IP เดียวหลายบัญชี = น่าตรวจสอบ)
     *
     * @return list<array{ip: string|null, views: int, users: int, last_at: string|null}>
     */
    public static function ips(ViewReport $report, int $limit = 30): array
    {
        $t = $report->table();

        return $report->query()
            ->selectRaw("{$t}.remote_ip as ip, count(*) as views, count(distinct {$t}.user_id) as users, max({$t}.created_at) as last_at")
            ->groupBy("{$t}.remote_ip")
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'ip' => $row->ip,
                'views' => (int) $row->views,
                'users' => (int) $row->users,
                'last_at' => $row->last_at,
            ])
            ->all();
    }

    /**
     * หน้าแรกที่เข้าของแต่ละ session (landing page) — นับจากแถวแรก (id ต่ำสุด) ของ session ในช่วงวันที่
     *
     * @return list<array{title: string|null, sessions: int}>
     */
    public static function landingPages(ViewReport $report, int $limit = 20): array
    {
        $t = $report->table();
        $firstIds = $report->query()->whereNotNull("{$t}.session_id")->selectRaw("min({$t}.id)")->groupBy("{$t}.session_id");

        return DB::table($t)
            ->whereIn('id', $firstIds)
            ->selectRaw('title_name as title, count(*) as sessions')
            ->groupBy('title_name')
            ->orderByDesc('sessions')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['title' => $row->title, 'sessions' => (int) $row->sessions])
            ->all();
    }

    /**
     * session ที่เปิดแค่หน้าเดียวแล้วออก (bounce) เทียบกับ session ทั้งหมด
     *
     * @return array{sessions: int, bounced: int, rate: float}
     */
    public static function bounce(ViewReport $report): array
    {
        $t = $report->table();
        $perSession = $report->query()->whereNotNull("{$t}.session_id")
            ->selectRaw("{$t}.session_id, count(*) as views")
            ->groupBy("{$t}.session_id");

        $row = DB::query()->fromSub($perSession, 's')
            ->selectRaw('count(*) as sessions, sum(case when views = 1 then 1 else 0 end) as bounced')
            ->first();

        $sessions = (int) ($row->sessions ?? 0);
        $bounced = (int) ($row->bounced ?? 0);

        return ['sessions' => $sessions, 'bounced' => $bounced, 'rate' => $sessions > 0 ? round($bounced / $sessions * 100, 1) : 0.0];
    }

    /**
     * ภาษาของเว็บไซต์ที่เปิด — จาก segment แรกของ uri_string (/{lang}/...) — หน้าบ้านเท่านั้น
     *
     * @return list<array{key: string|null, views: int, sessions: int}>
     */
    public static function siteLanguages(ViewReport $report): array
    {
        $t = $report->table();
        $expr = "substr({$t}.uri_string, 2, 2)";
        $third = "substr({$t}.uri_string, 4, 1)";

        return $report->query()
            ->selectRaw("case when ({$third} = '/' or {$third} = '') and length({$expr}) = 2 then {$expr} end as k, count(*) as views, count(distinct {$t}.session_id) as sessions")
            ->groupByRaw('k')
            ->orderByDesc('views')
            ->get()
            ->map(fn ($row) => ['key' => $row->k !== null ? (string) $row->k : null, 'views' => (int) $row->views, 'sessions' => (int) $row->sessions])
            ->all();
    }

    /**
     * บอทที่เข้าหน้า (แยกตามชื่อบอท) ในช่วงวันที่ — ใช้ query ของตารางตรง ๆ เพราะ report ของหน้าบ้านตัดบอทออกแล้ว
     *
     * @param  array{date_from: string, date_to: string}  $filters
     * @return list<array{key: string, views: int}>
     */
    public static function robots(string $table, array $filters, int $limit = 20): array
    {
        return DB::table($table)
            ->whereNull('deleted_at')
            ->whereNotNull('robot')
            ->whereBetween('action_date', [$filters['date_from'], $filters['date_to']])
            ->selectRaw('robot as k, count(*) as views')
            ->groupBy('robot')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['key' => (string) $row->k, 'views' => (int) $row->views])
            ->all();
    }

    /**
     * ตัวเลือกผู้ใช้งานหลังบ้าน (รวมที่ถูกลบแล้ว เพราะยังมีประวัติอยู่) สำหรับตัวกรอง
     *
     * @return list<array{id: int, title: string}>
     */
    public static function userOptions(): array
    {
        return DB::table('sys_user')
            ->where('user_type', 'back')
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get(['id', 'titlename', 'firstname', 'lastname', 'email', 'deleted_at'])
            ->map(fn ($u) => [
                'id' => (int) $u->id,
                'title' => trim(self::fullName($u).' ('.$u->email.')'.($u->deleted_at !== null ? ' — ถูกลบแล้ว' : '')),
            ])
            ->all();
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<int, array{name: string, email: string|null, group: string|null, deleted: bool}>
     */
    public static function userInfo(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DB::table('sys_user as u')
            ->leftJoin('sys_usergroup as g', 'g.id', '=', 'u.usergroup_id')
            ->whereIn('u.id', $ids)
            ->get(['u.id', 'u.titlename', 'u.firstname', 'u.lastname', 'u.email', 'u.deleted_at', 'g.name as group_name'])
            ->mapWithKeys(fn ($u) => [(int) $u->id => [
                'name' => self::fullName($u),
                'email' => $u->email,
                'group' => $u->group_name,
                'deleted' => $u->deleted_at !== null,
            ]])
            ->all();
    }

    private static function fullName(object $user): string
    {
        return trim(implode(' ', array_filter([$user->titlename, $user->firstname, $user->lastname]))); // รูปแบบเดียวกับ User::name
    }
}
