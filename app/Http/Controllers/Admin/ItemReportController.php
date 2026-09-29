<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Support\Report\ItemReport;
use App\Support\Report\ViewReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ฐานของเมนูรายงานการเข้าชม/คลิกของโมดูลเนื้อหา (article / page / banner)
 * แต่ละโมดูล extends แล้วกำหนด config() — หน้าจอใช้ชุดเดียวกันที่ Pages/Admin/Report/*
 * log action ใช้ module_code = "<โมดูล>.report.<แท็บ>" (ดู docs/PRD-article.md §4)
 */
abstract class ItemReportController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    /** จำนวนรายการในแท็บยอดนิยม */
    private const TOP_LIMIT = 20;

    /** จำนวนหมวดหมู่ที่แสดงในกราฟแนวโน้ม */
    private const TREND_CATEGORIES = 5;

    /** จำนวนแถวสูงสุดที่ส่งออกจากรายการประวัติ */
    private const EXPORT_LOG_LIMIT = 100000;

    /** แท็บรายงาน → ชื่อแท็บ (ใช้กับ LogBackAccess) */
    private const TABS = [
        'overview' => 'ภาพรวม',
        'top' => 'ยอดนิยม',
        'category' => 'ตามหมวดหมู่',
        'audience' => 'ผู้เข้าชมและแหล่งที่มา',
        'time' => 'ช่วงเวลา',
    ];

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     item_label: string,
     *     permission: string,
     *     item_permission: string,
     *     item_report_route: string
     * }
     */
    abstract protected function config(): array;

    abstract protected function report(): ItemReport;

    /**
     * รายการประวัติ — ชื่อรายการ / IP / วันเวลา (ค้นหา / ช่วงวันที่ / แบ่งหน้า / เรียง)
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $this->allowed($request)) {
            return redirect()->route('admin.dashboard');
        }

        $filters = $this->logFilters($request);
        $sort = in_array($request->query('sort'), ['title', 'remote_ip', 'created_at'], true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $logs = $this->report()->logQuery($filters)
            ->orderBy($sort === 'title' ? 'd.title' : "v.{$sort}", $direction)
            ->orderBy('v.id', 'desc') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn ($row) => [
                'id' => (int) $row->id,
                'item_id' => (int) $row->item_id,
                'title' => $row->title,
                'remote_ip' => $row->remote_ip,
                'created_at' => $row->created_at,
            ]);

        if (count($request->query()) === 0) {
            LogBackAccess::record($this->config()['title'].' - '.$this->indexTitle());
        }

        return Inertia::render('Admin/Report/Index', [
            'module' => $this->moduleMeta(),
            'logs' => $logs,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'can' => $this->can($request),
        ]);
    }

    /** ภาพรวม — รวมทุกรายการ (กรองหมวดหมู่ได้) */
    public function overview(Request $request): Response|RedirectResponse
    {
        return $this->tab($request, 'overview', fn (ViewReport $report) => [
            ...$this->report()->dashboard($report, $request),
            'top' => $this->report()->top($report, 5),
        ]);
    }

    /** รายการยอดนิยม 20 อันดับในช่วงวันที่ */
    public function top(Request $request): Response|RedirectResponse
    {
        return $this->tab($request, 'top', fn (ViewReport $report) => [
            'rows' => $this->report()->top($report, self::TOP_LIMIT),
            'summary' => $report->summary(),
        ]);
    }

    /** ยอดแยกตามหมวดหมู่ + แนวโน้มของหมวดหมู่ยอดนิยม (เฉพาะโมดูลที่มีหมวดหมู่) */
    public function category(Request $request): Response|RedirectResponse
    {
        if (! $this->report()->hasCategory()) {
            return redirect()->route($this->routePrefix().'.overview');
        }

        return $this->tab($request, 'category', function (ViewReport $report) {
            $rows = $this->report()->categories($report);
            $trendIds = array_values(array_filter(array_column(array_slice($rows, 0, self::TREND_CATEGORIES), 'id')));
            $trend = $this->report()->categoryTrend($report, $trendIds);
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
            ...$this->report()->audience($report, $request),
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

        if (! isset(self::TABS[$tab]) || ($tab === 'category' && ! $this->report()->hasCategory())) {
            return redirect()->route($this->routePrefix().'.overview');
        }

        $module = $this->report();
        $terms = $module->terms();
        $label = $this->config()['item_label'];
        $filters = ViewReport::filters($request);
        $categoryId = $tab === 'category' ? null : $this->categoryId($request);
        $report = $module->forModule($filters, $categoryId);

        LogBackAction::record("{$this->config()['key']}.report.{$tab}", 'export', "{$filters['date_from']} - {$filters['date_to']}", $categoryId);

        [$headers, $rows] = match ($tab) {
            'top' => [
                ['อันดับ', "รหัส{$label}", "ชื่อ{$label}", ...($module->hasCategory() ? ['หมวดหมู่'] : []), $terms['count'], $terms['unique'], $terms['ips']],
                array_map(fn (array $row) => [
                    $row['rank'], $row['id'], $row['title'].($row['deleted'] ? ' (ถูกลบแล้ว)' : ''),
                    ...($module->hasCategory() ? [$row['category_title']] : []),
                    $row['views'], $row['sessions'], $row['ips'],
                ], $module->top($report, self::TOP_LIMIT)),
            ],
            'category' => [
                ['หมวดหมู่', $terms['count'], $terms['unique'], "จำนวน{$label}ที่มีข้อมูล"],
                array_map(fn (array $row) => [$row['title'] ?? '-', $row['views'], $row['sessions'], $row['items']], $module->categories($report)),
            ],
            'audience' => [
                ['มิติ', 'ค่า', $terms['count']],
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
                ['ช่วงเวลา', 'วันที่เริ่มต้น', 'วันที่สิ้นสุด', $terms['count'], $terms['unique'], $terms['ips']],
                array_map(fn (array $row) => [
                    $row['label'], $row['start'], $row['end'], $row['views'], $row['sessions'], $row['ips'],
                ], $report->series()),
            ],
        };

        return ViewReport::csv("{$this->config()['key']}-report-{$tab}-{$filters['date_from']}-{$filters['date_to']}.csv", $headers, $rows);
    }

    /**
     * render แท็บรายงาน — เช็กสิทธิ์ + อ่านตัวกรอง + log (เฉพาะการเข้าหน้าครั้งแรก)
     *
     * @param  callable(ViewReport): array<string, mixed>  $build
     */
    private function tab(Request $request, string $tab, callable $build, bool $withCategory = true): Response|RedirectResponse
    {
        if (! $this->allowed($request)) {
            return redirect()->route('admin.dashboard');
        }

        $module = $this->report();
        $withCategory = $withCategory && $module->hasCategory();
        $filters = ViewReport::filters($request);
        $categoryId = $withCategory ? $this->categoryId($request) : null;
        $report = $module->forModule($filters, $categoryId);

        if (count($request->query()) === 0) {
            LogBackAccess::record($this->config()['title'].' - '.$this->tabTitle($tab));
            LogBackAction::record("{$this->config()['key']}.report.{$tab}", 'view', "{$filters['date_from']} - {$filters['date_to']}", null);
        }

        return Inertia::render('Admin/Report/'.ucfirst($tab), [
            'module' => $this->moduleMeta(),
            'filters' => $filters + ['category_id' => $categoryId],
            'categories' => $withCategory ? $module->categoryOptions() : null,
            'can' => $this->can($request),
            ...$build($report),
        ]);
    }

    /**
     * ข้อมูลโมดูลที่หน้าจอใช้ (ชื่อ, คำเรียกรายการ, ชนิดตัวเลข, route)
     *
     * @return array<string, mixed>
     */
    private function moduleMeta(): array
    {
        $config = $this->config();

        return [
            'key' => $config['key'],
            'title' => $config['title'],
            'item_label' => $config['item_label'],
            'metric' => $this->report()->metric,
            'has_category' => $this->report()->hasCategory(),
            'route_prefix' => $this->routePrefix(),
            'item_report_route' => $config['item_report_route'],
        ];
    }

    private function routePrefix(): string
    {
        return "admin.{$this->config()['key']}.report";
    }

    private function tabTitle(string $tab): string
    {
        return $tab === 'top' ? $this->config()['item_label'].'ยอดนิยม' : self::TABS[$tab];
    }

    private function indexTitle(): string
    {
        return $this->report()->metric === 'click' ? 'รายการคลิก' : 'รายการเข้าชม';
    }

    /**
     * view_item = ลิงก์ไปรายงานรายรายการได้ (ต้องมีสิทธิ์ดูรายการของโมดูล — ไม่มีสิทธิ์แสดงชื่อเป็นข้อความธรรมดา
     * เพราะกดไปแล้วจะถูก redirect ออก)
     *
     * @return array{view_item: bool}
     */
    private function can(Request $request): array
    {
        return ['view_item' => $request->user()->hasPermission($this->config()['item_permission'])];
    }

    private function allowed(Request $request): bool
    {
        return $request->user()->hasPermission($this->config()['permission']);
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

    /** ส่งออกรายการประวัติ (ข้อมูลดิบ) ตามตัวกรองของหน้ารายการ — สำหรับนำไปวิเคราะห์ต่อ */
    private function exportLogs(Request $request): StreamedResponse
    {
        $filters = $this->logFilters($request);
        $label = $this->config()['item_label'];
        $at = $this->report()->metric === 'click' ? 'วันเวลาที่คลิก' : 'วันเวลาที่เข้าชม';

        LogBackAction::record("{$this->config()['key']}.report.index", 'export', trim(($filters['date_from'] ?? '').' - '.($filters['date_to'] ?? ''), ' -') ?: null, null);

        $rows = (function () use ($filters) {
            foreach ($this->report()->logQuery($filters)->orderBy('v.id', 'desc')->limit(self::EXPORT_LOG_LIMIT)->cursor() as $row) {
                yield [$row->created_at, $row->item_id, $row->title, $row->remote_ip, $row->lang, $row->device_type, $row->browser, $row->platform, $row->referrer];
            }
        })();

        return ViewReport::csv(
            "{$this->config()['key']}-report-log-".now()->format('Ymd-His').'.csv',
            [$at, "รหัส{$label}", "ชื่อ{$label}", 'IP Address', 'ภาษา', 'อุปกรณ์', 'เบราว์เซอร์', 'ระบบปฏิบัติการ', 'แหล่งที่มา'],
            $rows,
        );
    }

    /** @return list<array{0: string, 1: string, 2: int}> */
    private function audienceRows(ViewReport $report, Request $request): array
    {
        $labels = ['lang' => 'ภาษา', 'device_type' => 'อุปกรณ์', 'browser' => 'เบราว์เซอร์', 'platform' => 'ระบบปฏิบัติการ'];
        $data = $this->report()->audience($report, $request);
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

        foreach ($data['referrers']['paths'] as $item) {
            $rows[] = ['หน้าในเว็บไซต์ที่อ้างอิงมา', $item['key'], $item['views']];
        }

        return $rows;
    }
}
