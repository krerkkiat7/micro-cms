<?php

namespace App\Http\Middleware;

use App\Models\SysMenuGroup;
use App\Models\User;
use App\Support\AppAsset;
use App\Support\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'titlename' => $request->user()->titlename,
                    'firstname' => $request->user()->firstname,
                    'lastname' => $request->user()->lastname,
                    'name' => $request->user()->name, // accessor: คำนำหน้า + ชื่อ + นามสกุล
                    'email' => $request->user()->email,
                    'mobile' => $request->user()->mobile,
                    'phone' => $request->user()->phone,
                    'line' => $request->user()->line,
                    'facebook' => $request->user()->facebook,
                    // รูปโปรไฟล์ที่เลือกจากโมดูลจัดการไฟล์ — ส่งแค่ hash_name ไปสร้าง URL thumbnail ฝั่ง frontend เอง
                    'profile_image_hash_name' => $request->user()->profileImage?->hash_name,
                    // โยน Array ของ Action Codes เช่น ['article.view', 'article.create', 'article.delete'] ไปยัง Vue
                    'permissions' => $request->user()->getPermissionsArray(),
                ] : null,
            ],
            // ชื่อไซต์จากการตั้งค่าระบบ — ใช้แสดงผลทั่วไป (title, โลโก้ใน sidebar/หน้า auth)
            'siteName' => fn () => Setting::siteName(),
            // URL โลโก้ที่ตั้งค่าไว้ (sys_setting: site.logo_id) — null = ยังไม่ได้ตั้งค่า
            // ให้ frontend (Components/AppLogo.vue) แสดง ApplicationLogo.vue (ไอคอน default เดิม) แทน
            'appLogoUrl' => fn () => AppAsset::logo() ? route('app.logo') : null,
            // เมนู sidebar หลังบ้าน สร้างจาก sys_menu_group + sys_menu กรองตามสิทธิ์ของผู้ใช้
            // (closure = ประเมินเฉพาะตอนที่ Inertia ต้องส่ง prop นี้จริง)
            'menu' => fn () => $this->adminMenu($request->user()),
            // ข้อความแจ้งผลสำเร็จ (flash) — successId ใหม่ทุกครั้งเพื่อให้ frontend ตรวจจับได้แม้ข้อความซ้ำ
            'flash' => function () use ($request) {
                $success = $request->session()->get('success');

                return [
                    'success' => $success,
                    'successId' => $success ? uniqid('flash_', true) : null,
                ];
            },
            // โทเคน log_back_access ของการเข้าหน้านี้ (LogBackAccess::record() เซ็ตไว้ใน attribute)
            // null = หน้านี้ไม่ได้บันทึก log — frontend ใช้ตัดสินใจว่าจะยิง keep-alive ping ไหม
            'accessLog' => fn () => [
                'token' => $request->attributes->get('access_log_token'),
            ],
        ];
    }

    /**
     * โครงเมนูหลังบ้าน: กลุ่มเมนู (sys_menu_group) → เมนูย่อย (sys_menu)
     * - เฉพาะผู้ใช้ user_type = back
     * - แสดงเฉพาะ status = 'Y' เรียงตาม sort_order
     * - เมนูย่อยที่มี action_code ต้องมีสิทธิ์นั้น ๆ ถึงจะแสดง (null = แสดงเสมอ)
     * - กลุ่มที่ไม่มีเมนูย่อยเหลือเลย จะถูกซ่อน
     *
     * @return list<array{id: string, name: string, icon: string|null, items: list<array{id: string, name: string, icon: string|null, routeName: string|null, href: string|null, activePattern: string|null}>}>
     */
    protected function adminMenu(?User $user): array
    {
        if (! $user || $user->user_type !== 'back') {
            return [];
        }

        $permissions = $user->getPermissionsArray();

        return SysMenuGroup::query()
            ->where('status', 'Y')
            ->with(['menus' => fn ($query) => $query->where('status', 'Y')->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(function (SysMenuGroup $group) use ($permissions) {
                $items = $group->menus
                    ->filter(fn ($menu) => $menu->action_code === null
                        || in_array($menu->action_code, $permissions, true))
                    ->map(fn ($menu) => [
                        'id' => $menu->id,
                        'name' => $menu->name,
                        'icon' => $menu->icon,
                        'routeName' => $menu->route_name,
                        'href' => $menu->route_name && Route::has($menu->route_name)
                            ? route($menu->route_name)
                            : null,
                        'activePattern' => $this->menuActivePattern($menu->route_name),
                    ])
                    ->values()
                    ->all();

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'icon' => $group->icon,
                    'items' => $items,
                ];
            })
            ->filter(fn ($group) => count($group['items']) > 0)
            ->values()
            ->all();
    }

    /**
     * pattern สำหรับเช็ค active ของเมนูย่อยใน sidebar (ใช้กับ Ziggy `route().current()`)
     *
     * - route ที่ลงท้าย `.index` = หน้ารายการของโมดูล CRUD → คืน `<prefix>.*`
     *   เพื่อให้ไฮไลต์ครอบทุกหน้าในโมดูล (add / edit / …)
     * - route หน้าเดี่ยว (เช่นหน้าประวัติ/รายงาน) → คืนชื่อ route ตรง ๆ
     */
    protected function menuActivePattern(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        if (str_ends_with($routeName, '.index')) {
            return substr($routeName, 0, -strlen('.index')).'.*';
        }

        return $routeName;
    }
}
