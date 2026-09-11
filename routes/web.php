<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\System\BackLogAccessController;
use App\Http\Controllers\Admin\System\BackLogActionController;
use App\Http\Controllers\Admin\System\BackLogLoginController;
use App\Http\Controllers\Admin\System\SettingController;
use App\Http\Controllers\Admin\System\UserController;
use App\Http\Controllers\Admin\System\UsergroupController;
use App\Http\Controllers\Front\HomeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| 1. Front-Office Routes (Multi-language Support)
|--------------------------------------------------------------------------
*/
// Redirect จากหน้า Root (/) ไปที่ภาษาเริ่มต้น เช่น /th
Route::get('/', function () {
    return redirect('/th');
});

// Group ทุก Route ของหน้าบ้านไว้ภายใต้ Prefix ภาษา ({lang} = th หรือ en)
Route::group([
    'prefix' => '{lang}',
    'where' => ['lang' => 'th|en'],
    'middleware' => ['web', 'setLocale'], // เดี๋ยวเราจะใส่ SetLocale Middleware ที่นี่
], function () {

    Route::get('/', [HomeController::class, 'index'])->name('front.home');
    // เพิ่มหน้าอื่นๆ ของ Front-office ตรงนี้...
});

/*
|--------------------------------------------------------------------------
| 2. Back-Office Routes (/admin)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {

    // 1. ดึงไฟล์ Auth Routes (Login, Register, Password Reset) มาไว้ภายใต้ /admin
    // ทำให้ URL กลายเป็น /admin/login, /admin/register อัตโนมัติ
    require __DIR__.'/auth_admin.php';

    // /admin เฉย ๆ — login แล้วไป dashboard, ยังไม่ login ไปหน้า login
    Route::get('/', function () {
        return redirect()->route(auth()->check() ? 'admin.dashboard' : 'admin.login');
    })->name('admin.home');

    // 2. Route หน้าหลังบ้านที่ต้องผ่านการ Login ก่อน
    Route::middleware(['auth', 'verified'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        // เพิ่ม Route หลังบ้านอื่นๆ ตรงนี้...
        Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('admin.profile.destroy');

        // จัดการผู้ใช้งานหลังบ้าน (user_type = back) — ตรวจสอบสิทธิ์ในแต่ละ method ของ UserController
        Route::prefix('system/user')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('admin.system.user.index');
            Route::get('/add', [UserController::class, 'add'])->name('admin.system.user.add');
            Route::post('/', [UserController::class, 'store'])->name('admin.system.user.store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('admin.system.user.edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('admin.system.user.update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('admin.system.user.destroy');
            Route::get('/{user}/password', [UserController::class, 'password'])->name('admin.system.user.password');
            Route::put('/{user}/password', [UserController::class, 'passwordUpdate'])->name('admin.system.user.password.update');
        });

        // จัดการกลุ่มผู้ใช้งานหลังบ้าน — ตรวจสอบสิทธิ์ในแต่ละ method ของ UsergroupController
        Route::prefix('system/usergroup')->group(function () {
            Route::get('/', [UsergroupController::class, 'index'])->name('admin.system.usergroup.index');
            Route::get('/add', [UsergroupController::class, 'add'])->name('admin.system.usergroup.add');
            Route::post('/', [UsergroupController::class, 'store'])->name('admin.system.usergroup.store');
            Route::get('/{usergroup}/edit', [UsergroupController::class, 'edit'])->name('admin.system.usergroup.edit');
            Route::put('/{usergroup}', [UsergroupController::class, 'update'])->name('admin.system.usergroup.update');
            Route::delete('/{usergroup}', [UsergroupController::class, 'destroy'])->name('admin.system.usergroup.destroy');
            Route::get('/{usergroup}/rights', [UsergroupController::class, 'rights'])->name('admin.system.usergroup.rights');
            Route::put('/{usergroup}/rights', [UsergroupController::class, 'rightsUpdate'])->name('admin.system.usergroup.rights.update');
        });

        // ประวัติหลังบ้าน (log_back_*) — ตรวจสอบสิทธิ์ในแต่ละ controller
        Route::prefix('system/backlog')->group(function () {
            // การเข้าชม (log_back_access)
            Route::prefix('access')->group(function () {
                Route::get('/', [BackLogAccessController::class, 'index'])->name('admin.system.backlog.access.index');
                // ปลายทาง keep-alive อัปเดต last_visited (ยิงจาก navigator.sendBeacon)
                Route::post('/ping', [BackLogAccessController::class, 'ping'])
                    ->name('admin.system.backlog.access.ping')
                    ->middleware('throttle:60,1');
            });

            // การเข้าสู่ระบบ (log_back_login)
            Route::get('/login', [BackLogLoginController::class, 'index'])->name('admin.system.backlog.login.index');

            // การกระทำ (log_back_action)
            Route::get('/action', [BackLogActionController::class, 'index'])->name('admin.system.backlog.action.index');
        });

        // ตั้งค่าระบบ (sys_setting) + ล้างแคช — ตรวจสอบสิทธิ์ในแต่ละ method ของ SettingController
        Route::prefix('system/setting')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('admin.system.setting.index');
            Route::put('/site', [SettingController::class, 'updateSite'])->name('admin.system.setting.update.site');
            Route::put('/smtp', [SettingController::class, 'updateSmtp'])->name('admin.system.setting.update.smtp');
            Route::put('/turnstile', [SettingController::class, 'updateTurnstile'])->name('admin.system.setting.update.turnstile');
            Route::put('/login-back', [SettingController::class, 'updateLoginBack'])->name('admin.system.setting.update.login_back');

            Route::get('/clearcache', [SettingController::class, 'clearcache'])->name('admin.system.setting.clearcache');
            Route::post('/clearcache/{group}', [SettingController::class, 'clearCacheGroup'])->name('admin.system.setting.clearcache.group');
            Route::post('/clearcache-all', [SettingController::class, 'clearCacheAll'])->name('admin.system.setting.clearcache.all');
        });
    });
});

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

// require __DIR__.'/auth.php';
