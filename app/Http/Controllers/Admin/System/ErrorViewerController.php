<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\LogBackAccess;
use App\Support\Report\AccessLogReport;
use App\Support\Report\ErrorLogReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ตรวจสอบ Error (admin.system.errorviewer.*) — อ่านไฟล์ json-error-{front,admin}-*.log ผ่าน ErrorLogReader (ดู docs/PRD-system.md §10)
 * สิทธิ์ system.error.view; อ่านอย่างเดียว (ไม่มีการแก้ไข/ลบ จึงไม่บันทึก LogBackAction เหมือนหน้าประวัติอื่น)
 */
class ErrorViewerController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.error.view')) {
            return redirect()->route('admin.dashboard');
        }

        if (count($request->query()) === 0) {
            LogBackAccess::record('ตรวจสอบ Error');
        }

        $side = ErrorLogReader::isSide($request->query('side')) ? $request->query('side') : 'front';
        $dates = ErrorLogReader::dates($side);
        $requested = $request->query('date');
        $date = ErrorLogReader::isDate($requested) && in_array($requested, $dates, true) ? $requested : ($dates[0] ?? null);
        $q = trim((string) $request->query('q', ''));

        $result = $date !== null ? ErrorLogReader::entries($side, $date, $q !== '' ? $q : null) : ['rows' => [], 'truncated' => false];
        $total = count($result['rows']);
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) $request->query('page', 1)), $lastPage);
        $pageRows = array_slice($result['rows'], ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        // ชื่อผู้ใช้หลังบ้านของแถวในหน้านี้ (ครั้งเดียวต่อหน้า)
        $users = AccessLogReport::userInfo(array_values(array_unique(array_filter(array_column($pageRows, 'user_id')))));
        $pageRows = array_map(fn (array $row) => $row + [
            'user_name' => $row['user_id'] !== null ? ($users[$row['user_id']]['name'] ?? null) : null,
        ], $pageRows);

        return Inertia::render('Admin/System/ErrorViewer/Index', [
            'side' => $side,
            'dates' => $dates,
            'date' => $date,
            'filters' => ['q' => $q],
            'rows' => [
                'data' => $pageRows,
                'current_page' => $page,
                'last_page' => $lastPage,
                'total' => $total,
                'from' => $total > 0 ? ($page - 1) * self::PER_PAGE + 1 : 0,
                'to' => min($page * self::PER_PAGE, $total),
            ],
            'summary' => ErrorLogReader::summary($result['rows']),
            'truncated' => $result['truncated'],
            'maxEntries' => ErrorLogReader::MAX_ENTRIES,
            'counts' => [
                'front' => count(ErrorLogReader::dates('front')),
                'admin' => count(ErrorLogReader::dates('admin')),
            ],
        ]);
    }

    /**
     * รายละเอียดเต็มจากรหัสอ้างอิง (ค้นทุกฝั่ง) — ใช้ทั้งตอนคลิกแถวและช่องค้นหารหัส
     */
    public function show(Request $request, string $reference): JsonResponse
    {
        if (! $request->user()->hasPermission('system.error.view')) {
            return response()->json(['message' => 'ไม่มีสิทธิ์'], 403);
        }

        $reference = strtoupper(trim($reference));
        $detail = ErrorLogReader::isReference($reference) ? ErrorLogReader::find($reference) : null;

        return $detail === null
            ? response()->json(['message' => "ไม่พบรหัสอ้างอิง {$reference}"], 404)
            : response()->json($detail);
    }
}
