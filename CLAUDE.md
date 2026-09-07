# CLAUDE.md

คู่มือสำหรับ Claude Code (และผู้พัฒนา) เมื่อทำงานกับ repository นี้

## ภาพรวมโปรเจกต์

Micro-CMS ที่เน้น **ติดตั้งง่าย ใช้งานง่าย** พัฒนาบน Laravel 12 + Inertia.js + Vue 3
โปรเจกต์อยู่ในช่วงเริ่มต้น โครงสร้างพื้นฐาน (auth หลังบ้าน, multi-language front, ระบบสิทธิ์)
วางไว้แล้ว แต่ยังไม่มีโมเดลเนื้อหา CMS จริง (`app/Http/Controllers/Admin/PostController.php` ยังว่าง)

## Tech Stack

| ส่วน | เทคโนโลยี |
|------|-----------|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | Inertia.js 2 + Vue 3 (`<script setup>` + TypeScript) + Tailwind CSS 3 |
| Build | Vite 7, `laravel-vite-plugin`, `vue-tsc` |
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
- `resources/js/Pages/Front/` + `resources/js/Layouts/FrontLayout.vue` — หน้าบ้าน
- `resources/js/Components/` — component ที่ใช้ร่วมกัน (มาจาก Breeze)

Controller ใน `Admin/` render ด้วยชื่อ page แบบ `Admin/...`, ใน `Front/` แบบ `Front/...`
(เช่น `Inertia::render('Admin/Dashboard', ...)`, `Inertia::render('Front/Home', ...)`)

## ระบบสิทธิ์ (Permissions)

ตารางทั้งหมดขึ้นต้นด้วย `sys_` (กำหนดในไฟล์ migration เดียว: `database/migrations/0001_01_01_000000_create_users_table.php`)

| ตาราง | Model | หมายเหตุ |
|-------|-------|----------|
| `sys_user` | `App\Models\User` | มี `user_type` (default `back`), `usergroup_id`, softDeletes |
| `sys_usergroup` | `App\Models\UserGroup` | softDeletes |
| `sys_action_group` | `App\Models\SysActionGroup` | |
| `sys_action` | `App\Models\SysAction` | มี `code` (unique) เช่น `system.user.view` |
| `sys_usergroup_action` | (pivot) | เชื่อม usergroup ↔ action |

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
- ลิงก์/ชื่อ route ทั้งหมดในหลังบ้านใช้ `route('admin.xxx')` (Ziggy) ให้ครบ prefix `admin.` เสมอ —
  หน้าบ้านบางที่ยัง hardcode `/th`, `/en`
- อีเมล reset password: URL ผูกกับ `route('admin.password.reset')` ผ่าน
  `ResetPassword::createUrlUsing()` ใน `AppServiceProvider::boot()` (Laravel default ใช้ `password.reset` ที่ไม่มี)
- comment ในโค้ดเป็นภาษาไทยได้ (โปรเจกต์ใช้อยู่แล้ว)

## หมายเหตุ / ความไม่สอดคล้องที่ควรรู้

- **`.env.example` ยังเป็นค่า default ของ Laravel** (`DB_CONNECTION=sqlite`, `SESSION_DRIVER=database`,
  `CACHE_STORE=database`, `REDIS_CLIENT=phpredis`). `.env` จริงตั้งเป็น MySQL + `SESSION_DRIVER=redis` +
  `REDIS_CLIENT=predis` แล้ว แต่ `CACHE_STORE` ยังเป็น `database` และ `QUEUE_CONNECTION=database`
  หากต้องการ cache บน Redis ตามเป้าหมายโปรเจกต์ ให้ตั้ง `CACHE_STORE=redis`
- `config/app.php` locale default = `en` (จาก `APP_LOCALE`) แต่ `SetLocale` middleware default = `th`
- `resources/js/Layouts/Admin/AdminLayout.vue` เป็นไฟล์ว่าง (ยังไม่ได้ใช้ — หลังบ้านใช้ `AuthenticatedLayout.vue`)
- database มีไฟล์ `database/database.sqlite` ค้างอยู่ (gitignore แล้ว; ไม่ได้ใช้เมื่อรันบน MySQL)
- `Admin/PostController.php` ยังเป็นไฟล์ว่าง (placeholder สำหรับโมดูลเนื้อหาที่จะทำ)
- Git remote: `https://github.com/krerkkiat7/micro-cms` (private) — branch `main`

### ประเด็นที่แก้ไปแล้ว (ประวัติ อย่าทำซ้ำ)
- ชื่อ route ทุกจุดในโค้ด/เทสปรับเป็น `admin.*` ครบแล้ว (เดิม Breeze อ้าง `login`/`dashboard`/`password.*` ที่ไม่มี)
- `migration down()` แก้ typo `sys_usergrouop` → `sys_usergroup` + เรียง drop ให้ปลอดภัยกับ FK แล้ว
- ลบไฟล์ Breeze ที่ตายแล้ว: `Pages/Welcome.vue`, `Pages/Dashboard.vue`, `Pages/Front/About.vue`

## ทดสอบ

- เทสอยู่ใน `tests/Feature/Auth/*` และ `tests/Feature/ProfileTest.php` (มาจาก Breeze, ใช้ Pest) —
  ปรับให้ใช้ prefix `/admin` + route `admin.*` แล้ว ปัจจุบัน **ผ่านทั้งหมด**
- `tests/Feature/Auth/EmailVerificationTest.php` ถูก `->skip()` ไว้ — `User` ยังไม่ implements
  `MustVerifyEmail` (ถ้าจะเปิดฟีเจอร์ verify email ต้อง implement contract ก่อน แล้วปลด skip)
- PHPUnit override เป็น SQLite `:memory:`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync` — ไม่แตะ MySQL/Redis จริง
