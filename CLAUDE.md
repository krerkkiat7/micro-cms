# CLAUDE.md

คู่มือสำหรับ Claude Code (และผู้พัฒนา) เมื่อทำงานกับ repository นี้

## ภาพรวมโปรเจกต์

Micro-CMS ที่เน้น **ติดตั้งง่าย ใช้งานง่าย** พัฒนาบน Laravel 12 + Inertia.js + Vue 3
โปรเจกต์อยู่ในช่วงเริ่มต้น โครงสร้างพื้นฐาน (auth หลังบ้าน, multi-language front, ระบบสิทธิ์)
วางไว้แล้ว แต่ยังไม่มีโมเดลเนื้อหา CMS จริง (`app/Http/Controllers/Admin/PostController.php` ยังว่าง)

ภาพรวมโมดูล/ส่วนจัดการระบบ + roadmap อยู่ที่ `docs/PRD-overview.md`
รายละเอียดส่วนจัดการระบบ (users, สิทธิ์, เมนู, template, ประวัติ, settings, files) อยู่ที่ `docs/PRD-system.md`

## Tech Stack

| ส่วน | เทคโนโลยี |
|------|-----------|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | Inertia.js 2 + Vue 3 (`<script setup>` + TypeScript) + Tailwind CSS **v4** (CSS-first config ใน `resources/css/app.css`) |
| Build | Vite 7, `laravel-vite-plugin`, `@tailwindcss/vite`, `vue-tsc` — ไม่มี `postcss.config.js`/`tailwind.config.js` |
| Icons | `lucide-vue-next` (ใช้ในหลังบ้าน) |
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

# ทดสอบ
php artisan test              # หรือ `composer test`
./vendor/bin/pest

# คุณภาพโค้ด
./vendor/bin/pint             # format PHP (Laravel Pint)
npm run build                 # vue-tsc typecheck + vite build
```

## โครงสร้าง Routing (`routes/web.php`)

- `/` → redirect ไป `/th`
- **Front-office**: prefix `{lang}` โดย `lang` = `th|en`, middleware `['web', 'setLocale']`
  - `SetLocale` middleware อ่าน `lang` จาก route แล้ว `App::setLocale()` (default `th`)
  - ชื่อ route ขึ้นต้น `front.*` (เช่น `front.home`)
- **Back-office**: prefix `/admin`
  - `routes/auth_admin.php` = auth routes ของ Breeze ที่ย้ายมาไว้ใต้ `/admin` (ชื่อ route ขึ้นต้น `admin.*` เช่น `admin.login`, `admin.password.request`)
  - routes ที่ต้อง login อยู่ใน group `middleware(['auth', 'verified'])` ชื่อขึ้นต้น `admin.*` (เช่น `admin.dashboard`, `admin.profile.edit`)

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

## ระบบสิทธิ์ (Permissions)

ตารางทั้งหมดขึ้นต้นด้วย `sys_` (กำหนดในไฟล์ migration เดียว: `database/migrations/0001_01_01_000000_create_users_table.php`)

| ตาราง | Model | หมายเหตุ |
|-------|-------|----------|
| `sys_user` | `App\Models\User` | ชื่อแยกเป็น `titlename`/`firstname`/`lastname` (+ accessor `name` = ชื่อเต็ม); ช่องทางติดต่อ `mobile`/`phone`/`line`/`facebook`; `user_type` (`back`/`front`, default `back`), `status` `char(1)` default `Y`; สถิติ login `last_login_at`/`failed_login_count`/`last_failed_login_at`; `usergroup_id`; **`SoftDeletes` (เปิดใช้ trait แล้ว)**. `email` **ไม่ unique ระดับ DB** |
| `sys_usergroup` | `App\Models\UserGroup` | มี `status` `char(1)` default `Y`, softDeletes (คอลัมน์) |
| `sys_action_group` | `App\Models\SysActionGroup` | `id` เป็น `string(20)` primary (กำหนดเอง); มี `sort_order`, `actions()` hasMany |
| `sys_action` | `App\Models\SysAction` | `id` เป็น `string(20)` primary; มี `code` (unique) เช่น `system.user.view`, `parent_id` (tree, self-FK), `sort_order` |
| `sys_usergroup_action` | (pivot) | เชื่อม usergroup ↔ action (`action_id` เป็น `string(20)`) |
| `sys_setting` | `App\Models\SysSetting` | ตั้งค่าระบบ key-value; composite PK `(group, name)` — ค้นด้วย `where()` ไม่ใช้ `find()`; timestamps + softDeletes. `group` เป็นคำสงวน MySQL (Laravel quote ให้) |

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
  `sys_usergroup.status`; `sys_action_group`/`sys_action` id เป็น `string(20)` + `sys_action.parent_id`/`sort_order`;
  เพิ่มตาราง `sys_setting`; เปิด `SoftDeletes` บน `User` — ปรับ seeder/factory/requests/controllers auth/
  `HandleInertiaRequests`/หน้า Vue profile+register/เทส ให้ตรงแล้ว (ดู `docs/PRD-system.md` ภาคผนวก)
- แยก password reset broker ตาม `user_type`: broker `users` → `password_reset_tokens` (back),
  broker `front` → `front_password_reset_tokens` (เตรียมไว้สำหรับ front-office auth)
- ลบไฟล์ Breeze ที่ตายแล้ว: `Pages/Welcome.vue`, `Pages/Dashboard.vue`, `Pages/Front/About.vue`
- อัปเกรด Tailwind v3 → v4; เปลี่ยน layout หลังบ้านเป็นสไตล์ TailAdmin (sidebar/header มืด);
  ลบ `AuthenticatedLayout.vue`, `GuestLayout.vue`, `Components/NavLink.vue`, `Components/ResponsiveNavLink.vue`

## ทดสอบ

- เทสอยู่ใน `tests/Feature/Auth/*` และ `tests/Feature/ProfileTest.php` (มาจาก Breeze, ใช้ Pest) —
  ปรับให้ใช้ prefix `/admin` + route `admin.*` แล้ว ปัจจุบัน **ผ่านทั้งหมด**
- `tests/Feature/Auth/EmailVerificationTest.php` ถูก `->skip()` ไว้ — `User` ยังไม่ implements
  `MustVerifyEmail` (ถ้าจะเปิดฟีเจอร์ verify email ต้อง implement contract ก่อน แล้วปลด skip)
- PHPUnit override เป็น SQLite `:memory:`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync` — ไม่แตะ MySQL/Redis จริง
