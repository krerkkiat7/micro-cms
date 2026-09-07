<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
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

        return Inertia::render('Admin/Dashboard', [
            'can' => [
                'articleCreate' => $user->hasPermission('article.create'),
                'systemUserView' => $user->hasPermission('system.user.view'),
            ],
        ]);
    }
}
