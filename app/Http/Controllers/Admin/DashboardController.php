<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogBackAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function index(Request $request): Response
    {
        // if (! $request->user()->hasPermission('system.user.view')) { // เปลี่ยนเป็นฟังก์ชันเช็กสิทธิ์ของคุณ
        //     abort(403, 'Unauthorized access.');
        // }

        $user = $request->user();

        LogBackAccess::record('แดชบอร์ด');

        return Inertia::render('Admin/Dashboard', [
            'can' => [
                'articleCreate' => $user->hasPermission('article.create'),
                'systemUserView' => $user->hasPermission('system.user.view'),
            ],
        ]);
    }
}
