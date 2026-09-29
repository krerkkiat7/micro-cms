<?php

namespace App\Support\Report;

use App\Support\Setting;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * รายงานการเข้าชมของโมดูลบทความ (article_item_view) — ใช้ร่วมกันระหว่างรายงานรายบทความ (ArticleItemController@report)
 * และเมนูรายงานภาพรวม (ArticleReportController) ดู docs/PRD-article.md §4
 */
final class ArticleReport
{
    public const TABLE = 'article_item_view';

    public const FK = 'article_item_info_id';

    /** รายงานเฉพาะบทความเดียว */
    public static function forItem(int $itemId, array $filters): ViewReport
    {
        return new ViewReport(self::TABLE, self::FK, $filters,
            fn (Builder $query) => $query->where(self::TABLE.'.'.self::FK, $itemId));
    }

    /** รายงานทั้งโมดูล — กรองหมวดหมู่ได้ (null = ทุกหมวดหมู่) */
    public static function forModule(array $filters, ?int $categoryId = null): ViewReport
    {
        return new ViewReport(self::TABLE, self::FK, $filters, $categoryId === null ? null
            : fn (Builder $query) => $query->whereIn(self::TABLE.'.'.self::FK, DB::table('article_item_info')
                ->select('id')
                ->where('article_category_info_id', $categoryId)));
    }

    /**
     * ข้อมูลรายงานแบบครบชุด (ช่วงเวลา / สรุป / แยกตามผู้เข้าชม / แหล่งที่มา / heatmap) — หน้ารายงานรายบทความ + แท็บภาพรวม
     *
     * @return array<string, mixed>
     */
    public static function dashboard(ViewReport $report, Request $request): array
    {
        $series = $report->series();

        return [
            'series' => $series,
            'summary' => $report->summary($series),
            ...self::audience($report, $request),
            'heatmap' => $report->heatmap(),
        ];
    }

    /**
     * @return array{breakdowns: array<string, list<array{key: string|null, views: int, sessions: int}>>, referrers: array<string, mixed>}
     */
    public static function audience(ViewReport $report, Request $request): array
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
     * บทความยอดนิยม พร้อมชื่อ/หมวดหมู่ (ภาษาหลัก)
     *
     * @return list<array<string, mixed>>
     */
    public static function top(ViewReport $report, int $limit = 20): array
    {
        $rows = $report->topIds($limit);
        $info = self::itemInfo(array_column($rows, 'id'));

        return array_map(fn (array $row, int $index) => [
            'rank' => $index + 1,
            ...$row,
            ...($info[$row['id']] ?? ['title' => null, 'category_title' => null, 'deleted' => true]),
        ], $rows, array_keys($rows));
    }

    /**
     * ยอดแยกตามหมวดหมู่ (เรียงมากไปน้อย)
     *
     * @return list<array{id: int|null, title: string|null, views: int, sessions: int, items: int}>
     */
    public static function categories(ViewReport $report): array
    {
        $table = self::TABLE;

        $rows = $report->query()
            ->leftJoin('article_item_info as i', 'i.id', '=', $report->fk())
            ->selectRaw("i.article_category_info_id as category_id, count(*) as views, count(distinct {$table}.session_id) as sessions, count(distinct {$report->fk()}) as items")
            ->groupBy('i.article_category_info_id')
            ->orderByDesc('views')
            ->get();

        $titles = self::categoryTitles($rows->pluck('category_id')->filter()->all());

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
    public static function categoryTrend(ViewReport $report, array $categoryIds): array
    {
        return $report->seriesBy(
            'i.article_category_info_id',
            fn (Builder $query) => $query->join('article_item_info as i', 'i.id', '=', $report->fk()),
            $categoryIds,
        );
    }

    /**
     * ชื่อ/หมวดหมู่ของบทความ (ภาษาหลัก) — รวมบทความที่ถูกลบแล้ว (flag deleted)
     *
     * @param  list<int>  $ids
     * @return array<int, array{title: string|null, category_title: string|null, deleted: bool}>
     */
    public static function itemInfo(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $lang = Setting::defaultLanguage();

        return DB::table('article_item_info as i')
            ->leftJoin('article_item_detail as d', fn ($join) => $join->on('d.id', '=', 'i.id')->where('d.lang', $lang))
            ->leftJoin('article_category_detail as cd', fn ($join) => $join->on('cd.id', '=', 'i.article_category_info_id')->where('cd.lang', $lang))
            ->whereIn('i.id', $ids)
            ->get(['i.id', 'i.deleted_at', 'd.title', 'cd.title as category_title'])
            ->mapWithKeys(fn ($row) => [(int) $row->id => [
                'title' => $row->title,
                'category_title' => $row->category_title,
                'deleted' => $row->deleted_at !== null,
            ]])
            ->all();
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<int, string|null>
     */
    public static function categoryTitles(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DB::table('article_category_detail')
            ->where('lang', Setting::defaultLanguage())
            ->whereIn('id', $ids)
            ->pluck('title', 'id')
            ->all();
    }
}
