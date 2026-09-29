<?php

namespace App\Http\Controllers\Admin\Article;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategoryInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Support\Report\ArticleReport;
use App\Support\Report\ViewReport;
use App\Support\Setting;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * เมนูรายงานการเข้าชมบทความ (ภาพรวมทั้งโมดูล) — สิทธิ์ article.report.view ทุกหน้า
 * log action ใช้ module_code = "article.report.<แท็บ>" (ดู docs/PRD-article.md §4)
 */
class ArticleReportController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    /** จำนวนบทความในแท็บยอดนิยม */
    private const TOP_LIMIT = 20;

    /** จำนวนหมวดหมู่ที่แสดงในกราฟแนวโน้ม */
    private const TREND_CATEGORIES = 5;

    /** แท็บรายงาน → ชื่อหน้า (ใช้กับ LogBackAccess) — ลำดับตรงกับ TabNav ฝั่งหน้าจอ */
    private const TABS = [
        'overview' => 'รายงานบทความ - ภาพรวม',
        'top' => 'รายงานบทความ - บทความยอดนิยม',
        'category' => 'รายงานบทความ - ตามหมวดหมู่',
        'audience' => 'รายงานบทความ - ผู้เข้าชมและแหล่งที่มา',
        'time' => 'รายงานบทความ - ช่วงเวลา',
    ];

    /** จำนวนแถวสูงสุดที่ส่งออกจากรายการเข้าชม */
    private const EXPORT_LOG_LIMIT = 100000;

    /**
     * รายการเข้าชมบทความ — ชื่อบทความ / IP / วันที่เข้าชม (ค้นหา / ช่วงวันที่ / แบ่งหน้า / เรียง)
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $this->allowed($request)) {
            return redirect()->route('admin.dashboard');
        }

        $filters = $this->logFilters($request);
        $sort = in_array($request->query('sort'), ['title', 'remote_ip', 'created_at'], true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $logs = $this->logQuery($filters)
            ->orderBy($sort === 'title' ? 'd.title' : "v.{$sort}", $direction)
            ->orderBy('v.id', 'desc') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn ($row) => [
                'id' => (int) $row->id,
                'article_id' => (int) $row->article_id,
                'title' => $row->title,
                'remote_ip' => $row->remote_ip,
                'created_at' => $row->created_at,
            ]);

        if (count($request->query()) === 0) {
            LogBackAccess::record('รายงานบทความ - รายการเข้าชม');
        }

        return Inertia::render('Admin/Article/Report/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    /** ภาพรวม — เหมือนรายงานรายบทความแต่รวมทุกบทความ (กรองหมวดหมู่ได้) */
    public function overview(Request $request): Response|RedirectResponse
    {
        return $this->tab($request, 'overview', fn (ViewReport $report, array $filters) => [
            ...ArticleReport::dashboard($report, $request),
            'top' => ArticleReport::top($report, 5),
        ]);
    }

    /** บทความยอดนิยม 20 อันดับในช่วงวันที่ */
    public function top(Request $request): Response|RedirectResponse
    {
        return $this->tab($request, 'top', fn (ViewReport $report) => [
            'rows' => ArticleReport::top($report, self::TOP_LIMIT),
            'summary' => $report->summary(),
        ]);
    }

    /** ยอดแยกตามหมวดหมู่ + แนวโน้มของหมวดหมู่ยอดนิยม */
    public function category(Request $request): Response|RedirectResponse
    {
        return $this->tab($request, 'category', function (ViewReport $report) {
            $rows = ArticleReport::categories($report);
            $trendIds = array_values(array_filter(array_column(array_slice($rows, 0, self::TREND_CATEGORIES), 'id')));
            $trend = ArticleReport::categoryTrend($report, $trendIds);
            $titles = array_column($rows, 'title', 'id');

            return [
                'rows' => $rows,
                'buckets' => $report->buckets(),
                'trend' => array_map(fn (int $id) => [
                    'id' => $id,
                    'title' => $titles[$id] ?? null,
                    'values' => $trend[$id] ?? [],
                ], $trendIds),
            ];
        }, withCategory: false);
    }

    /** ผู้เข้าชมและแหล่งที่มา — ภาษา / อุปกรณ์ / เบราว์เซอร์ / ระบบปฏิบัติการ / referrer */
    public function audience(Request $request): Response|RedirectResponse
    {
        return $this->tab($request, 'audience', fn (ViewReport $report) => [
            ...ArticleReport::audience($report, $request),
            'summary' => $report->summary(),
        ]);
    }

    /** ช่วงเวลา — วันในสัปดาห์ × ชั่วโมง */
    public function time(Request $request): Response|RedirectResponse
    {
        return $this->tab($request, 'time', fn (ViewReport $report) => [
            'heatmap' => $report->heatmap(),
            'summary' => $report->summary(),
        ]);
    }

    /**
     * ส่งออก CSV ของแท็บที่ระบุ (?tab=) ตามตัวกรองเดียวกับหน้าจอ
     */
    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        if (! $this->allowed($request)) {
            return redirect()->route('admin.dashboard');
        }

        $tab = (string) $request->query('tab');

        if ($tab === 'index') {
            return $this->exportLogs($request);
        }

        if (! isset(self::TABS[$tab])) {
            return redirect()->route('admin.article.report.overview');
        }

        $filters = ViewReport::filters($request);
        $categoryId = $tab === 'category' ? null : $this->categoryId($request);
        $report = ArticleReport::forModule($filters, $categoryId);

        LogBackAction::record("article.report.{$tab}", 'export', "{$filters['date_from']} - {$filters['date_to']}", $categoryId);

        [$headers, $rows] = match ($tab) {
            'top' => [
                ['อันดับ', 'รหัสบทความ', 'ชื่อบทความ', 'หมวดหมู่', 'ยอดเข้าชม', 'ผู้เข้าชมไม่ซ้ำ (session)', 'IP ไม่ซ้ำ'],
                array_map(fn (array $row) => [
                    $row['rank'], $row['id'], $row['title'].($row['deleted'] ? ' (ถูกลบแล้ว)' : ''), $row['category_title'],
                    $row['views'], $row['sessions'], $row['ips'],
                ], ArticleReport::top($report, self::TOP_LIMIT)),
            ],
            'category' => [
                ['หมวดหมู่', 'ยอดเข้าชม', 'ผู้เข้าชมไม่ซ้ำ (session)', 'จำนวนบทความที่มีผู้เข้าชม'],
                array_map(fn (array $row) => [$row['title'] ?? '-', $row['views'], $row['sessions'], $row['items']], ArticleReport::categories($report)),
            ],
            'audience' => [
                ['มิติ', 'ค่า', 'ยอดเข้าชม'],
                $this->audienceRows($report, $request),
            ],
            'time' => [
                ['วัน', ...array_map(fn (int $hour) => sprintf('%02d:00', $hour), range(0, 23)), 'รวม'],
                array_map(
                    fn (array $hours, int $day) => [['จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'อาทิตย์'][$day], ...$hours, array_sum($hours)],
                    $report->heatmap(),
                    range(0, 6),
                ),
            ],
            default => [
                ['ช่วงเวลา', 'วันที่เริ่มต้น', 'วันที่สิ้นสุด', 'ยอดเข้าชม', 'ผู้เข้าชมไม่ซ้ำ (session)', 'IP ไม่ซ้ำ'],
                array_map(fn (array $row) => [
                    $row['label'], $row['start'], $row['end'], $row['views'], $row['sessions'], $row['ips'],
                ], $report->series()),
            ],
        };

        return ViewReport::csv("article-report-{$tab}-{$filters['date_from']}-{$filters['date_to']}.csv", $headers, $rows);
    }

    /**
     * render แท็บรายงาน — เช็กสิทธิ์ + อ่านตัวกรอง + log (เฉพาะการเข้าหน้าครั้งแรก)
     *
     * @param  callable(ViewReport, array): array<string, mixed>  $build
     */
    private function tab(Request $request, string $tab, callable $build, bool $withCategory = true): Response|RedirectResponse
    {
        if (! $this->allowed($request)) {
            return redirect()->route('admin.dashboard');
        }

        $filters = ViewReport::filters($request);
        $categoryId = $withCategory ? $this->categoryId($request) : null;
        $report = ArticleReport::forModule($filters, $categoryId);

        if (count($request->query()) === 0) {
            LogBackAccess::record(self::TABS[$tab]);
            LogBackAction::record("article.report.{$tab}", 'view', "{$filters['date_from']} - {$filters['date_to']}", null);
        }

        return Inertia::render('Admin/Article/Report/'.ucfirst($tab), [
            'filters' => $filters + ['category_id' => $categoryId],
            'categories' => $withCategory ? $this->categoryOptions() : [],
            ...$build($report, $filters),
        ]);
    }

    private function allowed(Request $request): bool
    {
        return $request->user()->hasPermission('article.report.view');
    }

    private function categoryId(Request $request): ?int
    {
        $id = $request->query('category_id');

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * @return array{q: string|null, date_from: string|null, date_to: string|null, per_page: int}
     */
    private function logFilters(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page');

        return [
            'q' => $q !== '' ? $q : null,
            'date_from' => $this->toDate($request->query('date_from')),
            'date_to' => $this->toDate($request->query('date_to')),
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0],
        ];
    }

    /**
     * query รายการเข้าชม (ชื่อบทความเป็นภาษาหลัก)
     */
    private function logQuery(array $filters): Builder
    {
        $lang = Setting::defaultLanguage();

        return DB::table(ArticleReport::TABLE.' as v')
            ->leftJoin('article_item_detail as d', fn ($join) => $join->on('d.id', '=', 'v.'.ArticleReport::FK)->where('d.lang', $lang))
            ->whereNull('v.deleted_at')
            ->select('v.id', 'v.'.ArticleReport::FK.' as article_id', 'd.title', 'v.remote_ip', 'v.created_at',
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

    /** ส่งออกรายการเข้าชม (ข้อมูลดิบ) ตามตัวกรองของหน้ารายการ — สำหรับนำไปวิเคราะห์ต่อ */
    private function exportLogs(Request $request): StreamedResponse
    {
        $filters = $this->logFilters($request);

        LogBackAction::record('article.report.index', 'export', trim(($filters['date_from'] ?? '').' - '.($filters['date_to'] ?? ''), ' -') ?: null, null);

        $rows = (function () use ($filters) {
            foreach ($this->logQuery($filters)->orderBy('v.id', 'desc')->limit(self::EXPORT_LOG_LIMIT)->cursor() as $row) {
                yield [$row->created_at, $row->article_id, $row->title, $row->remote_ip, $row->lang, $row->device_type, $row->browser, $row->platform, $row->referrer];
            }
        })();

        return ViewReport::csv(
            'article-report-views-'.now()->format('Ymd-His').'.csv',
            ['วันเวลาที่เข้าชม', 'รหัสบทความ', 'ชื่อบทความ', 'IP Address', 'ภาษา', 'อุปกรณ์', 'เบราว์เซอร์', 'ระบบปฏิบัติการ', 'แหล่งที่มา'],
            $rows,
        );
    }

    /** @return list<array{0: string, 1: string, 2: int}> */
    private function audienceRows(ViewReport $report, Request $request): array
    {
        $labels = ['lang' => 'ภาษา', 'device_type' => 'อุปกรณ์', 'browser' => 'เบราว์เซอร์', 'platform' => 'ระบบปฏิบัติการ'];
        $data = ArticleReport::audience($report, $request);
        $rows = [];

        foreach ($data['breakdowns'] as $dimension => $items) {
            foreach ($items as $item) {
                $rows[] = [$labels[$dimension], $item['key'] ?? 'ไม่ทราบ', $item['views']];
            }
        }

        foreach ($data['referrers']['sources'] as $item) {
            $rows[] = ['กลุ่มแหล่งที่มา', $item['key'], $item['views']];
        }

        foreach ($data['referrers']['hosts'] as $item) {
            $rows[] = ['เว็บไซต์ที่อ้างอิงมา', $item['key'], $item['views']];
        }

        return $rows;
    }

    /** @return list<array{id: int, title: string|null}> */
    private function categoryOptions(): array
    {
        $lang = Setting::defaultLanguage();

        return ArticleCategoryInfo::query()
            ->join('article_category_detail as d', fn ($join) => $join->on('d.id', '=', 'article_category_info.id')->where('d.lang', $lang))
            ->whereNull('d.deleted_at')
            ->orderBy('article_category_info.sort_order')
            ->get(['article_category_info.id', 'd.title as title'])
            ->map(fn ($row) => ['id' => $row->id, 'title' => $row->title])
            ->values()
            ->all();
    }
}
