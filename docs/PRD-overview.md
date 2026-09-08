# PRD — ภาพรวมระบบ (Overview)

เอกสารนี้วางภาพรวมของระบบจัดการหลังบ้าน (admin) ทั้งหมด ใช้เป็นแผนอ้างอิงสำหรับงานพัฒนา
ต่อ ๆ ไป รายละเอียดเชิงลึกของ **ส่วนจัดการระบบ** อยู่ที่ [PRD-system.md](PRD-system.md)
ข้อมูล tech stack / คำสั่ง / convention ระดับโค้ด ดูที่ [../CLAUDE.md](../CLAUDE.md)

## 1. วิสัยทัศน์

Micro-CMS ที่เน้น **ติดตั้งง่าย ใช้งานง่าย** — เหมาะกับเว็บองค์กร/หน่วยงานขนาดเล็กถึงกลาง
ที่ต้องการหน้าเว็บหลายภาษา (ไทย/อังกฤษ) จัดการเนื้อหาเองได้ โดยไม่ต้องตั้งค่าซับซ้อน

พัฒนาบน Laravel 12 + Inertia.js 2 + Vue 3 (`<script setup lang="ts">`) + Tailwind CSS v4
ฐานข้อมูล MySQL 8 / session+cache บน Redis / queue แบบ database

## 2. โครงหลังบ้าน 2 กลุ่ม

หลังบ้านแบ่งเมนูออกเป็น 2 กลุ่มใหญ่

```
/admin
├── โมดูลเนื้อหา (Content Modules)
│   ├── บทความ (article)
│   ├── banner
│   ├── popup
│   ├── intropage
│   ├── page (หน้าเดี่ยว)
│   └── contact us
└── จัดการระบบ (System Management)   → รายละเอียดใน PRD-system.md
    ├── จัดการผู้ใช้งาน
    ├── จัดการกลุ่มผู้ใช้งาน + กำหนดสิทธิ์
    ├── จัดการเมนู (หน้าบ้าน)
    ├── จัดการ template (header / footer / layout)
    ├── ประวัติ login / เข้าชม / การกระทำ
    ├── ตั้งค่าระบบ/เว็บไซต์
    ├── profile
    ├── dashboard
    └── file management
```

## 3. โมดูลเนื้อหา

> สถานะปัจจุบัน: **ยังไม่ได้เริ่ม** — มีแค่ไฟล์ว่าง `app/Http/Controllers/Admin/PostController.php`
> ตารางด้านล่างเป็นเป้าหมายที่จะทยอยทำ (ยังไม่ได้ออกแบบ schema ละเอียด)

| โมดูล | วัตถุประสงค์ | ข้อมูลหลัก (ร่าง) | หน้าจอ | permission code (เสนอ) |
|-------|-------------|------------------|--------|------------------------|
| **บทความ (article)** | ข่าว/บทความ มีหมวดหมู่ แสดงตามภาษา | หัวข้อ, slug, เนื้อหา (rich text), รูปปก, หมวดหมู่, สถานะเผยแพร่, วันเผยแพร่, ภาษา | list + ค้นหา/กรอง, form สร้าง/แก้ไข, เผยแพร่/ถอน | `article.view` `article.create` `article.delete` |
| **banner** | แบนเนอร์สไลด์/โปรโมชันตามตำแหน่ง | รูป (ต่อภาษา), ลิงก์, ตำแหน่งแสดง, ช่วงเวลาแสดง, ลำดับ, สถานะ | list เรียงลำดับได้, form | `banner.view` `banner.create` `banner.delete` |
| **popup** | ป๊อปอัปประกาศเมื่อเข้าเว็บ | รูป/เนื้อหา, ลิงก์, ช่วงเวลาแสดง, เงื่อนไขแสดง (หน้าไหน/ความถี่), สถานะ | list, form | `popup.view` `popup.create` `popup.delete` |
| **intropage** | หน้าคั่นก่อนเข้าเว็บ (splash/โปรโมชัน) | รูป/วิดีโอพื้นหลัง, ปุ่ม, ช่วงเวลาแสดง, เปิด/ปิด | form เดี่ยว + preview | `intropage.view` `intropage.create` |
| **page (หน้าเดี่ยว)** | หน้าเนื้อหาคงที่ เช่น เกี่ยวกับเรา/นโยบาย | หัวข้อ, slug, เนื้อหา (rich text) ต่อภาษา, template ที่ใช้, สถานะ | list, form, ผูกกับเมนู | `page.view` `page.create` `page.delete` |
| **contact us** | ฟอร์มติดต่อหน้าบ้าน + กล่องข้อความที่ส่งเข้ามา | ตั้งค่าฟอร์ม/อีเมลรับแจ้ง + ตารางข้อความที่ส่งเข้ามา (ชื่อ, อีเมล, เรื่อง, ข้อความ, อ่าน/ยังไม่อ่าน) | ตั้งค่า, list ข้อความ, อ่าน/ลบ | `contact.view` `contact.setting` `contact.delete` |

## 4. Convention ที่ต้องรักษา

| เรื่อง | กติกา |
|--------|-------|
| ชื่อตารางระบบ | ขึ้นต้น `sys_` (เช่น `sys_user`, `sys_setting`) |
| ชื่อตารางเนื้อหา | ชื่อโมดูลเอกพจน์ (เช่น `article`, `banner`) — ยังไม่ fix |
| ชื่อ route หลังบ้าน | ขึ้นต้น `admin.` เสมอ + URL อยู่ใต้ `/admin` |
| ชื่อ route หน้าบ้าน | ขึ้นต้น `front.` + URL อยู่ใต้ `/{lang}` (`th`/`en`) ผ่าน middleware `setLocale` |
| permission code | รูปแบบ `<module>.<action>` เช่น `article.create`, `system.user.view` |
| การเช็กสิทธิ์ | `$user->hasPermission('code')` ใน controller + ส่ง `can` เป็น props ให้ Vue เช็ก `v-if` |
| แยกโฟลเดอร์ | `Admin/` vs `Front/` ทุกชั้น (controller, Pages, Layouts); component เฉพาะหลังบ้านใน `Components/Admin/` |
| ภาษา UI หลังบ้าน | ฮาร์ดโค้ดภาษาไทยในไฟล์ `.vue` (ไม่ได้ทำ i18n ฝั่ง admin) |

## 5. สถานะปัจจุบัน vs เป้าหมาย

| ส่วน | ปัจจุบัน | เป้าหมาย |
|------|----------|----------|
| Auth หลังบ้าน (login/register/reset/verify) | ✅ มี (Breeze ย้ายมาใต้ `/admin`) + เช็ก `user_type='back'` / `status='Y'` + บันทึกสถิติ login แล้ว | เพิ่มล็อกบัญชีอัตโนมัติเมื่อ login ผิดเกินเกณฑ์ (อ่านจาก `sys_setting`) |
| Layout หลังบ้าน (sidebar/header มืด) | ✅ มี (สไตล์ TailAdmin) | ต่อเมนูโมดูล/จัดการระบบเข้า sidebar |
| ระบบสิทธิ์ (`sys_*`) | ✅ ตาราง + model + `hasPermission()` + seeder ตัวอย่าง | หน้าจัดการกลุ่ม/สิทธิ์แบบ tree + middleware บังคับสิทธิ์ |
| profile | ✅ มี (แก้ชื่อ/ช่องทางติดต่อ/อีเมล) | เพิ่มอัปโหลดรูปโปรไฟล์ (อนาคต) |
| dashboard | 🟡 placeholder (การ์ดสถิติ "—") | ต่อสถิติจริงเมื่อมีโมดูล |
| โมดูลเนื้อหาทั้ง 6 | ❌ ยังไม่มี | ทยอยทำ |
| จัดการเมนูหลังบ้าน (`sys_menu_group`/`sys_menu`) | 🟢 ตาราง + seed + `AppSidebar` อ่านจาก DB (กรองตามสิทธิ์) | หน้า CRUD จัดเมนู |
| จัดการเมนูหน้าบ้าน / template / ประวัติ / file management | ❌ ยังไม่มี | ทยอยทำ (ดู PRD-system.md) |
| ตั้งค่าระบบ (`sys_setting`) | 🟡 มีตาราง + seed ตัวอย่างแล้ว | หน้า UI จัดการ + helper อ่านค่า |

## 6. การปรับ schema รอบนี้ (เฟส 0)

ทำในไฟล์ migration เดียว `database/migrations/0001_01_01_000000_create_users_table.php`
(ต้อง `php artisan migrate:fresh --seed` ใหม่)

| ตาราง | การเปลี่ยน |
|-------|-----------|
| `sys_user` | `name` → `titlename` (30, null) + `firstname` (100) + `lastname` (100); เพิ่ม `mobile` (20), `phone` (30), `line` (100), `facebook` (150), `status` `char(1)` default `'Y'`; `last_login_at` / `failed_login_count` / `last_failed_login_at`; `email` เหลือ 150 **และเลิก unique** (เช็กในโค้ดว่าไม่ซ้ำต่อ `user_type` ที่ยังไม่ถูกลบ); เปิด `SoftDeletes` |
| `sys_usergroup` | เพิ่ม `status` `char(1)` default `'Y'`; `name` 100, `description` 255 |
| `sys_action_group` | `id` เป็น `string(20)` primary (แทน auto-increment); เพิ่ม `status` `char(1)` default `'Y'` |
| `sys_action` | `id` เป็น `string(20)` primary; เพิ่ม `parent_id` `string(20)` null (tree) + `sort_order`; `code` 100, `name` 150 |
| `sys_usergroup_action` | `action_id` เป็น `string(20)` ให้ตรงกับ id ใหม่ |
| `sys_setting` (ใหม่) | `group` (50) + `name` (100) เป็น composite primary key; `value` `text` null; `timestamps` + `softDeletes` |
| `password_reset_tokens` / `front_password_reset_tokens` (ใหม่) | แยกตารางโทเคน reset ตาม broker (`users` = back / `front` = front) |

**migration แยกไฟล์:** `2026_09_08_000001_create_sys_menu_tables.php` — สร้าง `sys_menu_group` + `sys_menu`
(เมนูหลังบ้าน, string PK, `icon`, `status` `char(1)`, `softDeletes`) + `database/seeders/MenuSeeder.php` ข้อมูลตัวอย่าง — ดู §3.1 ใน PRD-system

รายละเอียดคอลัมน์ทุกช่อง + before/after ดูภาคผนวกใน [PRD-system.md](PRD-system.md#ภาคผนวก-สเปก-schema-รอบนี้)

## 7. Roadmap (ร่าง)

| เฟส | ขอบเขต |
|-----|--------|
| **0 — schema base** *(รอบนี้)* | ปรับ `sys_user`/`sys_usergroup`/`sys_action*` + สร้าง `sys_setting` + เปิด SoftDeletes + auth หลังบ้านเช็ก `user_type`/`status` + บันทึกสถิติ login + ปรับ seeder/factory/profile/register/เทส |
| 1 — จัดการผู้ใช้ & สิทธิ์ | CRUD `sys_user`, `sys_usergroup`, หน้าเลือกสิทธิ์แบบ tree, middleware บังคับสิทธิ์, ล็อกบัญชีเมื่อ login ผิดเกินเกณฑ์ (`sys_setting`) |
| 2 — ตั้งค่าระบบ & template & เมนู | หน้า `sys_setting`, `sys_template`; หน้า CRUD เมนูหลังบ้าน (`AppSidebar` อ่านจาก DB แล้ว); `sys_front_menu` (tree) สำหรับหน้าบ้าน |
| 3 — โมดูลเนื้อหาแรก | บทความ (article) + page (หน้าเดี่ยว) + file management (`sys_file`) |
| 4 — โมดูลที่เหลือ | banner, popup, intropage, contact us |
| 5 — ประวัติ & dashboard จริง | `sys_log_login` / `sys_log_visit` / `sys_log_action` + สถิติ dashboard |
