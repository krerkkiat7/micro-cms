<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\LogBackAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BackLogAccessController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    /**
     * หน้ารายการประวัติการเข้าชมหลังบ้าน — ค้นหา / กรองช่วงวันที่ / แบ่งหน้า
     */
    public function index(Request $request): InertiaResponse|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.backlog.access')) {
            return redirect()->route('admin.dashboard');
        }

        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page');

        $filters = [
            'q' => $q !== '' ? $q : null,
            'date_from' => $this->toDate($request->query('date_from')),
            'date_to' => $this->toDate($request->query('date_to')),
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0],
        ];

        // การเรียงลำดับ — เริ่มต้นที่ วันเวลาที่เข้าชม มากไปน้อย
        $sortable = ['name', 'uri_string', 'title_name', 'remote_ip', 'created_at', 'last_visited'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $logs = LogBackAccess::query()
            ->leftJoin('sys_user', 'sys_user.id', '=', 'log_back_access.user_id')
            ->select('log_back_access.*')
            ->with('user:id,titlename,firstname,lastname')
            ->when($filters['q'] !== null, function ($query) use ($filters) {
                $term = $filters['q'];

                $query->where(function ($inner) use ($term) {
                    $inner->where('log_back_access.uri_string', 'like', "%{$term}%")
                        ->orWhere('log_back_access.title_name', 'like', "%{$term}%")
                        ->orWhere('log_back_access.remote_ip', 'like', "%{$term}%");
                });
            })
            ->when($filters['date_from'] !== null, fn ($query) => $query->where('log_back_access.created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== null, fn ($query) => $query->where('log_back_access.created_at', '<', $this->nextDay($filters['date_to'])))
            ->when($sort === 'name', fn ($query) => $query
                ->orderBy('sys_user.firstname', $direction)
                ->orderBy('sys_user.lastname', $direction))
            ->when($sort !== 'name', fn ($query) => $query->orderBy("log_back_access.{$sort}", $direction))
            ->orderBy('log_back_access.id', 'desc') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (LogBackAccess $log) => [
                'id' => $log->id,
                'name' => $log->user?->name,
                'uri_string' => $log->uri_string,
                'title_name' => $log->title_name,
                'remote_ip' => $log->remote_ip,
                'created_at' => $log->created_at,
                'last_visited' => $log->last_visited,
                // รายละเอียดเพิ่มเติม (แสดงใน dialog)
                'session_id' => $log->session_id,
                'browser' => $log->browser,
                'browser_version' => $log->browser_version,
                'platform' => $log->platform,
                'device_type' => $log->device_type,
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
            LogBackAccess::record('ประวัติการใช้งานหลังบ้าน');
        }

        return Inertia::render('Admin/System/BackLogAccess/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * keep-alive — อัปเดต last_visited ของแถว log ที่ frontend เปิดค้างไว้
     *
     * client ยิงมาด้วย navigator.sendBeacon (ตอนสลับแท็บ/ปิดหน้า) + interval หยาบ ๆ
     * fire-and-forget: token เพี้ยน/ไม่ใช่ของผู้ใช้คนนี้ = ไม่ทำอะไร ตอบ 204 เสมอ
     * กันด้วย auth (session) + scope user_id — ไม่ต้องเช็ก permission (dashboard/profile ก็บันทึก log)
     */
    public function ping(Request $request): Response
    {
        $token = (string) $request->input('token');

        if (strlen($token) === 26) {
            LogBackAccess::query()
                ->where('token', $token)
                ->where('user_id', $request->user()->id)
                ->where('created_at', '>=', now()->subDay())
                ->update(['last_visited' => now()]);
        }

        return response()->noContent();
    }
}
