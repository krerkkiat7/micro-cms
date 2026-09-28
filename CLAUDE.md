# CLAUDE.md

คู่มือสำหรับ Claude Code (และผู้พัฒนา) เมื่อทำงานกับ repository นี้

## ภาพรวมโปรเจกต์

Micro-CMS ที่เน้น **ติดตั้งง่าย ใช้งานง่าย** พัฒนาบน Laravel 12 + Inertia.js + Vue 3
โปรเจกต์อยู่ในช่วงเริ่มต้น โครงสร้างพื้นฐาน (auth หลังบ้าน, multi-language front, ระบบสิทธิ์)
วางไว้แล้ว แต่ยังไม่มีโมเดลเนื้อหา CMS จริง (`app/Http/Controllers/Admin/PostController.php` ยังว่าง)

ภาพรวมโมดูล/ส่วนจัดการระบบ + roadmap อยู่ที่ `docs/PRD-overview.md`
รายละเอียดส่วนจัดการระบบ (users, สิทธิ์, เมนู, template, ประวัติ, settings, files) อยู่ที่ `docs/PRD-system.md`
(โมดูลย่อยที่มีเอกสารแยก: `docs/PRD-system-frontmenu.md`, `docs/PRD-system-template.md`)
หน้าบ้าน (front-office) อยู่ที่ `docs/PRD-front.md`

## Tech Stack

| ส่วน | เทคโนโลยี |
|------|-----------|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | Inertia.js 2 + Vue 3 (`<script setup>` + TypeScript) + Tailwind CSS **v4** (CSS-first config ใน `resources/css/app.css`) |
| Build | Vite 7, `laravel-vite-plugin`, `@tailwindcss/vite`, `vue-tsc` — ไม่มี `postcss.config.js`/`tailwind.config.js` |
| Icons | `lucide-vue-next` (ใช้ในหลังบ้าน) |
| Image processing | `intervention/image` (GD driver) — ใช้ generate thumbnail ในโมดูลจัดการไฟล์ (ต้องเปิด ext-gd) |
| Database | MySQL 8+ (dev ผ่าน `docker-compose.yml`) |
| Session / Cache | Redis (client = `predis`, ไม่ใช่ ext-phpredis) |
| Queue | database |
| Routing helper | Ziggy (`@routes` ใน `app.blade.php`, `ZiggyVue` ใน `app.ts`) |
| Auth scaffolding | Laravel Breeze (Inertia-Vue) — ถูกปรับให้อยู่ใต้ `/admin` |
| Node | 20+ |
| Test | Pest 3 (+ pest-plugin-laravel), PHPUnit config รัน SQLite `:memory:` |

## คำสั่งที่ใช้บ่อย

```bash
# ครั้งแรก
docker-compose up -d          # เปิด MySQL (my_cms) + Redis
composer install
cp .env.example .env          # แล้วปรับ DB_*, REDIS_*, SESSION_DRIVER, CACHE_STORE ให้ตรง (ดูหมายเหตุด้านล่าง)
php artisan key:generate
php artisan migrate --seed    # seed สร้าง user: admin@admin.com / password123
npm install

# พัฒนา
npm run dev                   # Vite dev server
php artisan serve             # หรือ `composer dev` (รัน serve + queue + pail + vite พร้อมกัน)
php artisan schedule:work     # (dev) scheduler — front:flush-views ทุกนาที (production ใช้ cron `schedule:run`)

# ทดสอบ
php artisan test              # หรือ `composer test`
./vendor/bin/pest

# คุณภาพโค้ด
./vendor/bin/pint             # format PHP (Laravel Pint)
npm run build                 # vue-tsc typecheck + vite build
```

## โครงสร้าง Routing (`routes/web.php`)

- `/` (`front.root`) และ `/{lang}` (`front.home`) → แสดง Intropage ที่เผยแพร่อยู่ (ไม่มี → redirect ไปหน้าแรกตามเมนู `is_home`)
- **Front-office**: prefix `{lang}` (ภาษาที่เปิดใช้ใน `sys_setting`), middleware `['web', 'setLocale', FrontSecurityHeaders]` — ดู `docs/PRD-front.md`
  - `SetLocale` middleware อ่าน `lang` จาก route แล้ว `App::setLocale()` (default `th`)
  - ชื่อ route ขึ้นต้น `front.*`: `front.page.item`, `front.article.category`, `front.article.category.item`, `front.article.item`
    (`/{lang}/page/item/{id}/{slug?}` ฯลฯ — slug ไม่บังคับ); ไฟล์สาธารณะ `front.file.*` (`/file/get/{hash}`), `front.access.ping`
  - controller หน้าบ้าน extends `Front\FrontController` (ส่ง prop `front` = layout จาก template + เมนู + ตั้งค่าไซต์, และ `seo`)
- **Back-office**: prefix `/admin`
  - `routes/auth_admin.php` = auth routes ของ Breeze ที่ย้ายมาไว้ใต้ `/admin` (ชื่อ route ขึ้นต้น `admin.*` เช่น `admin.login`, `admin.password.request`)
  - routes ที่ต้อง login อยู่ใน group `middleware(['auth', 'verified'])` ชื่อขึ้นต้น `admin.*` (เช่น `admin.dashboard`, `admin.profile.edit`)
  - `/admin` เฉย ๆ (`admin.home`) → redirect ไป `admin.dashboard` (login แล้ว) หรือ `admin.login` (ยังไม่ login)
  - `bootstrap/app.php` ตั้ง `redirectGuestsTo(route('admin.login'))` + `redirectUsersTo(route('admin.dashboard'))` —
    Breeze อยู่ใต้ `/admin` จึงต้อง override ไม่งั้น middleware `auth` เด้งไป route `login` ที่ไม่มี (error "Route [login] not defined")

## การแยกโฟลเดอร์ (สำคัญ — ต้องรักษาให้สม่ำเสมอ)

Controllers:
- `app/Http/Controllers/Admin/` — ผู้ดูแลระบบ
- `app/Http/Controllers/Front/` — ผู้ใช้งานทั่วไป

Vue pages / layouts:
- `resources/js/Pages/Admin/` + `resources/js/Layouts/Admin/` — หลังบ้าน
- `resources/js/Pages/Front/` + `resources/js/Layouts/Front/` — หน้าบ้าน
- `resources/js/Components/` — UI primitive ที่ใช้ร่วมกัน (ปุ่ม / input / modal / dropdown)
- `resources/js/Components/Admin/` — component เฉพาะหลังบ้าน (`AppSidebar`, `AppHeader`, `SidebarItem`, `UserMenu`)
- `resources/js/composables/` — เช่น `useSidebar.ts`

Controller ใน `Admin/` render ด้วยชื่อ page แบบ `Admin/...`, ใน `Front/` แบบ `Front/...`
(เช่น `Inertia::render('Admin/Dashboard', ...)`, `Inertia::render('Front/Home', ...)`)

### Layout หลังบ้าน
- `Layouts/Admin/AdminLayout.vue` — โครงหลัก: **sidebar โทนมืดถาวร** (ซ้าย, collapse ได้บน desktop /
  drawer บนมือถือ ผ่าน `composables/useSidebar.ts` — จำสถานะใน `localStorage`) + **header โทนมืด** +
  พื้นที่เนื้อหาสว่าง. ทุกหน้า `Admin/*` (ยกเว้น auth) wrap `<AdminLayout>` และมี slot `#header`
- `Layouts/Admin/AuthLayout.vue` — หน้า auth ก่อน login (Login/Register/Forgot/Reset/Confirm/VerifyEmail):
  split-screen ฟอร์มซ้าย + branding panel มืดขวา (จอ `lg`)
- สไตล์อ้างอิง TailAdmin Vue (MIT) — port เฉพาะโครง ไม่ได้ใช้ตัวเทมเพลตตรง ๆ (มัน vue-router SPA + Pinia);
  **ไม่มี dark-mode toggle** (chrome มืดตายตัว เนื้อหาสว่างเสมอ)
- `Components/Admin/AppSidebar.vue` — Dashboard + Profile ฮาร์ดโค้ด; กลุ่มเมนูจาก DB อยู่ section เดียวกับ Dashboard
  อ่านจาก shared prop `menu` (`HandleInertiaRequests::adminMenu()` — `sys_menu_group`/`sys_menu`, กรอง `status='Y'` +
  สิทธิ์ `action_code`, ตัดกลุ่มที่ว่าง, ส่ง `icon`+`href`+`activePattern`). `SidebarGroup.vue` = หัวข้อกลุ่มกดเปิด/ปิด
  (localStorage `admin.sidebar.group.<id>`, โหมด rail แสดงไอคอน) + ไฮไลต์หัวข้อกลุ่มเมื่อมีเมนูย่อย active.
  `activePattern` (คำนวณใน `adminMenu()` จาก `route_name`): route ลงท้าย `.index` → `<prefix>.*`
  (ไฮไลต์ครอบทุกหน้าในโมดูล เช่น add/edit), route หน้าเดี่ยว → ชื่อ route ตรง ๆ — โมดูลใหม่ตั้งหน้ารายการเป็น
  `admin.<module>.<sub>.index` แล้วได้ active state อัตโนมัติ. ไอคอน map ใน `Components/Admin/menuIcons.ts`
  (ชื่อ lucide PascalCase, curated) — เพิ่มไอคอน = import + ใส่ในแมพ. ดู `docs/PRD-system.md` §3.1

## ระบบสิทธิ์ (Permissions)

ตาราง `sys_*` หลักอยู่ในไฟล์ migration `database/migrations/0001_01_01_000000_create_users_table.php`
(เมนูหลังบ้าน `sys_menu_group`/`sys_menu` แยกไฟล์: `2026_09_08_000001_create_sys_menu_tables.php`;
ตาราง log ทั้งหมดรวมในไฟล์กลาง `2026_09_10_000001_create_log_tables.php` — ดู `docs/PRD-system.md` §5)

| ตาราง | Model | หมายเหตุ |
|-------|-------|----------|
| `sys_user` | `App\Models\User` | ชื่อแยกเป็น `titlename`/`firstname`/`lastname` (+ accessor `name` = ชื่อเต็ม); ช่องทางติดต่อ `mobile`/`phone`/`line`/`facebook`; `profile_image_id` (FK จริง → `file_info.id`, nullOnDelete — รูปโปรไฟล์ที่เลือกจากโมดูลจัดการไฟล์, `profileImage()` belongsTo); `user_type` (`back`/`front`, default `back`), `status` `char(1)` default `Y`; สถิติ login `last_login_at`/`failed_login_count`/`last_failed_login_at`; `password_changed_at`/`password_changed_by` (ตั้ง/เปลี่ยนรหัสผ่านครั้งล่าสุด); audit `created_by`/`updated_by`/`deleted_by` (`sys_user.id`, ไม่มี FK); `usergroup_id`; **`SoftDeletes` (เปิดใช้ trait แล้ว)**. `email` **ไม่ unique ระดับ DB** |
| `sys_usergroup` | `App\Models\UserGroup` | `status` `char(1)` default `Y`; `can_edit`/`can_delete` `char(1)` default `Y` (`N` = กลุ่มระบบ ห้ามแก้/ห้ามลบ — Super Admin seed เป็น `N`); audit `created_by`/`updated_by`/`deleted_by` (`sys_user.id`, ไม่มี FK); **`SoftDeletes` + `HasFactory` (เปิด trait แล้ว)**; relations `actions()` belongsToMany (`withPivot(created_by,updated_by)` + `withTimestamps()`), `users()` hasMany |
| `sys_action_group` | `App\Models\SysActionGroup` | `id` เป็น `string(20)` primary (กำหนดเอง); มี `sort_order`, `status` `char(1)` default `Y`, `actions()` hasMany |
| `sys_action` | `App\Models\SysAction` | `id` เป็น `string(20)` primary; มี `code` (unique) เช่น `system.user.view`, `parent_id` (tree, self-FK), `sort_order` |
| `sys_usergroup_action` | (pivot) | เชื่อม usergroup ↔ action (`action_id` เป็น `string(20)`); `created_by`/`updated_by` (`sys_user.id`) + `timestamps` — หน้ากำหนดสิทธิ์ `attach()` พร้อม pivot เหล่านี้ |
| `sys_setting` | `App\Models\SysSetting` | ตั้งค่าระบบ key-value; composite PK `(group, name)` — ค้นด้วย `where()` ไม่ใช้ `find()`; `created_by`/`updated_by` (`sys_user.id`) + timestamps + softDeletes. `group` เป็นคำสงวน MySQL (Laravel quote ให้) |
| `sys_menu_group` | `App\Models\SysMenuGroup` | กลุ่มเมนู sidebar หลังบ้าน; `id` `string(20)` primary; `icon`, `sort_order`, `status`, softDeletes; `menus()` hasMany |
| `sys_menu` | `App\Models\SysMenu` | เมนูย่อยหลังบ้าน; `id` `string(30)` primary; `menu_group_id` FK; `icon` (ชื่อ lucide PascalCase), `route_name` (ลิงก์โมดูล), `action_code` (`sys_action.code` — เช็กในโค้ด, ไม่มี FK), `sort_order`, `status`, softDeletes. seed ใน `MenuSeeder` |
| `log_back_access` | `App\Models\LogBackAccess` | log การเข้าชมหน้าหลังบ้าน (1 request = 1 แถว); `token` (ULID unique) สำหรับ keep-alive ping, `last_visited` (bump ตอน ping, ครั้งแรก = `created_at`), `action_date` (index, กรองรายวัน), `session_id`/`remote_ip`/`agent`/`browser*`/`platform`/`device_type`/`referrer`/`accept_*`, `status` `char(1)` `Y`, audit `created_by`/`updated_by`/`deleted_by`, `timestamps` + softDeletes. model เติม `token`/`action_date`/`last_visited` อัตโนมัติใน `creating`; static `LogBackAccess::record($title)` = บันทึก 1 แถวจาก request ปัจจุบัน (ดู §ประวัติ). permission `system.backlog.access` (seed แล้ว) |
| `log_back_login` | `App\Models\LogBackLogin` | log การเข้า/ออกระบบหลังบ้าน (1 เหตุการณ์ = 1 แถว); `log_type` (`login`/`logout`), `result` (`success`/`fail`/`block`), `username` (อีเมลที่กรอก), `note` (เหตุผล), `remote_ip`, `action_date` (`date`, model เติม), `status` `char(1)` `Y`, audit + `timestamps` + softDeletes. **ไม่มีคอลัมน์ password** (ไม่เก็บรหัสที่กรอก). `user_id` เก็บเมื่อสำเร็จ/logout เท่านั้น. static: `loginSuccess(User)` / `loginFailed(username,note)` / `loginBlocked(username,note)` / `logout(userId,username)`. permission `system.backlog.login` (seed แล้ว) |
| `log_back_action` | `App\Models\LogBackAction` | log การกระทำในหลังบ้าน (create/view/update/delete บนข้อมูล); `module_code` (เช่น `system.user`, `system.usergroup.rights`), `action_type`, `value_string` (ชื่อข้อมูล), `ref_id` (id ข้อมูล), `action_date` (`date`, model เติม), `remote_ip`, `geo_ip`, `status` + audit + `timestamps` + softDeletes. static `LogBackAction::record($moduleCode, $actionType, $valueString, $refId)` — เซ็ต `user_id`/`created_by` = `Auth::id()`, `remote_ip` = `ClientIp::from()`. permission `system.backlog.action` (seed แล้ว). **โมดูล CRUD ใหม่ทุกตัวต้องเรียก `record()` ตาม pattern `system.user`** |

การเช็กสิทธิ์:
- `$user->hasPermission('system.user.view')` — คืน `bool` (คืน `false` ถ้า user ไม่มี usergroup)
- `$user->getPermissionsArray()` — คืน array ของ action codes (คืน `[]` ถ้าไม่มี usergroup)
- share ไป frontend ผ่าน `HandleInertiaRequests::share()` → `auth.user.permissions`
- ฝั่ง Controller: ส่ง `can` เป็น props (ดู `Admin/DashboardController`) แล้วเช็ก `v-if="can.xxx"` ใน Vue

> ยังไม่มี middleware/gate บังคับสิทธิ์แบบรวมศูนย์ — การเช็กทำใน controller/หน้า เป็นราย ๆ (โค้ด `abort(403)` ถูก comment ไว้)

## Convention

- PHP: PSR-12 / Laravel Pint, indent 4 spaces (ดู `.editorconfig`)
- Vue หลังบ้าน: `<script setup lang="ts">` + `defineProps<{...}>()`
- Vue หน้าบ้าน: ปัจจุบันบางไฟล์ยังเป็น `<script setup>` ธรรมดา (`Front/Home.vue`) — เขียนใหม่ให้เป็น TypeScript
- Path alias: `@/` → `resources/js/` (ตั้งใน `tsconfig.json` + Vite)
- สี: ใช้ token `brand-*` (น้ำเงิน) เป็นสีหลัก และ `admin-900/800` เป็นโทนมืดของ sidebar/header —
  กำหนดใน `@theme` ของ `resources/css/app.css` (ปุ่มหลัก = `Components/PrimaryButton.vue`)
- ลิงก์/ชื่อ route ทั้งหมดในหลังบ้านใช้ `route('admin.xxx')` (Ziggy) ให้ครบ prefix `admin.` เสมอ —
  หน้าบ้านบางที่ยัง hardcode `/th`, `/en`
- อีเมล reset password: URL ผูกกับ `route('admin.password.reset')` ผ่าน
  `ResetPassword::createUrlUsing()` ใน `AppServiceProvider::boot()` (Laravel default ใช้ `password.reset` ที่ไม่มี)
- comment ในโค้ดเป็นภาษาไทยได้ (โปรเจกต์ใช้อยู่แล้ว)
- **Dropdown ทุกจุดในระบบใช้ `Components/SearchableSelect.vue`** (พิมพ์ค้นหาตัวเลือกได้) ไม่ใช่ native `<select>`/
  `SelectInput.vue` เดิม (ลบไฟล์นี้ออกไปแล้ว) — v-model เป็น string, ส่งตัวเลือกผ่าน prop `options`
  (`{value,label,disabled?}[]`) แทนการเขียน `<option>` ลูก รายละเอียด/ตัวอย่างการแปลงดู `docs/PRD-overview.md` §5.7

## หมายเหตุ / ความไม่สอดคล้องที่ควรรู้

- `.env.example` สะท้อน stack เป้าหมายแล้ว: MySQL 8 (`my_cms`) + Redis สำหรับ session/cache +
  `REDIS_CLIENT=predis` + `QUEUE_CONNECTION=database` (ค่า DB/Redis ตรงกับ `docker-compose.yml`)
- `.env` จริงในเครื่อง dev ปัจจุบันยัง `CACHE_STORE=database` — ปรับเป็น `redis` ให้ตรง `.env.example` ได้
- `config/app.php` locale default = `en` (จาก `APP_LOCALE` ใน `.env` เครื่อง dev) แต่ `.env.example`
  และ `SetLocale` middleware ใช้ `th` เป็นค่าเริ่มต้น
- database มีไฟล์ `database/database.sqlite` ค้างอยู่ (gitignore แล้ว; ไม่ได้ใช้เมื่อรันบน MySQL)
- `Admin/PostController.php` ยังเป็นไฟล์ว่าง (placeholder สำหรับโมดูลเนื้อหาที่จะทำ)
- `sys_setting` มี seed ตัวอย่างกลุ่ม `site` (`site_name`/`site_email`/`site_description`) — ยังไม่มีหน้า UI จัดการ
- การล็อกบัญชีอัตโนมัติเมื่อ `failed_login_count` เกินเกณฑ์ยังไม่ทำ — รอดึงเกณฑ์จาก `sys_setting` (ดู `docs/PRD-system.md` §5)
- Git remote: `https://github.com/krerkkiat7/micro-cms` (private) — branch `main`

### การเข้าสู่ระบบหลังบ้าน (auth)

- `sys_user` เดียวเก็บผู้ใช้ทั้ง `back` (หลังบ้าน) และ `front` (หน้าบ้าน) แยกด้วย `user_type`
- **login หน้าบ้าน/หลังบ้านแยกกันเด็ดขาด** — หลังบ้าน = guard `web`, หน้าบ้าน = guard `front` (`config/auth.php`, session คนละคีย์)
  โค้ดหน้าบ้านอ่านผู้ใช้ผ่าน `App\Support\Front\FrontAuth::id()` เท่านั้น (คืนเฉพาะ `user_type = front` ใน guard `front`) **ห้ามใช้ `Auth::id()`/
  `$request->user()` ฝั่งหน้าบ้าน** (= ผู้ใช้หลังบ้าน); ผู้ใช้ `back` ใช้งานหน้าบ้านแบบ login ไม่ได้ — front-office auth ในอนาคตต้อง login ผ่าน guard `front`
- `LoginRequest::authenticate()` ตรวจ `user_type = 'back'` + `status = 'Y'` เสมอ; รหัสผ่านถูกแต่ `status = 'N'`
  → ข้อความ "บัญชีนี้ถูกระงับการใช้งาน…"; login สำเร็จ/ไม่สำเร็จ อัปเดต `last_login_at` /
  `failed_login_count` / `last_failed_login_at` (สำเร็จ = เคลียร์ตัวนับ)
- password reset: `password_reset_tokens` (broker `users`, PK `email`) = ผู้ใช้ `back` เท่านั้น —
  ผู้ใช้ `front` มี broker `front` + ตาราง `front_password_reset_tokens` แยก (เตรียมไว้ ยังไม่มี route);
  controller หลังบ้านใช้ `Password::broker('users')->sendResetLink($request->only('email') + ['user_type' => 'back'])`
- `email` ไม่ unique ระดับ DB — เช็ก "ห้ามซ้ำกับ `user_type` เดียวกันที่ยังไม่ถูกลบ" ในโค้ดผ่าน
  `Rule::unique(User::class)->where('user_type', …)->whereNull('deleted_at')` (`RegisteredUserController`,
  `ProfileUpdateRequest`) — ถ้าเพิ่มจุดสมัคร/แก้ email ใหม่ ต้องใส่ scope นี้ด้วย
- `User` ใช้ `SoftDeletes` — `$user->delete()` เป็น soft delete; เทสที่เกี่ยวข้องใช้ `assertSoftDeleted`

### ประเด็นที่แก้ไปแล้ว (ประวัติ อย่าทำซ้ำ)
- ชื่อ route ทุกจุดในโค้ด/เทสปรับเป็น `admin.*` ครบแล้ว (เดิม Breeze อ้าง `login`/`dashboard`/`password.*` ที่ไม่มี)
- `migration down()` แก้ typo `sys_usergrouop` → `sys_usergroup` + เรียง drop ให้ปลอดภัยกับ FK แล้ว
- ปรับ schema (เฟส 0): `sys_user.name` → `titlename`/`firstname`/`lastname` + `mobile`/`phone`/`line`/`facebook`/`status`
  + สถิติ login (`last_login_at`/`failed_login_count`/`last_failed_login_at`); `email` เลิก unique;
  `sys_usergroup.status`, `sys_action_group.status`; `sys_action_group`/`sys_action` id เป็น `string(20)` + `sys_action.parent_id`/`sort_order`;
  เพิ่มตาราง `sys_setting`; เปิด `SoftDeletes` บน `User` — ปรับ seeder/factory/requests/controllers auth/
  `HandleInertiaRequests`/หน้า Vue profile+register/เทส ให้ตรงแล้ว (ดู `docs/PRD-system.md` ภาคผนวก)
- แยก password reset broker ตาม `user_type`: broker `users` → `password_reset_tokens` (back),
  broker `front` → `front_password_reset_tokens` (เตรียมไว้สำหรับ front-office auth)
- เพิ่มตารางเมนูหลังบ้าน `sys_menu_group`/`sys_menu` (migration `2026_09_08_000001_*`) + `MenuSeeder`
  (7 กลุ่ม + 23 เมนู + `icon`, เรียกจาก `DatabaseSeeder`) — `AppSidebar.vue` render จาก shared prop `menu`
  ระดับเดียวกับ Dashboard (`SidebarGroup.vue` = กลุ่มกดเปิด/ปิด + ไอคอน, กรองตามสิทธิ์)
- `DatabaseSeeder`/`MenuSeeder` ปรับเป็นข้อมูลจริง: 7 action group + 48 action (tree),
  กลุ่ม id ใช้ชุดเดียวกับ `sys_menu_group` (`article`/`banner`/`popup`/`intropage`/`page`/`contactus`/`system`),
  `sys_menu.action_code` ทุกตัวตรงกับ `sys_action.code`; seeder ทุกจุดเป็น `updateOrCreate`/`sync` รันซ้ำได้
- ลบไฟล์ Breeze ที่ตายแล้ว: `Pages/Welcome.vue`, `Pages/Dashboard.vue`, `Pages/Front/About.vue`
- อัปเกรด Tailwind v3 → v4; เปลี่ยน layout หลังบ้านเป็นสไตล์ TailAdmin (sidebar/header มืด);
  ลบ `AuthenticatedLayout.vue`, `GuestLayout.vue`, `Components/NavLink.vue`, `Components/ResponsiveNavLink.vue`
- โมดูล `system.user` (จัดการผู้ใช้งานหลังบ้าน) เสร็จ + เป็น **ต้นแบบตาม `docs/PRD-overview.md` §5** —
  `adminMenu()` ส่ง `activePattern` ต่อเมนู, shared prop `flash.success`+`SuccessDialog`, `ConfirmDialog`,
  component กลาง `SelectInput`/`Pagination`/`StatusBadge`/`Textarea`/`Admin/{PageHeader,Breadcrumbs,TabNav}`
- โมดูล `system.usergroup` (จัดการกลุ่มผู้ใช้งาน) เสร็จครบ: list/add/edit + **หน้ากำหนดสิทธิ์**
  (`admin.system.usergroup.rights` + `.rights.update`) — tree `sys_action_group`/`sys_action`,
  checkbox parent→ลูก, เลือก/ไม่เลือกทั้งหมดต่อกลุ่ม, บันทึกแบบ `detach()`+`attach()` (`Components/Admin/PermissionTreeNode.vue`
  recursive); `sys_usergroup` เพิ่ม `can_edit`/`can_delete` + เปิด `SoftDeletes`;
  guard: ชื่อกลุ่มห้ามซ้ำ, ห้ามลบกลุ่มที่มีสมาชิก, กลุ่ม `can_edit`/`can_delete='N'` แก้/ลบไม่ได้
- เพิ่มคอลัมน์ audit ผู้กระทำ (`sys_user.id`, ไม่มี FK) — `sys_user`/`sys_usergroup`: `created_by`/`updated_by`/`deleted_by`;
  `sys_usergroup_action`/`sys_setting`: `created_by`/`updated_by`; `sys_user` เพิ่ม `password_changed_at`/`password_changed_by`.
  `UserController`/`UsergroupController` เซ็ตค่าจาก `$request->user()->id` ตอน store (`created_by` + ตั้งรหัสผ่าน = `password_changed_*`),
  update (`updated_by`), destroy (`deleted_by` — save ก่อน soft delete เพราะ `runSoftDelete` ไม่ save attribute อื่น),
  เปลี่ยนรหัสผ่าน (`password_changed_*` + `updated_by`), หน้ากำหนดสิทธิ์ (`attach()` พร้อม pivot `created_by`/`updated_by`,
  `withTimestamps()` เติม `created_at`/`updated_at`). audit column ยังไม่มี UI แสดงผล / seeder ปล่อยเป็น null
- เริ่มระบบ log หลังบ้าน — migration กลาง `2026_09_10_000001_create_log_tables.php` (ไฟล์เดียวสำหรับ log ทุกตัว
  ทั้ง `log_back_*`/`log_front_*`) + ตาราง `log_back_access` + model `App\Models\LogBackAccess`
  (ตาม convention `sys_*`: `status` char(1)/`softDeletes`/audit; `token` ULID + `last_visited` สำหรับ keep-alive).
  permission/เมนู seed ไว้ก่อนแล้ว (`system.backlog.access` ฯลฯ)
- บันทึก log การเข้าหน้าจอหลังบ้าน — `LogBackAccess::record('ชื่อหน้า')` (static, เก็บ request ปัจจุบัน + parse
  User-Agent ด้วย `App\Support\UserAgentParser` heuristic เบา ๆ ไม่พึ่ง package, geo_ip ปล่อย null).
  `remote_ip` อ่านจาก header proxy ก่อน (`CF-Connecting-IP` / `X-Real-IP` / `X-Forwarded-For` ตัวแรกที่ valid)
  ค่อย fallback `$request->ip()` — `App\Support\ClientIp::from()` (ใช้ร่วมกับ `log_back_login`).
  เรียกในทุก controller ที่ render หน้าจอ **หลัง permission guard** (dashboard, profile.edit, user/usergroup
  index+add+edit+password/rights, backlog.access.index) — หน้ารายการเรียกเฉพาะเมื่อ `count($request->query()) === 0`
  (ไม่ log ตอนค้นหา/แบ่งหน้า). `record()` เก็บ `token` ลง `$request->attributes` → `HandleInertiaRequests` แชร์เป็น prop `accessLog.token`
- keep-alive `last_visited` — composable `resources/js/composables/useAccessHeartbeat.ts` (เรียกครั้งเดียวใน
  `AdminLayout.vue`) อ่าน `accessLog.token` แล้วยิง `navigator.sendBeacon` (fallback `fetch keepalive`) ไป
  `POST admin.system.backlog.access.ping` ตอน tab hidden / `pagehide` / `onBeforeUnmount` + interval 45 วิ.
  `BackLogAccessController@ping` (ไม่เช็ก permission, กันด้วย auth + scope `user_id` + `created_at >= -1 วัน`,
  `throttle:60,1`, ตอบ 204). CSRF: route `admin/system/backlog/access/ping` ถูก **ยกเว้น CSRF**
  ใน `bootstrap/app.php` (`validateCsrfTokens(except: [...])`) — sendBeacon ตั้ง header ไม่ได้ กันด้วย auth+scope แทน.
  **ห้ามเซ็ต `X-CSRF-TOKEN` เป็น axios default จาก `<meta csrf>`** — Inertia ไม่ re-render `<head>` โทเคนจะค้าง
  หลัง session regenerate (login/logout) → 419; Inertia ใช้คุกกี้ `XSRF-TOKEN` เองอยู่แล้ว อย่าไปแทรก
- หน้ารายการประวัติ — `admin.system.backlog.access.index` → `BackLogAccessController@index` (เช็ก `system.backlog.access`
  ไม่มีสิทธิ์ redirect ไป dashboard). `resources/js/Pages/Admin/System/BackLogAccess/Index.vue` — ตาราง
  (ชื่อ-นามสกุล/URL/ชื่อหน้า/IP/เวลาเข้าชม=`created_at`/เวลาออก=`last_visited`) คลิกแถวเปิด `Components/Admin/DetailDialog.vue`
  (dialog กลาง ใช้ซ้ำได้ — Teleport+Transition แบบ `ConfirmDialog`) แสดง field ทั้งหมด. ค้นหา 1 ช่อง (URL/ชื่อหน้า/IP)
  + ช่วงวันที่ `date_from`/`date_to` (กรอง `created_at`) + paging + per_page + sort (default `created_at` desc,
  `sort=name` leftJoin `sys_user`). เมนู sidebar คลิกได้แล้ว (route มีจริง)
- log_back_login (การเข้า/ออกระบบ) — บันทึกใน `LoginRequest::authenticate()` (สำเร็จ / รหัสผิด / ไม่พบบัญชี / บัญชีถูกระงับ)
  + `ensureIsNotRateLimited()` (ถูก throttle = `block`) + `AuthenticatedSessionController::destroy()` (logout — เก็บ
  `Auth::id()`/email **ก่อน** logout). หน้ารายการ `admin.system.backlog.login.index` → `BackLogLoginController@index`
  (เช็ก `system.backlog.login`) → `Pages/Admin/System/BackLogLogin/Index.vue` — คอลัมน์ ชื่อ-นามสกุล/Username/ประเภท/
  ผลลัพธ์ (pill สี)/IP/วันเวลา, กรอง q(username,ip,note)+ประเภท+ผลลัพธ์+ช่วงวันที่, คลิกแถวเปิด `DetailDialog`.
  route ทั้งหมดจัดกลุ่มใต้ `system/backlog` ใน `routes/web.php`
- log_back_action (การกระทำ) — `LogBackAction::record($moduleCode, $actionType, $valueString, $refId)` เรียกใน
  `UserController` (store→`create`, edit→`view`, update→`update`, destroy→`delete` [เก็บ name/id ก่อนลบ],
  password→`view`, passwordUpdate→`update`; module `system.user` / `system.user.password`) และ `UsergroupController`
  (เทียบเคียง; module `system.usergroup` / `system.usergroup.rights`) — **หน้ารายการ/หน้า add ไม่บันทึก**.
  หน้ารายการ `admin.system.backlog.action.index` → `BackLogActionController@index` (เช็ก `system.backlog.action`)
  → `Pages/Admin/System/BackLogAction/Index.vue` — คอลัมน์ ชื่อ-นามสกุล/โมดูล/ประเภท (pill)/ข้อมูล/IP/วันเวลา,
  กรอง q(value_string,module_code,ip) + dropdown "โมดูล"/"ประเภทการกระทำ" (distinct จากคอลัมน์) + ช่วงวันที่.
  `toDate()` helper ย้ายไป base `App\Http\Controllers\Controller` (ใช้ร่วม 3 log viewer). **ยังไม่ทำ**: log ฝั่งหน้าบ้าน
- โมดูลจัดการไฟล์ (`file_info`/`folder_info`, migration `2026_09_14_000001_create_file_management_tables.php`)
  — พื้นที่ไฟล์ส่วนตัวของผู้ใช้หลังบ้านแต่ละคน (`Admin\System\FileController` ajax ทั้งหมด ไม่ใช่ Inertia
  visit ยกเว้นหน้า index) + เสิร์ฟไฟล์ผ่าน `Admin\System\FileServeController`/`App\Support\FileDelivery`
  (`BinaryFileResponse` รองรับ Range/206 อัตโนมัติจาก `Router::toResponse()`, ETag/304, thumbnail cache ไฟล์
  บน disk ด้วย `intervention/image`) + cache DB lookup ด้วย `App\Support\FileCache` (ปุ่มล้างที่หน้า
  ตั้งค่าระบบ → ล้างแคช). **ไม่มี permission gate** (เหมือน Dashboard/Profile) — เมนู "จัดการไฟล์" เป็นลิงก์
  hardcode ใน `AppSidebar.vue` (ต่อจากโปรไฟล์) + `UserMenu.vue` ไม่ผ่าน `sys_menu` (ลบ row `system-file`
  ที่เคย seed ไว้ก่อนหน้าออกจาก `MenuSeeder.php` แล้ว). ส่วน "เลือกไฟล์" ทำเป็น component reusable
  (`Components/Admin/FileManager/FilePickerField.vue` + `FilePickerDialog.vue`) — ยังไม่ผูกกับฟิลด์จริง
  เพราะ `sys_user`/บทความยังไม่มีฟิลด์รูปภาพ. รายละเอียดเต็มดู `docs/PRD-system.md` §9
- ปรับปรุงโมดูลจัดการไฟล์รอบสอง: `FolderList.vue` แสดง "[ไม่มีโฟลเดอร์]" พร้อมจำนวนไฟล์ (endpoint
  `admin.system.file.folders` ส่ง `root_count` เพิ่ม) เหมือนโฟลเดอร์อื่น; `FileBrowser.vue` เพิ่มตัวเลือก
  สลับมุมมองการ์ด/แถว (`viewMode`) — มุมมองแถวแสดงรูป/ไอคอนซ้าย ชื่อเต็ม 1 บรรทัด แล้วอีกบรรทัดแยกขนาด/
  นามสกุล; ปุ่ม paging เปลี่ยนป้าย "Previous"/"Next" เป็น `<<`/`>>`, จัด layout ใหม่เป็น 3 โซน (ซ้าย=จำนวน
  รายการ, กลาง=เลขหน้า, ขวา=จำนวนต่อหน้าไม่มีข้อความกำกับ) กลางตกบรรทัดใหม่เองที่จอแคบ (breakpoint `sm`).
  แก้บั๊ก `FileUploadDropzone.vue`: push object ธรรมดาลง `ref([])` แล้วแก้ property ทีหลังใน callback async
  ไม่ trigger re-render (ต้อง `reactive()` ก่อน push) ทำให้ไอคอนค้างเป็นหมุนทั้งที่อัพโหลดเสร็จแล้ว; เพิ่ม
  auto-dismiss แถวที่สำเร็จหลัง 5 วินาทีด้วย fade+พับความสูง (`TransitionGroup` + JS `leave` hook)
- เพิ่ม `sys_user.profile_image_id` (migration แยก `2026_09_14_000002_...`, FK จริง → `file_info.id`
  nullOnDelete) — ฟิลด์ "รูปโปรไฟล์" ในฟอร์มเพิ่ม/แก้ไขผู้ใช้งาน (ต่อจาก Facebook) ใช้
  `FilePickerField.vue` เลือกได้ 1 รูป จำกัดเฉพาะนามสกุลรูปภาพ; validate แค่ว่า `file_info` แถวนั้นมีอยู่จริง
  และ `status='Y'` ผ่าน `StoreUserRequest`/`UpdateUserRequest`/`ProfileUpdateRequest` — **ไม่จำกัดว่าต้องเป็น
  ไฟล์ของใคร** (เลือกไฟล์ที่คนอื่นอัพโหลดไว้ในระบบมาใช้ได้ ตามที่ตั้งใจ — เดิมเคยจำกัดด้วย `user_id` ของผู้กระทำ
  แล้วพบว่าเช็กไม่ได้ผลตามต้องการ จึงเปลี่ยนมาเช็กแค่ว่าไฟล์มีอยู่จริงแทน)
- โมดูล page (หน้าเพจเดี่ยว) เสร็จ list/add/edit + **แท็บโครงสร้าง** — migration `2026_09_20_000001_create_page_item_tables.php`
  (`page_item_info`/`_detail` + `page_item_row`/`column`/`widget` + `*_detail`, คอลัมน์ข้อความเกริ่นนำชื่อ `intro_text`),
  `PageItemController` (`admin.page.item.*` + `.layout`/`.layout.update`, log module `page.item` / `page.item.layout`),
  `App\Support\PageLayoutSync` บันทึก tree ทั้งชุดโดย **คง id เดิม** (id หายจากที่ส่งมา = soft delete, widget ย้ายข้ามคอลัมน์ได้),
  ฝั่งหน้าจอ state/dialog อยู่ที่ `Pages/Admin/Page/Item/Layout.vue` แล้วส่งให้ `Components/Admin/PageLayout/*` ผ่าน
  provide/inject (`composables/usePageLayoutEditor.ts`); `ColorPickerInput` มี prop `transparent`; ประเภท widget แต่ละประเภทมี**ตารางตั้งค่าของตัวเอง** `page_item_widget_<ประเภท>` (PK = `page_item_widget.id`; เลิกใช้คอลัมน์ `setting` JSON) จัดการผ่าน `App\Support\PageWidget\*` (`PageWidgetType` + `PageWidgetRegistry`; ฐาน `SettingsWidget` ประกาศฟิลด์ใน `fields()` ครั้งเดียว → `CategoryListWidget` → `SlideshowWidget`/`SlidesetArticleWidget`) คู่กับ `resources/js/utils/pageWidget.ts` — ตอนนี้เสร็จ `slideshowbanner` (Slideshow จาก banner), `slidesetarticle`/`slidesetbanner` (Slideset — การ์ดเลื่อนได้ ตั้งค่าเยอะ แบ่งเป็นการ์ด/ติ๊กซ่อนแสดงทีละส่วน; article มีปุ่ม "อ่านทั้งหมด" ที่ข้อความแยกภาษาเก็บตาราง `*_detail` ผ่าน `detailFields()` ของ `SettingsWidget`), `slideshowarticle` (จาก article — สืบทอด `SlideshowWidget` ตั้งค่าเหมือนกัน แต่เรียงตามวันที่เผยแพร่ได้อย่างเดียวเพราะบทความไม่มี sort_order ต่อรายการ) และ `gridarticle`
  (Grid จาก article — กล่องเรียงต่อเนื่องหลายคอลัมน์ **ไม่เลื่อน** ต่างจาก Slideset, มี 3 รูปแบบผ่าน `display_type`: การ์ด/แถวที่มีรูปภาพ/แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ
  — สองแบบหลังบังคับแสดงหัวเรื่องเสมอและแบบวันที่บังคับแสดงวันที่ด้วย ทั้ง FE ซ่อนตัวติ๊กและ backend บังคับตอนบันทึก; ส่วนของรายการ/ปุ่ม "อ่านทั้งหมด" ใช้ฟิลด์ร่วมกับ `slidesetarticle`
  ผ่าน `textFields()`/`metaFields()` ที่ย้ายขึ้นไปอยู่ `CategoryListWidget` และ trait `HasReadAllButton`) และ `gridbanner` (โครงเดียวกับ
  `gridarticle` แต่ตัดวันที่เผยแพร่/จำนวนเข้าชม/ปุ่มอ่านทั้งหมด/รูปแบบ `row_date` ออก เพราะ banner ไม่มีแนวคิด "วันที่เผยแพร่ที่แสดงต่อผู้ชม" —
  เรียงลำดับได้ 4 แบบเหมือน `slidesetbanner` ผ่าน `ReadsBanners`) — รอบปรับปรุง: Slideset (ทั้ง article/banner) และ Grid (ทั้ง article/banner)
  เพิ่ม `border_color` (สีเส้นขอบ, default เทาอ่อน `#E5E7EB`) และ `item_background` (สีพื้นหลังของแต่ละรายการ, default ขาว, เลือก
  transparent ได้) ในกล่องของการ์ด ผ่าน `CategoryListWidget::cardBoxFields()` ที่รวม `show_border`/`rounded_corners` เดิมไว้ด้วยกัน (Grid จาก
  article ไม่เคยมีเส้นขอบ/มุมมนมาก่อนจึงได้ครบชุดใหม่ทั้งหมด); `gridarticle` รูปแบบ `row_date` (กล่องวันที่แทนรูปภาพ) แยกตัวอักษรเป็นเลขวัน
  (`date_day_*`, ตัวใหญ่ตัวหนา เทาเข้ม) กับเดือน/ปี (`date_month_*`, ตัวเล็ก เทาอ่อน) คนละกลุ่มกัน + สีพื้นหลังกล่อง (`date_box_background`)
  ผ่าน `GridArticleWidget::dateBoxFields()`, `placeholder` เป็นประเภท legacy (ดู `docs/PRD-page.md` §3 วิธีเพิ่มประเภทใหม่; **ชื่อ FK ที่ Laravel ตั้งอัตโนมัติยาวเกิน 64 ตัวอักษรของ MySQL ไม่ได้ — เทส SQLite จับไม่ได้ ให้ตั้งชื่อเองผ่าน `constrained(table, 'id', 'ชื่อสั้น')` แล้วลอง `migrate` บน MySQL dev เสมอ**) — slug ของ `page_item_detail`
  ถูกเคลียร์เป็น null ตอนลบหน้า เพราะ unique(lang, slug) ระดับ DB ยังนับแถว detail ที่ไม่ถูก soft delete; `PageSeeder` สร้างหน้าตัวอย่าง
  (`is_temp='Y'`) รายละเอียดเต็มดู `docs/PRD-page.md`
  รอบปรับปรุง: แถว/คอลัมน์/widget มี **หัวเรื่องรอง** (`*_detail.subtitle`) และการจัดรูปแบบตัวอักษรของหัวเรื่อง/หัวเรื่องรอง/ข้อความเกริ่นนำ
  (ขนาด/ฟอนต์ไทย default Sarabun/จัดตำแหน่ง default กึ่งกลาง/สี default ดำ ไม่มี transparent — 12 คอลัมน์แบบแบนต่อตาราง, migration
  `2026_09_20_000002_*`, ศูนย์กลางที่ `App\Support\PageTextStyle` + trait `HasPageTextStyle`), widget มีพื้นหลังเหมือนแถว/คอลัมน์;
  หัวเรื่องที่แสดงใช้ `<h2>`/`<h3>`/`<h4>` (แถว/คอลัมน์/widget) ส่วนหัวเรื่องรองและเกริ่นนำเป็น `<div>`; แถบจัดการมีไอคอนถังขยะ (confirm ก่อนลบ);
  หน้าโครงสร้างโหลดฟอนต์ไทยจาก Bunny Fonts (`fontsUrl`)
  **ข้อควรระวัง:** ห้ามตั้งชื่อ prop ของ Vue component ว่า `style`/`class` แล้วส่งค่า object เข้ามา — Vue ถือเป็น attribute พิเศษและ
  คัดลอก object ให้ก่อนส่ง (ค่าที่ component แก้ไม่ถึง object เดิม; เคยทำให้ตัวตั้งค่าตัวอักษรใน dialog แถว/คอลัมน์/widget ไม่ทำงาน แก้เป็น `textStyle`)
- **timezone ของระบบ** — Laravel เดิมฮาร์ดโค้ด `config('app.timezone')` เป็น `UTC` (ไม่มี `APP_TIMEZONE` ให้ตั้งผ่าน `.env` เลย) ทำให้
  `now()`/`Carbon::now()` ทั้งระบบผิดเพี้ยนจากเวลาไทยจริงอยู่ 7 ชั่วโมง (เคยทำให้บทความที่ตั้งวันที่เผยแพร่เป็นเวลาไทย "ตอนนี้" ไม่ขึ้นใน
  widget ที่กรองตามช่วงเผยแพร่ จนกว่าจะตั้งเวลาย้อนไปหลายชั่วโมง — `resources/js/Components/Admin/DateTimeInput.vue` ไม่มีการแปลง timezone
  ใด ๆ ส่งค่าที่พิมพ์ดิบ ๆ ไปเก็บในคอลัมน์ `dateTime` ของ MySQL ตรง ๆ) ตอนนี้ timezone เป็นลำดับชั้น: **(1) `sys_setting` กลุ่ม `site`
  คอลัมน์ `timezone`** (ตั้งได้ที่หน้าตั้งค่าระบบ → ฟิลด์ "โซนเวลา", ตัวเลือกจาก `Setting::timezoneOptions()` = `DateTimeZone::listIdentifiers()`
  ทั้งหมด ผ่าน SearchableSelect) **> (2) `.env` `APP_TIMEZONE`** (ค่าแนะนำของโปรเจกต์ = `Asia/Bangkok`, ตั้งไว้ใน `.env.example`/`.env` แล้ว)
  **> (3) ค่า default ของ php.ini** — `config/app.php` ตั้งค่าฐาน (`'timezone' => env('APP_TIMEZONE', date_default_timezone_get())`)
  แล้ว `App\Providers\AppServiceProvider::applyTimezoneSetting()` (คู่กับ `applySmtpSetting()` ที่มีอยู่แล้ว pattern เดียวกัน — เช็ก
  `Schema::hasTable('sys_setting')` กันพังตอนยังไม่ migrate) override ทับอีกชั้นถ้ามีค่าใน `sys_setting` ทำงานทุก request/คำสั่ง artisan
  เพราะ provider boot ทำงานทั้ง HTTP และ console — **ข้อมูลเดิมใน DB ไม่ต้อง migrate ย้อนหลัง** เพราะค่าที่เคยพิมพ์ไว้ก็คือเวลาไทยที่ตั้งใจ
  อยู่แล้ว ปัญหาอยู่ที่การคำนวณ "ตอนนี้" เพื่อเปรียบเทียบเท่านั้น
- **หน้าโครงสร้างของโมดูล page — เลิกลากสลับตรงในหน้าจอสำหรับคอลัมน์/widget แล้ว ใช้ dialog เรียงลำดับทั้งหมด** (เดิมคอลัมน์/widget ลากสลับ
  ในหน้าจอ canvas ได้เลยด้วย `vuedraggable` ที่จับ grip บนแถบจัดการ — แต่ widget แสดงตัวอย่างจริงตามค่าตั้งค่า ยิ่งมีประเภท/ตั้งค่าเยอะขึ้น
  (เช่น Grid) กล่องก็ยิ่งสูง ลากในหน้าจอยากขึ้นเรื่อย ๆ) ตอนนี้ทั้ง 3 ชั้น (แถว/คอลัมน์/widget) ใช้รูปแบบเดียวกับ `RowReorderDialog` เดิม:
  ปุ่มเรียงลำดับบนแถบจัดการเปิด dialog, แก้บนสำเนาแล้วกด "ยืนยันลำดับ" ค่อยมีผลจริง — `ColumnReorderDialog.vue` แสดงกล่องของ**ทุกแถวแบบ fix**
  แล้วลากกล่องคอลัมน์สลับ/ย้ายข้ามแถวได้ (vuedraggable group เดียวกันทุกแถว), `WidgetReorderDialog.vue` แสดงกล่องของทุกแถว+ทุกคอลัมน์แบบ fix
  แล้วลากกล่อง widget สลับ/ย้ายข้ามคอลัมน์ (หรือข้ามแถว) ได้ — `LayoutToolbar.vue` เลิกแยก "grip ลากในหน้าจอ" (คอลัมน์/widget) กับ "ปุ่มเปิด
  dialog" (แถว) ทั้งสามชั้นใช้ปุ่มเปิด dialog เหมือนกันหมดแล้ว; `RowBlock.vue`/`ColumnBlock.vue` เอา `<draggable>` ที่ครอบคอลัมน์/widget ออก
  เหลือแค่ `v-for` เฉย ๆ
- **widget `customtext` (Custom Text) เสร็จแล้ว — ประเภท widget สุดท้ายของ §2.1** ต่างจากทุกประเภทก่อนหน้าที่ตั้งค่าเป็นแถวเดียวต่อ widget
  (`page_item_widget_<ประเภท>` PK = `page_item_widget.id`, extends `SettingsWidget`) — Custom Text ให้กรอกเนื้อหาเองแบ่งเป็น **"part"
  เรียงลำดับได้หลายรายการต่อ 1 widget** เหมือนระบบ part ของบทความ (`ArticleItemPart`) รองรับ 4 ประเภท part: `text`/`image`/`images`/`video`
  (ตัดเอกสารออกจากชุดของบทความ) เพราะเป็นรายการหลายแถวจึงไม่ extend `SettingsWidget` แต่ implement `PageWidgetType` ตรง ๆ
  (`App\Support\PageWidget\CustomTextWidget`, `relation()` เป็น `hasMany` → `toArray()` ได้ `Collection` แทน `Model` เดี่ยว — ต้องแก้
  `PageWidgetType::toArray()`/`SettingsWidget::toArray()` ให้รับ `Model|Collection|null` แทน `?Model` เดิม) `save()` แทนที่ part ทั้งหมด
  ของ widget ด้วยชุดที่ส่งมาใหม่ทุกครั้ง (ลบแล้วสร้างใหม่ เทียบเคียง `ArticleItemController::syncParts()`) ตาราง `page_item_widget_customtext_part`
  (+ `_file` + `_detail`, migration `2026_09_27_000001_*`) — **หัวเรื่องของแต่ละ part จัดรูปแบบได้เอง** (ขนาด/ตัวหนา/ฟอนต์/ตำแหน่ง/สี อยู่บนตัว part
  เอง ต่างจาก part ของบทความที่ไม่มีการจัดรูปแบบเลย — ตัวหนา `title_bold` เพิ่มทีหลังใน migration `2026_09_28_000001_*` ให้ครบชุดเดียวกับหัวเรื่อง/
  ข้อความเกริ่นนำของ Slideset/Grid) ฝั่งหน้าจอ `utils/pageWidgetCustomText.ts` (เทียบเคียง `utils/articleParts.ts`) +
  `Components/Admin/PageLayout/widgets/CustomTextFields.vue` + subfolder `widgets/CustomTextPart/` (`PartCard.vue` แสดงหัวเรื่อง+การจัดรูปแบบ
  ครั้งเดียวแล้ว dispatch ไปยัง `PartText`/`PartImage`/`PartImages`/`PartVideo`, เรียงลำดับผ่าน `PartReorderDialog.vue` เหมือน part ของบทความ)
  — เพิ่ม prop `stacked` ให้ `LangFieldGroup.vue` (บังคับคอลัมน์เดียว) ใช้เฉพาะกับตัวแก้ไขข้อความ (rich text) ของ part ข้อความ เพราะ dialog
  ตั้งค่า widget มี 2 คอลัมน์อยู่แล้ว ซ้อนอีกชั้นจะเบียดเกินไป — ฟิลด์อื่น (หัวเรื่อง, alt text) ยังคง 2 คอลัมน์ปกติ ดู `docs/PRD-page.md` §3
- **โมดูล "จัดการเมนูหน้าบ้าน" (front menu)** — เฉพาะฝั่งจัดการเท่านั้น (ยังไม่ render จริงที่หน้าบ้าน) `front_menu_info`/
  `front_menu_detail` (migration `2026_09_29_000001_*`, pattern `_info`/`_detail` แทนตาราง `sys_front_menu` เดียวที่เอกสาร
  เคยเสนอไว้ก่อนหน้า) tree ไม่จำกัดระดับผ่าน `parent_id` — เฉพาะเมนูประเภท `heading` (`App\Support\FrontMenuType`) เป็น parent
  ได้ ประเภทอื่นผูกได้กับหมวดหมู่บทความ/บทความ/หน้าเพจ/URL ภายนอก พร้อมชุดตั้งค่า "หัวเรื่องของหน้าเป้าหมาย" (รูปพื้นหลัง +
  หัวเรื่อง/หัวเรื่องรองจัดสไตล์ได้ + ตำแหน่ง 9 ทิศ — **reuse `Components/Admin/IntropageBackground/PositionPicker.vue` ตรง ๆ**
  เพราะ `header_content_align` ใช้ value set เดียวกับ CSS `background-position`, reuse ฟอนต์จาก `PageTextStyle::fontNames()`
  ของโมดูล Page) หน้าเดียว `admin.system.menu.index` (`Pages/Admin/System/FrontMenu/Index.vue`) ไม่มีหน้า add/edit แยก ทุกอย่าง
  ทำผ่าน dialog: `MenuFormDialog.vue` (ฟอร์มยาว), `MenuReorderDialog.vue` (ลาก tree ข้ามระดับด้วย `vuedraggable` ซ้อนกันแบบ
  recursive ผ่าน `MenuReorderNode.vue` — เฉพาะเมนู `heading` เท่านั้นที่ render กล่องลูกให้ลาก ประเภทอื่นวางเมนูอื่นลงไปไม่ได้
  โดยธรรมชาติ ไม่ต้องเช็กเพิ่มฝั่ง UI, มี `:move` callback กัน cycle), `ArticleItemPickerDialog.vue`/`PageItemPickerDialog.vue`
  (dialog เลือก 1 รายการใหม่ — ไม่มีของเดิมให้ reuse) permission `system.menu.view/manage/delete` และเมนู sidebar
  `system-menu` **seed ไว้รอแล้วตั้งแต่ก่อนโมดูลนี้เริ่มทำ** (`DatabaseSeeder`/`MenuSeeder.php`) แค่สร้างโค้ดให้ตรงชื่อ route
  `admin.system.menu.*` ไม่ต้องแก้ seeder ส่วนสิทธิ์/เมนู — `FrontMenuSeeder` (เรียกหลัง `ArticleSeeder`/`PageSeeder` เพราะอ้าง
  id ตัวอย่างของทั้งคู่) ดูรายละเอียดเต็มที่ `docs/PRD-system-frontmenu.md`
- **โมดูล "จัดการ Template" (system.template)** — เฉพาะฝั่งจัดการ (ยังไม่ render จริงที่หน้าบ้าน) `sys_template` (ข้อมูลทั่วไป +
  Custom CSS/JS + หน้า Loading) + ตั้งค่าโซน 1:1 `sys_template_header`/`_body`/`_footer`/`_aside` (PK = `sys_template_id`,
  migration `2026_09_30_000001_*`) คอลัมน์แบนทั้งหมด — **ทะเบียนฟิลด์ของทุกโซนอยู่ที่เดียว `App\Support\Template\TemplateZone::fields()`**
  (ค่าเริ่มต้น + rules; เพิ่มคอลัมน์ต้องแก้ migration + `TemplateZone` + interface ใน `resources/js/utils/template.ts` ให้ตรงกัน),
  แม่แบบตั้งต้นตอนเพิ่มอยู่ที่ `TemplatePreset` (classic/corporate/centered/minimal/dark). **ใช้งานได้ครั้งละ 1 รายการและต้องมี
  รายการที่ใช้งานอยู่เสมอ** (เปิดรายการใหม่ = ปิดที่เหลือใน transaction; ปิด/ลบรายการที่ใช้งานอยู่ไม่ได้). โลโก้/ชื่อเว็บ/ติดต่อ/social/ภาษา/
  ลิขสิทธิ์อ่านจาก `sys_setting` ไม่เก็บซ้ำ. แท็บ: ข้อมูลทั่วไป / โครงสร้าง (preview 4 โซน + `ZoneToolbar` เฟือง+ลูกตา, dialog
  แก้บนสำเนา, บันทึกทีเดียว pattern เดียวกับหน้าโครงสร้างของ page; preview ใช้ข้อมูลจริงจาก `TemplateController::previewData()` +
  `App\Support\FrontMenuTree`, กดไม่ได้) / Custom CSS/JS / หน้า Loading — component อยู่ `Components/Admin/Template/*`
  (`VisualPicker.vue` = เปลือกการ์ด SVG, `YesNoCheckbox.vue` = แสดง/ซ่อน Y/N). permission/เมนู seed ไว้ก่อนแล้ว, log module
  `system.template`, seed ตัวอย่าง `TemplateSeeder` — ดู `docs/PRD-system-template.md`

- **หน้าบ้านรอบแรก (branch `front-init`) — ดู `docs/PRD-front.md`** — Intropage (layout เปล่า `Layouts/Front/IntroLayout.vue`),
  layout หน้าภายใน `Layouts/Front/FrontLayout.vue` จาก template ที่เปิดใช้งาน (`App\Support\Front\FrontLayoutData`, component
  `Components/Front/Template/*`, `LanguageFlag`/`SocialIcon` ย้ายไป `Components/Template/` ใช้ร่วม), ส่วนหัว+breadcrumb ตามเมนู
  (`FrontMenuResolver`), หน้าเพจ (`PageLayoutReader` + `CategoryListWidget::frontItems()` — `previewQuery()`/`articleQuery()`/`bannerQuery()`
  รับ `$lang` เพิ่ม), หมวดหมู่/รายละเอียดบทความ (`ArticleReader`, part ครบ 7 รูปแบบกลุ่มรูปใน `Components/Front/ContentPart/*`),
  SEO/AEO/GEO (`SeoMeta` → meta/hreflang/OG/JSON-LD ฝั่ง server ใน `app.blade.php` + `SeoHead.vue`), WCAG (skip link, landmark,
  เครื่องมือขนาดตัวอักษร/การแสดงสี, carousel หยุดได้, dialog focus trap), security (`HtmlSanitizer` สำหรับ rich text, `FrontUrl::safeExternal()`,
  `FrontSecurityHeaders`, หน้าบ้านไม่ส่ง `auth.user`/`menu` และ Ziggy ส่งเฉพาะกลุ่ม `front` — `config/ziggy.php`),
  หน้า error หน้าบ้าน (`FrontErrorPage` ผูกใน `bootstrap/app.php`), ข้อความ UI `lang/{th,en}/front.php`.
  **ข้อมูล `is_temp='Y'` แสดงที่หน้าบ้านตามปกติ — ห้ามกรอง `is_temp` ใน query หน้าบ้าน**
- **cache หน้าบ้าน** `App\Support\Front\FrontCache` (key มี version — `forgetAll()` เพิ่ม version แทน `Cache::flush()`), ล้างอัตโนมัติผ่าน
  trait `App\Models\Concerns\FlushesFrontCache` บนทุก model ที่หน้าบ้านใช้ + `Setting::forget()`; ปุ่ม "ล้าง Cache หน้าบ้าน" ในหน้าล้างแคช.
  โมดูลใหม่ที่หน้าบ้านแสดงผล ต้องใส่ trait นี้ใน model ด้วย
- **ยอดเข้าชม** `article_item_view` / `page_item_view` + `page_item_info.view_amount` (migration `2026_10_01_000001_*`) —
  `App\Support\Front\ViewCounter` (ข้ามบอท + dedupe 5 นาที/session, บันทึกหลังส่ง response ด้วย `defer()`, โหมด redis = คิว Redis +
  `php artisan front:flush-views` ทุกนาที batch insert/update) — **production ต้องตั้ง cron `schedule:run`**; `config/front.php`.
  ใช้ `defer()` ไม่ใช่ `app()->terminating()` (callback ของ terminating สะสมข้าม request ในเทส/Octane)
- **โมดูล page รอบปรับปรุง (branch `page-edit`)** — ฟอร์มข้อมูลทั่วไปเรียงใหม่: ข้อมูลหน้าเพจ → SEO/AEO/GEO (รวมรูปแทนหน้าในหัวข้อ
  "การแชร์ไปโซเชียลมีเดีย") → พื้นหลังของทั้งหน้า → สถานะ; ระยะห่างของแถว/คอลัมน์/widget (`use_padding` default `N` + `padding_*` 4 ด้าน px,
  แถวมี `gap_x`/`gap_y` default 24) migration `2026_10_03_000001_*`, ศูนย์กลาง `App\Support\PageSpacing` + trait `HasPageSpacing`,
  UI `Components/Admin/PageLayout/SpacingFields.vue`; หน้าบ้านหน้าเพจไม่มีระยะบน/ล่างของตัวหน้าและแถวไม่มี `py-6` ตายตัวแล้ว — ดู `docs/PRD-page.md`.
  รอบสอง: ตัวหนา `*_bold` ในการจัดรูปแบบตัวอักษรของแถว/คอลัมน์/widget (`PageTextStyle::FIELDS` มี `bold` แล้ว = 15 คอลัมน์), "แสดงหัวเรื่อง"/"การแสดงเนื้อหา"
  เป็น `SegmentedChoice` (ผู้ใช้เรียกว่า segmented control) และซ่อนส่วนที่เกี่ยวข้องเมื่อไม่แสดงหัวเรื่อง (ค่าไม่ลบ), หน้าบ้านส่งระดับหัวเรื่องต่อลงชั้นถัดไปเมื่อชั้นบนไม่มีหัวเรื่อง.
  รอบสาม: Slideshow ตำแหน่งข้อความ 9 ตำแหน่ง (`text_align` ค่าแบบ background-position, default `center`) + ตัวหนา `title_bold`/`intro_text_bold`;
  **นับคลิก banner** (Slideshow/Slideset/Grid) → `banner_item_click` + `banner_item_info.click_amount` ผ่าน `ViewCounter` ประเภท `banner`
  + `POST front.banner.click` (sendBeacon, ยกเว้น CSRF) — ดู `docs/PRD-front.md` §8.
  รอบสี่: Grid (article/banner) รูปแบบแถวมี `content_align` (บน/กึ่งกลาง/ล่าง, default บน), ความกว้างรูปกรอกด้วยแถบเลื่อน (`RangeNumberField.vue`),
  ตัวเลือกสีในกล่องของการ์ด/กล่องวันที่เต็มความกว้าง (`CardBoxFields.vue` ใช้ร่วม Slideset ด้วย), `SegmentedChoice` รองรับ `icon`;
  ปุ่ม "อ่านทั้งหมด": ลิงก์ปลายทางเลือกจากเมนู (`read_all_link_type` default `menu` + `read_all_menu_id`) หรือกำหนดเอง, path ภายในที่ไม่มีภาษาเติม
  `/{lang}` ให้ (`FrontUrl::withLang()`) — ใช้กับเมนูลิงค์ภายนอกทุกจุดด้วย (header/footer/aside ผ่าน `FrontMenuResolver::link()`).
  **RichTextEditor** (`Components/Admin/RichTextEditor.vue` — ใช้ทุกจุด: part ข้อความของบทความ/Custom Text, รายละเอียดหมวดหมู่บทความ) มีจัดข้อความ
  ซ้าย/กึ่งกลาง/ขวา/เต็มแนว (`@tiptap/extension-text-align`) + ระยะห่างระหว่างบรรทัด (`utils/tiptapLineHeight.ts`, default 1.5 จาก CSS) เก็บเป็น
  style บนแท็ก block — `HtmlSanitizer` เก็บไว้เฉพาะ 2 ค่านี้ (รายการ LINE_HEIGHTS ต้องตรงกันสองฝั่ง); CSS เนื้อหา `.rich-text-content` ย้ายไป `app.css`.
  **ลิงก์หน้าบ้านไม่มีเส้นใต้ตอน hover** (เหลือแค่ cursor — เส้นใต้ที่เหลือเป็นตัวบอกสถานะ active/ภาษาปัจจุบัน/ลิงก์ในเนื้อหา rich text)
- **โมดูลบทความรอบปรับปรุง (branch `article-edit`)** — ฟอร์มหลังบ้านหมวดหมู่/บทความเป็น component ร่วม Add/Edit
  `Components/Admin/ArticleForm/{ArticleCategoryFormFields,ArticleItemFormFields,ArticleSeoFields}.vue` (+ `utils/articleForm.ts`) เรียงการ์ดใหม่;
  **ตั้งค่าบทความมีทะเบียนเดียว `App\Support\ArticleSetting`** (defaults/rules/`listSetting()`/`detailSetting()` — request/controller/seeder/หน้าบ้านอ่านจากนี่,
  ตัวเลือกฝั่งจอ `utils/articleSetting.ts`) เพิ่มแสดง/ซ่อนเกริ่นนำ-รายละเอียดหมวดหมู่, การเรียงตั้งต้น, วันที่/ยอดเข้าชม, กลุ่มการ์ด/แถว (อัตราส่วน/fit/สี/บรรทัด)
  และรายละเอียดบทความ (รูปหน้าปก/ปุ่มพิมพ์/ตำแหน่งแชร์). หน้าบ้าน: ค้นหา `?q=` + เรียง `?sort=` (`ArticleReader::paginateList()`/`applySort()`),
  `Components/Front/Article/ArticleListView.vue` ใช้ร่วมหน้าหมวดหมู่/แท็ก, หน้ารายละเอียดเรียงใหม่ + `ShareButtons.vue` + แท็กเป็นลิงก์,
  **หน้าใหม่ `front.article.tag` (`/{lang}/article/tag/{tag}` — ชื่อแท็ก)** ดู `docs/PRD-article.md` §3 / `docs/PRD-front.md` §5
- **`log_front_access`** (ในไฟล์ log กลาง — เพิ่มหลังจากไฟล์นั้น migrate แล้ว เครื่อง dev ต้องสร้างตารางเอง/`migrate:fresh`) —
  `LogFrontAccess::record()` ทุก controller หน้าบ้าน, keep-alive `useAccessHeartbeat('front.access.ping')` scope token + session_id,
  หน้ารายการหลังบ้าน `admin.system.frontlog.access.index`

## ทดสอบ

- เทสหน้าบ้านอยู่ `tests/Feature/Front/FrontSiteTest.php` (seed `DatabaseSeeder` แล้วใช้ข้อมูลตัวอย่าง)
- เทสอยู่ใน `tests/Feature/Auth/*` และ `tests/Feature/ProfileTest.php` (มาจาก Breeze, ใช้ Pest) —
  ปรับให้ใช้ prefix `/admin` + route `admin.*` แล้ว ปัจจุบัน **ผ่านทั้งหมด**
- `tests/Feature/Auth/EmailVerificationTest.php` ถูก `->skip()` ไว้ — `User` ยังไม่ implements
  `MustVerifyEmail` (ถ้าจะเปิดฟีเจอร์ verify email ต้อง implement contract ก่อน แล้วปลด skip)
- PHPUnit override เป็น SQLite `:memory:`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync` — ไม่แตะ MySQL/Redis จริง
