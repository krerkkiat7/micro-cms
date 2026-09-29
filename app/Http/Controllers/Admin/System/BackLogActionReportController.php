<?php

namespace App\Http\Controllers\Admin\System;

use App\Support\Report\ActionLogReport;
use App\Support\Report\ViewReport;
use Illuminate\Http\Request;

/**
 * สถิติของประวัติการกระทำหลังบ้าน (log_back_action) — แท็บต่อจากหน้ารายการ admin.system.backlog.action.index
 * สิทธิ์ system.backlog.action (ดู LogStatsController)
 */
class BackLogActionReportController extends LogStatsController
{
    protected function permission(): string
    {
        return 'system.backlog.action';
    }

    protected function pageFolder(): string
    {
        return 'Admin/System/BackLogAction';
    }

    protected function title(): string
    {
        return 'ประวัติการกระทำหลังบ้าน';
    }

    protected function fileKey(): string
    {
        return 'backlog-action';
    }

    protected function tabs(): array
    {
        return [
            'overview' => 'ภาพรวม',
            'user' => 'ผู้ใช้งาน',
            'module' => 'โมดูลและข้อมูล',
            'time' => 'ช่วงเวลา',
        ];
    }

    private function report(array $filters, ?int $userId): ViewReport
    {
        return ActionLogReport::make($filters, $userId);
    }

    protected function build(string $tab, array $filters, ?int $userId, Request $request): array
    {
        $report = $this->report($filters, $userId);

        return ['counts' => ActionLogReport::counts($report)] + match ($tab) {
            'overview' => [
                'summary' => $report->summary(),
                'buckets' => $report->buckets(),
                'trend' => ActionLogReport::trend($report),
                'users' => ActionLogReport::users($report, 5),
                'modules' => ActionLogReport::modules($report, 5),
            ],
            'user' => [
                'users' => ActionLogReport::users($report),
            ],
            'module' => [
                'modules' => ActionLogReport::modules($report),
                'records' => ActionLogReport::records($report),
            ],
            'time' => [
                'heatmap' => $report->heatmap(),
                'changeHeatmap' => ActionLogReport::changesOnly($filters, $userId)->heatmap(),
            ],
        };
    }

    protected function exportRows(string $tab, array $filters, ?int $userId, Request $request): array
    {
        $report = $this->report($filters, $userId);
        $types = ['เพิ่ม', 'ดู', 'แก้ไข', 'ลบ', 'อื่น ๆ'];

        return match ($tab) {
            'user' => [
                ['ผู้ใช้งาน', 'กลุ่มผู้ใช้งาน', 'ทั้งหมด', ...$types, 'จำนวนโมดูล', 'จำนวนวันที่มีการกระทำ', 'ล่าสุด'],
                array_map(fn (array $r) => [
                    ($r['name'] ?? '-').($r['deleted'] ? ' (ถูกลบแล้ว)' : ''), $r['group'], $r['total'],
                    $r['create'], $r['view'], $r['update'], $r['delete'], $r['other'], $r['modules'], $r['active_days'], $r['last_at'],
                ], ActionLogReport::users($report, 1000)),
            ],
            'module' => [
                ['โมดูล', 'ทั้งหมด', ...$types, 'จำนวนผู้ใช้งาน', 'ล่าสุด'],
                array_map(fn (array $r) => [
                    $r['module'], $r['total'], $r['create'], $r['view'], $r['update'], $r['delete'], $r['other'], $r['users'], $r['last_at'],
                ], ActionLogReport::modules($report, 1000)),
            ],
            'time' => $this->heatmapCsv($report->heatmap()),
            default => (function () use ($report) {
                $trend = ActionLogReport::trend($report);
                $buckets = $report->buckets();

                return [
                    ['ช่วงเวลา', 'วันที่เริ่มต้น', 'วันที่สิ้นสุด', 'เพิ่ม', 'แก้ไข', 'ลบ', 'ดู'],
                    array_map(fn (array $b, int $i) => [
                        $b['label'], $b['start'], $b['end'], $trend['create'][$i] ?? 0, $trend['update'][$i] ?? 0, $trend['delete'][$i] ?? 0, $trend['view'][$i] ?? 0,
                    ], $buckets, array_keys($buckets)),
                ];
            })(),
        };
    }
}
