<?php

namespace App\Http\Controllers\Admin\System;

use App\Support\Report\LoginLogReport;
use App\Support\Report\ViewReport;
use Illuminate\Http\Request;

/**
 * สถิติของประวัติการเข้าสู่ระบบหลังบ้าน (log_back_login) — แท็บต่อจากหน้ารายการ admin.system.backlog.login.index
 * สิทธิ์ system.backlog.login (ดู LogStatsController); กรองผู้ใช้งาน = เหตุการณ์ของ user_id นั้น + ที่กรอกอีเมลของบัญชีนั้น
 */
class BackLogLoginReportController extends LogStatsController
{
    protected function permission(): string
    {
        return 'system.backlog.login';
    }

    protected function pageFolder(): string
    {
        return 'Admin/System/BackLogLogin';
    }

    protected function title(): string
    {
        return 'ประวัติการเข้าสู่ระบบหลังบ้าน';
    }

    protected function fileKey(): string
    {
        return 'backlog-login';
    }

    protected function tabs(): array
    {
        return [
            'overview' => 'ภาพรวม',
            'account' => 'บัญชีผู้ใช้งาน',
            'security' => 'ความปลอดภัย',
            'time' => 'ช่วงเวลา',
        ];
    }

    /** ทุกเหตุการณ์ (รวม logout) */
    private function all(array $filters, ?int $userId): ViewReport
    {
        return LoginLogReport::make($filters, $userId, LoginLogReport::emailOf($userId));
    }

    /** เฉพาะการพยายามเข้าสู่ระบบตามผลลัพธ์ */
    private function attempts(array $filters, ?int $userId, array $results = LoginLogReport::RESULTS): ViewReport
    {
        return LoginLogReport::make($filters, $userId, LoginLogReport::emailOf($userId), $results);
    }

    protected function build(string $tab, array $filters, ?int $userId, Request $request): array
    {
        $all = $this->all($filters, $userId);
        $counts = LoginLogReport::counts($all);

        return ['counts' => $counts] + match ($tab) {
            'overview' => (function () use ($all, $filters, $userId) {
                $attempts = $this->attempts($filters, $userId);

                return [
                    'summary' => $attempts->summary(),
                    'buckets' => $all->buckets(),
                    'trend' => LoginLogReport::trend($all),
                    'reasons' => LoginLogReport::reasons($all),
                ];
            })(),
            'account' => [
                'accounts' => LoginLogReport::accounts($all),
            ],
            'security' => [
                'ips' => LoginLogReport::failedIps($all),
                'reasons' => LoginLogReport::reasons($all),
                'buckets' => $all->buckets(),
                'trend' => LoginLogReport::trend($all),
            ],
            'time' => [
                'successHeatmap' => $this->attempts($filters, $userId, ['success'])->heatmap(),
                'failedHeatmap' => $this->attempts($filters, $userId, ['fail', 'block'])->heatmap(),
            ],
        };
    }

    protected function exportRows(string $tab, array $filters, ?int $userId, Request $request): array
    {
        $all = $this->all($filters, $userId);

        return match ($tab) {
            'account' => [
                ['Username', 'ผู้ใช้งาน', 'สำเร็จ', 'ไม่สำเร็จ', 'ถูกบล็อก', 'ออกจากระบบ', 'IP ที่ใช้', 'สำเร็จล่าสุด', 'ไม่สำเร็จล่าสุด'],
                array_map(fn (array $r) => [
                    $r['username'], $r['name'], $r['success'], $r['fail'], $r['block'], $r['logout'], $r['ips'], $r['last_success_at'], $r['last_fail_at'],
                ], LoginLogReport::accounts($all, 1000)),
            ],
            'security' => [
                ['IP Address', 'ไม่สำเร็จ/ถูกบล็อก', 'สำเร็จ', 'จำนวนบัญชีที่ลอง', 'ครั้งล่าสุด'],
                array_map(fn (array $r) => [$r['ip'], $r['failed'], $r['success'], $r['usernames'], $r['last_at']], LoginLogReport::failedIps($all, 1000)),
            ],
            'time' => $this->heatmapCsv($this->attempts($filters, $userId)->heatmap()),
            default => (function () use ($all) {
                $trend = LoginLogReport::trend($all);

                return [
                    ['ช่วงเวลา', 'วันที่เริ่มต้น', 'วันที่สิ้นสุด', 'สำเร็จ', 'ไม่สำเร็จ', 'ถูกบล็อก'],
                    array_map(fn (array $b, int $i) => [
                        $b['label'], $b['start'], $b['end'], $trend['success'][$i] ?? 0, $trend['fail'][$i] ?? 0, $trend['block'][$i] ?? 0,
                    ], $all->buckets(), array_keys($all->buckets())),
                ];
            })(),
        };
    }
}
