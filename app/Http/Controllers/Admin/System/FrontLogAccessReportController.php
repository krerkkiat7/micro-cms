<?php

namespace App\Http\Controllers\Admin\System;

use App\Support\Report\AccessLogReport;
use App\Support\Report\ViewReport;
use Illuminate\Http\Request;

/**
 * สถิติของประวัติการใช้งานหน้าบ้าน (log_front_access) — แท็บต่อจากหน้ารายการ admin.system.frontlog.access.index
 * สิทธิ์ system.frontlog.access (ดู LogStatsController)
 *
 * ยังไม่มีผู้ใช้งานหน้าบ้าน (login หน้าบ้านจะมาใน phase ถัดไป) จึงไม่มีตัวกรอง/สถิติรายผู้ใช้งาน — นับผู้เข้าชมจาก session/IP
 * ตัดบอทออกจากทุกสถิติ แล้วแสดงจำนวนบอทแยกต่างหาก
 */
class FrontLogAccessReportController extends LogStatsController
{
    protected function permission(): string
    {
        return 'system.frontlog.access';
    }

    protected function pageFolder(): string
    {
        return 'Admin/System/FrontLogAccess';
    }

    protected function title(): string
    {
        return 'ประวัติการใช้งานหน้าบ้าน';
    }

    protected function fileKey(): string
    {
        return 'frontlog-access';
    }

    protected function hasUserFilter(): bool
    {
        return false;
    }

    protected function tabs(): array
    {
        return [
            'overview' => 'ภาพรวม',
            'page' => 'หน้าที่เข้าชม',
            'source' => 'แหล่งที่มาและภาษา',
            'device' => 'อุปกรณ์และเครือข่าย',
            'time' => 'ช่วงเวลา',
        ];
    }

    private function report(array $filters): ViewReport
    {
        return AccessLogReport::make(AccessLogReport::FRONT, $filters, excludeRobots: true);
    }

    protected function build(string $tab, array $filters, ?int $userId, Request $request): array
    {
        $report = $this->report($filters);
        $robots = AccessLogReport::robots(AccessLogReport::FRONT, $filters);
        $base = [
            'durationCap' => AccessLogReport::DURATION_CAP,
            'robotViews' => array_sum(array_column($robots, 'views')),
        ];

        return $base + match ($tab) {
            'overview' => (function () use ($report) {
                $series = $report->series();

                return [
                    'series' => $series,
                    'summary' => $report->summary($series),
                    'duration' => AccessLogReport::durationSummary($report),
                    'bounce' => AccessLogReport::bounce($report),
                    'pages' => AccessLogReport::pages($report, 5),
                    'landing' => AccessLogReport::landingPages($report, 5),
                ];
            })(),
            'page' => [
                'summary' => $report->summary(),
                'duration' => AccessLogReport::durationSummary($report),
                'pages' => AccessLogReport::pages($report),
                'landing' => AccessLogReport::landingPages($report),
            ],
            'source' => [
                'summary' => $report->summary(),
                'referrers' => $report->referrers($request->getHost()),
                'languages' => AccessLogReport::siteLanguages($report),
                'acceptLanguages' => $report->breakdown('accept_lang'),
            ],
            'device' => [
                'summary' => $report->summary(),
                'breakdowns' => [
                    'device_type' => $report->breakdown('device_type'),
                    'browser' => $report->breakdown('browser'),
                    'platform' => $report->breakdown('platform'),
                ],
                'ips' => AccessLogReport::ips($report),
                'robots' => $robots,
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
        $report = $this->report($filters);

        return match ($tab) {
            'page' => [
                ['หน้า', 'จำนวนการเปิดหน้า', 'session', 'เวลาเฉลี่ย (วินาที)', 'เวลารวม (วินาที)', 'เข้าล่าสุด', 'จำนวนครั้งที่เป็นหน้าแรกของ session'],
                (function () use ($report) {
                    $landing = array_column(AccessLogReport::landingPages($report, 1000), 'sessions', 'title');

                    return array_map(fn (array $r) => [
                        $r['title'] ?? '-', $r['views'], $r['sessions'], $r['avg_seconds'], $r['total_seconds'], $r['last_at'], $landing[$r['title']] ?? 0,
                    ], AccessLogReport::pages($report, 1000));
                })(),
            ],
            'source' => [
                ['มิติ', 'ค่า', 'จำนวนการเปิดหน้า'],
                (function () use ($report, $request) {
                    $rows = [];
                    $referrers = $report->referrers($request->getHost(), 1000);

                    foreach ($referrers['sources'] as $item) {
                        $rows[] = ['กลุ่มแหล่งที่มา', $item['key'], $item['views']];
                    }
                    foreach ($referrers['hosts'] as $item) {
                        $rows[] = ['เว็บไซต์ที่อ้างอิงมา', $item['key'], $item['views']];
                    }
                    foreach ($referrers['paths'] as $item) {
                        $rows[] = ['หน้าในเว็บไซต์ที่อ้างอิงมา', $item['key'], $item['views']];
                    }
                    foreach (AccessLogReport::siteLanguages($report) as $item) {
                        $rows[] = ['ภาษาของเว็บไซต์', $item['key'] ?? 'ไม่ระบุ', $item['views']];
                    }
                    foreach ($report->breakdown('accept_lang', 100) as $item) {
                        $rows[] = ['ภาษาของเบราว์เซอร์', $item['key'] ?? 'ไม่ทราบ', $item['views']];
                    }

                    return $rows;
                })(),
            ],
            'device' => [
                ['มิติ', 'ค่า', 'จำนวนการเปิดหน้า'],
                (function () use ($report, $filters) {
                    $rows = [];

                    foreach (['device_type' => 'อุปกรณ์', 'browser' => 'เบราว์เซอร์', 'platform' => 'ระบบปฏิบัติการ'] as $column => $label) {
                        foreach ($report->breakdown($column) as $item) {
                            $rows[] = [$label, $item['key'] ?? 'ไม่ทราบ', $item['views']];
                        }
                    }
                    foreach (AccessLogReport::ips($report, 1000) as $item) {
                        $rows[] = ['IP Address', $item['ip'] ?? '-', $item['views']];
                    }
                    foreach (AccessLogReport::robots(AccessLogReport::FRONT, $filters, 1000) as $item) {
                        $rows[] = ['บอท', $item['key'], $item['views']];
                    }

                    return $rows;
                })(),
            ],
            'time' => $this->heatmapCsv($report->heatmap()),
            default => $this->seriesCsv($report->series(), ['จำนวนการเปิดหน้า', 'ผู้เข้าชม (session)', 'IP ไม่ซ้ำ']),
        };
    }
}
