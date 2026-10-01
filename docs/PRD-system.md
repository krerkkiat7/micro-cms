# PRD — ส่วนจัดการระบบ (System Management)

เอกสารนี้ลงรายละเอียดของกลุ่มเมนู **จัดการระบบ** ในหลังบ้าน ภาพรวมทั้งระบบดูที่
[PRD-overview.md](PRD-overview.md)

สถานะ: 🟢 มีแล้ว · 🟡 มีบางส่วน · 🔴 ยังไม่มี

| # | หัวข้อ | ตารางหลัก | สถานะ |
|---|--------|-----------|-------|
| 1 | จัดการผู้ใช้งาน | `sys_user` | 🟢 list/add/edit/เปลี่ยนรหัสผ่าน เสร็จ (ต้นแบบ §5) |
| 2 | จัดการกลุ่มผู้ใช้งาน + สิทธิ์ | `sys_usergroup`, `sys_action_group`, `sys_action`, `sys_usergroup_action` | 🟢 list/add/edit + หน้ากำหนดสิทธิ์ (tree) เสร็จ |
| 3 | จัดการเมนู (หลังบ้าน / หน้าบ้าน) | `sys_menu_group`, `sys_menu` / `front_menu_info`+`front_menu_detail` | 🟡 เมนูหลังบ้าน: ตาราง/model/seed ตัวอย่างมี, ยังไม่ต่อ UI · หน้าบ้าน: 🟢 admin CRUD + render หน้าบ้าน ([PRD-front.md](PRD-front.md)) |
| 4 | จัดการ template | `sys_template` + `sys_template_header/body/footer/aside` | 🟡 หลังบ้านครบ (list/add/ข้อมูลทั่วไป/โครงสร้าง+preview/Custom CSS/JS/Loading) · render หน้าบ้าน 🟢 — [PRD-system-template.md](PRD-system-template.md), [PRD-front.md](PRD-front.md) §3 |
| 5 | ประวัติ login / เข้าชม / การกระทำ | `log_back_access`, `log_back_action`, `log_back_login` (+ `log_front_*`) | 🟡 หลังบ้านครบ 3 ตัว (บันทึก + หน้ารายการ) · `log_front_access` 🟢 · `log_front_action`/`log_front_login` 🔴 |
| 6 | ตั้งค่าระบบ/เว็บไซต์ | `sys_setting` | 🟡 ตาราง/model/seed ตัวอย่างมี, ยังไม่มี UI |
| 7 | profile | `sys_user` | 🟢 |
| 8 | dashboard | — | 🟢 มีแล้ว |
| 9 | file management | `file_info`, `folder_info` | 🟢 หน้าเต็ม + dialog เลือกไฟล์ (reusable, ยังไม่ผูกฟิลด์จริง) เสร็จ |

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
| `password_changed_at` | timestamp null | เวลาที่ตั้ง/เปลี่ยนรหัสผ่านครั้งล่าสุด (ตั้งตอนสร้างผู้ใช้ + ตอนเปลี่ยนรหัสผ่าน) |
| `password_changed_by` | bigint null (`sys_user.id`, ไม่มี FK) | ผู้ตั้ง/เปลี่ยนรหัสผ่านครั้งล่าสุด |
| `created_by` / `updated_by` / `deleted_by` | bigint null (`sys_user.id`, ไม่มี FK) | ผู้สร้าง / ผู้แก้ไขล่าสุด / ผู้ลบ — controller เซ็ตจาก `$request->user()->id` |
| `remember_token`, `timestamps`, `deleted_at` | | **soft delete เปิดใช้แล้ว** (`User` ใช้ trait `SoftDeletes`) |

**Model** `App\Models\User` — `SoftDeletes`; accessor `name` คืน `"คำนำหน้า ชื่อ นามสกุล"`;
cast `last_login_at` / `last_failed_login_at` / `password_changed_at` เป็น datetime

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

**ล็อกบัญชีอัตโนมัติ** (ทำแล้ว) — ตั้งค่าระบบกลุ่ม `login_back` (`lockout_enabled` + `lockout_count`): ผิดครบจำนวน →
`status = 'N'` + บันทึก `log_back_login` ผล `block`; ผู้ดูแลเปิดบัญชีคืน (N → Y) ในหน้าแก้ไขผู้ใช้ = รีเซ็ต `failed_login_count` อัตโนมัติ

**ตรวจสถานะทุก request** — middleware `App\Http\Middleware\EnsureBackUserActive` (group หลังบ้านที่ต้อง login): ผู้ใช้ที่ถูกระงับ
ระหว่างที่ยัง login อยู่หลุดทันทีใน request ถัดไป; `auth.session` (`AuthenticateSession`) ทำให้เปลี่ยนรหัสผ่านแล้ว session อื่นของ
ผู้ใช้คนนั้นหลุด; กลุ่มผู้ใช้ที่ `status = 'N'` = ไม่มีสิทธิ์ใด ๆ (`User::getPermissionsArray()` คืน `[]`)

**ไม่มีหน้าสมัครสมาชิกหลังบ้าน** — route `/admin/register` ของ Breeze ถูกลบแล้ว ผู้ใช้หลังบ้านสร้างได้จากหน้าจัดการผู้ใช้งานเท่านั้น

**กันการยกระดับสิทธิ์** (กลุ่มระบบ = `sys_usergroup.can_edit = 'N'` เช่น Super Admin — `UserGroup::isSystem()` / `User::isSystemUser()`):
- เฉพาะผู้ใช้ในกลุ่มระบบเท่านั้นที่แก้ไข/ลบ/เปลี่ยนรหัสผ่านผู้ใช้ในกลุ่มระบบ และย้ายผู้ใช้เข้ากลุ่มระบบได้ —
  ผู้ใช้อื่นเห็นหน้าแก้ไขแบบอ่านอย่างเดียว + ข้อความ `UserController::SYSTEM_GROUP_MESSAGE`, dropdown กลุ่มแสดงกลุ่มระบบเป็น disabled
- **การย้ายผู้ใช้เข้ากลุ่มระบบ:** ให้ผู้ใช้ที่อยู่ในกลุ่มระบบ (เช่น admin@admin.com ที่ seed ไว้) เข้าหน้าแก้ไขผู้ใช้คนนั้นแล้วเลือกกลุ่มระบบ
- ห้ามกำหนดสิทธิ์ให้กลุ่มของตัวเอง (หน้ากำหนดสิทธิ์ของกลุ่มตัวเองเป็นแบบอ่านอย่างเดียว)

**หน้าจอ**
- `รายการผู้ใช้` — ตาราง (ชื่อ, อีเมล, กลุ่ม, สถานะ, login ล่าสุด), ค้นหา, กรองตามกลุ่ม/สถานะ
- `ฟอร์มเพิ่ม/แก้ไข` — ข้อมูลชื่อ + ช่องทางติดต่อ + รูปโปรไฟล์ (ต่อจาก Facebook — เลือกได้ 1 รูปผ่าน
  `FilePickerField.vue` จำกัดเฉพาะนามสกุลรูปภาพ, เลือกใหม่ = แทนที่รูปเดิม, เก็บใน `sys_user.profile_image_id`
  อ้างอิง `file_info.id` — validate แค่ว่าไฟล์นั้นมีอยู่จริงและยัง `status='Y'` เท่านั้น ไม่จำกัดว่าต้องเป็นไฟล์
  ของใคร)
  + กลุ่มผู้ใช้ + สถานะ + ตั้ง/รีเซ็ตรหัสผ่าน + ปุ่มรีเซ็ต `failed_login_count`
- ลบผู้ใช้ = soft delete; ระงับชั่วคราวใช้ `status = 'N'`
- **audit ผู้กระทำ** — สร้าง: `created_by` + `password_changed_at`/`password_changed_by` (ตั้งรหัสผ่านครั้งแรก);
  แก้ไข: `updated_by`; ลบ: `deleted_by` (save ก่อน soft delete); เปลี่ยนรหัสผ่าน: `password_changed_at`/`password_changed_by` + `updated_by`

**Permission code** — `system.user.view`, `system.user.create` (รวมแก้ไข), `system.user.delete`

**Route (เสนอ)** — `admin.system.users.index|create|store|edit|update|destroy` ใต้ `/admin/system/users`


---

## 2. จัดการกลุ่มผู้ใช้งาน + กำหนดสิทธิ์

> **สถานะ:** ครบทั้ง 4 หน้าจอตาม [PRD-overview §5](PRD-overview.md#5-แบบแผนหน้าจอหลังบ้าน-back-office-screen-blueprint) —
> `Admin\System\UsergroupController` + `resources/js/Pages/Admin/System/Usergroup/*`, route `admin.system.usergroup.*` (เอกพจน์),
> permission `system.usergroup.view/manage/delete/rights` (ตาม seeder). **หน้ากำหนดสิทธิ์เสร็จแล้ว** (tree + checkbox parent→ลูก, บันทึก detach+attach)

**วัตถุประสงค์** — นิยาม "กลุ่มผู้ใช้" (บทบาท) แล้วติ๊กสิทธิ์ (action) ให้กลุ่ม ผู้ใช้ที่อยู่ในกลุ่มจะได้สิทธิ์ตามนั้น

**Data model**

`sys_usergroup` (ปรับในเฟส 0)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK | |
| `name` | `varchar(100)` | ชื่อกลุ่ม เช่น "Super Admin" |
| `description` | `varchar(255)` null | |
| `status` | `char(1)` default `Y` | `Y`/`N` |
| `can_edit` | `char(1)` default `Y` | `N` = กลุ่มระบบ ห้ามแก้ไข (Super Admin seed เป็น `N`) |
| `can_delete` | `char(1)` default `Y` | `N` = กลุ่มระบบ ห้ามลบ (Super Admin seed เป็น `N`) |
| `created_by` / `updated_by` / `deleted_by` | bigint null (`sys_user.id`, ไม่มี FK) | ผู้สร้าง / ผู้แก้ไขล่าสุด / ผู้ลบ — controller เซ็ตจาก `$request->user()->id` |
| `timestamps`, `deleted_at` | | `SoftDeletes` (เปิด trait บน `UserGroup` แล้ว) |

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
| `created_by` / `updated_by` | bigint null (`sys_user.id`, ไม่มี FK) — ผู้กำหนดสิทธิ์ (หน้ากำหนดสิทธิ์ `attach()` พร้อม pivot นี้) |
| composite PK `(usergroup_id, action_id)` + `timestamps` | |

**Model**
- `UserGroup::actions()` — belongsToMany ผ่าน pivot (`withPivot('created_by', 'updated_by')` + `withTimestamps()`) · `UserGroup::users()` — hasMany (`usergroup_id`) · `HasFactory` + `SoftDeletes`
- `SysActionGroup::actions()` — hasMany
- `SysAction::group()` / `parent()` / `children()`
- `User::hasPermission($code)` / `getPermissionsArray()` — อ่านจาก `group->actions->pluck('code')`

**หน้าจอ** (ครบทั้ง 4)
- ✅ `รายการกลุ่ม` — ตาราง (ชื่อกลุ่ม, รายละเอียด, จำนวนสิทธิ์, จำนวนสมาชิก, สถานะ) + ค้นหา/กรอง/เรียง/paging
- ✅ `ฟอร์มเพิ่ม/แก้ไข` — ชื่อกลุ่ม / รายละเอียด (textarea) / สถานะ; แก้ไขมี tab "ข้อมูลทั่วไป" + "กำหนดสิทธิ์";
  ปุ่มบันทึก/ลบ เคารพ `can_edit`/`can_delete` + guard ชื่อซ้ำ / กลุ่มมีสมาชิก
- ✅ `กำหนดสิทธิ์` (`admin.system.usergroup.rights` + `.rights.update`) — **ต้นไม้สิทธิ์**: กลุ่มตาม `sys_action_group`
  (เรียง `sort_order`, เปิด/ปิดได้เหมือนเมนูข้าง), ภายในแสดง `sys_action` เป็น tree ตาม `parent_id`/`sort_order`;
  checkbox สะท้อน `sys_usergroup_action` — parent ยังไม่ติ๊ก → ลูก disabled + เคลียร์; ต่อกลุ่มมี "(เลือก/ทั้งหมด)" +
  ปุ่ม "เลือกทั้งหมด" / "ไม่เลือกทั้งหมด"; บันทึกแบบ `detach()` แล้ว `attach()` (ลบ pivot จริง);
  server ตัด action ที่ ancestor ไม่ถูกเลือกทิ้ง (`pruneOrphanActions`); `can_edit='N'` แสดงอย่างเดียว; permission `system.usergroup.rights`
- **audit ผู้กระทำ** — สร้าง: `created_by`; แก้ไข: `updated_by`; ลบ: `deleted_by` (save ก่อน soft delete);
  กำหนดสิทธิ์: `attach()` เขียน pivot `created_by`/`updated_by` = ผู้กระทำ + `created_at`/`updated_at` (ผ่าน `withTimestamps()`)

**Permission code (ที่ seed จริง)** — `system.usergroup.view` / `system.usergroup.manage` / `system.usergroup.delete` / `system.usergroup.rights`

**Route (ที่ทำจริง)** — `admin.system.usergroup.*` (เอกพจน์) ใต้ `/admin/system/usergroup`

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

### 3.2 เมนูหน้าบ้าน (public nav) — 🟡 schema + admin CRUD เสร็จแล้ว, ยังไม่ render ที่หน้าบ้าน

**วัตถุประสงค์** — จัดเมนูนำทางของเว็บหน้าบ้านแบบ tree (ผูกกับหน้าเพจ, หมวดหมู่บทความ, บทความ หรือ URL ภายนอก)
พร้อมชุดตั้งค่า "หัวเรื่องของหน้าเป้าหมาย" (รูปพื้นหลัง + หัวเรื่อง/หัวเรื่องรองพร้อมสไตล์ตัวอักษร)

**Data model — `front_menu_info` + `front_menu_detail`** (ตาม pattern `_info`/`_detail` เดียวกับ `article_category_info/detail`,
`page_item_info/detail` — **ไม่ใช่ตารางเดียว `sys_front_menu`** ตามที่เคยเสนอไว้ในรุ่นก่อนหน้าของเอกสารนี้ เพื่อให้
ชื่อเมนู/หัวเรื่อง/หัวเรื่องรองแยกภาษาได้แบบเดียวกับโมดูลอื่น) รายละเอียดครบดู [PRD-system-frontmenu.md](PRD-system-frontmenu.md)

**Model** — `App\Models\FrontMenuInfo` (`parent()`/`children()` self-relation, `SoftDeletes`) / `App\Models\FrontMenuDetail`
(composite key `id`+`lang` เหมือน `PageItemDetail` — ค้นด้วย `where()` เสมอ ห้าม `find()`)

**ประเภทเมนู** — `App\Support\FrontMenuType`: ไม่กำหนด / เมนูหัวข้อ (`heading` — parent ได้อย่างเดียว ไม่มีลิงก์ของตัวเอง) /
ลิงค์ภายนอก / บทความ-รายการตามหมวดหมู่ / บทความ-รายละเอียดบทความ / หน้าเพจ

**หน้าจอ** — `admin.system.menu.index` (`Pages/Admin/System/FrontMenu/Index.vue`) หน้าเดียว: รายการแบบ tree
(`MenuTreeNode.vue` recursive) + ปุ่ม "เพิ่ม"/แก้ไขเปิด `MenuFormDialog.vue` (dialog ยาวตามฟิลด์ทั้งหมด — reuse
`FilePickerField`/`ColorPickerInput`/`PositionPicker` (จาก `IntropageBackground/`, ใช้ value set เดียวกับ
`background-position` สำหรับ `header_content_align` แบบ 9 ทิศ)/`LangFieldGroup`) + ปุ่ม "เรียงลำดับ" เปิด
`MenuReorderDialog.vue` (ลาก tree ข้ามระดับได้ด้วย `vuedraggable` ซ้อนกันแบบ recursive — `MenuReorderNode.vue`
render กล่องลูกให้เฉพาะเมนูประเภท `heading` เท่านั้น เมนูประเภทอื่นจึงวางเมนูอื่นลงไปไม่ได้โดยธรรมชาติ, กัน cycle ด้วย
`:move` callback เพิ่ม) — เลือกบทความ/หน้าเพจทำผ่าน dialog picker ใหม่ (`ArticleItemPickerDialog.vue`/
`PageItemPickerDialog.vue`, ค้นหา+paginate ผ่าน `admin.system.menu.pick.articles/pages`) ส่วนหมวดหมู่บทความเป็น
dropdown ธรรมดา (`SearchableSelect`) เพราะหมวดหมู่มีจำนวนจำกัด

**เงื่อนไข/การทำงาน** — เมนู `is_home='Y'` มีได้แถวเดียวทั้งระบบ (ตั้งใหม่แล้วสลับเดิมให้อัตโนมัติในทรานแซกชันเดียว) ·
ซ่อนเมนูประเภท `heading` จะ cascade ซ่อนเมนูลูกทุกระดับไปด้วย (เปิดกลับมาไม่ cascade คืนให้ลูก) · ลบได้เฉพาะเมนูที่ไม่มี
เมนูลูก · แก้ไข parent/ประเภทมี guard กัน cycle และกันเปลี่ยนประเภทออกจาก `heading` ทั้งที่ยังมีเมนูลูกอยู่ (`UpdateFrontMenuRequest::withValidator()`)

**Permission** — `system.menu.view`/`manage`/`delete` (seed ไว้แล้วใน `DatabaseSeeder` ตั้งแต่ก่อนโมดูลนี้จะเริ่มทำ —
`manage` ครอบคลุม store/update/reorder/toggle-status ทั้งหมด ไม่ได้แยก action ย่อยเพิ่ม)

**Route** — `admin.system.menu.*` ใต้ `/admin/system/menu` (`index`/`store`/`{menu}`→`update`/`destroy`/
`{menu}/status`→`toggleStatus`/`reorder`/`pick/articles`/`pick/pages`) — เมนู sidebar หลังบ้าน `system-menu`
(`MenuSeeder.php`) ชี้มาที่นี่อยู่แล้วตั้งแต่ก่อนมีโค้ดจริง

**Log** — `LogBackAction::record('system.menu', ...)` ทุกจุด create/update/delete (รวม reorder → `update`)
ตาม convention log ที่บังคับทุกโมดูล CRUD ใหม่

**FrontMenuSeeder** — ข้อมูลตัวอย่าง (`is_temp='Y'`) ต้องรันหลัง `ArticleSeeder`/`PageSeeder`: เมนูหน้าแรก
(`is_home`, ชี้หน้าเพจตัวอย่าง) + เมนูหัวข้อ "บทความ" มีลูก 3 รายการชี้หมวดหมู่บทความตัวอย่าง — เรียกจาก `DatabaseSeeder`

**ยังไม่ทำ (นอกขอบเขตรอบนี้)** — การ render เมนูจริงที่หน้าบ้าน: level 1 เรียงแนวนอน เมนูลูกแสดงลงมาด้านล่าง,
level 2 เป็นต้นไปเรียงแนวตั้งไปด้านข้าง (dropdown/flyout) — ต้องมี Front controller อ่าน tree ตาม `lang`
ปัจจุบันแล้ว render component หน้าบ้าน (`resources/js/Components/Front/`) รายละเอียดสเปกที่ต้องทำต่อดู
[PRD-system-frontmenu.md](PRD-system-frontmenu.md) §ท้ายเอกสาร

---

## 4. จัดการ template — 🟢 หลังบ้านเสร็จ · render หน้าบ้านเสร็จ (ดู [PRD-front.md](PRD-front.md) §3)

**วัตถุประสงค์** — กำหนดหน้าตาส่วนกลางของหน้าบ้าน 4 โซน (header / main body / footer / aside) สร้างได้หลายรายการ ใช้งานได้ครั้งละ 1 รายการ

รายละเอียดเต็ม (data model, ตัวเลือก, แม่แบบตั้งต้น, หน้าจอ, permission/log, roadmap หน้าบ้าน) ดู [PRD-system-template.md](PRD-system-template.md)

- ตาราง: `sys_template` (ข้อมูลทั่วไป + Custom CSS/JS + หน้า Loading) + ตั้งค่าโซน 1:1 `sys_template_header` / `_body` / `_footer` / `_aside`
  (migration `2026_09_30_000001_create_sys_template_tables.php`) — แทนข้อเสนอเดิม (`id` varchar + `header_html`/`footer_html`/`config` JSON + `is_default`)
- ข้อมูลไซต์ (โลโก้/ชื่อ/ติดต่อ/social/ภาษา/ลิขสิทธิ์) อ่านจาก `sys_setting` ไม่เก็บซ้ำ
- Permission — `system.template.view`, `system.template.manage`, `system.template.delete` (ใช้ `manage` แทน `create` ตามที่ seed ไว้)

---

## 5. ประวัติ login / เข้าชม / การกระทำ — 🟡 ฝั่งหลังบ้านครบ 3 ตัว (`log_back_access` + `log_back_login` + `log_back_action`) · หน้าบ้าน `log_front_access` 🟢

**วัตถุประสงค์** — เก็บ log เพื่อตรวจสอบย้อนหลังและวิเคราะห์การเข้าชม

เก็บ log แยก 3 ประเภทต่อฝั่ง (หลังบ้าน `log_back_*` / หน้าบ้าน `log_front_*`) รวมทั้งหมด 6 ตาราง —
ทุกตารางอยู่ในไฟล์ migration กลางไฟล์เดียว `database/migrations/2026_09_10_000001_create_log_tables.php`
(ตารางถัดไปให้ `Schema::create` เพิ่มในไฟล์นี้ ไม่แยกไฟล์) และเดินตาม convention ของตาราง `sys_*`
(คอลัมน์ `status` char(1) `'Y'`/`'N'`, `softDeletes`, บล็อก audit `created_by`/`updated_by`/`deleted_by`
= `sys_user.id` ไม่มี FK, comment คอลัมน์ภาษาไทย)

| ตาราง | เก็บอะไร | สถานะ |
|-------|---------|-------|
| `log_back_access` / `log_front_access` | การเข้าชม/เข้าถึงหน้า (1 request = 1 แถว) | `log_back_access` 🟢 ตาราง/model + บันทึก + keep-alive + หน้ารายการ · `log_front_access` 🟢 ตาราง/model + บันทึก (insert หลังส่ง response) + keep-alive (token + session_id) + หน้ารายการ |
| `log_back_action` / `log_front_action` | การกระทำบนข้อมูล — `module_code`, `action_type` (`create`/`view`/`update`/`delete`), `value_string` (ชื่อข้อมูล), `ref_id`, `remote_ip` | `log_back_action` 🟢 ตาราง/model + บันทึก (system.user + system.usergroup) + หน้ารายการ · `log_front_action` 🔴 |
| `log_back_login` / `log_front_login` | การเข้า/ออกระบบ — `log_type` (`login`/`logout`), `result` (`success`/`fail`/`block`), `username`, `note`, `remote_ip` | `log_back_login` 🟢 ตาราง/model + บันทึก + หน้ารายการ · `log_front_login` 🔴 |

### `log_back_access` — คอลัมน์

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint AI | |
| `user_id` | bigint null | `sys_user.id` (ไม่มี FK) — null = ยังไม่ล็อกอิน |
| `token` | `varchar(26)` unique null | ULID สาธารณะ — ใช้อ้างอิงตอน keep-alive ping โดยไม่เปิดเผย `id` |
| `session_id` | `varchar(100)` null | |
| `uri_string` | `text` null | URI ที่เข้าถึง |
| `title_name` | `varchar(255)` null | ชื่อหน้า / ชื่อ route |
| `remote_ip` | `varchar(45)` null | รองรับ IPv6 |
| `geo_ip` / `geo_ip_city` | `varchar(10)` / `varchar(250)` null | รหัสประเทศ / เมือง จาก IP (ยังไม่มี resolver — ปล่อย null) |
| `browser` / `browser_version` / `platform` | `varchar(50)` null | แยกจาก User-Agent ด้วย `App\Support\UserAgentParser` (heuristic เบา ๆ ไม่พึ่ง package; ไม่รู้จัก = null) |
| `mobile` / `device_type` / `robot` | `varchar(50)` null | รุ่นอุปกรณ์ / ประเภท (desktop/tablet/mobile/robot) / ชื่อบอท |
| `referrer` | `varchar(250)` null | |
| `agent` | `varchar(255)` null | User-Agent ดิบ |
| `accept_lang` / `accept_charset` | `varchar(50)` null | |
| `action_date` | `date` null | ไว้กรอง/สรุปรายวัน (index) — เติมอัตโนมัติใน model |
| `last_visited` | `datetime` null | เวลาที่อยู่หน้านี้ล่าสุด (keep-alive); ครั้งแรก = `created_at` |
| `status` | `char(1)` default `Y` | |
| `created_by` / `updated_by` / `deleted_by` | bigint null | audit (`sys_user.id`, ไม่มี FK) |
| `created_at` / `updated_at` / `deleted_at` | timestamp null | `timestamps()` + `softDeletes()` |

index: `user_id`, `session_id`, `action_date`, `created_at`

**การเก็บ `log_back_access` (ทำแล้ว)** — static `App\Models\LogBackAccess::record('ชื่อหน้า')` เรียกจาก
controller ที่ render หน้าจอ **หลังผ่าน permission guard** (dashboard, profile.edit, user/usergroup
index+add+edit+password/rights) — ตามแบบระบบเดิมที่เรียก static function ในแต่ละ action ไม่ใช่ middleware
เพราะต้องเลือกได้ว่าหน้าไหนบันทึก. หน้ารายการเรียกเฉพาะเมื่อ `count($request->query()) === 0`
(ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง). `record()` เก็บ `token` ลง `$request->attributes` แล้ว
`HandleInertiaRequests::share()` แชร์เป็น prop `accessLog.token`. `remote_ip` อ่านจาก header ของ
proxy/CDN ก่อน (`CF-Connecting-IP` → `X-Real-IP` → `X-Forwarded-For` ตัวแรกที่เป็น IP ถูกต้อง)
ค่อย fallback `$request->ip()` — `App\Support\ClientIp::from()` (header ปลอมได้ถ้าเข้าตรงไม่ผ่าน proxy
แต่ log ยอมรับได้ — ใช้ร่วมกับ `log_back_login`)

> ฝั่งอื่นที่ยังไม่ทำ: `log_*_action` ผ่าน model observer หรือ trait กลาง;
> `log_front_access` / `log_front_login` ผ่าน middleware/auth ฝั่งหน้าบ้าน (ต้องสร้างตารางก่อน)

### `log_back_access` — สถิติ (แท็บต่อจากหน้ารายการ)

หน้า "ประวัติการใช้งานหลังบ้าน" มีแท็บ **รายการ** (เดิม) + **ภาพรวม / ผู้ใช้งาน / หน้าจอ / อุปกรณ์และเครือข่าย / ช่วงเวลา**
(`BackLogAccessReportController`, route `admin.system.backlog.access.{overview,user,page,device,time,export}`, สิทธิ์ `system.backlog.access`
เดียวกับหน้ารายการ, บันทึกเฉพาะ `LogBackAccess` เหมือนหน้าประวัติอื่น). ตัวคำนวณ `App\Support\Report\AccessLogReport` (ใช้ร่วมกับหน้าบ้าน) ต่อยอด `ViewReport`
(FK = `user_id` → จำนวนผู้ใช้งาน) — ใช้ component รายงานกลางชุดเดียวกับรายงานบทความ (`Components/Admin/Report/*`, คำชุด `metric = access`)
+ `Components/Admin/LogStats/*` (`StatsShell`, `DurationCards`, `PageStatsTable`, `StatTiles`, `ActionTypeTable`) + `Components/Admin/BackLogAccess/UserStatsTable`,
หน้า `Pages/Admin/System/BackLogAccess/*`

**โครงร่วมของหน้าสถิติประวัติทุกตัว** — controller extends `Admin\System\LogStatsController` (กำหนด `permission()` / `tabs()` / `build()` /
`exportRows()`), route แต่ละแท็บชี้ `show()` + `->defaults('tab', '<แท็บ>')`, ส่งออก `export?tab=`; หน้าจอใช้ `StatsShell` ที่อ่านแท็บจาก
`LOG_STATS` ใน `resources/js/utils/logStats.ts` (แท็บแรก "รายการ" = หน้ารายการเดิม) — `ViewReport` รับคอลัมน์ที่นับ "ไม่ซ้ำ" ได้ (`uniqueColumn`,
default `session_id`) สำหรับตารางที่ไม่มี session
- ตัวกรอง: ช่วงวันที่ (+ปุ่มลัด) / รายวัน-สัปดาห์-เดือน-ปี / **ผู้ใช้งาน** (`user_id`, รวมผู้ใช้ที่ถูกลบ) — ส่งต่อระหว่างแท็บ; ทุกแท็บส่งออก CSV ได้
- **เวลาที่ใช้ต่อหน้าจอ** = `last_visited - created_at` (keep-alive ทุก 45 วินาที) ตัดไม่เกิน `DURATION_CAP` = 30 นาทีต่อครั้ง (กันแท็บที่เปิดทิ้งไว้) —
  แสดงเป็นเวลาเฉลี่ยต่อหน้าจอ / ต่อ session / เวลาใช้งานรวมโดยประมาณ
- ผู้ใช้งาน: จำนวนเข้าหน้าจอ / เข้าระบบ (session) / วันที่ใช้งาน / หน้าจอที่ต่างกัน / IP / เวลาใช้งานรวม-เฉลี่ย / ครั้งแรก-ล่าสุด (เรียงคอลัมน์ในหน้าจอได้,
  กดชื่อ = ดูภาพรวมเฉพาะคนนั้น); หน้าจอ: จัดกลุ่มตาม `title_name` + เวลาเฉลี่ย (กราฟเวลาเฉลี่ยนับเฉพาะหน้าที่เปิด ≥ 3 ครั้ง)
- อุปกรณ์และเครือข่าย: อุปกรณ์/เบราว์เซอร์/OS + IP 30 อันดับ พร้อมจำนวนบัญชีต่อ IP (มากกว่า 1 บัญชี = ไฮไลต์ให้ตรวจสอบ)
- ช่วงเวลา: heatmap วัน × ชั่วโมง, สัดส่วนในเวลาทำการ (จ.–ศ. 08:00–17:59) / นอกเวลา / เสาร์-อาทิตย์

### `log_back_login` / `log_back_action` / `log_front_access` — สถิติ

| ประวัติ | route (+ `.export`) / สิทธิ์ | แท็บ | ตัวคำนวณ |
|---|---|---|---|
| การเข้าสู่ระบบหลังบ้าน | `admin.system.backlog.login.*` / `system.backlog.login` | ภาพรวม (สำเร็จ/ไม่สำเร็จ/บล็อก/ออกจากระบบ, อัตราสำเร็จ, แนวโน้มตามผลลัพธ์, สาเหตุ) · บัญชีผู้ใช้งาน (ต่อ username, ผิดพลาด ≥ 5 ครั้ง = เตือน, username ที่ไม่เคยสำเร็จ) · ความปลอดภัย (IP ที่ผิดพลาด + จำนวนบัญชีที่ลอง — ลอง ≥ 3 บัญชี หรือผิด ≥ 10 ครั้งโดยไม่เคยสำเร็จ = น่าสงสัย) · ช่วงเวลา (heatmap สำเร็จ / ผิดพลาด + ในเวลา/นอกเวลาทำการ) | `LoginLogReport` (unique = `username`; กรองผู้ใช้งาน = `user_id` นั้น + ที่กรอกอีเมลของบัญชีนั้น) |
| การกระทำหลังบ้าน | `admin.system.backlog.action.*` / `system.backlog.action` | ภาพรวม (แยกประเภท เพิ่ม/แก้ไข/ลบ/ดู/อื่น ๆ + แนวโน้ม) · ผู้ใช้งาน (ต่อคนแยกประเภท) · โมดูลและข้อมูล (ต่อ `module_code` + ข้อมูลที่ถูกเปลี่ยนแปลงบ่อยตาม `module_code`+`ref_id`) · ช่วงเวลา (heatmap ทั้งหมด / เฉพาะการเปลี่ยนแปลงข้อมูล) | `ActionLogReport` (unique = `user_id`) |
| การใช้งานหน้าบ้าน | `admin.system.frontlog.access.*` / `system.frontlog.access` | ภาพรวม (+ bounce rate, หน้าต่อ session, landing page) · หน้าที่เข้าชม · แหล่งที่มาและภาษา (referrer, ภาษาของเว็บจาก `/{lang}/`, ภาษาเบราว์เซอร์) · อุปกรณ์และเครือข่าย (+ บอท) · ช่วงเวลา | `AccessLogReport` (ตัดบอทออกจากทุกสถิติ แล้วแสดงแยก; **ยังไม่มีผู้ใช้งานหน้าบ้าน** จึงไม่มีตัวกรอง/แท็บผู้ใช้งาน — เพิ่มได้เมื่อมี login หน้าบ้าน) |

### `log_back_login` — คอลัมน์ + การเก็บ (ทำแล้ว)

`id`, `user_id` (bigint null — เก็บเมื่อ `success`/`logout`), `log_type` `varchar(10)` (`login`/`logout`),
`username` `varchar(150)` (อีเมลที่กรอก), `result` `varchar(10)` (`success`/`fail`/`block`),
`note` `varchar(1000)` (เหตุผล), `remote_ip` `varchar(45)`, `action_date` `date` (model เติม),
`status` `char(1)` `Y`, audit + `timestamps` + `softDeletes`. **ไม่มีคอลัมน์ `password`** (ไม่เก็บรหัสที่กรอก).
index: `user_id`, `username`, `log_type`, `result`, `created_at`

บันทึกด้วย static `App\Models\LogBackLogin::{loginSuccess|loginFailed|loginBlocked|logout}()` เรียกจาก:
- `App\Http\Requests\Auth\LoginRequest::authenticate()` — สำเร็จ / รหัสผิด (`fail`) / ไม่พบบัญชี (`fail`) /
  บัญชีถูกระงับ `status='N'` (`block`)
- `LoginRequest::ensureIsNotRateLimited()` — ถูก throttle เกิน 5 ครั้ง (`block`)
- `AuthenticatedSessionController::destroy()` — logout (เก็บ `Auth::id()`/email **ก่อน** `Auth::logout()`)

`remote_ip` ผ่าน `App\Support\ClientIp::from()` เช่นเดียวกับ `log_back_access`

### `log_back_action` — คอลัมน์ + การเก็บ (ทำแล้ว)

`id`, `user_id` (ผู้กระทำ), `module_code` `varchar(50)` (เช่น `system.user`, `system.usergroup.rights`),
`action_type` `varchar(50)` (`create`/`view`/`update`/`delete`/…), `value_string` `varchar(500)` (ชื่อข้อมูล),
`ref_id` (id ข้อมูล), `action_date` `date` (model เติม), `remote_ip` `varchar(45)`, `geo_ip` `varchar(10)`,
`status` `char(1)` `Y`, audit + `timestamps` + `softDeletes`.
index: `user_id`, `module_code`, `action_type`, `action_date`, `created_at`, `[module_code, ref_id]`

บันทึกด้วย static `App\Models\LogBackAction::record($moduleCode, $actionType, $valueString, $refId)`
(เซ็ต `user_id`/`created_by` = `Auth::id()`, `remote_ip` = `ClientIp::from()`) เรียกจาก controller
**หลัง DB op สำเร็จ ก่อน redirect** (หรือคู่กับ `LogBackAccess::record()` สำหรับหน้า view):

| จุด | module_code | action_type |
|---|---|---|
| `UserController` store / edit / update / destroy | `system.user` | create / view / update / delete |
| `UserController` password / passwordUpdate | `system.user.password` | view / update |
| `UsergroupController` store / edit / update / destroy | `system.usergroup` | create / view / update / delete |
| `UsergroupController` rights / rightsUpdate | `system.usergroup.rights` | view / update |

หน้ารายการ (`index`) และหน้าฟอร์มสร้าง (`add`) **ไม่บันทึก** action.
`destroy` เก็บ `name`/`id` ไว้ก่อนลบ. **โมดูล CRUD หลังบ้านที่ทำต่อไปทุกตัว ต้องเรียก `record()` ตาม pattern นี้**

**keep-alive `last_visited` (ทำแล้ว)** — composable `resources/js/composables/useAccessHeartbeat.ts`
(เรียกครั้งเดียวใน `AdminLayout.vue`) อ่าน `accessLog.token` แล้วยิง `navigator.sendBeacon()`
(fallback `fetch(..., {keepalive:true})`) ไป `POST admin.system.backlog.access.ping` ตอน
`visibilitychange`→`hidden`, `pagehide`, `onBeforeUnmount` + interval 45 วิ (เฉพาะตอน tab visible).
`BackLogAccessController@ping` อัปเดต `last_visited = now()` โดย scope `token` + `user_id` ของผู้ใช้
+ `created_at >= -1 วัน` — ไม่เช็ก permission (กันด้วย auth + scope), `throttle:60,1`, ตอบ 204.
CSRF: route นี้ถูก **ยกเว้น CSRF** ใน `bootstrap/app.php` (`validateCsrfTokens(except: [...])`) เพราะ
sendBeacon ตั้ง header/`_token` ที่สดไม่ได้ (Inertia ไม่ re-render `<head>` โทเคน meta จะค้างหลัง
session regenerate → 419) — Inertia จัดการ CSRF ผ่านคุกกี้ `XSRF-TOKEN` เอง ห้ามเซ็ต `X-CSRF-TOKEN` axios default ทับ.
ไม่ต้องเข้ารหัส `id` เพราะ `token` เป็น ULID เดาไม่ได้ + endpoint ทำได้แค่ bump timestamp

**หน้าจอ `log_back_access` (ทำแล้ว)** — `admin.system.backlog.access.index` → `BackLogAccessController@index`
(เช็ก `system.backlog.access`, ไม่มีสิทธิ์ redirect ไป dashboard). `Pages/Admin/System/BackLogAccess/Index.vue`:
ตาราง 6 คอลัมน์ (ชื่อ-นามสกุล [ไม่มี = `-`] / URL / ชื่อหน้า / IP / เวลาที่เข้าชม = `created_at` /
เวลาที่ออกจากหน้า = `last_visited`) — คลิกแถวเปิด `Components/Admin/DetailDialog.vue` (modal กลาง ใช้ซ้ำได้)
แสดง field ทั้งหมดของ record. ตัวกรอง: ช่องค้นหาเดียว (URL / ชื่อหน้า / IP), ช่วงวันที่ `date_from`/`date_to`
(กรอง `created_at`), enter ในช่องหรือปุ่ม "ค้นหา" (ไอคอนแว่นขยาย), ปุ่ม "เริ่มใหม่" (ไอคอน RotateCcw).
paging + เลือกจำนวนต่อหน้า + แสดง "แสดง X–Y จาก N รายการ". sort default `created_at` desc
(`sort=name` ใช้ leftJoin `sys_user`). ยังไม่มี export CSV

**หน้าจอ `log_back_login` (ทำแล้ว)** — `admin.system.backlog.login.index` → `BackLogLoginController@index`
(เช็ก `system.backlog.login`). `Pages/Admin/System/BackLogLogin/Index.vue`: ตาราง ชื่อ-นามสกุล [ไม่มี = `-`] /
Username / ประเภท / ผลลัพธ์ (pill สี เขียว-แดง-เหลือง) / IP / วันเวลา — คลิกแถวเปิด `DetailDialog`.
ตัวกรอง: ช่องค้นหา (Username / IP / หมายเหตุ) + select ประเภท + select ผลลัพธ์ + ช่วงวันที่ (กรอง `created_at`).
paging + per_page + sort default `created_at` desc (`sort=name` leftJoin `sys_user`)

**หน้าจอ `log_back_action` (ทำแล้ว)** — `admin.system.backlog.action.index` → `BackLogActionController@index`
(เช็ก `system.backlog.action`). `Pages/Admin/System/BackLogAction/Index.vue`: ตาราง ชื่อ-นามสกุล [`-` ถ้า null] /
โมดูล / ประเภทการกระทำ (pill สี — เพิ่ม/ดู/แก้ไข/ลบ) / ข้อมูล / IP / วันเวลา — คลิกแถวเปิด `DetailDialog`.
ตัวกรอง: ช่องค้นหา (`value_string` / `module_code` / IP) + dropdown **"โมดูล"** และ **"ประเภทการกระทำ"**
(จาก `SELECT DISTINCT` ของคอลัมน์นั้น ๆ) + ช่วงวันที่. `toDate()` ของ 3 log viewer ย้ายไป base `Controller`

**หน้าจอ log ฝั่งหน้าบ้าน** — `log_front_access` 🟢 "ประวัติการใช้งาน - หน้าบ้าน" (`admin.system.frontlog.access.index` →
`Admin\System\FrontLogAccessController` → `Pages/Admin/System/FrontLogAccess/Index.vue`) เหมือนของหลังบ้าน + ตัวกรอง
ผู้เข้าชม (บุคคล/บอท) + คอลัมน์อุปกรณ์ — การบันทึก/keep-alive ดู [PRD-front.md](PRD-front.md) §9 · `log_front_action`/`log_front_login` 🔴 (รอสมาชิกหน้าบ้าน)

**Permission** — seed ไว้แล้ว: `system.backlog.access`/`.action`/`.login` และ `system.frontlog.access`/`.action`/`.login`
(เมนู sidebar route_name `admin.system.backlog.*.index` / `admin.system.frontlog.*.index` —
`backlog.*` ทั้ง 3 และ `frontlog.access` มี route จริงแล้ว เมนูคลิกได้; `frontlog.action`/`.login` ยังไม่มี route เมนูจึง render จาง)

**หมายเหตุ**
- คนละส่วนกับ `sys_user.last_login_at` / `failed_login_count` / `last_failed_login_at` — ฟิลด์บน `sys_user`
  เก็บ "สถานะล่าสุด" ต่อผู้ใช้ (ใช้ประกอบการล็อกบัญชี) ส่วน `log_back_login` เก็บประวัติทุกครั้งแบบ append
- การล็อกบัญชีอัตโนมัติ: ดู §1 (ตั้งค่ากลุ่ม `login_back`)

### แนวทาง retention ในอนาคต (ยังไม่ทำ)

ตอนนี้ตาราง log และประวัติการเข้าชม (`log_back_*`, `log_front_access`, `article_item_view`, `page_item_view`, `banner_item_click`)
**เก็บทุกแถวไม่มีการลบ** — ข้อมูลครบแต่โตขึ้นเรื่อย ๆ. เมื่อข้อมูลเยอะจนรายงาน/หน้ารายการช้า มีทางเลือกที่ข้อมูลยังครบและรายงานยังเร็วดังนี้
(เลือกได้มากกว่า 1 ข้อ ทำเรียงจากข้อ 1):

1. **ตารางสรุปรายวัน (rollup)** — คำสั่ง scheduler ทุกคืนสรุปยอดของวันก่อนหน้าลงตารางใหม่ เช่น `report_daily_view`
   (วันที่ × ประเภท × รายการ × ภาษา × อุปกรณ์ → จำนวนครั้ง/ผู้เข้าชมไม่ซ้ำ) แล้วให้ `ViewReport`/`AccessLogReport` อ่านช่วงวันที่ผ่านมาแล้ว
   จากตารางสรุป อ่านข้อมูลดิบเฉพาะวันนี้ → รายงานเร็วคงที่ไม่ว่าข้อมูลดิบจะเยอะแค่ไหน และลบข้อมูลดิบที่เก่ากว่าที่กำหนดได้โดยสถิติไม่หาย
   (ข้อจำกัด: ผู้เข้าชมไม่ซ้ำข้ามหลายวันนับจากตารางสรุปตรง ๆ ไม่ได้ — เก็บค่าแยกต่อช่วงที่ต้องใช้ หรือใช้ HyperLogLog)
2. **แบ่ง partition รายเดือนตาม `action_date`** (MySQL partitioning) — query ที่กรองช่วงวันอ่านเฉพาะ partition ที่เกี่ยวข้อง
   และลบข้อมูลเก่าได้ทีละเดือนแบบ `DROP PARTITION` (เร็วมาก ไม่ล็อกตาราง) — ต้องปรับ primary key ให้มี `action_date` ด้วย
3. **ย้ายข้อมูลเก่าไปเก็บถาวร (archive)** — ย้ายแถวที่เก่ากว่า N เดือนไปตาราง `*_archive` (หรือ export เป็นไฟล์ CSV/JSON รายเดือนเก็บ
   ใน storage) ก่อนลบจากตารางหลัก — ตารางหลักเล็ก หน้ารายการเร็ว แต่ยังเปิดย้อนดูได้เมื่อจำเป็น
4. **ลบข้อมูลดิบตามอายุ (prune)** — ใช้ `Prunable` ของ Laravel + `model:prune` ใน scheduler ตามจำนวนวันใน config (0 = ไม่ลบ)
   ควรทำหลังมีข้อ 1 แล้วเท่านั้น (ไม่งั้นสถิติย้อนหลังหาย) และเป็น hard delete (คนละชั้นกับ `softDeletes`)

---

## 6. ตั้งค่าระบบ/เว็บไซต์

**วัตถุประสงค์** — เก็บค่าตั้งค่าทั่วไปแบบ key-value จัดกลุ่มด้วย `group` (เช่น `site`, `contact`, `seo`, `social`)

**Data model — `sys_setting`** (สร้างในเฟส 0)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `group` | `varchar(50)` | ส่วนหนึ่งของ composite PK — เช่น `site` |
| `name` | `varchar(100)` | ส่วนหนึ่งของ composite PK — เช่น `site_name` |
| `value` | `text` null | ค่า (string/JSON แล้วแต่ key) |
| `created_by` / `updated_by` | bigint null (`sys_user.id`, ไม่มี FK) | ผู้สร้าง / ผู้แก้ไขล่าสุด (ยังไม่มี UI เขียน) |
| `timestamps`, `deleted_at` | | timestamps + softDeletes |

**ค่าลับเข้ารหัส** — `Setting::SECRETS` (`smtp.password`, `turnstile.key_secret`) เก็บใน `value` แบบเข้ารหัสด้วย Laravel `Crypt`
(AES-256-CBC + HMAC-SHA256 ด้วยกุญแจ `APP_KEY`) ทั้งใน DB และใน cache — เข้ารหัสอัตโนมัติตอนบันทึก (`SysSetting::saving`), ถอดรหัสใน
`Setting::group()`/`get()`; หน้าตั้งค่าไม่ส่งค่าจริงไปหน้าจอ (เว้นว่าง = ใช้ค่าเดิม). ข้อมูลเดิมเข้ารหัสด้วย migration `2026_10_09_000001_*`.
**ย้ายเครื่อง/ติดตั้งใหม่ต้องใช้ `APP_KEY` เดิม** — ถ้าเปลี่ยน ค่าลับจะอ่านได้เป็น null (Turnstile ถือว่ายังไม่ตั้งค่า, SMTP ไม่มีรหัสผ่าน) ต้องกรอกใหม่ที่หน้าตั้งค่าระบบ.
เพิ่มค่าลับใหม่ = เพิ่มชื่อใน `Setting::SECRETS` (+ migration เข้ารหัสค่าเดิม และไม่ส่งค่าไปหน้าจอใน `SettingController::SECRET_FIELDS`)

primary key = `(group, name)` — Eloquent ไม่รองรับ composite key เต็มรูปแบบ ให้ค้นด้วย
`SysSetting::where('group', ...)->where('name', ...)` (ไม่ใช้ `find()`)

> `group` เป็นคำสงวนของ MySQL — schema builder / query builder ของ Laravel quote ให้อัตโนมัติ
> แต่ raw SQL ต้องใส่ backtick `` `group` `` เอง

**Seed ตัวอย่าง (เฟส 0)** — กลุ่ม `site`: `site_name`, `site_email`, `site_description`

**หน้าจอ** — ฟอร์มแยกแท็บตาม group; แต่ละ field map กับ 1 แถว; บันทึกทั้งกลุ่มพร้อมกัน (upsert)

**กลุ่ม Google Map** (`google_map`) — `api_key` ของ Maps Embed API ใช้สร้างแผนที่จากพิกัดในหน้าติดต่อเรา (`App\Support\GoogleMap`)
ว่าง = ใช้ลิงก์ embed แบบไม่ใช้ key (แสดงไม่เรียบร้อย) — route `admin.system.setting.update.google_map` ดู [PRD-contactus.md](PRD-contactus.md) §5

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

## 8. dashboard — 🟢 มีแล้ว

**Route** — `admin.dashboard` ใต้ `/admin/dashboard` — เปิดได้ทุกคนที่ login (ไม่มี permission ของตัวเอง) บันทึก `LogBackAccess::record('แดชบอร์ด')`

**หลักการ** — แสดงเฉพาะข้อมูลที่ช่วยตัดสินใจ/ต้องดำเนินการ และ **แต่ละส่วนผูกกับสิทธิ์ของส่วนนั้น**: ไม่มีสิทธิ์ = backend ไม่ query และไม่ส่งข้อมูล
(ค่า `null`/array ว่าง) หน้าจอแค่แสดงตามข้อมูลที่ได้รับ ไม่เช็กสิทธิ์ซ้ำ; ไม่มีสิทธิ์สักส่วน → empty state

**Backend** — `Admin\DashboardController@index` → `App\Support\Report\DashboardReport` (props `shortcuts` + `dashboard`) ใช้ตัวคำนวณเดียวกับเมนูรายงาน
(`ViewReport`/`ItemReport`/`AccessLogReport`) ตัวเลขจึงตรงกับหน้ารายงาน

| ส่วน (บนลงล่าง) | สิทธิ์ | รายละเอียด |
|---|---|---|
| ปุ่มลัด (หัวหน้า) | `article/page/banner.item.manage` | เพิ่มบทความ / หน้าเพจ / ป้ายโฆษณา |
| รายการที่ควรดำเนินการ | `contactus.item.view`, `article.item.view`, `popup.item.view`, `system.backlog.login` | ข้อความติดต่อยังไม่อ่าน/พิจารณา, บทความ/Popup ที่จะหมดเผยแพร่ใน 7 วัน, login ไม่สำเร็จ/ถูกบล็อกใน 24 ชม. — ส่งเฉพาะรายการที่ > 0 |
| การ์ดตัวเลข 7 วัน (เทียบ 7 วันก่อนหน้า) | `system.frontlog.access`, `article/page/banner.report.view`, `contactus.item.view` | ผู้เข้าชมเว็บ (session ไม่ซ้ำ ตัดบอท), ยอดเข้าชมบทความ/หน้าเพจ, ยอดคลิกป้ายโฆษณา, ข้อความติดต่อใหม่ |
| กราฟแนวโน้ม 30 วัน | ตามการ์ดตัวเลข (ยกเว้นติดต่อเรา) | 1 เส้นต่อ metric |
| บทความยอดนิยม 7 วัน (5 อันดับ) | `article.report.view` | ชื่อเป็นลิงก์แก้ไขเฉพาะเมื่อมี `article.item.view` |
| ข้อความติดต่อล่าสุด (5) | `contactus.item.view` | |
| ภาพรวมเนื้อหา | `<module>.item.view` ต่อโมดูล | เผยแพร่อยู่ / ทั้งหมด ของบทความ/หน้าเพจ/ป้ายโฆษณา/Popup/Intropage (เงื่อนไขเผยแพร่เดียวกับหน้าบ้าน, นับ `is_temp`) |
| กิจกรรมล่าสุดในหลังบ้าน (6) | `system.backlog.action` | จาก `log_back_action` |

**หน้าจอ** — `Pages/Admin/Dashboard.vue` + `Components/Admin/Dashboard/*` (ชนิดข้อมูลใน `utils/dashboard.ts`), กราฟใช้ `Report/ViewTrendChart.vue`

**เพิ่มส่วนใหม่** — เพิ่ม method ใน `DashboardReport` ที่เช็ก `can()` ก่อนคำนวณเสมอ + key ใน `toArray()` + type ใน `utils/dashboard.ts` + เทสใน `tests/Feature/Admin/DashboardTest.php`

---

## 9. file management — 🟢 มีแล้ว

**วัตถุประสงค์** — พื้นที่ไฟล์ส่วนตัวของผู้ใช้หลังบ้านแต่ละคน (เห็นเฉพาะไฟล์ของตัวเอง) ใช้ทั้งแบบเข้าเมนู
"จัดการไฟล์" เต็มหน้า และแบบ dialog เลือกไฟล์ที่ยกไปฝังในฟอร์มของโมดูลอื่น (ยังไม่มีฟิลด์จริงผูกไว้ในตอนนี้
เพราะ `sys_user`/บทความยังไม่มีฟิลด์รูปภาพ — component พร้อมใช้ทันทีที่มีฟิลด์จริง ดู
`Components/Admin/FileManager/FilePickerField.vue`)

**การแสดงรูปตัวอย่าง** — รูปในรายการไฟล์ (มุมมองการ์ด/แถวใน `FileBrowser.vue`) และรายการไฟล์ที่เลือกแล้วใน `FilePickerField.vue`
แสดงแบบ `object-fit: contain` (เห็นรูปทั้งภาพ ไม่ครอป จะได้รู้อัตราส่วนจริงก่อนเลือก) บนพื้นเทาอ่อน `bg-gray-100` + เส้นขอบใน `ring-gray-200`
ให้เห็นว่าส่วนที่เหลือคือพื้นที่ว่างของกรอบ (thumbnail ที่เสิร์ฟมาย่อตามความกว้างโดยคงสัดส่วนอยู่แล้ว)

**ไม่มี permission gate** — ต่างจากโมดูล `system.*` อื่น ๆ เมนู "จัดการไฟล์" (sidebar ต่อจากโปรไฟล์ +
header user-dropdown) และ `Admin\System\FileController`/`FileServeController` ไม่เช็ก `hasPermission()`
เหมือน Dashboard/Profile เพราะเป็นพื้นที่ส่วนตัว ทุก query กรองด้วย `user_id` แทน permission action
`system.file.manage` (`system908`) ยังเก็บไว้เผื่ออนาคตทำมุมมองแอดมินข้าม user

**Data model — `file_info` / `folder_info`** (migration
`2026_09_14_000001_create_file_management_tables.php`) — แปลงจากระบบเดิม (schema แบบ
`ID/UserID/Name/HashName/...`) มาเป็น convention ของโปรเจกต์ (snake_case, `timestamps()`+`softDeletes()`,
`status` char(1) `Y`/`N`, audit `created_by`/`updated_by`/`deleted_by`)

| ตาราง | คอลัมน์สำคัญ | หมายเหตุ |
|-------|-------------|----------|
| `folder_info` | `user_id`, `name`, `status` | โฟลเดอร์ของผู้ใช้ 1 คน — เพิ่มได้อย่างเดียวจากหน้าจอ (ยังไม่มีลบ/แก้ไข) |
| `file_info` | `user_id`, `folder_id` (FK → `folder_info`, nullOnDelete), `name` (ชื่อไฟล์จริง), `hash_name` (unique, ULID+นามสกุล — ใช้เป็นค่าใน URL เสิร์ฟไฟล์), `file_size`, `extension`, `mime_type`, `path` (path บน disk) | `folder_id = null` = โฟลเดอร์ราก ("ไม่มีโฟลเดอร์") |

**Model** — `App\Models\FolderInfo` (`files()` hasMany, scope `ownedBy()`) / `App\Models\FileInfo`
(`folder()` belongsTo, scope `ownedBy()`, `isImage()`, เติม `hash_name` อัตโนมัติใน `booted()` แบบเดียวกับ
`token` ของ `LogBackAccess`) — ทั้งคู่ `SoftDeletes`

**การจัดเก็บไฟล์จริง** — disk `local` (`storage/app/private`, ไม่ symlink ไป public) โครงสร้าง
`filemanager/{ปี}/{เดือน}/{วัน}/{hash_name}` ตั้งค่าที่ `config/filemanagement.php` (นามสกุล/mime ที่อนุญาต,
ขนาดสูงสุด 5MB, ความกว้าง thumbnail ค่าเริ่มต้น) — อัพโหลดตรวจทั้งนามสกุล (`File::types()`) และ mime จริง
ให้ตรงกับนามสกุลที่ระบุ (`StoreFileUploadRequest::withValidator()`)

**Route** (ทั้งหมดใต้ `Route::middleware(['auth','verified'])`)

| Method | Path | route name | หมายเหตุ |
|---|---|---|---|
| GET | `/admin/system/file` | `admin.system.file.index` | หน้า Inertia — โหลดข้อมูลจริงด้วย ajax หลัง mount |
| GET | `/admin/system/file/folders` | `admin.system.file.folders` | JSON รายการโฟลเดอร์ (scope user) |
| POST | `/admin/system/file/folders` | `admin.system.file.folders.store` | JSON สร้างโฟลเดอร์ |
| GET | `/admin/system/file/list` | `admin.system.file.list` | JSON paginate ไฟล์ (ค้นหา/เรียง/paging) |
| POST | `/admin/system/file/upload` | `admin.system.file.upload` | JSON อัพโหลดทีละไฟล์ (frontend ยิงขนานหลาย request สำหรับ multi-file) |
| DELETE | `/admin/system/file/{file}` | `admin.system.file.destroy` | soft delete (scope user) |
| GET | `/admin/file/get/{hashname}` | `admin.system.file.get` | เสิร์ฟไฟล์ inline — login เท่านั้น ไม่ scope เจ้าของ (hash เดาไม่ได้) |
| GET | `/admin/file/type/download/get/{hashname}` | `admin.system.file.get.download` | บังคับดาวน์โหลด ใช้ชื่อไฟล์จริง |
| GET | `/admin/file/type/thumbnail/get/{hashname}` | `admin.system.file.get.thumbnail` | thumbnail กว้าง 500px (default) |
| GET | `/admin/file/type/thumbnail/size/{size}/get/{hashname}` | `admin.system.file.get.thumbnail.size` | thumbnail กำหนดความกว้างเอง |

> `FileServeController::thumbnail()` อ่าน `{size}`/`{hashname}` ผ่าน `$request->route()` ตรง ๆ แทนพารามิเตอร์
> ของเมธอด — เพราะ Laravel bind พารามิเตอร์ primitive แบบเรียงตามตำแหน่งในลำดับของ URI ไม่ใช่ตามชื่อ
> พารามิเตอร์ของเมธอด และ route มี `{size}` นำหน้า `{hashname}` ต่างจาก route อื่นที่ไม่มี `{size}`

**การเสิร์ฟไฟล์ (`App\Support\FileDelivery`)** — ฟังก์ชันกลาง ใช้ `Symfony\BinaryFileResponse` (Laravel
เรียก `$response->prepare($request)` ให้อัตโนมัติใน `Router::toResponse()` ซึ่งจัดการ `Range`/`206 Partial
Content` ให้เอง — ทดสอบแล้วว่า mp3/mp4 seek ได้ผ่าน route เดียวกัน ไม่ต้อง parse header เอง), ตั้ง
`ETag`/`Last-Modified`/`Cache-Control: private, max-age=86400` ทุก response และเช็ก `If-None-Match` คืน
`304` ก่อนอ่านไฟล์จริงถ้าตรงกัน — thumbnail generate ครั้งแรกด้วย `intervention/image` (GD driver) แล้ว cache
ไฟล์ที่ resize ไว้ที่ `filemanager/thumbnails/{width}/{hash_name}` (ไม่ resize ซ้ำทุก request)

**thumbnail ล่วงหน้า** — อัปโหลดรูปแล้ว `FileController::upload` เรียก `FileDelivery::pregenerateThumbnails()` ผ่าน `defer()` (หลังส่ง response —
ผู้อัปโหลดไม่ต้องรอ และไม่ต้องมี queue worker) สร้างขนาดใน `config('filemanagement.pregenerate_thumbnail_sizes')` = 100/200 (หน้าจัดการไฟล์) +
640/1280/1600/1920 (ขนาดที่หน้าบ้านใช้) โดย decode รูปต้นฉบับครั้งเดียวแล้วย่อทุกขนาด (`scaleDown` ไม่ขยายรูปเล็ก, lock ต่อไฟล์);
ขนาดอื่นยังสร้างตอนถูกขอครั้งแรกตามเดิม. รูปที่อัปโหลดก่อนมีระบบนี้: `php artisan files:thumbnails` (ข้ามขนาดที่มีแล้ว, `--id=` เฉพาะบางไฟล์) —
เพิ่มขนาดหน้าบ้านใหม่ในโค้ด ให้เพิ่มใน config นี้แล้วรันคำสั่งอีกครั้ง

**Cache** — `App\Support\FileCache` (cache-first lookup `file_info` ด้วย `hash_name`, TTL 6 ชั่วโมง) —
ปุ่ม "ล้าง Cache ไฟล์" อยู่ที่หน้า `admin.system.setting.clearcache` (route
`admin.system.setting.clearcache.files`) — driver cache เป็น `database` ไม่รองรับ tag จึงล้างทั้ง cache
store (`Cache::flush()`)

**Frontend** — `Pages/Admin/System/File/Index.vue` + component ใต้ `Components/Admin/FileManager/`
(`FolderList`, `FileUploadDropzone`, `FileBrowser`, `FilePickerDialog`, `FilePickerField`) — ทั้งหมดคุยกับ
backend ด้วย ajax (axios) ไม่ใช่ Inertia visit เพราะต้องใช้ซ้ำได้จาก dialog ที่ฝังในหน้าอื่น

**หมายเหตุ / follow-up ในอนาคต**
- performance เพิ่มเติมระดับ web server: nginx รองรับ `X-Accel-Redirect` และ Apache รองรับ
  `X-Sendfile`/`mod_xsendfile` เพื่อให้ web server เป็นคน stream ไฟล์แทน PHP-FPM โดยตรง (ยังไม่ได้ wire
  เพราะขึ้นกับ config ของเครื่อง deploy จริง) — แนวทาง: ให้ `FileServeController` ส่ง header
  `X-Accel-Redirect`/`X-Sendfile` ชี้ไป internal path แทนการ return `BinaryFileResponse` ตรง ๆ
- path สาธารณะแบบไม่ต้อง login (โลโก้/favicon ในอนาคต) ให้เขียน controller ใหม่ต่างหากแล้วเรียก
  `App\Support\FileDelivery::respond()` ซ้ำได้เลย (ตัวตรวจสิทธิ์เป็นหน้าที่ของแต่ละ controller ไม่ใช่ของ
  `FileDelivery`)
- ยังไม่มี UI ผูก `FilePickerField.vue` เข้ากับฟิลด์จริง (รอ `sys_user` เพิ่มคอลัมน์รูปโปรไฟล์ / โมดูลบทความ)

---

## 10. หน้า error + การเก็บข้อมูล error — 🟢 มีแล้ว

**หน้าจอ** (แยกหลังบ้าน/หน้าบ้าน, ผูกใน `bootstrap/app.php` → `$exceptions->respond()`):

| | หลังบ้าน (`/admin*`) | หน้าบ้าน |
|---|---|---|
| คลาส | `App\Support\Admin\AdminErrorPage` | `App\Support\Front\FrontErrorPage` |
| หน้า | `Pages/Admin/Error.vue` + `Layouts/Admin/ErrorLayout.vue` (การ์ดบนพื้นเทาเข้ม) | `Pages/Front/Error.vue` + `Layouts/Front/IntroLayout.vue` |
| ข้อความ | `lang/th/error.php` (ไทย) | `lang/{ภาษา}/front.php` ตามภาษาของ URL |
| กลับหน้าแรก | `admin.home` (→ dashboard / login) · 419 มีปุ่มเข้าสู่ระบบอีกครั้ง | `/{lang}` |

- ทุกสถานะ 4xx/5xx — ข้อความเฉพาะ `App\Support\ErrorStatus::KNOWN` (400/403/404/405/410/413/419/429/500/503) ที่เหลือใช้ `4xx`/`5xx`
- **5xx (ยกเว้น 503) บอกแค่ "เกิดข้อผิดพลาด" + รหัสอ้างอิง** — ไม่ใช้ message ของ exception เลย (รวม `abort(403, '...')`); `APP_DEBUG=true` + 5xx ใช้หน้า debug ของ Laravel;
  JSON request ตอบ JSON ตามเดิม
- **หน้าสำรอง** `resources/views/errors/{4xx,5xx,401,…,503}.blade.php` + `layout.blade.php` (ข้อมูลจาก `App\Support\ErrorFallbackPage`) — CSS inline
  ไม่พึ่ง Vite/DB: ใช้เมื่อสร้างหน้าแบบ Inertia ไม่ได้ (DB ล่ม/ยังไม่ build) และ path ที่ไม่ใช่หน้าเว็บ (`/file/*`, `/apps/*`) — ไฟล์รายสถานะมีไว้ override
  view ของ Laravel ที่ชื่อเดียวกัน (ไม่งั้นของ Laravel ชนะ `4xx`/`5xx`)

**การเก็บข้อมูล error** — `App\Support\ErrorReference`:
- รหัสอ้างอิง `ERR-XXXXXXXX` (8 ตัวอักษรไม่กำกวม) สร้างครั้งเดียวต่อ request → แสดงบนหน้า 5xx และอยู่ใน log — ผู้ใช้แจ้งรหัส ผู้ดูแลค้นใน log ได้ทันที
- `$exceptions->context()` เติมข้อมูลให้ทุก exception ที่ถูก report: `reference`, `url`, `method`, `route`, `user_id` (หลังบ้าน), `front_user_id`, `ip` (`ClientIp`),
  `user_agent`, `referer`, `input_keys` (**ชื่อฟิลด์เท่านั้น ไม่เก็บค่า** — กันรหัสผ่าน/ข้อมูลส่วนบุคคล)
- `$exceptions->report()` เขียนซ้ำลงไฟล์ error **แยกหน้าบ้าน/หลังบ้าน** (`ErrorReference::channel()`, `config/logging.php` daily **เก็บ `LOG_ERROR_DAYS` = 90 วัน**):
  `error_front` (หน้าบ้าน + path สาธารณะ เช่น `/file`, `/sitemap.xml`) / `error_admin` (`/admin*` + คำสั่ง artisan/queue ที่ไม่ได้มาจากหน้าเว็บ)
  เป็น channel แบบ stack ที่เขียน **2 รูปแบบแยกไฟล์**: `storage/logs/text-error-{front,admin}-YYYY-MM-DD.log` (ข้อความอ่านง่าย) +
  `storage/logs/json-error-{front,admin}-YYYY-MM-DD.log` (1 บรรทัด = 1 รายการ JSON — หน้า "ตรวจสอบ Error" อ่านไฟล์นี้) —
  มี class/message/file:line/trace) — ไม่ขึ้นกับ `LOG_STACK` ของเครื่อง; `.env.example` แนะนำ `LOG_STACK=daily` + `LOG_DAILY_DAYS=90` ด้วย
- exception ที่ Laravel ไม่ report อยู่แล้ว (404/403/419/ValidationException ฯลฯ) ไม่ลง log
- ค้นหา: หน้า **ตรวจสอบ Error** ในหลังบ้าน (§10.2) หรือ `grep ERR-XXXXXXXX storage/logs/text-error-*.log` (ทั้ง 2 ฝั่งในคำสั่งเดียว)

หน้าตา: หน้าบ้าน = การ์ดขาวบนพื้นสว่าง; หลังบ้าน = การ์ดบนพื้นเทาเข้ม `admin-900` (โทนเดียวกับ sidebar) — แยกกันชัดเจน

### 10.1 วิธีทดสอบดูหน้า error

1. `.env` ตั้ง `APP_DEBUG=false` (ถ้า `true` error 5xx จะเป็นหน้า debug ของ Laravel แทน) แล้วเปิดเว็บตามปกติ — **route ทดสอบมีเฉพาะ `APP_ENV=local`**
2. เปิด URL ตัวอย่าง (`{status}` = 400–599):

| ต้องการดู | หน้าบ้าน | หลังบ้าน (ไม่ต้อง login) |
|---|---|---|
| ทุกสถานะ | `/th/test-error/{status}`, `/en/test-error/{status}` | `/admin/test-error/{status}` |
| 500 (throw จริง → log + รหัสอ้างอิง) | `/th/test-error/500` | `/admin/test-error/500` |
| สถานะไม่มีข้อความเฉพาะ (ใช้ 4xx/5xx) | `/th/test-error/418`, `/th/test-error/502` | `/admin/test-error/418` |

3. ทดสอบจากสถานการณ์จริง:
   - **404** — URL ที่ไม่มี เช่น `/th/abc`, `/en/page/item/999999`, `/admin/abc`
   - **405** — เปิด `/admin/logout` ตรง ๆ ในเบราว์เซอร์ (route รับเฉพาะ POST)
   - **419** — เปิดหน้า login หลังบ้าน → ลบคุกกี้ของเว็บ (DevTools → Application → Cookies) → กดเข้าสู่ระบบ
   - **429** — ส่งฟอร์มติดต่อเราเกิน 10 ครั้ง/นาที, หรือ login ผิดติดกันหลายครั้ง (ถูกบล็อก)
   - **503** — `php artisan down` (เลิกด้วย `php artisan up`)
   - **หน้าสำรอง Blade** — ปิด MySQL (`docker-compose stop`) แล้วเปิดหน้าใดก็ได้ → 500 แบบ Blade (เปิดคืน `docker-compose start`); หรือ `/file/get/ไม่มีจริง` → 404 แบบ Blade
4. ดู log ของ 500: เมนู **ตรวจสอบ Error** → ช่องค้นหาใส่รหัสอ้างอิงที่หน้าเว็บแสดง (หรือเปิด `storage/logs/text-error-{front,admin}-YYYY-MM-DD.log`)
5. ทดสอบเสร็จแล้วคืน `APP_DEBUG=true` สำหรับการพัฒนา

### 10.2 หน้า "ตรวจสอบ Error" (`admin.system.errorviewer.*`)

- สิทธิ์ `system.error.view` (`system910`), เมนู `system-errorviewer` "ตรวจสอบ Error" ไอคอน `Bug` (กลุ่มจัดการระบบ)
- `Admin\System\ErrorViewerController` — `index` (Inertia `Admin/System/ErrorViewer/Index`, query `side`/`date`/`q`/`page`, 50 รายการ/หน้า) +
  `show/{reference}` (JSON รายละเอียดเต็ม ค้นทุกฝั่งทุกวัน); ไม่มีสิทธิ์ = redirect dashboard / 403; อ่านอย่างเดียวจึงไม่บันทึก `LogBackAction`
- ตัวอ่าน `App\Support\Report\ErrorLogReader` — อ่านเฉพาะ `json-error-*` (หาไฟล์จาก path ของ channel `error_{side}_json`), รายการวันที่จากชื่อไฟล์,
  อ่านทีละบรรทัดสูงสุด 10,000 รายการ/วัน (เกิน = แจ้ง truncated), กลุ่ม error ที่เกิดซ้ำ (class + file:line) 5 อันดับ; ตรวจรูปแบบ side/วันที่/รหัสก่อนประกอบ path เสมอ
- หน้าจอ: แท็บหน้าบ้าน/หลังบ้าน, `Components/Admin/ErrorViewer/AvailableDatePicker.vue` (ปฏิทินกดได้เฉพาะวันที่มีไฟล์ + ปุ่มวันล่าสุด),
  ช่องค้นหา (รูปแบบ `ERR-XXXXXXXX` = เปิดรายละเอียดทันที / ข้อความอื่น = กรองวันนั้น), `ErrorLogDetailDialog.vue` (message, ผู้ใช้, IP, ฟิลด์ที่ส่งมา, stack trace)

**ข้อเสนอเพิ่มเติม (ยังไม่ทำ)**
- แจ้งเตือนทันทีเมื่อเกิด 5xx — เพิ่ม channel `slack` หรือส่งอีเมล (มี SMTP ในตั้งค่าระบบแล้ว) แบบจำกัดความถี่ (เช่น รหัส error เดิมแจ้งไม่เกิน 1 ครั้ง/ชั่วโมง)
- บันทึก 404 ของหน้าบ้าน (URL + referrer) เพื่อหาลิงก์เสียจากเว็บอื่น/เมนู แล้วทำ redirect 301 — อาจเป็นตาราง `log_front_notfound` ในไฟล์ log กลาง
- เก็บ 419 ที่เกิดถี่ในหลังบ้าน (session หมดอายุระหว่างกรอกฟอร์มยาว) เพื่อพิจารณาปรับ `SESSION_LIFETIME`
- ถ้าต้องการหน้าจอดู error ในหลังบ้าน: ตาราง `log_error` + หน้าประวัติ (error ตอน DB ล่มยังลงไฟล์อย่างเดียว)

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
| `app/Models/UserGroup.php` | `fillable` เพิ่ม `status`, `can_edit`, `can_delete`; เปิด `HasFactory` + `SoftDeletes`; relation `users()` |
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

---

## ภาคผนวก: คอลัมน์ audit ผู้กระทำ (created_by / updated_by / deleted_by)

เพิ่มในไฟล์ migration เดิม `0001_01_01_000000_create_users_table.php` — ต้อง `php artisan migrate:fresh --seed` ใหม่

```
sys_user
+ password_changed_at     timestamp    NULL          // ตั้ง/เปลี่ยนรหัสผ่านครั้งล่าสุด
+ password_changed_by     bigint       NULL          // sys_user.id — ไม่มี FK
+ created_by              bigint       NULL          // sys_user.id — ไม่มี FK
+ updated_by              bigint       NULL
+ deleted_by              bigint       NULL

sys_usergroup
+ created_by / updated_by / deleted_by   bigint NULL  // sys_user.id — ไม่มี FK

sys_usergroup_action (pivot)
+ created_by / updated_by                bigint NULL  // ผู้กำหนดสิทธิ์

sys_setting
+ created_by / updated_by                bigint NULL  // ยังไม่มี UI เขียน
```

> ไม่ผูก FK เพื่อเลี่ยงปัญหาลำดับ seed / self-reference (แนวเดียวกับ `sys_menu.action_code`) — ตรวจความถูกต้องในโค้ด

### ผลกระทบต่อโค้ด

| ไฟล์ | การเปลี่ยน |
|------|-----------|
| `app/Models/User.php` | `fillable` เพิ่ม `created_by`/`updated_by`/`deleted_by`/`password_changed_at`/`password_changed_by`; cast `password_changed_at` เป็น datetime |
| `app/Models/UserGroup.php` | `fillable` เพิ่ม `created_by`/`updated_by`/`deleted_by`; `actions()` เพิ่ม `withPivot('created_by','updated_by')` + `withTimestamps()` |
| `app/Models/SysSetting.php` | `fillable` เพิ่ม `created_by`/`updated_by` |
| `app/Http/Controllers/Admin/System/UserController.php` | `store`: `created_by` + `password_changed_at`/`password_changed_by`; `update`: `updated_by`; `destroy`: `deleted_by` (save ก่อน soft delete); `passwordUpdate`: `password_changed_at`/`password_changed_by` + `updated_by` |
| `app/Http/Controllers/Admin/System/UsergroupController.php` | `store`: `created_by`; `update`: `updated_by`; `destroy`: `deleted_by` (save ก่อน soft delete); `rightsUpdate`: `attach($ids, ['created_by'=>…, 'updated_by'=>…])` |
| `tests/Feature/Admin/System/UserManagementTest.php`, `UsergroupManagementTest.php` | assert ค่า audit หลัง store/update/destroy/password/rights |

> **ยังไม่ทำ:** UI แสดงผู้สร้าง/ผู้แก้ไข/ผู้ลบ ในหน้ารายการ/แก้ไข; seeder ปล่อย audit เป็น `null`;
> ProfileController / Breeze auth (แก้โปรไฟล์ตัวเอง, เปลี่ยนรหัสผ่านตัวเอง, สมัคร) ยังไม่เขียน audit — ทำเพิ่มได้ภายหลัง
