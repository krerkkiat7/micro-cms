<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\LogBackAccess;
use App\Support\Report\AccessLogReport;
use App\Support\Report\ViewReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ฐานของหน้าสถิติประวัติ (log_back_access / log_back_login / log_back_action / log_front_access) — แท็บต่อจากหน้ารายการของแต่ละประวัติ
 * route ของแต่ละแท็บชี้มาที่ show() พร้อม ->defaults('tab', '<แท็บ>'); ส่งออก CSV ที่ export() (?tab=)
 * สิทธิ์เดียวกับหน้ารายการ, บันทึกเฉพาะ LogBackAccess เหมือนหน้าประวัติอื่น (ไม่มี LogBackAction)
 * ตัวกรอง: ช่วงวันที่ + รายวัน/สัปดาห์/เดือน/ปี (ViewReport::filters) + ผู้ใช้งาน (user_id — ถ้า hasUserFilter())
 */
abstract class LogStatsController extends Controller
{
    /** สิทธิ์ที่ใช้ดู (เดียวกับหน้ารายการ) */
    abstract protected function permission(): string;

    /** โฟลเดอร์หน้า Inertia เช่น Admin/System/BackLogLogin */
    abstract protected function pageFolder(): string;

    /** ชื่อประวัติ (ใช้กับ LogBackAccess) */
    abstract protected function title(): string;

    /** ชื่อไฟล์ CSV (ส่วนหน้า) */
    abstract protected function fileKey(): string;

    /**
     * แท็บสถิติ → ชื่อแท็บ
     *
     * @return array<string, string>
     */
    abstract protected function tabs(): array;

    /**
     * props ของแท็บ
     *
     * @param  array{date_from: string, date_to: string, period: string}  $filters
     * @return array<string, mixed>
     */
    abstract protected function build(string $tab, array $filters, ?int $userId, Request $request): array;

    /**
     * หัวคอลัมน์ + แถวของ CSV ของแท็บ
     *
     * @param  array{date_from: string, date_to: string, period: string}  $filters
     * @return array{0: list<string>, 1: iterable<array<int, mixed>>}
     */
    abstract protected function exportRows(string $tab, array $filters, ?int $userId, Request $request): array;

    /** มีตัวกรองผู้ใช้งานหลังบ้านหรือไม่ */
    protected function hasUserFilter(): bool
    {
        return true;
    }

    public function show(Request $request, string $tab): Response|RedirectResponse
    {
        if (! $this->allowed($request)) {
            return redirect()->route('admin.dashboard');
        }

        if (! isset($this->tabs()[$tab])) {
            abort(404);
        }

        $filters = ViewReport::filters($request);
        $userId = $this->userId($request);

        if (count($request->query()) === 0) {
            LogBackAccess::record($this->title().' - สถิติ'.$this->tabs()[$tab]);
        }

        return Inertia::render($this->pageFolder().'/'.ucfirst($tab), [
            'filters' => $filters + ['user_id' => $userId],
            'userOptions' => $this->hasUserFilter() ? AccessLogReport::userOptions() : null,
            ...$this->build($tab, $filters, $userId, $request),
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

        if (! isset($this->tabs()[$tab])) {
            abort(404);
        }

        $filters = ViewReport::filters($request);
        [$headers, $rows] = $this->exportRows($tab, $filters, $this->userId($request), $request);

        return ViewReport::csv("{$this->fileKey()}-{$tab}-{$filters['date_from']}-{$filters['date_to']}.csv", $headers, $rows);
    }

    /**
     * ตาราง heatmap วัน × ชั่วโมง เป็นแถว CSV
     *
     * @param  list<list<int>>  $heatmap
     * @return array{0: list<string>, 1: list<array<int, mixed>>}
     */
    protected function heatmapCsv(array $heatmap): array
    {
        return [
            ['วัน', ...array_map(fn (int $hour) => sprintf('%02d:00', $hour), range(0, 23)), 'รวม'],
            array_map(
                fn (array $hours, int $day) => [['จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'อาทิตย์'][$day], ...$hours, array_sum($hours)],
                $heatmap,
                range(0, 6),
            ),
        ];
    }

    /**
     * ตารางรายช่วงเวลาเป็นแถว CSV
     *
     * @param  list<array<string, mixed>>  $series
     * @param  list<string>  $headers  หัวคอลัมน์ของ views / sessions / ips
     * @return array{0: list<string>, 1: list<array<int, mixed>>}
     */
    protected function seriesCsv(array $series, array $headers): array
    {
        return [
            ['ช่วงเวลา', 'วันที่เริ่มต้น', 'วันที่สิ้นสุด', ...$headers],
            array_map(fn (array $row) => [$row['label'], $row['start'], $row['end'], $row['views'], $row['sessions'], $row['ips']], $series),
        ];
    }

    protected function allowed(Request $request): bool
    {
        return $request->user()->hasPermission($this->permission());
    }

    protected function userId(Request $request): ?int
    {
        $id = $request->query('user_id');

        return $this->hasUserFilter() && is_numeric($id) ? (int) $id : null;
    }
}
