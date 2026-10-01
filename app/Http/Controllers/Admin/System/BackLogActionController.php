<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BackLogActionController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    /**
     * หน้ารายการประวัติการกระทำหลังบ้าน — ค้นหา / กรองโมดูล-ประเภท-ช่วงวันที่ / แบ่งหน้า
     */
    public function index(Request $request): InertiaResponse|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.backlog.action')) {
            return redirect()->route('admin.dashboard');
        }

        $q = trim((string) $request->query('q', ''));
        $moduleCode = trim((string) $request->query('module_code', ''));
        $actionType = trim((string) $request->query('action_type', ''));
        $perPage = (int) $request->query('per_page');

        $filters = [
            'q' => $q !== '' ? $q : null,
            'module_code' => $moduleCode !== '' ? $moduleCode : null,
            'action_type' => $actionType !== '' ? $actionType : null,
            'date_from' => $this->toDate($request->query('date_from')),
            'date_to' => $this->toDate($request->query('date_to')),
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0],
        ];

        // การเรียงลำดับ — เริ่มต้นที่ วันเวลา มากไปน้อย
        $sortable = ['name', 'module_code', 'action_type', 'value_string', 'remote_ip', 'created_at'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $logs = LogBackAction::query()
            ->leftJoin('sys_user', 'sys_user.id', '=', 'log_back_action.user_id')
            ->select('log_back_action.*')
            ->with('user:id,titlename,firstname,lastname')
            ->when($filters['q'] !== null, function ($query) use ($filters) {
                $term = $filters['q'];

                $query->where(function ($inner) use ($term) {
                    $inner->where('log_back_action.value_string', 'like', "%{$term}%")
                        ->orWhere('log_back_action.module_code', 'like', "%{$term}%")
                        ->orWhere('log_back_action.remote_ip', 'like', "%{$term}%");
                });
            })
            ->when($filters['module_code'] !== null, fn ($query) => $query->where('log_back_action.module_code', $filters['module_code']))
            ->when($filters['action_type'] !== null, fn ($query) => $query->where('log_back_action.action_type', $filters['action_type']))
            ->when($filters['date_from'] !== null, fn ($query) => $query->where('log_back_action.created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== null, fn ($query) => $query->where('log_back_action.created_at', '<', $this->nextDay($filters['date_to'])))
            ->when($sort === 'name', fn ($query) => $query
                ->orderBy('sys_user.firstname', $direction)
                ->orderBy('sys_user.lastname', $direction))
            ->when($sort !== 'name', fn ($query) => $query->orderBy("log_back_action.{$sort}", $direction))
            ->orderBy('log_back_action.id', 'desc') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (LogBackAction $log) => [
                'id' => $log->id,
                'name' => $log->user?->name,
                'module_code' => $log->module_code,
                'action_type' => $log->action_type,
                'value_string' => $log->value_string,
                'ref_id' => $log->ref_id,
                'remote_ip' => $log->remote_ip,
                'geo_ip' => $log->geo_ip,
                'created_at' => $log->created_at,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('ประวัติการกระทำหลังบ้าน');
        }

        return Inertia::render('Admin/System/BackLogAction/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'moduleOptions' => $this->distinctColumn('module_code'),
            'actionTypeOptions' => $this->distinctColumn('action_type'),
        ]);
    }

    /**
     * ค่าที่ไม่ซ้ำของคอลัมน์หนึ่ง (สำหรับ dropdown ตัวกรอง)
     *
     * @return list<string>
     */
    private function distinctColumn(string $column): array
    {
        return LogBackAction::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }
}
