<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Front\HomeController;

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
    'middleware' => ['web', 'setLocale'] // เดี๋ยวเราจะใส่ SetLocale Middleware ที่นี่
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

    // 2. Route หน้าหลังบ้านที่ต้องผ่านการ Login ก่อน
    Route::middleware(['auth', 'verified'])->group(function () {
        
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        
        // เพิ่ม Route หลังบ้านอื่นๆ ตรงนี้...
        Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('admin.profile.destroy');
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
