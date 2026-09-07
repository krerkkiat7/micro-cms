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

> ไฟล์ Breeze เดิมที่ยังไม่ได้ย้าย/ลบ: `resources/js/Pages/Dashboard.vue`, `Pages/Welcome.vue`
> ให้ใช้เวอร์ชันใน `Admin/` แทน

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
- `$user->hasPermission('system.user.view')` — คืน `bool`
- `$user->getPermissionsArray()` — คืน array ของ action codes
- share ไป frontend ผ่าน `HandleInertiaRequests::share()` → `auth.user.permissions`
- ฝั่ง Controller: ส่ง `can` เป็น props (ดู `Admin/DashboardController`) แล้วเช็ก `v-if="can.xxx"` ใน Vue

> ยังไม่มี middleware/gate บังคับสิทธิ์แบบรวมศูนย์ — การเช็กทำใน controller/หน้า เป็นราย ๆ (โค้ด `abort(403)` ถูก comment ไว้)

## Convention

- PHP: PSR-12 / Laravel Pint, indent 4 spaces (ดู `.editorconfig`)
- Vue หลังบ้าน: `<script setup lang="ts">` + `defineProps<{...}>()`
- Vue หน้าบ้าน: ปัจจุบันบางไฟล์ยังเป็น `<script setup>` ธรรมดา (`Front/Home.vue`) — เขียนใหม่ให้เป็น TypeScript
- Path alias: `@/` → `resources/js/` (ตั้งใน `tsconfig.json` + Vite)
- ลิงก์ภายในหลังบ้านใช้ `route('admin.xxx')` (Ziggy) — หน้าบ้านบางที่ยัง hardcode `/th`, `/en`
- comment ในโค้ดเป็นภาษาไทยได้ (โปรเจกต์ใช้อยู่แล้ว)

## หมายเหตุ / ความไม่สอดคล้องที่ควรรู้

- **`.env.example` ยังเป็นค่า default ของ Laravel** (`DB_CONNECTION=sqlite`, `SESSION_DRIVER=database`,
  `CACHE_STORE=database`, `REDIS_CLIENT=phpredis`). `.env` จริงตั้งเป็น MySQL + `SESSION_DRIVER=redis` +
  `REDIS_CLIENT=predis` แล้ว แต่ `CACHE_STORE` ยังเป็น `database` และ `QUEUE_CONNECTION=database`
  หากต้องการ cache บน Redis ตามเป้าหมายโปรเจกต์ ให้ตั้ง `CACHE_STORE=redis`
- `config/app.php` locale default = `en` (จาก `APP_LOCALE`) แต่ `SetLocale` middleware default = `th`
- ไม่มี route ชื่อ `login`/`password.request` (มีแต่ `admin.*`) — โค้ด Breeze บางส่วนที่อ้าง
  `Route::has('password.request')` / `Route::has('login')` จะได้ `false` เสมอ
- `resources/js/Layouts/Admin/AdminLayout.vue` และ `Pages/Front/About.vue` เป็นไฟล์ว่าง
- `migration down()` มี typo (`sys_usergrouop`)
- database มีไฟล์ `database/database.sqlite` ค้างอยู่ (ไม่ได้ใช้เมื่อรันบน MySQL)
- ยังไม่มี `git` repository ในโฟลเดอร์นี้

## ทดสอบ

- เทสอยู่ใน `tests/Feature/Auth/*` และ `tests/Feature/ProfileTest.php` (มาจาก Breeze, ใช้ Pest)
- เทสเหล่านี้อ้าง route/หน้าแบบเดิม (`/login`, `/register`, `Auth/Login`) — **จะ fail** จนกว่าจะปรับให้ตรงกับ prefix `/admin` และ page path `Admin/Auth/*`
- PHPUnit override เป็น SQLite `:memory:`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync` — ไม่แตะ MySQL/Redis จริง
