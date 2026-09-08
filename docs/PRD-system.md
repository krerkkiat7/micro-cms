# PRD — ส่วนจัดการระบบ (System Management)

เอกสารนี้ลงรายละเอียดของกลุ่มเมนู **จัดการระบบ** ในหลังบ้าน ภาพรวมทั้งระบบดูที่
[PRD-overview.md](PRD-overview.md)

สถานะ: 🟢 มีแล้ว · 🟡 มีบางส่วน · 🔴 ยังไม่มี

| # | หัวข้อ | ตารางหลัก | สถานะ |
|---|--------|-----------|-------|
| 1 | จัดการผู้ใช้งาน | `sys_user` | 🟡 ตาราง/model มี, ยังไม่มี UI |
| 2 | จัดการกลุ่มผู้ใช้งาน + สิทธิ์ | `sys_usergroup`, `sys_action_group`, `sys_action`, `sys_usergroup_action` | 🟡 ตาราง/model/seeder มี, ยังไม่มี UI |
| 3 | จัดการเมนู (หน้าบ้าน) | `sys_menu` *(เสนอ)* | 🔴 |
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
| `email` | `varchar(150)` unique | ใช้ล็อกอิน |
| `email_verified_at` | timestamp null | |
| `password` | `varchar(255)` | hash |
| `user_type` | `varchar(20)` default `back` | `back` = ผู้ดูแล (ช่องทางเผื่อผู้ใช้หน้าบ้านในอนาคต) |
| `mobile` | `varchar(20)` null | เบอร์มือถือ |
| `phone` | `varchar(30)` null | เบอร์ติดต่อ |
| `line` | `varchar(100)` null | LINE id |
| `facebook` | `varchar(150)` null | facebook (url/handle) |
| `status` | `char(1)` default `Y` | `Y` = ใช้งาน, `N` = ไม่ใช้งาน |
| `usergroup_id` | bigint FK → `sys_usergroup` null (nullOnDelete) | กลุ่มสิทธิ์ |
| `remember_token`, `timestamps`, `deleted_at` | | softDeletes (คอลัมน์มี, model ยังไม่เปิดใช้ trait) |

**Model** `App\Models\User` — accessor `name` คืน `"คำนำหน้า ชื่อ นามสกุล"` (ใช้แสดงผลใน UI/แชร์ Inertia)

**หน้าจอ**
- `รายการผู้ใช้` — ตาราง (ชื่อ, อีเมล, กลุ่ม, สถานะ), ค้นหา, กรองตามกลุ่ม/สถานะ
- `ฟอร์มเพิ่ม/แก้ไข` — ข้อมูลชื่อ + ช่องทางติดต่อ + กลุ่มผู้ใช้ + สถานะ + ตั้ง/รีเซ็ตรหัสผ่าน
- ปิดการใช้งาน (ตั้ง `status = 'N'`) แทนการลบจริงเป็นค่าเริ่มต้น

**Permission code** — `system.user.view`, `system.user.create` (รวมแก้ไข), `system.user.delete`

**Route (เสนอ)** — `admin.system.users.index|create|store|edit|update|destroy` ใต้ `/admin/system/users`

**หมายเหตุ**
- ตอน login ต้องเพิ่มเงื่อนไข `status = 'Y'` (ปัจจุบัน `LoginRequest` เช็กแค่ email/password) — ทำในเฟส 1
- `RegisteredUserController` สร้างผู้ใช้ใหม่โดยไม่กำหนด `usergroup_id` → `getPermissionsArray()` คืน `[]` จนกว่าจะถูกจัดกลุ่ม

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
| `id` | `varchar(20)` PK | กำหนดเอง เช่น `system-user` |
| `name` | `varchar(100)` | เช่น "จัดการผู้ใช้งาน" |
| `sort_order` | unsigned int default 0 | ลำดับแสดง |
| `timestamps`, `deleted_at` | | |

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

## 3. จัดการเมนู (หน้าบ้าน) — 🔴 เสนอ

**วัตถุประสงค์** — จัดเมนูนำทางของเว็บหน้าบ้านแบบ tree ต่อภาษา ผูกกับหน้า (page), โมดูล หรือ URL ภายนอก

**Data model เสนอ — `sys_menu`**

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK | |
| `parent_id` | bigint null FK → `sys_menu` | tree |
| `lang` | `char(2)` | `th` / `en` |
| `title` | `varchar(150)` | ข้อความเมนู |
| `type` | `varchar(20)` | `page` / `module` / `url` / `label` |
| `target_id` | bigint null | id ของ page/หมวด เมื่อ `type` = page/module |
| `url` | `varchar(255)` null | เมื่อ `type = url` |
| `open_new_tab` | boolean default false | |
| `sort_order` | unsigned int default 0 | |
| `status` | `char(1)` default `Y` | |
| `timestamps`, `deleted_at` | | |

**หน้าจอ** — ตัวจัดเรียง tree (drag & drop), ฟอร์มต่อโหนด, สลับภาษา

**Permission** — `system.menu.view`, `system.menu.create`, `system.menu.delete`

**หมายเหตุ** — ปัจจุบันเมนู sidebar หลังบ้านฮาร์ดโค้ดใน `resources/js/Components/Admin/AppSidebar.vue`
ส่วนนี้เป็นเมนู **หน้าบ้าน** คนละชุดกัน หน้าบ้านต้อง query `sys_menu` ตาม locale แล้ว render

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

**หมายเหตุ** — พิจารณา retention (ลบอัตโนมัติเกิน N วัน) ผ่าน scheduled command

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
  email                   varchar(255) → varchar(150) unique
  password                varchar(255)               // คงเดิม
  user_type               varchar(20)  default 'back'// คงเดิม
+ mobile                  varchar(20)  NULL
+ phone                   varchar(30)  NULL
+ line                    varchar(100) NULL
+ facebook                varchar(150) NULL
+ status                  char(1)      default 'Y'   // Y=ใช้งาน, N=ไม่ใช้งาน
  usergroup_id            FK sys_usergroup nullOnDelete // คงเดิม
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

### ผลกระทบต่อโค้ด (ทำครบในเฟส 0)

| ไฟล์ | การเปลี่ยน |
|------|-----------|
| `app/Models/User.php` | `fillable` ใหม่ + accessor `name` (คำนำหน้า+ชื่อ+นามสกุล) |
| `app/Models/UserGroup.php` | `fillable` เพิ่ม `status` |
| `app/Models/SysActionGroup.php` | `$incrementing=false`, `$keyType='string'`, `actions()` hasMany |
| `app/Models/SysAction.php` | `$incrementing=false`, `$keyType='string'`, `group()`/`parent()`/`children()` |
| `app/Models/SysSetting.php` | **ไฟล์ใหม่** — softDeletes, key เป็น string, ไม่ใช้ `find()` |
| `database/seeders/DatabaseSeeder.php` | id เป็น string, `status`, ชื่อผู้ใช้แยกส่วน, seed `sys_setting` กลุ่ม `site` |
| `database/factories/UserFactory.php` | `titlename`/`firstname`/`lastname`/`status` แทน `name` |
| `app/Http/Requests/ProfileUpdateRequest.php` | rule ตาม field ใหม่ (`email` max 150) |
| `app/Http/Controllers/Admin/Auth/RegisteredUserController.php` | validate/create ด้วย `firstname`/`lastname`/`titlename` |
| `app/Http/Middleware/HandleInertiaRequests.php` | แชร์ `titlename`/`firstname`/`lastname`/`name`/`mobile`/`phone`/`line`/`facebook`/`permissions` |
| `resources/js/types/index.d.ts` | `User` interface ใหม่ |
| `resources/js/Pages/Admin/Profile/Partials/UpdateProfileInformationForm.vue` | ฟอร์มชื่อ + ช่องทางติดต่อ |
| `resources/js/Pages/Admin/Auth/Register.vue` | ฟอร์ม คำนำหน้า/ชื่อ/นามสกุล |
| `tests/Feature/ProfileTest.php`, `tests/Feature/Auth/RegistrationTest.php` | payload/assertion ตาม field ใหม่ |
