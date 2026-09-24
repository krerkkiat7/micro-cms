<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\LogBackAccess;
use App\Models\LogFrontAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * ประวัติการใช้งาน - หน้าบ้าน (log_front_access) — โครงเดียวกับ BackLogAccessController (ค้นหา / ช่วงวันที่ / แบ่งหน้า / เรียง)
 * เพิ่มตัวกรอง "ผู้เข้าชม" (ทั้งหมด / บุคคล / บอท) เพราะหน้าบ้านมีบอท/crawler เข้ามาจำนวนมาก
 */
class FrontLogAccessController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    public function index(Request $request): InertiaResponse|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.frontlog.access')) {
            return redirect()->route('admin.dashboard');
        }

        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page');
        $visitor = $request->query('visitor');

        $filters = [
            'q' => $q !== '' ? $q : null,
            'visitor' => in_array($visitor, ['human', 'robot'], true) ? $visitor : null,
            'date_from' => $this->toDate($request->query('date_from')),
            'date_to' => $this->toDate($request->query('date_to')),
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0],
        ];

        // การเรียงลำดับ — เริ่มต้นที่ วันเวลาที่เข้าชม มากไปน้อย
        $sortable = ['name', 'uri_string', 'title_name', 'remote_ip', 'device_type', 'created_at', 'last_visited'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $logs = LogFrontAccess::query()
            ->leftJoin('sys_user', 'sys_user.id', '=', 'log_front_access.user_id')
            ->select('log_front_access.*')
            ->with('user:id,titlename,firstname,lastname')
            ->when($filters['q'] !== null, function ($query) use ($filters) {
                $term = $filters['q'];

                $query->where(function ($inner) use ($term) {
                    $inner->where('log_front_access.uri_string', 'like', "%{$term}%")
                        ->orWhere('log_front_access.title_name', 'like', "%{$term}%")
                        ->orWhere('log_front_access.remote_ip', 'like', "%{$term}%");
                });
            })
            ->when($filters['visitor'] === 'human', fn ($query) => $query->whereNull('log_front_access.robot'))
            ->when($filters['visitor'] === 'robot', fn ($query) => $query->whereNotNull('log_front_access.robot'))
            ->when($filters['date_from'] !== null, fn ($query) => $query->whereDate('log_front_access.created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== null, fn ($query) => $query->whereDate('log_front_access.created_at', '<=', $filters['date_to']))
            ->when($sort === 'name', fn ($query) => $query
                ->orderBy('sys_user.firstname', $direction)
                ->orderBy('sys_user.lastname', $direction))
            ->when($sort !== 'name', fn ($query) => $query->orderBy("log_front_access.{$sort}", $direction))
            ->orderBy('log_front_access.id', 'desc') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (LogFrontAccess $log) => [
                'id' => $log->id,
                'name' => $log->user?->name,
                'uri_string' => $log->uri_string,
                'title_name' => $log->title_name,
                'remote_ip' => $log->remote_ip,
                'device_type' => $log->device_type,
                'created_at' => $log->created_at,
                'last_visited' => $log->last_visited,
                // รายละเอียดเพิ่มเติม (แสดงใน dialog)
                'session_id' => $log->session_id,
                'browser' => $log->browser,
                'browser_version' => $log->browser_version,
                'platform' => $log->platform,
                'mobile' => $log->mobile,
                'robot' => $log->robot,
                'referrer' => $log->referrer,
                'agent' => $log->agent,
                'accept_lang' => $log->accept_lang,
                'accept_charset' => $log->accept_charset,
                'geo_ip' => $log->geo_ip,
                'geo_ip_city' => $log->geo_ip_city,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('ประวัติการใช้งานหน้าบ้าน');
        }

        return Inertia::render('Admin/System/FrontLogAccess/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }
}
