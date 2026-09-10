<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\LogBackAccess;
use App\Models\LogBackLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BackLogLoginController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    /**
     * หน้ารายการประวัติการเข้าสู่ระบบหลังบ้าน — ค้นหา / กรองประเภท-ผลลัพธ์-ช่วงวันที่ / แบ่งหน้า
     */
    public function index(Request $request): InertiaResponse|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.backlog.login')) {
            return redirect()->route('admin.dashboard');
        }

        $q = trim((string) $request->query('q', ''));
        $logType = $request->query('log_type');
        $result = $request->query('result');
        $perPage = (int) $request->query('per_page');

        $filters = [
            'q' => $q !== '' ? $q : null,
            'log_type' => in_array($logType, ['login', 'logout'], true) ? $logType : null,
            'result' => in_array($result, ['success', 'fail', 'block'], true) ? $result : null,
            'date_from' => $this->toDate($request->query('date_from')),
            'date_to' => $this->toDate($request->query('date_to')),
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0],
        ];

        // การเรียงลำดับ — เริ่มต้นที่ วันเวลา มากไปน้อย
        $sortable = ['name', 'username', 'log_type', 'result', 'remote_ip', 'created_at'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $logs = LogBackLogin::query()
            ->leftJoin('sys_user', 'sys_user.id', '=', 'log_back_login.user_id')
            ->select('log_back_login.*')
            ->with('user:id,titlename,firstname,lastname')
            ->when($filters['q'] !== null, function ($query) use ($filters) {
                $term = $filters['q'];

                $query->where(function ($inner) use ($term) {
                    $inner->where('log_back_login.username', 'like', "%{$term}%")
                        ->orWhere('log_back_login.remote_ip', 'like', "%{$term}%")
                        ->orWhere('log_back_login.note', 'like', "%{$term}%");
                });
            })
            ->when($filters['log_type'] !== null, fn ($query) => $query->where('log_back_login.log_type', $filters['log_type']))
            ->when($filters['result'] !== null, fn ($query) => $query->where('log_back_login.result', $filters['result']))
            ->when($filters['date_from'] !== null, fn ($query) => $query->whereDate('log_back_login.created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== null, fn ($query) => $query->whereDate('log_back_login.created_at', '<=', $filters['date_to']))
            ->when($sort === 'name', fn ($query) => $query
                ->orderBy('sys_user.firstname', $direction)
                ->orderBy('sys_user.lastname', $direction))
            ->when($sort !== 'name', fn ($query) => $query->orderBy("log_back_login.{$sort}", $direction))
            ->orderBy('log_back_login.id', 'desc') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (LogBackLogin $log) => [
                'id' => $log->id,
                'name' => $log->user?->name,
                'username' => $log->username,
                'log_type' => $log->log_type,
                'result' => $log->result,
                'note' => $log->note,
                'remote_ip' => $log->remote_ip,
                'created_at' => $log->created_at,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('ประวัติการเข้าสู่ระบบหลังบ้าน');
        }

        return Inertia::render('Admin/System/BackLogLogin/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * แปลง input วันที่ (Y-m-d) เป็นสตริงวันที่ — คืน null ถ้าว่างหรือ parse ไม่ได้
     */
    private function toDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }
}
