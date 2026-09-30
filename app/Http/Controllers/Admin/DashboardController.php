<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogBackAccess;
use App\Support\Report\DashboardReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * หน้า Dashboard — เปิดได้ทุกคนที่ login แต่แต่ละส่วนคำนวณ/ส่งเฉพาะเมื่อมีสิทธิ์ของส่วนนั้น (ดู DashboardReport)
     */
    public function index(Request $request): Response
    {
        LogBackAccess::record('แดชบอร์ด');

        $report = new DashboardReport($request->user());

        return Inertia::render('Admin/Dashboard', [
            'shortcuts' => $report->shortcuts(),
            'dashboard' => $report->toArray(),
        ]);
    }
}
