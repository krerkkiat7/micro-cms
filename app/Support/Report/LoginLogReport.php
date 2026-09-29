<?php

namespace App\Support\Report;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * สถิติการเข้า/ออกระบบหลังบ้านจาก log_back_login (1 แถว = 1 เหตุการณ์: login success/fail/block หรือ logout)
 * ViewReport ใช้ FK = user_id (เก็บเฉพาะตอนสำเร็จ/logout) และนับ "ไม่ซ้ำ" ด้วย username (อีเมลที่กรอก — มีทั้งตอนสำเร็จและล้มเหลว)
 */
final class LoginLogReport
{
    public const TABLE = 'log_back_login';

    public const RESULTS = ['success', 'fail', 'block'];

    /**
     * @param  array{date_from: string, date_to: string, period: string}  $filters
     * @param  string|null  $email  กรองเฉพาะบัญชี (username ที่กรอก หรือ user_id ของบัญชีนั้น)
     * @param  list<string>|null  $results  กรองเฉพาะการเข้าสู่ระบบที่ผลลัพธ์ตามนี้ (null = ทุกเหตุการณ์รวม logout)
     */
    public static function make(array $filters, ?int $userId = null, ?string $email = null, ?array $results = null): ViewReport
    {
        $t = self::TABLE;

        return new ViewReport($t, 'user_id', $filters, function (Builder $query) use ($t, $userId, $email, $results) {
            $query->when($userId !== null, fn ($q) => $q->where(fn ($inner) => $inner
                ->where("{$t}.user_id", $userId)
                ->when($email !== null, fn ($or) => $or->orWhere("{$t}.username", $email))))
                ->when($results !== null, fn ($q) => $q->where("{$t}.log_type", 'login')->whereIn("{$t}.result", $results));
        }, 'username');
    }

    /**
     * จำนวนตามประเภทเหตุการณ์ + อัตราสำเร็จ
     *
     * @return array{success: int, fail: int, block: int, logout: int, attempts: int, success_rate: float, accounts: int, ips: int}
     */
    public static function counts(ViewReport $report): array
    {
        $t = self::TABLE;

        $row = $report->query()
            ->selectRaw("sum(case when {$t}.log_type = 'login' and {$t}.result = 'success' then 1 else 0 end) as success,
                sum(case when {$t}.log_type = 'login' and {$t}.result = 'fail' then 1 else 0 end) as fail,
                sum(case when {$t}.log_type = 'login' and {$t}.result = 'block' then 1 else 0 end) as block,
                sum(case when {$t}.log_type = 'logout' then 1 else 0 end) as logout,
                count(distinct {$t}.username) as accounts, count(distinct {$t}.remote_ip) as ips")
            ->first();

        $success = (int) ($row->success ?? 0);
        $attempts = $success + (int) ($row->fail ?? 0) + (int) ($row->block ?? 0);

        return [
            'success' => $success,
            'fail' => (int) ($row->fail ?? 0),
            'block' => (int) ($row->block ?? 0),
            'logout' => (int) ($row->logout ?? 0),
            'attempts' => $attempts,
            'success_rate' => $attempts > 0 ? round($success / $attempts * 100, 1) : 0.0,
            'accounts' => (int) ($row->accounts ?? 0),
            'ips' => (int) ($row->ips ?? 0),
        ];
    }

    /**
     * แนวโน้มตามช่วงเวลาแยกผลลัพธ์ของการเข้าสู่ระบบ (success / fail / block)
     *
     * @return array<string, list<int>>
     */
    public static function trend(ViewReport $report): array
    {
        $t = self::TABLE;

        return $report->seriesBy("{$t}.result", fn (Builder $query) => $query->where("{$t}.log_type", 'login'), self::RESULTS);
    }

    /**
     * สรุปรายบัญชี (ตาม username ที่กรอก) — สำเร็จ / ไม่สำเร็จ / ถูกบล็อก / ออกจากระบบ / IP / ล่าสุด
     *
     * @return list<array<string, mixed>>
     */
    public static function accounts(ViewReport $report, int $limit = 100): array
    {
        $t = self::TABLE;

        $rows = $report->query()
            ->selectRaw("{$t}.username as username, max({$t}.user_id) as user_id,
                sum(case when {$t}.log_type = 'login' and {$t}.result = 'success' then 1 else 0 end) as success,
                sum(case when {$t}.log_type = 'login' and {$t}.result = 'fail' then 1 else 0 end) as fail,
                sum(case when {$t}.log_type = 'login' and {$t}.result = 'block' then 1 else 0 end) as block,
                sum(case when {$t}.log_type = 'logout' then 1 else 0 end) as logout,
                count(distinct {$t}.remote_ip) as ips,
                max(case when {$t}.result = 'success' and {$t}.log_type = 'login' then {$t}.created_at end) as last_success_at,
                max(case when {$t}.result in ('fail', 'block') then {$t}.created_at end) as last_fail_at")
            ->groupBy("{$t}.username")
            ->orderByRaw('count(*) desc')
            ->limit($limit)
            ->get();

        $users = AccessLogReport::userInfo($rows->pluck('user_id')->filter()->all());

        return $rows->map(fn ($row) => [
            'username' => $row->username,
            'user_id' => $row->user_id !== null ? (int) $row->user_id : null,
            'name' => $row->user_id !== null ? ($users[$row->user_id]['name'] ?? null) : null,
            'success' => (int) $row->success,
            'fail' => (int) $row->fail,
            'block' => (int) $row->block,
            'logout' => (int) $row->logout,
            'ips' => (int) $row->ips,
            'last_success_at' => $row->last_success_at,
            'last_fail_at' => $row->last_fail_at,
        ])->values()->all();
    }

    /**
     * IP ที่เข้าสู่ระบบไม่สำเร็จ/ถูกบล็อก — จำนวนบัญชีที่ลองต่อ IP สูง = สัญญาณการเดารหัสผ่านหลายบัญชี
     *
     * @return list<array{ip: string|null, failed: int, success: int, usernames: int, last_at: string|null}>
     */
    public static function failedIps(ViewReport $report, int $limit = 30): array
    {
        $t = self::TABLE;

        return $report->query()
            ->where("{$t}.log_type", 'login')
            ->selectRaw("{$t}.remote_ip as ip,
                sum(case when {$t}.result in ('fail', 'block') then 1 else 0 end) as failed,
                sum(case when {$t}.result = 'success' then 1 else 0 end) as success,
                count(distinct case when {$t}.result in ('fail', 'block') then {$t}.username end) as usernames,
                max({$t}.created_at) as last_at")
            ->groupBy("{$t}.remote_ip")
            ->havingRaw("sum(case when {$t}.result in ('fail', 'block') then 1 else 0 end) > 0")
            ->orderByDesc('failed')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'ip' => $row->ip,
                'failed' => (int) $row->failed,
                'success' => (int) $row->success,
                'usernames' => (int) $row->usernames,
                'last_at' => $row->last_at,
            ])
            ->all();
    }

    /**
     * สาเหตุที่เข้าสู่ระบบไม่สำเร็จ/ถูกบล็อก (คอลัมน์ note)
     *
     * @return list<array{key: string, result: string, views: int}>
     */
    public static function reasons(ViewReport $report): array
    {
        $t = self::TABLE;

        return $report->query()
            ->where("{$t}.log_type", 'login')
            ->whereIn("{$t}.result", ['fail', 'block'])
            ->selectRaw("{$t}.note as k, {$t}.result as result, count(*) as views")
            ->groupBy("{$t}.note", "{$t}.result")
            ->orderByDesc('views')
            ->limit(20)
            ->get()
            ->map(fn ($row) => ['key' => (string) ($row->k ?? '-'), 'result' => (string) $row->result, 'views' => (int) $row->views])
            ->all();
    }

    /** อีเมลของผู้ใช้ (ใช้กรองเหตุการณ์ที่กรอก username เป็นอีเมลของบัญชีนี้) */
    public static function emailOf(?int $userId): ?string
    {
        return $userId === null ? null : DB::table('sys_user')->where('id', $userId)->value('email');
    }
}
