# PRD — ส่วนจัดการระบบ (System Management)

เอกสารนี้ลงรายละเอียดของกลุ่มเมนู **จัดการระบบ** ในหลังบ้าน ภาพรวมทั้งระบบดูที่
[PRD-overview.md](PRD-overview.md)

สถานะ: 🟢 มีแล้ว · 🟡 มีบางส่วน · 🔴 ยังไม่มี

| # | หัวข้อ | ตารางหลัก | สถานะ |
|---|--------|-----------|-------|
| 1 | จัดการผู้ใช้งาน | `sys_user` | 🟡 ตาราง/model มี, ยังไม่มี UI |
| 2 | จัดการกลุ่มผู้ใช้งาน + สิทธิ์ | `sys_usergroup`, `sys_action_group`, `sys_action`, `sys_usergroup_action` | 🟡 ตาราง/model/seeder มี, ยังไม่มี UI |
| 3 | จัดการเมนู (หลังบ้าน / หน้าบ้าน) | `sys_menu_group`, `sys_menu` / `sys_front_menu` *(เสนอ)* | 🟡 เมนูหลังบ้าน: ตาราง/model/seed ตัวอย่างมี, ยังไม่ต่อ UI · หน้าบ้าน: 🔴 |
| 4 | จัดการ template | `sys_template` *(เสนอ)* | 🔴 |
| 5 | ประวัติ login / เข้าชม / การกระทำ | `sys_log_login`, `sys_log_visit`, `sys_log_action` *(เสนอ)* | 🔴 |
| 6 | ตั้งค่าระบบ/เว็บไซต์ | `sys_setting` | 🟡 ตาราง/model/seed ตัวอย่างมี, ยังไม่มี UI |
| 7 | profile | `sys_user` | 🟢 |
| 8 | dashboard | — | 🟡 placeholder |
| 9 | file management | `sys_file` *(เสนอ)* | 🔴 |

---

## 1. จัดการผู้ใช้งาน

**วัตถุประสงค์** — เพิ่ม/แก้ไข/ปิดการใช้งานบัญชีผู้ดูแลระบบ กำหนดกลุ่มผู้ใช้ (สิทธิ์) ให้แต่ละคน

**Data model — `sys_user`** (ปรับในเฟส 0)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK | |
| `titlename` | `varchar(30)` null | คำนำหน้า |
| `firstname` | `varchar(100)` | ชื่อ |
| `lastname` | `varchar(100)` | นามสกุล |
| `email` | `varchar(150)` **index (ไม่ unique)** | ใช้ล็อกอิน — ดู "กติกา email ไม่ซ้ำ" ด้านล่าง |
| `email_verified_at` | timestamp null | |
| `password` | `varchar(255)` | hash |
| `user_type` | `varchar(20)` default `back` | `back` = ผู้ใช้หลังบ้าน, `front` = ผู้ใช้หน้าบ้าน — ตาราง `sys_user` เดียวใช้ทั้งสองฝั่ง |
| `mobile` | `varchar(20)` null | เบอร์มือถือ |
| `phone` | `varchar(30)` null | เบอร์ติดต่อ |
| `line` | `varchar(100)` null | LINE id |
| `facebook` | `varchar(150)` null | facebook (url/handle) |
| `status` | `char(1)` default `Y` | `Y` = ใช้งาน, `N` = ถูกระงับ |
| `last_login_at` | timestamp null | เวลาที่ login สำเร็จครั้งล่าสุด |
| `failed_login_count` | `smallint unsigned` default 0 | จำนวนครั้งที่ login ไม่สำเร็จติดต่อกัน |
| `last_failed_login_at` | timestamp null | เวลาที่ login ไม่สำเร็จครั้งล่าสุด |
| `usergroup_id` | bigint FK → `sys_usergroup` null (nullOnDelete) | กลุ่มสิทธิ์ |
| `remember_token`, `timestamps`, `deleted_at` | | **soft delete เปิดใช้แล้ว** (`User` ใช้ trait `SoftDeletes`) |

**Model** `App\Models\User` — `SoftDeletes`; accessor `name` คืน `"คำนำหน้า ชื่อ นามสกุล"`;
cast `last_login_at` / `last_failed_login_at` เป็น datetime

### กติกา email ไม่ซ้ำ (สำคัญ)

`email` **ไม่มี unique constraint ระดับ DB** เพราะตาราง `sys_user` เดียวเก็บผู้ใช้ทั้งหน้าบ้าน/หลังบ้าน
และรองรับ soft delete จึงมีกรณีที่ email ซ้ำได้อย่างถูกต้อง:
- ผู้ใช้ `front` และ `back` ใช้ email เดียวกัน
- บัญชีเดิมถูกลบ (soft delete) แล้วสมัครใหม่ด้วย email เดิม

การเช็กทำ **ในโค้ด** ว่า "ห้ามซ้ำกับผู้ใช้ `user_type` เดียวกันที่ยังไม่ถูกลบ" ผ่าน
`Rule::unique(...)->where('user_type', <type>)->whereNull('deleted_at')`
(`RegisteredUserController` = `back`; `ProfileUpdateRequest` = `user_type` ของผู้ใช้ปัจจุบัน + `ignore(id)`)

### การเข้าสู่ระบบหลังบ้าน (`/admin/login`)

`App\Http\Requests\Auth\LoginRequest::authenticate()`:
- ตรวจกับ `user_type = 'back'` เสมอ — ผู้ใช้ `front` (หรือ email ที่ไม่มีบัญชี back) → `auth.failed`
- `Auth::attempt(['email', 'password', 'user_type' => 'back', 'status' => 'Y'])` — soft delete scope กันบัญชีที่ถูกลบอยู่แล้ว
- ถ้ารหัสผ่านถูกต้องแต่ `status = 'N'` → แจ้ง **"บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ"** (ไม่นับเป็น login ไม่สำเร็จ)
- **login สำเร็จ** → ตั้ง `failed_login_count = 0`, `last_failed_login_at = null`, `last_login_at = now()`
- **login ไม่สำเร็จ (รหัสผ่านผิด)** → `failed_login_count += 1`, `last_failed_login_at = now()`

**Password reset** — ตาราง `password_reset_tokens` มี `email` เป็น PK (1 แถว/email) ซึ่งพอสำหรับผู้ใช้
`back` เพราะกติกา "email ไม่ซ้ำต่อ user_type" การันตีว่ามีผู้ใช้ `back` ที่ยังไม่ถูกลบ ≤ 1 คนต่อ email
รองรับกรณี email เดียวกันมีทั้งบัญชี `back` และ `front` ด้วยการ **แยก broker + แยกตาราง**:

| broker (config `auth.passwords.*`) | ตาราง | ใช้กับ |
|-----------------------------------|-------|--------|
| `users` (default) | `password_reset_tokens` | ผู้ใช้หลังบ้าน — `/admin/forgot-password`, `/admin/reset-password` |
| `front` | `front_password_reset_tokens` | ผู้ใช้หน้าบ้าน (ยังไม่มี route/หน้า — เตรียมโครงไว้) |

controller หลังบ้านเรียก `Password::broker('users')->sendResetLink($request->only('email') + ['user_type' => 'back'])`
(และ `->reset(...)` เช่นกัน) — front-office ในอนาคตใช้ `Password::broker('front')` + `['user_type' => 'front']`
> `AppServiceProvider` override `ResetPassword::createUrlUsing()` ให้ทุก broker ชี้ URL ไป `admin.password.reset` —
> เมื่อทำ front auth ต้องแยก URL ตาม broker

> **ยังไม่ทำ:** การล็อกบัญชีอัตโนมัติเมื่อ `failed_login_count` เกินเกณฑ์ — จะทำพร้อมกับตอนดึงค่าเกณฑ์
> จาก `sys_setting` (เช่น กลุ่ม `security`, key `max_failed_login`) มาใช้ในเฟสถัดไป

**หน้าจอ**
- `รายการผู้ใช้` — ตาราง (ชื่อ, อีเมล, กลุ่ม, สถานะ, login ล่าสุด), ค้นหา, กรองตามกลุ่ม/สถานะ
- `ฟอร์มเพิ่ม/แก้ไข` — ข้อมูลชื่อ + ช่องทางติดต่อ + กลุ่มผู้ใช้ + สถานะ + ตั้ง/รีเซ็ตรหัสผ่าน + ปุ่มรีเซ็ต `failed_login_count`
- ลบผู้ใช้ = soft delete; ระงับชั่วคราวใช้ `status = 'N'`

**Permission code** — `system.user.view`, `system.user.create` (รวมแก้ไข), `system.user.delete`

**Route (เสนอ)** — `admin.system.users.index|create|store|edit|update|destroy` ใต้ `/admin/system/users`

**หมายเหตุ**
- `RegisteredUserController` สร้างผู้ใช้ใหม่ด้วย `user_type = 'back'`, `status = 'Y'` แต่ไม่กำหนด `usergroup_id`
  → `getPermissionsArray()` คืน `[]` จนกว่าจะถูกจัดกลุ่ม

---

## 2. จัดการกลุ่มผู้ใช้งาน + กำหนดสิทธิ์

**วัตถุประสงค์** — นิยาม "กลุ่มผู้ใช้" (บทบาท) แล้วติ๊กสิทธิ์ (action) ให้กลุ่ม ผู้ใช้ที่อยู่ในกลุ่มจะได้สิทธิ์ตามนั้น

**Data model**

`sys_usergroup` (ปรับในเฟส 0)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK | |
| `name` | `varchar(100)` | ชื่อกลุ่ม เช่น "Super Admin" |
| `description` | `varchar(255)` null | |
| `status` | `char(1)` default `Y` | `Y`/`N` |
| `timestamps`, `deleted_at` | | softDeletes (คอลัมน์มี) |

`sys_action_group` (ปรับในเฟส 0) — หมวดของสิทธิ์ เพื่อจัดกลุ่มในหน้าติ๊ก

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `varchar(20)` PK | กำหนดเอง เช่น `article`, `system` |
| `name` | `varchar(100)` | เช่น "จัดการผู้ใช้งาน" |
| `sort_order` | unsigned int default 0 | ลำดับแสดง |
| `status` | `char(1)` default `Y` | `Y` = แสดง, `N` = ซ่อน (ในหน้ากำหนดสิทธิ์) |
| `timestamps`, `deleted_at` | | softDeletes |

`sys_action` (ปรับในเฟส 0) — สิทธิ์รายตัว

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `varchar(20)` PK | กำหนดเอง (seeder ใช้ค่าเดียวกับ `code`) |
| `action_group_id` | `varchar(20)` FK → `sys_action_group` (cascade) | หมวด |
| `parent_id` | `varchar(20)` null FK → `sys_action` (nullOnDelete) | สำหรับทำ tree (สิทธิ์แม่-ลูก) |
| `code` | `varchar(100)` unique | ใช้เช็กในโค้ด เช่น `system.user.view` |
| `name` | `varchar(150)` | ชื่อไทย เช่น "ดูรายการผู้ใช้งาน" |
| `sort_order` | unsigned int default 0 | |
| `timestamps`, `deleted_at` | | |

`sys_usergroup_action` (pivot, ปรับในเฟส 0)

| คอลัมน์ | ชนิด |
|---------|------|
| `usergroup_id` | bigint FK → `sys_usergroup` (cascade) |
| `action_id` | `varchar(20)` FK → `sys_action` (cascade) |
| composite PK `(usergroup_id, action_id)` + `timestamps` | |

**Model**
- `UserGroup::actions()` — belongsToMany ผ่าน pivot
- `SysActionGroup::actions()` — hasMany
- `SysAction::group()` / `parent()` / `children()`
- `User::hasPermission($code)` / `getPermissionsArray()` — อ่านจาก `group->actions->pluck('code')`

**หน้าจอ**
- `รายการกลุ่ม` — ตาราง (ชื่อ, จำนวนสิทธิ์, จำนวนสมาชิก, สถานะ)
- `ฟอร์มกลุ่ม` — ชื่อ/คำอธิบาย/สถานะ + **ต้นไม้สิทธิ์**: จัดกลุ่มตาม `sys_action_group` (เรียงด้วย `sort_order`),
  ภายในกลุ่มแสดง `sys_action` เป็น tree ตาม `parent_id`/`sort_order` — ติ๊ก parent = ติ๊กลูกทั้งหมด
- บันทึกด้วย `->actions()->sync([...])`

**Permission code (เสนอ)** — `system.usergroup.view`, `system.usergroup.create`, `system.usergroup.delete`
(ชุด `system.action.*` สำหรับจัดการรายการสิทธิ์เอง — ทำภายหลังหรือ seed อย่างเดียว)

**Route (เสนอ)** — `admin.system.usergroups.*` ใต้ `/admin/system/usergroups`

**หมายเหตุ** — ยังไม่มี middleware/gate บังคับสิทธิ์รวมศูนย์ (โค้ด `abort(403)` ถูก comment ไว้ใน controller)
เฟส 1 ควรเพิ่ม route middleware เช่น `can:system.user.view` หรือ middleware กำหนดเองที่อ่าน `hasPermission()`

---

## 3. จัดการเมนู

มี 2 ชุดที่คนละเรื่องกัน: **เมนูหลังบ้าน** (sidebar admin) และ **เมนูหน้าบ้าน** (nav เว็บสาธารณะ)

### 3.1 เมนูหลังบ้าน (admin sidebar) — 🟡 มีตาราง + seed ตัวอย่างแล้ว, ยังไม่ต่อ UI

**วัตถุประสงค์** — เก็บโครงเมนู sidebar หลังบ้านใน DB แทนการฮาร์ดโค้ดใน
`resources/js/Components/Admin/AppSidebar.vue` โครง 2 ระดับ: กลุ่ม → เมนูย่อย (ลิงก์ไปโมดูล)

**Data model — `sys_menu_group`** (migration `2026_09_08_000001_create_sys_menu_tables.php`)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `varchar(20)` PK | กำหนดเอง — ใช้ชุดเดียวกับ `sys_action_group` (`article`, `banner`, `popup`, `intropage`, `page`, `contactus`, `system`) |
| `name` | `varchar(100)` | ชื่อกลุ่มที่แสดง |
| `icon` | `varchar(40)` null | ชื่อไอคอน lucide (PascalCase) เช่น `Settings` — ดูรายการที่รองรับใน `resources/js/Components/Admin/menuIcons.ts` |
| `sort_order` | `unsigned int` default 0 | ลำดับการแสดงผล |
| `status` | `char(1)` default `Y` | `Y` = แสดง, `N` = ซ่อน |
| `timestamps`, `deleted_at` | | softDeletes |

**Data model — `sys_menu`**

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `varchar(30)` PK | กำหนดเอง เช่น `system-user` |
| `menu_group_id` | `varchar(20)` FK → `sys_menu_group` (cascadeOnDelete) | กลุ่มที่สังกัด |
| `name` | `varchar(100)` | ชื่อเมนูที่แสดง |
| `icon` | `varchar(40)` null | ชื่อไอคอน lucide (PascalCase) — map ใน `menuIcons.ts`, null = ใช้ fallback (`Circle`) |
| `route_name` | `varchar(100)` null | ชื่อ route เพื่อลิงก์ไปโมดูล เช่น `admin.system.users.index` |
| `sort_order` | `unsigned int` default 0 | ลำดับในกลุ่ม |
| `action_code` | `varchar(100)` null | ค่า `sys_action.code` — เงื่อนไขแสดงเมนู (เช็กในโค้ดด้วย `hasPermission()`; ไม่มี FK เพราะ action อาจยังไม่ถูก seed); `null` = แสดงเสมอ |
| `status` | `char(1)` default `Y` | `Y` = แสดง, `N` = ซ่อน |
| `timestamps`, `deleted_at` | | softDeletes |

**Model** — `App\Models\SysMenuGroup` (`menus()` hasMany) / `App\Models\SysMenu` (`group()` belongsTo) — string key, `SoftDeletes`

**Seeder** — `Database\Seeders\MenuSeeder` (เรียกจาก `DatabaseSeeder` และรันเดี่ยวได้ด้วย
`php artisan db:seed --class=MenuSeeder`; `updateOrCreate` → รันซ้ำได้) — 7 กลุ่ม + 23 เมนู (มี `icon` ครบ)
`action_code` ของทุกเมนูตรงกับ `sys_action.code` ที่ seed ใน `DatabaseSeeder` แล้ว;
`route_name` ยังเป็น route ที่คาดว่าจะมี (เจ้าของโปรเจกต์จะเข้าไปปรับเพิ่ม)

**การแสดงผล (ทำแล้ว)** — `HandleInertiaRequests::adminMenu()` แชร์ prop `menu` (เฉพาะ `user_type='back'`):
- กลุ่ม `status='Y'` เรียงตาม `sort_order`; เมนูย่อย `status='Y'` เรียงตาม `sort_order`
- เมนูย่อยที่มี `action_code` ต้องมีสิทธิ์นั้น (`getPermissionsArray()`) ถึงจะติดมา; `action_code = null` = แสดงเสมอ
- กลุ่มที่ไม่เหลือเมนูย่อยเลย → ตัดออก
- ส่ง `icon` (ชื่อ) + `href` (resolve จาก `route_name` ถ้ามี route จริง `Route::has`, ไม่งั้น `null`) ไปด้วย
- `AppSidebar.vue` — Dashboard + กลุ่มเมนูจาก DB อยู่ **section เดียวกัน ("เมนู")**; `SidebarGroup.vue` =
  หัวข้อกลุ่ม (ไอคอน + toggle เปิด/ปิด, จำสถานะ `localStorage` `admin.sidebar.group.<id>`, กางอัตโนมัติเมื่อ
  route ปัจจุบันอยู่ในกลุ่ม) + เมนูย่อย (ไอคอน + `<Link>` ถ้ามี `href`, ไม่งั้นข้อความจาง). โหมด sidebar ย่อ
  แสดงเฉพาะไอคอนกลุ่ม กดแล้วกาง sidebar + เปิดกลุ่ม
- ไอคอน: DB เก็บชื่อ lucide แบบ PascalCase, frontend map เป็น component ใน
  `resources/js/Components/Admin/menuIcons.ts` (curated เพื่อ tree-shake) — เพิ่มไอคอนใหม่ = import แล้วใส่ในแมพนั้น

**ที่เหลือต้องทำ** — หน้า CRUD จัดกลุ่มเมนู/เมนู; ไอคอนต่อเมนู (ยังไม่มีคอลัมน์ icon)

**Permission** — `system.menu.view`, `system.menu.manage`, `system.menu.delete`

**Route (เสนอ)** — `admin.system.menu.*` ใต้ `/admin/system/menu`

### 3.2 เมนูหน้าบ้าน (public nav) — 🔴 เสนอ

**วัตถุประสงค์** — จัดเมนูนำทางของเว็บหน้าบ้านแบบ tree ต่อภาษา ผูกกับหน้า (page), โมดูล หรือ URL ภายนอก

**Data model เสนอ — `sys_front_menu`** (แยกจาก `sys_menu` ของหลังบ้าน)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK | |
| `parent_id` | bigint null FK → `sys_front_menu` | tree |
| `lang` | `char(2)` | `th` / `en` |
| `title` | `varchar(150)` | ข้อความเมนู |
| `type` | `varchar(20)` | `page` / `module` / `url` / `label` |
| `target_id` | bigint null | id ของ page/หมวด เมื่อ `type` = page/module |
| `url` | `varchar(255)` null | เมื่อ `type = url` |
| `open_new_tab` | boolean default false | |
| `sort_order` | unsigned int default 0 | |
| `status` | `char(1)` default `Y` | |
| `timestamps`, `deleted_at` | | |

**หน้าจอ** — ตัวจัดเรียง tree (drag & drop), ฟอร์มต่อโหนด, สลับภาษา — หน้าบ้าน query ตาม locale แล้ว render

**Permission** — `system.frontmenu.view`, `system.frontmenu.create`, `system.frontmenu.delete`

---

## 4. จัดการ template — 🔴 เสนอ

**วัตถุประสงค์** — กำหนดชุดการแสดงผล layout ของหน้าบ้าน (header / footer / โครงหน้า) เลือกใช้ต่อ page ได้

**Data model เสนอ — `sys_template`**

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `varchar(20)` PK | เช่น `default`, `landing` |
| `name` | `varchar(100)` | ชื่อที่แสดง |
| `header_html` / `footer_html` | `longtext` null | หรือเก็บเป็น config JSON |
| `config` | `json` null | ตัวเลือก layout (มี sidebar, ความกว้าง, ฯลฯ) |
| `is_default` | boolean default false | |
| `status` | `char(1)` default `Y` | |
| `timestamps`, `deleted_at` | | |

**หน้าจอ** — list, ฟอร์มแก้ไข (+ preview), ตั้ง default

**Permission** — `system.template.view`, `system.template.create`, `system.template.delete`

---

## 5. ประวัติ login / เข้าชม / การกระทำ — 🔴 เสนอ (นอกขอบเขตเฟส 0)

**วัตถุประสงค์** — เก็บ log เพื่อตรวจสอบย้อนหลังและวิเคราะห์การเข้าชม

| ตาราง (เสนอ) | เก็บอะไร | คอลัมน์หลัก |
|--------------|---------|-------------|
| `sys_log_login` | ทุกครั้งที่พยายามล็อกอินหลังบ้าน | `user_id` null, `email`, `ip`, `user_agent`, `success` bool, `created_at` |
| `sys_log_visit` | การเข้าชมหน้าบ้าน | `url`, `lang`, `ip`, `user_agent`, `referer`, `session_id`, `created_at` |
| `sys_log_action` | การกระทำในหลังบ้าน (สร้าง/แก้/ลบ) | `user_id`, `action` (`create`/`update`/`delete`), `model`, `model_id`, `changes` json, `ip`, `created_at` |

**การเก็บ** — `sys_log_login` ผูกกับ event `Login`/`Failed`; `sys_log_visit` ผ่าน middleware บน route หน้าบ้าน;
`sys_log_action` ผ่าน model observer หรือ trait กลาง

**หน้าจอ** — 3 หน้ารายการ (ตาราง + กรองช่วงวันที่/ผู้ใช้), export CSV

**Permission** — `system.log.login`, `system.log.visit`, `system.log.action`

**หมายเหตุ**
- พิจารณา retention (ลบอัตโนมัติเกิน N วัน) ผ่าน scheduled command
- คนละส่วนกับ `sys_user.last_login_at` / `failed_login_count` / `last_failed_login_at` — ฟิลด์บน `sys_user`
  เก็บ "สถานะล่าสุด" ต่อผู้ใช้ (ใช้ประกอบการล็อกบัญชี) ส่วน `sys_log_login` เก็บประวัติทุกครั้งแบบ append
- การล็อกบัญชีเมื่อ `failed_login_count` เกินเกณฑ์: อ่านเกณฑ์จาก `sys_setting` (เสนอกลุ่ม `security`,
  key `max_failed_login`, `lockout_minutes`) — เมื่อถึงเกณฑ์ให้บล็อก login จนกว่าจะพ้นเวลา/แอดมินรีเซ็ต

---

## 6. ตั้งค่าระบบ/เว็บไซต์

**วัตถุประสงค์** — เก็บค่าตั้งค่าทั่วไปแบบ key-value จัดกลุ่มด้วย `group` (เช่น `site`, `contact`, `seo`, `social`)

**Data model — `sys_setting`** (สร้างในเฟส 0)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `group` | `varchar(50)` | ส่วนหนึ่งของ composite PK — เช่น `site` |
| `name` | `varchar(100)` | ส่วนหนึ่งของ composite PK — เช่น `site_name` |
| `value` | `text` null | ค่า (string/JSON แล้วแต่ key) |
| `timestamps`, `deleted_at` | | timestamps + softDeletes |

primary key = `(group, name)` — Eloquent ไม่รองรับ composite key เต็มรูปแบบ ให้ค้นด้วย
`SysSetting::where('group', ...)->where('name', ...)` (ไม่ใช้ `find()`)

> `group` เป็นคำสงวนของ MySQL — schema builder / query builder ของ Laravel quote ให้อัตโนมัติ
> แต่ raw SQL ต้องใส่ backtick `` `group` `` เอง

**Seed ตัวอย่าง (เฟส 0)** — กลุ่ม `site`: `site_name`, `site_email`, `site_description`

**หน้าจอ** — ฟอร์มแยกแท็บตาม group; แต่ละ field map กับ 1 แถว; บันทึกทั้งกลุ่มพร้อมกัน (upsert)

**helper (เสนอ)** — `setting('site.site_name', $default)` ที่ cache รวมทั้งตารางไว้ (invalidate ตอนบันทึก)

**Permission** — `system.setting.view`, `system.setting.update`

---

## 7. profile — 🟢 มีแล้ว

**วัตถุประสงค์** — ให้ผู้ใช้ที่ล็อกอินแก้ข้อมูลบัญชีตัวเอง

**หน้าจอ** — `resources/js/Pages/Admin/Profile/Edit.vue` + partials
- `UpdateProfileInformationForm` — คำนำหน้า / ชื่อ / นามสกุล / เบอร์มือถือ / เบอร์ติดต่อ / LINE / Facebook / อีเมล
  (ปรับให้รองรับ field ใหม่ในเฟส 0)
- `UpdatePasswordForm` — เปลี่ยนรหัสผ่าน
- `DeleteUserForm` — ลบบัญชีตัวเอง (ยืนยันด้วยรหัสผ่าน; ปัจจุบันลบจริง)

**Controller** — `Admin\ProfileController` (`edit` / `update` ผ่าน `ProfileUpdateRequest` / `destroy`)

**Route** — `admin.profile.edit|update|destroy` ใต้ `/admin/profile`

---

## 8. dashboard — 🟡 placeholder

**หน้าจอ** — `resources/js/Pages/Admin/Dashboard.vue`: การ์ดสถิติ (ตอนนี้ค่า "—") + รายการสถานะสิทธิ์

**Controller** — `Admin\DashboardController@index` ส่ง `can` (ผลของ `hasPermission()`) เป็น props

**Route** — `admin.dashboard` ใต้ `/admin/dashboard`

**เป้าหมาย** — เมื่อมีโมดูลจริง: จำนวนบทความ/หน้า/ผู้ใช้, ข้อความติดต่อที่ยังไม่อ่าน, กราฟการเข้าชม (จาก `sys_log_visit`)

---

## 9. file management — 🔴 เสนอ

**วัตถุประสงค์** — คลังไฟล์/รูปกลางสำหรับใช้ในทุกโมดูล (แนบใน rich text, รูปปก, banner ฯลฯ)

**Data model เสนอ — `sys_file`**

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK | |
| `disk` | `varchar(20)` default `public` | ใช้ disk `public` ที่มีอยู่ (`storage/app/public` → `/storage`) |
| `path` | `varchar(255)` | path บน disk |
| `original_name` | `varchar(255)` | |
| `mime` | `varchar(100)` | |
| `size` | unsigned bigint | ไบต์ |
| `folder` | `varchar(255)` null | โฟลเดอร์เสมือนสำหรับจัดหมวด |
| `uploaded_by` | bigint FK → `sys_user` null | |
| `timestamps`, `deleted_at` | | |

**หน้าจอ** — ตัวเลือกไฟล์ (grid + อัปโหลด drag & drop + เลือกโฟลเดอร์), ใช้เป็น modal picker ในฟอร์มโมดูล

**Permission** — `system.file.view`, `system.file.upload`, `system.file.delete`

**หมายเหตุ** — ต้องรัน `php artisan storage:link` (config ไว้แล้ว) ; พิจารณาจำกัดชนิด/ขนาดไฟล์ใน config

---

## ภาคผนวก: สเปก schema รอบนี้

ไฟล์เดียว: `database/migrations/0001_01_01_000000_create_users_table.php` — ต้อง `php artisan migrate:fresh --seed` ใหม่

### `sys_user` — before → after

```
- name                    varchar(255)
+ titlename               varchar(30)  NULL          // คำนำหน้า
+ firstname               varchar(100)               // ชื่อ
+ lastname                varchar(100)               // นามสกุล
  email                   varchar(255) unique → varchar(150) INDEX (ไม่ unique — เช็กในโค้ด)
  password                varchar(255)               // คงเดิม
  user_type               varchar(20)  default 'back'// back = หลังบ้าน, front = หน้าบ้าน
+ mobile                  varchar(20)  NULL
+ phone                   varchar(30)  NULL
+ line                    varchar(100) NULL
+ facebook                varchar(150) NULL
+ status                  char(1)      default 'Y'   // Y=ใช้งาน, N=ถูกระงับ
+ last_login_at           timestamp    NULL          // login สำเร็จล่าสุด
+ failed_login_count      smallint unsigned default 0// login ไม่สำเร็จติดต่อกัน
+ last_failed_login_at    timestamp    NULL          // login ไม่สำเร็จล่าสุด
  usergroup_id            FK sys_usergroup nullOnDelete // คงเดิม
  deleted_at              (softDeletes) — User ใช้ trait SoftDeletes แล้ว
```

### `sys_usergroup`

```
  name                    varchar(255) → varchar(100)
  description             varchar(255) NULL           // คงเดิม (ระบุขนาดชัด)
+ status                  char(1)      default 'Y'
```

### `sys_action_group`

```
- id                      bigint auto-increment
+ id                      varchar(20)  PRIMARY KEY
  name                    varchar(255) → varchar(100)
  sort_order              int → unsigned int default 0
+ status                  char(1)      default 'Y'
```

### `sys_action`

```
- id                      bigint auto-increment
+ id                      varchar(20)  PRIMARY KEY
- action_group_id         bigint FK
+ action_group_id         varchar(20)  FK sys_action_group  cascadeOnDelete
+ parent_id               varchar(20)  NULL FK sys_action    nullOnDelete   // tree
  code                    varchar(255) → varchar(100) unique
  name                    varchar(255) → varchar(150)
+ sort_order              unsigned int default 0
```

### `sys_usergroup_action`

```
  usergroup_id            bigint FK sys_usergroup cascadeOnDelete           // คงเดิม
- action_id               bigint FK
+ action_id               varchar(20)  FK sys_action cascadeOnDelete
  PRIMARY KEY (usergroup_id, action_id) + timestamps                       // คงเดิม
```

### `sys_setting` — ใหม่

```
+ group                   varchar(50)   ┐ composite
+ name                    varchar(100)  ┘ PRIMARY KEY (group, name)
+ value                   text          NULL
+ timestamps
+ deleted_at              (softDeletes)
```

### `front_password_reset_tokens` — ใหม่

```
+ email                   varchar(255)  PRIMARY KEY   // โครงเดียวกับ password_reset_tokens
+ token                   varchar(255)
+ created_at              timestamp     NULL
```
โทเคนรีเซ็ตรหัสผ่านของผู้ใช้ `front` (broker `front` ใน `config/auth.php`) แยกจาก `password_reset_tokens`
ของผู้ใช้ `back` — ให้ email เดียวกันมีโทเคนรีเซ็ตของทั้งสองประเภทพร้อมกันได้

### `sys_menu_group` / `sys_menu` — ใหม่ (แยกไฟล์ `2026_09_08_000001_create_sys_menu_tables.php`)

```
sys_menu_group
+ id                      varchar(20)  PRIMARY KEY
+ name                    varchar(100)
+ icon                    varchar(40)  NULL   // ชื่อไอคอน lucide (PascalCase)
+ sort_order              unsigned int default 0
+ status                  char(1)      default 'Y'
+ timestamps + deleted_at (softDeletes)

sys_menu
+ id                      varchar(30)  PRIMARY KEY
+ menu_group_id           varchar(20)  FK sys_menu_group  cascadeOnDelete
+ name                    varchar(100)
+ icon                    varchar(40)  NULL   // ชื่อไอคอน lucide (PascalCase)
+ route_name              varchar(100) NULL   // ชื่อ route ลิงก์ไปโมดูล
+ sort_order              unsigned int default 0
+ action_code             varchar(100) NULL   // sys_action.code — เช็กในโค้ด ไม่มี FK
+ status                  char(1)      default 'Y'
+ timestamps + deleted_at (softDeletes)
+ INDEX (menu_group_id, sort_order)
```

### ผลกระทบต่อโค้ด (ทำครบในเฟส 0)

| ไฟล์ | การเปลี่ยน |
|------|-----------|
| `config/auth.php` | เพิ่ม password broker `front` (ตาราง `front_password_reset_tokens`) |
| `app/Models/User.php` | `fillable` ใหม่ + accessor `name` + trait `SoftDeletes` + cast `last_login_at`/`last_failed_login_at` |
| `app/Models/UserGroup.php` | `fillable` เพิ่ม `status` |
| `app/Models/SysActionGroup.php` | `$incrementing=false`, `$keyType='string'`, `actions()` hasMany, `fillable` มี `status` (คอลัมน์ `sys_action_group.status`) |
| `app/Models/SysAction.php` | `$incrementing=false`, `$keyType='string'`, `group()`/`parent()`/`children()` |
| `app/Models/SysSetting.php` | **ไฟล์ใหม่** — softDeletes, key เป็น string, ไม่ใช้ `find()` |
| `app/Models/SysMenuGroup.php`, `app/Models/SysMenu.php` | **ไฟล์ใหม่** — string key, softDeletes, `menus()`/`group()` |
| `database/seeders/MenuSeeder.php` | **ไฟล์ใหม่** — 7 กลุ่ม + 23 เมนู (`updateOrCreate`, `action_code` ตรงกับ `sys_action.code`) |
| `database/migrations/2026_09_08_000001_create_sys_menu_tables.php` | **ไฟล์ใหม่** — `sys_menu_group` + `sys_menu` |
| `database/seeders/DatabaseSeeder.php` | 7 action group + 48 action (tree ผ่าน `parent_id`) + Super Admin (sync ทุกสิทธิ์) + admin user + `sys_setting`; ทุกจุดเป็น `updateOrCreate`/`sync` (รันซ้ำได้) |
| `database/factories/UserFactory.php` | field ใหม่ + `user_type='back'`; state `front()` / `inactive()` |
| `app/Http/Requests/ProfileUpdateRequest.php` | rule field ใหม่ (`email` max 150) + `unique` scope `user_type` + `whereNull('deleted_at')` |
| `app/Http/Requests/Auth/LoginRequest.php` | `authenticate()` เช็ก `user_type='back'`+`status='Y'`, ข้อความ block, บันทึกสถิติ login สำเร็จ/ไม่สำเร็จ |
| `app/Http/Controllers/Admin/Auth/RegisteredUserController.php` | สร้างด้วย `user_type='back'`,`status='Y'` + `unique` scope `user_type='back'` + `whereNull('deleted_at')` |
| `app/Http/Controllers/Admin/Auth/PasswordResetLinkController.php`, `NewPasswordController.php` | ใช้ `Password::broker('users')` + credential `user_type='back'` |
| `tests/Feature/Auth/PasswordResetTest.php` | เทส front user ขอ reset ไม่ได้ + โทเคน back/front ของ email เดียวกันแยกกัน |
| `app/Http/Middleware/HandleInertiaRequests.php` | แชร์ข้อมูล user (`titlename`…`permissions`) + prop `menu` (`adminMenu()` — sidebar หลังบ้านกรองตามสิทธิ์) |
| `resources/js/Components/Admin/AppSidebar.vue` | render prop `menu` เป็น section เดียวกับ Dashboard |
| `resources/js/Components/Admin/SidebarGroup.vue` | **ไฟล์ใหม่** — กลุ่มเมนู (ไอคอน + กดเปิด/ปิด, localStorage) + เมนูย่อย + โหมด rail |
| `resources/js/Components/Admin/menuIcons.ts` | **ไฟล์ใหม่** — map ชื่อไอคอน (PascalCase) → lucide component (curated) |
| `resources/js/types/index.d.ts` | เพิ่ม `MenuItem`/`MenuGroup` (มี `icon`) + `menu` ใน `PageProps` |
| `resources/js/types/index.d.ts` | `User` interface ใหม่ |
| `resources/js/Pages/Admin/Profile/Partials/UpdateProfileInformationForm.vue` | ฟอร์มชื่อ + ช่องทางติดต่อ |
| `resources/js/Pages/Admin/Auth/Register.vue` | ฟอร์ม คำนำหน้า/ชื่อ/นามสกุล |
| `tests/Feature/ProfileTest.php` | payload field ใหม่ + `assertSoftDeleted` |
| `tests/Feature/Auth/RegistrationTest.php` | payload field ใหม่ + เทส email ซ้ำข้าม `user_type` / หลัง soft delete |
| `tests/Feature/Auth/AuthenticationTest.php` | เทส front user / บัญชีถูกระงับ / บันทึกสถิติ login |
