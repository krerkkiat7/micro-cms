<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\LogBackAccess;
use App\Support\Report\BackLogAccessReport;
use App\Support\Report\ViewReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * สถิติของประวัติการใช้งานหลังบ้าน (log_back_access) — แท็บต่อจากหน้ารายการ admin.system.backlog.access.index
 * สิทธิ์ system.backlog.access เดียวกับหน้ารายการ; บันทึกเฉพาะ LogBackAccess เหมือนหน้าประวัติอื่น (ไม่มี LogBackAction)
 * ตัวกรอง: ช่วงวันที่ + รายวัน/สัปดาห์/เดือน/ปี (ViewReport::filters) + ผู้ใช้งาน (user_id)
 */
class BackLogAccessReportController extends Controller
{
    /** แท็บสถิติ → ชื่อแท็บ (ลำดับตรงกับหน้าจอ) */
    private const TABS = [
        'overview' => 'ภาพรวม',
        'user' => 'ผู้ใช้งาน',
        'page' => 'หน้าจอ',
        'device' => 'อุปกรณ์และเครือข่าย',
        'time' => 'ช่วงเวลา',
    ];

    public function overview(Request $request): Response|RedirectResponse
    {
        return $this->tab($request, 'overview', function (ViewReport $report) {
            $series = $report->series();

            return [
                'series' => $series,
                'summary' => $report->summary($series),
                'duration' => BackLogAccessReport::durationSummary($report),
                'users' => BackLogAccessReport::users($report, 5),
                'pages' => BackLogAccessReport::pages($report, 5),
            ];
        });
    }

    public function user(Request $request): Response|RedirectResponse
    {
        return $this->tab($request, 'user', fn (ViewReport $report) => [
            'summary' => $report->summary(),
            'duration' => BackLogAccessReport::durationSummary($report),
            'users' => BackLogAccessReport::users($report),
        ]);
    }

    public function page(Request $request): Response|RedirectResponse
    {
        return $this->tab($request, 'page', fn (ViewReport $report) => [
            'summary' => $report->summary(),
            'duration' => BackLogAccessReport::durationSummary($report),
            'pages' => BackLogAccessReport::pages($report),
        ]);
    }

    public function device(Request $request): Response|RedirectResponse
    {
        return $this->tab($request, 'device', fn (ViewReport $report) => [
            'summary' => $report->summary(),
            'breakdowns' => [
                'device_type' => $report->breakdown('device_type'),
                'browser' => $report->breakdown('browser'),
                'platform' => $report->breakdown('platform'),
            ],
            'ips' => BackLogAccessReport::ips($report),
        ]);
    }

    public function time(Request $request): Response|RedirectResponse
    {
        return $this->tab($request, 'time', fn (ViewReport $report) => [
            'summary' => $report->summary(),
            'duration' => BackLogAccessReport::durationSummary($report),
            'heatmap' => $report->heatmap(),
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

        if (! isset(self::TABS[$tab])) {
            return redirect()->route('admin.system.backlog.access.overview');
        }

        $filters = ViewReport::filters($request);
        $report = BackLogAccessReport::make($filters, $this->userId($request));

        [$headers, $rows] = match ($tab) {
            'user' => [
                ['ผู้ใช้งาน', 'อีเมล', 'กลุ่มผู้ใช้งาน', 'จำนวนการเข้าหน้าจอ', 'session', 'จำนวนวันที่ใช้งาน', 'จำนวนหน้าจอที่ต่างกัน', 'IP ที่ใช้', 'เวลาใช้งานรวม (วินาที)', 'เวลาเฉลี่ยต่อหน้าจอ (วินาที)', 'เข้าใช้ครั้งแรกในช่วง', 'เข้าใช้ล่าสุดในช่วง'],
                array_map(fn (array $r) => [
                    ($r['name'] ?? '-').($r['deleted'] ? ' (ถูกลบแล้ว)' : ''), $r['email'], $r['group'], $r['views'], $r['sessions'], $r['active_days'],
                    $r['pages'], $r['ips'], $r['total_seconds'], $r['avg_seconds'], $r['first_at'], $r['last_at'],
                ], BackLogAccessReport::users($report, 1000)),
            ],
            'page' => [
                ['หน้าจอ', 'จำนวนการเข้าหน้าจอ', 'ผู้ใช้งานไม่ซ้ำ', 'session', 'เวลาเฉลี่ย (วินาที)', 'เวลารวม (วินาที)', 'เข้าล่าสุด'],
                array_map(fn (array $r) => [
                    $r['title'] ?? '-', $r['views'], $r['users'], $r['sessions'], $r['avg_seconds'], $r['total_seconds'], $r['last_at'],
                ], BackLogAccessReport::pages($report, 1000)),
            ],
            'device' => [
                ['มิติ', 'ค่า', 'จำนวนการเข้าหน้าจอ', 'ผู้ใช้งานไม่ซ้ำ'],
                $this->deviceRows($report),
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
                ['ช่วงเวลา', 'วันที่เริ่มต้น', 'วันที่สิ้นสุด', 'จำนวนการเข้าหน้าจอ', 'session', 'IP ไม่ซ้ำ'],
                array_map(fn (array $row) => [
                    $row['label'], $row['start'], $row['end'], $row['views'], $row['sessions'], $row['ips'],
                ], $report->series()),
            ],
        };

        return ViewReport::csv("backlog-access-{$tab}-{$filters['date_from']}-{$filters['date_to']}.csv", $headers, $rows);
    }

    /**
     * render แท็บสถิติ — เช็กสิทธิ์ + อ่านตัวกรอง + log (เฉพาะการเข้าหน้าครั้งแรก)
     *
     * @param  callable(ViewReport): array<string, mixed>  $build
     */
    private function tab(Request $request, string $tab, callable $build): Response|RedirectResponse
    {
        if (! $this->allowed($request)) {
            return redirect()->route('admin.dashboard');
        }

        $filters = ViewReport::filters($request);
        $userId = $this->userId($request);

        if (count($request->query()) === 0) {
            LogBackAccess::record('ประวัติการใช้งานหลังบ้าน - สถิติ'.self::TABS[$tab]);
        }

        return Inertia::render('Admin/System/BackLogAccess/'.ucfirst($tab), [
            'filters' => $filters + ['user_id' => $userId],
            'userOptions' => BackLogAccessReport::userOptions(),
            'durationCap' => BackLogAccessReport::DURATION_CAP,
            ...$build(BackLogAccessReport::make($filters, $userId)),
        ]);
    }

    private function allowed(Request $request): bool
    {
        return $request->user()->hasPermission('system.backlog.access');
    }

    private function userId(Request $request): ?int
    {
        $id = $request->query('user_id');

        return is_numeric($id) ? (int) $id : null;
    }

    /** @return list<array{0: string, 1: string, 2: int, 3: int|string}> */
    private function deviceRows(ViewReport $report): array
    {
        $rows = [];

        foreach (['device_type' => 'อุปกรณ์', 'browser' => 'เบราว์เซอร์', 'platform' => 'ระบบปฏิบัติการ'] as $column => $label) {
            foreach ($report->breakdown($column) as $item) {
                $rows[] = [$label, $item['key'] ?? 'ไม่ทราบ', $item['views'], ''];
            }
        }

        foreach (BackLogAccessReport::ips($report, 1000) as $item) {
            $rows[] = ['IP Address', $item['ip'] ?? '-', $item['views'], $item['users']];
        }

        return $rows;
    }
}
