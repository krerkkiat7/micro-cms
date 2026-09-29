<?php

namespace App\Http\Controllers\Admin\System;

use App\Support\Report\AccessLogReport;
use App\Support\Report\ViewReport;
use Illuminate\Http\Request;

/**
 * สถิติของประวัติการใช้งานหลังบ้าน (log_back_access) — แท็บต่อจากหน้ารายการ admin.system.backlog.access.index
 * สิทธิ์ system.backlog.access (ดู LogStatsController)
 */
class BackLogAccessReportController extends LogStatsController
{
    protected function permission(): string
    {
        return 'system.backlog.access';
    }

    protected function pageFolder(): string
    {
        return 'Admin/System/BackLogAccess';
    }

    protected function title(): string
    {
        return 'ประวัติการใช้งานหลังบ้าน';
    }

    protected function fileKey(): string
    {
        return 'backlog-access';
    }

    protected function tabs(): array
    {
        return [
            'overview' => 'ภาพรวม',
            'user' => 'ผู้ใช้งาน',
            'page' => 'หน้าจอ',
            'device' => 'อุปกรณ์และเครือข่าย',
            'time' => 'ช่วงเวลา',
        ];
    }

    private function report(array $filters, ?int $userId): ViewReport
    {
        return AccessLogReport::make(AccessLogReport::BACK, $filters, $userId);
    }

    protected function build(string $tab, array $filters, ?int $userId, Request $request): array
    {
        $report = $this->report($filters, $userId);
        $base = ['durationCap' => AccessLogReport::DURATION_CAP];

        return $base + match ($tab) {
            'overview' => (function () use ($report) {
                $series = $report->series();

                return [
                    'series' => $series,
                    'summary' => $report->summary($series),
                    'duration' => AccessLogReport::durationSummary($report),
                    'users' => AccessLogReport::users($report, 5),
                    'pages' => AccessLogReport::pages($report, 5),
                ];
            })(),
            'user' => [
                'summary' => $report->summary(),
                'duration' => AccessLogReport::durationSummary($report),
                'users' => AccessLogReport::users($report),
            ],
            'page' => [
                'summary' => $report->summary(),
                'duration' => AccessLogReport::durationSummary($report),
                'pages' => AccessLogReport::pages($report),
            ],
            'device' => [
                'summary' => $report->summary(),
                'breakdowns' => [
                    'device_type' => $report->breakdown('device_type'),
                    'browser' => $report->breakdown('browser'),
                    'platform' => $report->breakdown('platform'),
                ],
                'ips' => AccessLogReport::ips($report),
            ],
            'time' => [
                'summary' => $report->summary(),
                'duration' => AccessLogReport::durationSummary($report),
                'heatmap' => $report->heatmap(),
            ],
        };
    }

    protected function exportRows(string $tab, array $filters, ?int $userId, Request $request): array
    {
        $report = $this->report($filters, $userId);

        return match ($tab) {
            'user' => [
                ['ผู้ใช้งาน', 'อีเมล', 'กลุ่มผู้ใช้งาน', 'จำนวนการเข้าหน้าจอ', 'session', 'จำนวนวันที่ใช้งาน', 'จำนวนหน้าจอที่ต่างกัน', 'IP ที่ใช้', 'เวลาใช้งานรวม (วินาที)', 'เวลาเฉลี่ยต่อหน้าจอ (วินาที)', 'เข้าใช้ครั้งแรกในช่วง', 'เข้าใช้ล่าสุดในช่วง'],
                array_map(fn (array $r) => [
                    ($r['name'] ?? '-').($r['deleted'] ? ' (ถูกลบแล้ว)' : ''), $r['email'], $r['group'], $r['views'], $r['sessions'], $r['active_days'],
                    $r['pages'], $r['ips'], $r['total_seconds'], $r['avg_seconds'], $r['first_at'], $r['last_at'],
                ], AccessLogReport::users($report, 1000)),
            ],
            'page' => [
                ['หน้าจอ', 'จำนวนการเข้าหน้าจอ', 'ผู้ใช้งานไม่ซ้ำ', 'session', 'เวลาเฉลี่ย (วินาที)', 'เวลารวม (วินาที)', 'เข้าล่าสุด'],
                array_map(fn (array $r) => [
                    $r['title'] ?? '-', $r['views'], $r['users'], $r['sessions'], $r['avg_seconds'], $r['total_seconds'], $r['last_at'],
                ], AccessLogReport::pages($report, 1000)),
            ],
            'device' => [
                ['มิติ', 'ค่า', 'จำนวนการเข้าหน้าจอ', 'ผู้ใช้งานไม่ซ้ำ'],
                $this->deviceRows($report),
            ],
            'time' => $this->heatmapCsv($report->heatmap()),
            default => $this->seriesCsv($report->series(), ['จำนวนการเข้าหน้าจอ', 'session', 'IP ไม่ซ้ำ']),
        };
    }

    /** @return list<array<int, mixed>> */
    private function deviceRows(ViewReport $report): array
    {
        $rows = [];

        foreach (['device_type' => 'อุปกรณ์', 'browser' => 'เบราว์เซอร์', 'platform' => 'ระบบปฏิบัติการ'] as $column => $label) {
            foreach ($report->breakdown($column) as $item) {
                $rows[] = [$label, $item['key'] ?? 'ไม่ทราบ', $item['views'], ''];
            }
        }

        foreach (AccessLogReport::ips($report, 1000) as $item) {
            $rows[] = ['IP Address', $item['ip'] ?? '-', $item['views'], $item['users']];
        }

        return $rows;
    }
}
