<?php

namespace App\Support\Report;

use App\Support\Setting;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * รายงานการเข้าชม/คลิกของโมดูลเนื้อหา — ผูกตารางประวัติ (ที่ ViewCounter เขียน) กับตาราง info/detail/หมวดหมู่ของโมดูล
 * ใช้ร่วมกันระหว่างรายงานรายรายการ (…ItemController@report) และเมนูรายงานของโมดูล (…ReportController)
 *
 * โมดูลที่รองรับ: article (เข้าชม), page (เข้าชม, ไม่มีหมวดหมู่), banner (คลิก) — ดู docs/PRD-article.md §4
 */
final class ItemReport
{
    /**
     * @param  string  $metric  view = ยอดเข้าชม, click = ยอดคลิก (ใช้เลือกคำที่แสดง)
     */
    private function __construct(
        public readonly string $key,
        public readonly string $table,
        public readonly string $fk,
        public readonly string $infoTable,
        public readonly string $detailTable,
        public readonly string $metric,
        public readonly ?string $categoryColumn = null,
        public readonly ?string $categoryDetailTable = null,
        private readonly string $categoryOrder = 'c.sort_order',
    ) {}

    public static function article(): self
    {
        return new self('article', 'article_item_view', 'article_item_info_id', 'article_item_info', 'article_item_detail', 'view',
            'article_category_info_id', 'article_category_detail');
    }

    public static function page(): self
    {
        return new self('page', 'page_item_view', 'page_item_info_id', 'page_item_info', 'page_item_detail', 'view');
    }

    public static function banner(): self
    {
        return new self('banner', 'banner_item_click', 'banner_item_info_id', 'banner_item_info', 'banner_item_detail', 'click',
            'banner_category_info_id', 'banner_category_detail', 'd.title'); // หมวดหมู่ banner ไม่มี sort_order — เรียงตามชื่อเหมือนหน้ารายการ
    }

    public function hasCategory(): bool
    {
        return $this->categoryColumn !== null;
    }

    /**
     * คำที่ใช้กับหัวคอลัมน์ CSV (ฝั่งหน้าจอมีชุดเดียวกันใน utils/report.ts)
     *
     * @return array{count: string, unique: string, ips: string}
     */
    public function terms(): array
    {
        return $this->metric === 'click'
            ? ['count' => 'ยอดคลิก', 'unique' => 'ผู้คลิกไม่ซ้ำ (session)', 'ips' => 'IP ไม่ซ้ำ']
            : ['count' => 'ยอดเข้าชม', 'unique' => 'ผู้เข้าชมไม่ซ้ำ (session)', 'ips' => 'IP ไม่ซ้ำ'];
    }

    /** รายงานเฉพาะรายการเดียว */
    public function forItem(int $itemId, array $filters): ViewReport
    {
        return new ViewReport($this->table, $this->fk, $filters,
            fn (Builder $query) => $query->where("{$this->table}.{$this->fk}", $itemId));
    }

    /** รายงานทั้งโมดูล — กรองหมวดหมู่ได้ (null = ทุกหมวดหมู่ / โมดูลไม่มีหมวดหมู่) */
    public function forModule(array $filters, ?int $categoryId = null): ViewReport
    {
        $scope = $categoryId === null || ! $this->hasCategory() ? null
            : fn (Builder $query) => $query->whereIn("{$this->table}.{$this->fk}", DB::table($this->infoTable)
                ->select('id')
                ->where($this->categoryColumn, $categoryId));

        return new ViewReport($this->table, $this->fk, $filters, $scope);
    }

    /**
     * ข้อมูลรายงานแบบครบชุด (ช่วงเวลา / สรุป / ผู้เข้าชม / แหล่งที่มา / heatmap)
     *
     * @return array<string, mixed>
     */
    public function dashboard(ViewReport $report, Request $request): array
    {
        $series = $report->series();

        return [
            'series' => $series,
            'summary' => $report->summary($series),
            ...$this->audience($report, $request),
            'heatmap' => $report->heatmap(),
        ];
    }

    /**
     * @return array{breakdowns: array<string, list<array{key: string|null, views: int, sessions: int}>>, referrers: array<string, mixed>}
     */
    public function audience(ViewReport $report, Request $request): array
    {
        return [
            'breakdowns' => [
                'lang' => $report->breakdown('lang'),
                'device_type' => $report->breakdown('device_type'),
                'browser' => $report->breakdown('browser'),
                'platform' => $report->breakdown('platform'),
            ],
            'referrers' => $report->referrers($request->getHost()),
        ];
    }

    /**
     * รายการยอดนิยม พร้อมชื่อ/หมวดหมู่ (ภาษาหลัก)
     *
     * @return list<array<string, mixed>>
     */
    public function top(ViewReport $report, int $limit = 20): array
    {
        $rows = $report->topIds($limit);
        $info = $this->itemInfo(array_column($rows, 'id'));

        return array_map(fn (array $row, int $index) => [
            'rank' => $index + 1,
            ...$row,
            ...($info[$row['id']] ?? ['title' => null, 'category_title' => null, 'deleted' => true]),
        ], $rows, array_keys($rows));
    }

    /**
     * ยอดแยกตามหมวดหมู่ (เรียงมากไปน้อย) — โมดูลที่ไม่มีหมวดหมู่คืน []
     *
     * @return list<array{id: int|null, title: string|null, views: int, sessions: int, items: int}>
     */
    public function categories(ViewReport $report): array
    {
        if (! $this->hasCategory()) {
            return [];
        }

        $rows = $report->query()
            ->leftJoin("{$this->infoTable} as i", 'i.id', '=', $report->fk())
            ->selectRaw("i.{$this->categoryColumn} as category_id, count(*) as views, count(distinct {$this->table}.session_id) as sessions, count(distinct {$report->fk()}) as items")
            ->groupBy("i.{$this->categoryColumn}")
            ->orderByDesc('views')
            ->get();

        $titles = $this->categoryTitles($rows->pluck('category_id')->filter()->all());

        return $rows->map(fn ($row) => [
            'id' => $row->category_id !== null ? (int) $row->category_id : null,
            'title' => $row->category_id !== null ? ($titles[$row->category_id] ?? null) : null,
            'views' => (int) $row->views,
            'sessions' => (int) $row->sessions,
            'items' => (int) $row->items,
        ])->values()->all();
    }

    /**
     * แนวโน้มตามช่วงเวลาของหมวดหมู่ที่ระบุ
     *
     * @param  list<int>  $categoryIds
     * @return array<int, list<int>>
     */
    public function categoryTrend(ViewReport $report, array $categoryIds): array
    {
        if (! $this->hasCategory()) {
            return [];
        }

        return $report->seriesBy(
            "i.{$this->categoryColumn}",
            fn (Builder $query) => $query->join("{$this->infoTable} as i", 'i.id', '=', $report->fk()),
            $categoryIds,
        );
    }

    /**
     * ชื่อ/หมวดหมู่ของรายการ (ภาษาหลัก) — รวมรายการที่ถูกลบแล้ว (flag deleted)
     *
     * @param  list<int>  $ids
     * @return array<int, array{title: string|null, category_title: string|null, deleted: bool}>
     */
    public function itemInfo(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $lang = Setting::defaultLanguage();

        $query = DB::table("{$this->infoTable} as i")
            ->leftJoin("{$this->detailTable} as d", fn ($join) => $join->on('d.id', '=', 'i.id')->where('d.lang', $lang))
            ->whereIn('i.id', $ids);

        $columns = ['i.id', 'i.deleted_at', 'd.title'];

        if ($this->hasCategory()) {
            $query->leftJoin("{$this->categoryDetailTable} as cd", fn ($join) => $join->on('cd.id', '=', "i.{$this->categoryColumn}")->where('cd.lang', $lang));
            $columns[] = 'cd.title as category_title';
        }

        return $query->get($columns)
            ->mapWithKeys(fn ($row) => [(int) $row->id => [
                'title' => $row->title,
                'category_title' => $row->category_title ?? null,
                'deleted' => $row->deleted_at !== null,
            ]])
            ->all();
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<int, string|null>
     */
    public function categoryTitles(array $ids): array
    {
        if ($ids === [] || ! $this->hasCategory()) {
            return [];
        }

        return DB::table($this->categoryDetailTable)
            ->where('lang', Setting::defaultLanguage())
            ->whereIn('id', $ids)
            ->pluck('title', 'id')
            ->all();
    }

    /**
     * ตัวเลือกหมวดหมู่ (ภาษาหลัก, เรียงแบบเดียวกับหน้ารายการของโมดูล) สำหรับตัวกรอง
     *
     * @return list<array{id: int, title: string|null}>
     */
    public function categoryOptions(): array
    {
        if (! $this->hasCategory()) {
            return [];
        }

        $infoTable = str_replace('_detail', '_info', $this->categoryDetailTable);
        $lang = Setting::defaultLanguage();

        return DB::table("{$infoTable} as c")
            ->join("{$this->categoryDetailTable} as d", fn ($join) => $join->on('d.id', '=', 'c.id')->where('d.lang', $lang))
            ->whereNull('c.deleted_at')
            ->whereNull('d.deleted_at')
            ->orderBy($this->categoryOrder)
            ->get(['c.id', 'd.title'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'title' => $row->title])
            ->all();
    }

    /**
     * query รายการประวัติ (ชื่อรายการเป็นภาษาหลัก) — หน้ารายการของเมนูรายงาน + ส่งออกข้อมูลดิบ
     *
     * @param  array{q: string|null, date_from: string|null, date_to: string|null}  $filters
     */
    public function logQuery(array $filters): Builder
    {
        $lang = Setting::defaultLanguage();

        return DB::table("{$this->table} as v")
            ->leftJoin("{$this->detailTable} as d", fn ($join) => $join->on('d.id', '=', "v.{$this->fk}")->where('d.lang', $lang))
            ->whereNull('v.deleted_at')
            ->select('v.id', "v.{$this->fk} as item_id", 'd.title', 'v.remote_ip', 'v.created_at',
                'v.lang', 'v.device_type', 'v.browser', 'v.platform', 'v.referrer')
            ->when($filters['q'] !== null, function ($query) use ($filters) {
                $term = $filters['q'];
                $query->where(fn ($inner) => $inner
                    ->where('d.title', 'like', "%{$term}%")
                    ->orWhere('v.remote_ip', 'like', "%{$term}%"));
            })
            ->when($filters['date_from'] !== null, fn ($query) => $query->where('v.action_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== null, fn ($query) => $query->where('v.action_date', '<=', $filters['date_to']));
    }
}
