<?php

use App\Http\Controllers\Admin\Article\ArticleCategoryController;
use App\Http\Controllers\Admin\Article\ArticleItemController;
use App\Http\Controllers\Admin\Article\ArticleSettingController;
use App\Http\Controllers\Admin\Article\ArticleTagController;
use App\Http\Controllers\Admin\Banner\BannerCategoryController;
use App\Http\Controllers\Admin\Banner\BannerItemController;
use App\Http\Controllers\Admin\Banner\BannerSettingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Intropage\IntropageItemController;
use App\Http\Controllers\Admin\Page\PageItemController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\System\BackLogAccessController;
use App\Http\Controllers\Admin\System\BackLogActionController;
use App\Http\Controllers\Admin\System\BackLogLoginController;
use App\Http\Controllers\Admin\System\FileController;
use App\Http\Controllers\Admin\System\FileServeController;
use App\Http\Controllers\Admin\System\FrontLogAccessController;
use App\Http\Controllers\Admin\System\FrontMenuController;
use App\Http\Controllers\Admin\System\SettingController;
use App\Http\Controllers\Admin\System\TemplateController;
use App\Http\Controllers\Admin\System\UserController;
use App\Http\Controllers\Admin\System\UsergroupController;
use App\Http\Controllers\AppAssetController;
use App\Http\Controllers\Front\AccessLogController as FrontAccessLogController;
use App\Http\Controllers\Front\Article\ArticleCategoryController as FrontArticleCategoryController;
use App\Http\Controllers\Front\Article\ArticleItemController as FrontArticleItemController;
use App\Http\Controllers\Front\Article\ArticleTagController as FrontArticleTagController;
use App\Http\Controllers\Front\Banner\BannerItemController as FrontBannerItemController;
use App\Http\Controllers\Front\FileController as FrontFileController;
use App\Http\Controllers\Front\Intropage\IntropageController;
use App\Http\Controllers\Front\Page\PageItemController as FrontPageItemController;
use App\Http\Middleware\FrontSecurityHeaders;
use App\Support\Setting;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| 1. Front-Office Routes (Multi-language Support) — ดู docs/PRD-front.md
|--------------------------------------------------------------------------
*/
// หน้าแรก (/) — แสดง Intropage ของภาษาหลักทันที (ไม่มี Intropage ที่เผยแพร่อยู่ = redirect ไปหน้าแรกที่กำหนดในเมนู)
Route::get('/', [IntropageController::class, 'root'])
    ->middleware(['setLocale', FrontSecurityHeaders::class])
    ->name('front.root');

// โลโก้/favicon สาธารณะของระบบ — ไม่ต้อง login, ไม่มี prefix ภาษา {lang} และไม่อยู่ใต้ /admin
// เพราะเป็น asset ที่ทั้งฝั่งแอดมินและหน้าบ้านใช้ร่วมกัน (ดู App\Http\Controllers\AppAssetController)
// ชื่อ route (app.logo/app.favicon) เป็นข้อยกเว้นของ convention front.*/admin.* ปกติ ตามที่กำหนดไว้โดยเฉพาะ
Route::get('/apps/logo.png', [AppAssetController::class, 'logo'])->name('app.logo');
Route::get('/apps/favicon.ico', [AppAssetController::class, 'favicon'])->name('app.favicon');

// ไฟล์/รูปของเนื้อหาหน้าบ้าน (สาธารณะ อ้างอิงด้วย hash_name) — URL สร้างจาก App\Support\Front\FrontFile ที่เดียว
Route::prefix('file')->controller(FrontFileController::class)->group(function () {
    Route::get('/get/{hashname}', 'show')->name('front.file.get');
    Route::get('/type/download/get/{hashname}', 'download')->name('front.file.download');
    Route::get('/type/thumbnail/size/{size}/get/{hashname}', 'thumbnail')
        ->name('front.file.thumbnail')
        ->where('size', '[0-9]+');
});

// keep-alive ของ log_front_access (ยกเว้น CSRF ใน bootstrap/app.php — sendBeacon ตั้ง header ไม่ได้)
Route::post('/front/access/ping', [FrontAccessLogController::class, 'ping'])
    ->name('front.access.ping')
    ->middleware('throttle:60,1');

// นับการคลิกลิงก์ banner (sendBeacon — ยกเว้น CSRF ใน bootstrap/app.php เหมือน ping ด้านบน)
Route::post('/front/banner/click', [FrontBannerItemController::class, 'click'])
    ->name('front.banner.click')
    ->middleware('throttle:60,1');

// Group ทุก Route ของหน้าบ้านไว้ภายใต้ Prefix ภาษา — {lang} รับเฉพาะภาษาที่เปิดใช้งานจริงตามตั้งค่าระบบ
// (sys_setting: site.lang_selected, ดู App\Support\Setting::languageRoutePattern()) ไม่ hardcode th|en อีกต่อไป
// หมายเหตุ: ถ้าใช้ `route:cache` ในอนาคต ต้องรัน route:cache ใหม่ทุกครั้งที่แก้ค่านี้ เพราะ where ถูกฝังไว้ตอน cache
Route::group([
    'prefix' => '{lang}',
    'where' => ['lang' => Setting::languageRoutePattern()],
    'middleware' => ['web', 'setLocale', FrontSecurityHeaders::class],
], function () {
    // Intropage (ไม่มีที่เผยแพร่อยู่ = redirect ไปหน้าแรกที่กำหนดในเมนู)
    Route::get('/', [IntropageController::class, 'index'])->name('front.home');

    // หน้าเพจ — id จำเป็น, slug ไม่บังคับ
    Route::get('/page/item/{id}/{slug?}', [FrontPageItemController::class, 'show'])
        ->whereNumber('id')
        ->name('front.page.item');

    // รายละเอียดบทความภายใต้หมวดหมู่ — ลงทะเบียนก่อน route รายการหมวดหมู่ (3 ส่วนหลัง category ต้องไม่ถูกตีความเป็น {id}/{slug})
    // slug ของหมวดหมู่ห้ามเป็นตัวเลขล้วน (validation หลังบ้าน) จึงไม่ชนกับ {article_id}
    Route::get('/article/category/{id}/{article_id}/{slug?}', [FrontArticleItemController::class, 'showInCategory'])
        ->whereNumber(['id', 'article_id'])
        ->name('front.article.category.item');

    // รายการบทความของหมวดหมู่
    Route::get('/article/category/{id}/{slug?}', [FrontArticleCategoryController::class, 'show'])
        ->whereNumber('id')
        ->name('front.article.category');

    // รายละเอียดบทความ (เข้าตรง)
    Route::get('/article/item/{id}/{slug?}', [FrontArticleItemController::class, 'show'])
        ->whereNumber('id')
        ->name('front.article.item');

    // รายการบทความตามแท็ก — {tag} = ชื่อแท็ก (ภาษาใดก็ได้) ไม่พบแท็ก/ไม่มีบทความ = แสดง "ไม่พบข้อมูล" (ไม่ใช่ 404)
    Route::get('/article/tag/{tag}', [FrontArticleTagController::class, 'show'])
        ->where('tag', '.+')
        ->name('front.article.tag');
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

        // หมวดหมู่บทความ — ตรวจสอบสิทธิ์ในแต่ละ method ของ ArticleCategoryController
        Route::prefix('article/category')->group(function () {
            Route::get('/', [ArticleCategoryController::class, 'index'])->name('admin.article.category.index');
            Route::get('/add', [ArticleCategoryController::class, 'add'])->name('admin.article.category.add');
            Route::post('/', [ArticleCategoryController::class, 'store'])->name('admin.article.category.store');
            Route::get('/{category}/edit', [ArticleCategoryController::class, 'edit'])->name('admin.article.category.edit');
            Route::put('/{category}', [ArticleCategoryController::class, 'update'])->name('admin.article.category.update');
            Route::delete('/{category}', [ArticleCategoryController::class, 'destroy'])->name('admin.article.category.destroy');
        });

        // บทความ — ตรวจสอบสิทธิ์ในแต่ละ method ของ ArticleItemController
        Route::prefix('article/item')->group(function () {
            Route::get('/', [ArticleItemController::class, 'index'])->name('admin.article.item.index');
            Route::get('/add', [ArticleItemController::class, 'add'])->name('admin.article.item.add');
            Route::post('/', [ArticleItemController::class, 'store'])->name('admin.article.item.store');
            Route::get('/{item}/edit', [ArticleItemController::class, 'edit'])->name('admin.article.item.edit');
            Route::put('/{item}', [ArticleItemController::class, 'update'])->name('admin.article.item.update');
            Route::delete('/{item}', [ArticleItemController::class, 'destroy'])->name('admin.article.item.destroy');
        });

        // แท็กบทความ — จัดการเต็มรูปแบบ + endpoint ajax ค้นหา/สร้างด่วนที่ใช้จาก TagPicker.vue
        // (แยกเส้นทาง 'quick' ออกจาก store ปกติ เพราะเป็นคนละการกระทำ: quickStore สร้างจากในฟอร์มบทความ,
        // store เป็นการบันทึกจากหน้าจัดการแท็กโดยตรง) ตรวจสิทธิ์ในแต่ละ method ของ ArticleTagController
        Route::prefix('article/tag')->group(function () {
            Route::get('/', [ArticleTagController::class, 'index'])->name('admin.article.tag.index');
            Route::get('/add', [ArticleTagController::class, 'add'])->name('admin.article.tag.add');
            Route::get('/search', [ArticleTagController::class, 'search'])->name('admin.article.tag.search');
            Route::post('/quick', [ArticleTagController::class, 'quickStore'])->name('admin.article.tag.quickStore');
            Route::post('/', [ArticleTagController::class, 'store'])->name('admin.article.tag.store');
            Route::get('/{tag}/edit', [ArticleTagController::class, 'edit'])->name('admin.article.tag.edit');
            Route::put('/{tag}', [ArticleTagController::class, 'update'])->name('admin.article.tag.update');
            Route::delete('/{tag}', [ArticleTagController::class, 'destroy'])->name('admin.article.tag.destroy');
        });

        // ตั้งค่าโมดูลบทความ — ตรวจสอบสิทธิ์ในแต่ละ method ของ ArticleSettingController (article.setting.manage เดียว)
        Route::prefix('article/setting')->group(function () {
            Route::get('/', [ArticleSettingController::class, 'index'])->name('admin.article.setting.index');
            Route::put('/', [ArticleSettingController::class, 'update'])->name('admin.article.setting.update');

            Route::get('/clearcache', [ArticleSettingController::class, 'clearcache'])->name('admin.article.setting.clearcache');
            Route::post('/clearcache/setting', [ArticleSettingController::class, 'clearCacheSetting'])->name('admin.article.setting.clearcache.setting');
            Route::post('/clearcache-all', [ArticleSettingController::class, 'clearCacheAll'])->name('admin.article.setting.clearcache.all');
        });

        // หมวดหมู่ป้ายโฆษณา — ตรวจสอบสิทธิ์ในแต่ละ method ของ BannerCategoryController
        Route::prefix('banner/category')->group(function () {
            Route::get('/', [BannerCategoryController::class, 'index'])->name('admin.banner.category.index');
            Route::get('/add', [BannerCategoryController::class, 'add'])->name('admin.banner.category.add');
            Route::post('/', [BannerCategoryController::class, 'store'])->name('admin.banner.category.store');
            Route::get('/{category}/edit', [BannerCategoryController::class, 'edit'])->name('admin.banner.category.edit');
            Route::put('/{category}', [BannerCategoryController::class, 'update'])->name('admin.banner.category.update');
            Route::delete('/{category}', [BannerCategoryController::class, 'destroy'])->name('admin.banner.category.destroy');
        });

        // ป้ายโฆษณา — ตรวจสอบสิทธิ์ในแต่ละ method ของ BannerItemController
        Route::prefix('banner/item')->group(function () {
            Route::get('/', [BannerItemController::class, 'index'])->name('admin.banner.item.index');
            Route::get('/add', [BannerItemController::class, 'add'])->name('admin.banner.item.add');
            Route::post('/', [BannerItemController::class, 'store'])->name('admin.banner.item.store');
            Route::get('/{item}/edit', [BannerItemController::class, 'edit'])->name('admin.banner.item.edit');
            Route::put('/{item}', [BannerItemController::class, 'update'])->name('admin.banner.item.update');
            Route::delete('/{item}', [BannerItemController::class, 'destroy'])->name('admin.banner.item.destroy');
        });

        // ตั้งค่าโมดูลป้ายโฆษณา — placeholder เฉพาะ index (ยังไม่มีฟิลด์ให้บันทึก/ล้างแคชจริง)
        Route::prefix('banner/setting')->group(function () {
            Route::get('/', [BannerSettingController::class, 'index'])->name('admin.banner.setting.index');
        });

        // Intropage (หน้าคั่นก่อนเข้าเว็บ) — ตรวจสอบสิทธิ์ในแต่ละ method ของ IntropageItemController
        // การจัดการปุ่มด้านล่าง (intropage_item_button) ส่งมาพร้อมกับ store/update ไม่มี route แยก
        // (เหมือน article_item_part ที่จัดการอยู่ในฟอร์มบทความ ไม่มี PartController)
        Route::prefix('intropage/item')->group(function () {
            Route::get('/', [IntropageItemController::class, 'index'])->name('admin.intropage.item.index');
            Route::get('/add', [IntropageItemController::class, 'add'])->name('admin.intropage.item.add');
            Route::post('/', [IntropageItemController::class, 'store'])->name('admin.intropage.item.store');
            Route::get('/{item}/edit', [IntropageItemController::class, 'edit'])->name('admin.intropage.item.edit');
            Route::put('/{item}', [IntropageItemController::class, 'update'])->name('admin.intropage.item.update');
            Route::delete('/{item}', [IntropageItemController::class, 'destroy'])->name('admin.intropage.item.destroy');
        });

        // หน้าเพจ (page_item_*) — ตรวจสอบสิทธิ์ในแต่ละ method ของ PageItemController
        // ข้อมูลทั่วไป (edit/update) กับโครงสร้าง แถว → คอลัมน์ → widget (layout/layout.update) เป็นคนละ tab บันทึกแยกกัน
        Route::prefix('page/item')->group(function () {
            Route::get('/', [PageItemController::class, 'index'])->name('admin.page.item.index');
            Route::get('/add', [PageItemController::class, 'add'])->name('admin.page.item.add');
            Route::get('/widget/preview', [PageItemController::class, 'widgetPreview'])->name('admin.page.item.widget.preview');
            Route::post('/', [PageItemController::class, 'store'])->name('admin.page.item.store');
            Route::get('/{item}/edit', [PageItemController::class, 'edit'])->name('admin.page.item.edit');
            Route::put('/{item}', [PageItemController::class, 'update'])->name('admin.page.item.update');
            Route::delete('/{item}', [PageItemController::class, 'destroy'])->name('admin.page.item.destroy');
            Route::get('/{item}/layout', [PageItemController::class, 'layout'])->name('admin.page.item.layout');
            Route::put('/{item}/layout', [PageItemController::class, 'layoutUpdate'])->name('admin.page.item.layout.update');
        });

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

        // จัดการเมนูหน้าบ้าน — ตรวจสอบสิทธิ์ในแต่ละ method ของ FrontMenuController
        Route::prefix('system/menu')->group(function () {
            Route::get('/', [FrontMenuController::class, 'index'])->name('admin.system.menu.index');
            Route::post('/', [FrontMenuController::class, 'store'])->name('admin.system.menu.store');
            Route::put('/reorder', [FrontMenuController::class, 'reorder'])->name('admin.system.menu.reorder');
            Route::get('/pick/articles', [FrontMenuController::class, 'pickArticles'])->name('admin.system.menu.pick.articles');
            Route::get('/pick/pages', [FrontMenuController::class, 'pickPages'])->name('admin.system.menu.pick.pages');
            Route::put('/{menu}', [FrontMenuController::class, 'update'])->name('admin.system.menu.update');
            Route::delete('/{menu}', [FrontMenuController::class, 'destroy'])->name('admin.system.menu.destroy');
            Route::put('/{menu}/status', [FrontMenuController::class, 'toggleStatus'])->name('admin.system.menu.status');
        });

        // จัดการ Template หน้าบ้าน — ตรวจสอบสิทธิ์ในแต่ละ method ของ TemplateController
        Route::prefix('system/template')->controller(TemplateController::class)->group(function () {
            Route::get('/', 'index')->name('admin.system.template.index');
            Route::get('/add', 'add')->name('admin.system.template.add');
            Route::post('/', 'store')->name('admin.system.template.store');
            Route::get('/{template}/edit', 'edit')->name('admin.system.template.edit');
            Route::put('/{template}', 'update')->name('admin.system.template.update');
            Route::delete('/{template}', 'destroy')->name('admin.system.template.destroy');
            Route::put('/{template}/activate', 'activate')->name('admin.system.template.activate');
            Route::get('/{template}/layout', 'layout')->name('admin.system.template.layout');
            Route::put('/{template}/layout', 'layoutUpdate')->name('admin.system.template.layout.update');
            Route::get('/{template}/code', 'code')->name('admin.system.template.code');
            Route::put('/{template}/code', 'codeUpdate')->name('admin.system.template.code.update');
            Route::get('/{template}/loading', 'loading')->name('admin.system.template.loading');
            Route::put('/{template}/loading', 'loadingUpdate')->name('admin.system.template.loading.update');
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

        // ประวัติหน้าบ้าน (log_front_*) — ตรวจสอบสิทธิ์ในแต่ละ controller
        Route::prefix('system/frontlog')->group(function () {
            // การเข้าชม (log_front_access)
            Route::get('/access', [FrontLogAccessController::class, 'index'])->name('admin.system.frontlog.access.index');
        });

        // ตั้งค่าระบบ (sys_setting) + ล้างแคช — ตรวจสอบสิทธิ์ในแต่ละ method ของ SettingController
        Route::prefix('system/setting')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('admin.system.setting.index');
            Route::put('/site', [SettingController::class, 'updateSite'])->name('admin.system.setting.update.site');
            Route::put('/contact', [SettingController::class, 'updateContact'])->name('admin.system.setting.update.contact');
            Route::put('/social', [SettingController::class, 'updateSocial'])->name('admin.system.setting.update.social');
            Route::put('/google-analytics', [SettingController::class, 'updateGoogleAnalytics'])->name('admin.system.setting.update.google_analytics');
            Route::put('/smtp', [SettingController::class, 'updateSmtp'])->name('admin.system.setting.update.smtp');
            // ทดสอบส่งอีเมลด้วยค่า SMTP ที่บันทึกไว้ — throttle กันสแปม/กดรัวเป็น cannon เมล
            Route::post('/smtp/test', [SettingController::class, 'testSmtp'])
                ->name('admin.system.setting.smtp.test')
                ->middleware('throttle:10,1');
            Route::put('/turnstile', [SettingController::class, 'updateTurnstile'])->name('admin.system.setting.update.turnstile');
            Route::put('/login-back', [SettingController::class, 'updateLoginBack'])->name('admin.system.setting.update.login_back');

            Route::get('/clearcache', [SettingController::class, 'clearcache'])->name('admin.system.setting.clearcache');
            Route::post('/clearcache/{group}', [SettingController::class, 'clearCacheGroup'])->name('admin.system.setting.clearcache.group');
            Route::post('/clearcache-all', [SettingController::class, 'clearCacheAll'])->name('admin.system.setting.clearcache.all');
            Route::post('/clearcache-files', [SettingController::class, 'clearCacheFiles'])->name('admin.system.setting.clearcache.files');
            Route::post('/clearcache-front', [SettingController::class, 'clearCacheFront'])->name('admin.system.setting.clearcache.front');
        });

        // จัดการไฟล์ (file_info/folder_info) — ทุกคนที่ login เข้าได้เหมือน Dashboard/Profile
        // ไม่เช็ก permission เพราะเป็นพื้นที่ไฟล์ส่วนตัว เห็นเฉพาะของตัวเองเท่านั้น (scope user_id ใน controller)
        Route::prefix('system/file')->group(function () {
            Route::get('/', [FileController::class, 'index'])->name('admin.system.file.index');
            Route::get('/folders', [FileController::class, 'folders'])->name('admin.system.file.folders');
            Route::post('/folders', [FileController::class, 'storeFolder'])->name('admin.system.file.folders.store');
            Route::get('/list', [FileController::class, 'list'])->name('admin.system.file.list');
            Route::post('/upload', [FileController::class, 'upload'])->name('admin.system.file.upload');
            Route::delete('/{file}', [FileController::class, 'destroy'])->name('admin.system.file.destroy');
        });

        // เสิร์ฟไฟล์ (แสดง/ดาวน์โหลด/thumbnail) ด้วย hash_name — ต้อง login เท่านั้น ไม่จำกัดเจ้าของไฟล์
        // (hash_name เป็น ULID เดาไม่ได้) — path ตรงตามที่กำหนด: /admin/file/...
        Route::prefix('file')->group(function () {
            Route::get('/get/{hashname}', [FileServeController::class, 'show'])->name('admin.system.file.get');
            Route::get('/type/download/get/{hashname}', [FileServeController::class, 'download'])->name('admin.system.file.get.download');
            Route::get('/type/thumbnail/get/{hashname}', [FileServeController::class, 'thumbnail'])->name('admin.system.file.get.thumbnail');
            Route::get('/type/thumbnail/size/{size}/get/{hashname}', [FileServeController::class, 'thumbnail'])
                ->name('admin.system.file.get.thumbnail.size')
                ->where('size', '[0-9]+');
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
