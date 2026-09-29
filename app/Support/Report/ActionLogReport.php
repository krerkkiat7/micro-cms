<?php

namespace App\Support\Report;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * สถิติการกระทำในหลังบ้านจาก log_back_action (1 แถว = การกระทำ 1 ครั้ง: create / view / update / delete / export ฯลฯ บนข้อมูล)
 * ViewReport ใช้ FK = user_id และนับ "ไม่ซ้ำ" ด้วย user_id (ตารางไม่มี session)
 */
final class ActionLogReport
{
    public const TABLE = 'log_back_action';

    /** ประเภทการกระทำที่ "เปลี่ยนแปลงข้อมูล" (ใช้แยกจากการเปิดดู/ส่งออก) */
    public const CHANGE_TYPES = ['create', 'update', 'delete'];

    /**
     * @param  array{date_from: string, date_to: string, period: string}  $filters
     */
    public static function make(array $filters, ?int $userId = null): ViewReport
    {
        $t = self::TABLE;

        return new ViewReport($t, 'user_id', $filters, $userId === null ? null
            : fn (Builder $query) => $query->where("{$t}.user_id", $userId), 'user_id');
    }

    /**
     * เฉพาะการกระทำที่เปลี่ยนแปลงข้อมูล (create / update / delete)
     *
     * @param  array{date_from: string, date_to: string, period: string}  $filters
     */
    public static function changesOnly(array $filters, ?int $userId = null): ViewReport
    {
        $t = self::TABLE;

        return new ViewReport($t, 'user_id', $filters, fn (Builder $query) => $query
            ->whereIn("{$t}.action_type", self::CHANGE_TYPES)
            ->when($userId !== null, fn ($q) => $q->where("{$t}.user_id", $userId)), 'user_id');
    }

    /**
     * นิพจน์ SQL นับตามประเภท — คืน select ของ create/view/update/delete/other
     */
    private static function typeColumns(): string
    {
        $t = self::TABLE;

        return "sum(case when {$t}.action_type = 'create' then 1 else 0 end) as creates,
            sum(case when {$t}.action_type = 'view' then 1 else 0 end) as views_count,
            sum(case when {$t}.action_type = 'update' then 1 else 0 end) as updates,
            sum(case when {$t}.action_type = 'delete' then 1 else 0 end) as deletes,
            sum(case when {$t}.action_type not in ('create', 'view', 'update', 'delete') or {$t}.action_type is null then 1 else 0 end) as others";
    }

    /** @return array{create: int, view: int, update: int, delete: int, other: int} */
    private static function typeCounts(object $row): array
    {
        return [
            'create' => (int) ($row->creates ?? 0),
            'view' => (int) ($row->views_count ?? 0),
            'update' => (int) ($row->updates ?? 0),
            'delete' => (int) ($row->deletes ?? 0),
            'other' => (int) ($row->others ?? 0),
        ];
    }

    /**
     * จำนวนตามประเภทการกระทำ + จำนวนโมดูล/ข้อมูลที่ถูกกระทำ
     *
     * @return array<string, int>
     */
    public static function counts(ViewReport $report): array
    {
        $t = self::TABLE;
        // นับคู่ (module_code, ref_id) ไม่ซ้ำ — MySQL ใช้ concat() (|| ใน MySQL คือ OR), SQLite ใช้ ||
        $pair = DB::connection()->getDriverName() === 'sqlite'
            ? "{$t}.module_code || ':' || {$t}.ref_id"
            : "concat({$t}.module_code, ':', {$t}.ref_id)";

        $row = $report->query()
            ->selectRaw(self::typeColumns().", count(distinct {$t}.module_code) as modules,
                count(distinct case when {$t}.ref_id is not null then {$pair} end) as records")
            ->first();

        return [...self::typeCounts($row), 'modules' => (int) ($row->modules ?? 0), 'records' => (int) ($row->records ?? 0)];
    }

    /**
     * แนวโน้มตามช่วงเวลาแยกประเภทการกระทำ (create / update / delete / view)
     *
     * @return array<string, list<int>>
     */
    public static function trend(ViewReport $report): array
    {
        return $report->seriesBy(self::TABLE.'.action_type', fn (Builder $query) => $query, ['create', 'update', 'delete', 'view']);
    }

    /**
     * สรุปรายผู้ใช้งาน
     *
     * @return list<array<string, mixed>>
     */
    public static function users(ViewReport $report, int $limit = 100): array
    {
        $t = self::TABLE;

        $rows = $report->query()
            ->selectRaw("{$t}.user_id as user_id, count(*) as total, ".self::typeColumns().",
                count(distinct {$t}.module_code) as modules, count(distinct {$t}.action_date) as active_days, max({$t}.created_at) as last_at")
            ->groupBy("{$t}.user_id")
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $users = AccessLogReport::userInfo($rows->pluck('user_id')->filter()->all());

        return $rows->map(fn ($row) => [
            'user_id' => $row->user_id !== null ? (int) $row->user_id : null,
            'name' => $row->user_id !== null ? ($users[$row->user_id]['name'] ?? null) : null,
            'group' => $row->user_id !== null ? ($users[$row->user_id]['group'] ?? null) : null,
            'deleted' => $row->user_id !== null && ($users[$row->user_id]['deleted'] ?? true),
            'total' => (int) $row->total,
            ...self::typeCounts($row),
            'modules' => (int) $row->modules,
            'active_days' => (int) $row->active_days,
            'last_at' => $row->last_at,
        ])->values()->all();
    }

    /**
     * สรุปรายโมดูล (module_code)
     *
     * @return list<array<string, mixed>>
     */
    public static function modules(ViewReport $report, int $limit = 100): array
    {
        $t = self::TABLE;

        return $report->query()
            ->selectRaw("{$t}.module_code as module, count(*) as total, ".self::typeColumns().",
                count(distinct {$t}.user_id) as users, max({$t}.created_at) as last_at")
            ->groupBy("{$t}.module_code")
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'module' => $row->module,
                'total' => (int) $row->total,
                ...self::typeCounts($row),
                'users' => (int) $row->users,
                'last_at' => $row->last_at,
            ])
            ->all();
    }

    /**
     * ข้อมูลที่ถูกเปลี่ยนแปลงบ่อย (create/update/delete ต่อ module_code + ref_id) — ชื่อข้อมูลเป็นค่าล่าสุดที่บันทึกไว้
     *
     * @return list<array<string, mixed>>
     */
    public static function records(ViewReport $report, int $limit = 30): array
    {
        $t = self::TABLE;

        $rows = $report->query()
            ->whereNotNull("{$t}.ref_id")
            ->whereIn("{$t}.action_type", self::CHANGE_TYPES)
            ->selectRaw("{$t}.module_code as module, {$t}.ref_id as ref_id, count(*) as changes, count(distinct {$t}.user_id) as users,
                max({$t}.id) as last_id, max({$t}.created_at) as last_at")
            ->groupBy("{$t}.module_code", "{$t}.ref_id")
            ->orderByDesc('changes')
            ->limit($limit)
            ->get();

        $names = DB::table($t)->whereIn('id', $rows->pluck('last_id'))->pluck('value_string', 'id');

        return $rows->map(fn ($row) => [
            'module' => $row->module,
            'ref_id' => (int) $row->ref_id,
            'name' => $names[$row->last_id] ?? null,
            'changes' => (int) $row->changes,
            'users' => (int) $row->users,
            'last_at' => $row->last_at,
        ])->values()->all();
    }
}
