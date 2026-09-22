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

> สถานะปัจจุบัน: **บทความ (article) เสร็จครบ** — หมวดหมู่ ตัวบทความ (`article_item_*` + content part
> แบบลากสลับลำดับ + แท็ก) และหน้าตั้งค่าโมดูล (+ ล้างแคช) เสร็จครบ schema+CRUD+UI แล้ว รายละเอียดเต็มดู
> [PRD-article.md](PRD-article.md) **banner เสร็จหมวดหมู่+ป้ายโฆษณาแล้ว** (หน้าตั้งค่ายังเป็น placeholder)
> รายละเอียดเต็มดู [PRD-banner.md](PRD-banner.md) **intropage เสร็จ schema+CRUD+UI แล้ว** (บันทึกเก็บได้หลาย
> รายการ พร้อมปุ่มด้านล่างแบบเรียงลำดับ/เพิ่ม/ลบได้ — กติกาว่าจะ "แสดงจริง" รายการไหนยังไม่ทำที่หน้าบ้าน)
> รายละเอียดเต็มดู [PRD-intropage.md](PRD-intropage.md) **page เสร็จ schema+CRUD+UI แล้ว** (หน้าเพจเดี่ยว +
> หน้าจัดโครงสร้าง แถว → คอลัมน์ → widget แบบ grid 12 — ประเภท widget ยังรอกำหนด) รายละเอียดเต็มดู
> [PRD-page.md](PRD-page.md) ที่เหลือ (popup/contact us) ยังไม่ได้เริ่ม
> มีแค่ไฟล์ว่าง `app/Http/Controllers/Admin/PostController.php` ตารางด้านล่างเป็นเป้าหมายที่จะทยอยทำ
> (ยังไม่ได้ออกแบบ schema ละเอียด)

| โมดูล | วัตถุประสงค์ | ข้อมูลหลัก (ร่าง) | หน้าจอ | permission code |
|-------|-------------|------------------|--------|------------------------|
| **บทความ (article)** — [PRD-article.md](PRD-article.md) | ข่าว/บทความ มีหมวดหมู่ 1 ระดับ (1 บทความ 1 หมวดหมู่), รองรับ SEO/AEO/GEO และหลายภาษาตาม `sys_setting` | หมวดหมู่: รูปปก+ลำดับ+สถานะ (ร่วม) + หัวข้อ/slug/SEO (แยกภาษา) · บทความ: หมวดหมู่+รูปปก+วันเผยแพร่ (ร่วม) + หัวข้อ/slug/SEO (แยกภาษา) + เนื้อหาแบบแบ่ง part (ข้อความ/รูปภาพ/วิดีโอ/เอกสาร) + แท็ก | หมวดหมู่: list, form (เสร็จ) · บทความ: list, add, edit + part editor (เสร็จ) · แท็ก: list, add, edit (เสร็จ) · ตั้งค่า: index + ล้างแคช (เสร็จ) | `article.category.view/manage/delete` `article.item.view/manage/delete` (ใช้ร่วมกับหน้าจัดการแท็กด้วย) `article.setting.manage` (seed แล้ว) |
| **banner** — [PRD-banner.md](PRD-banner.md) | แบนเนอร์รูปภาพตามตำแหน่งแสดงผล (หมวดหมู่ = ตำแหน่ง เช่น ไฮไลท์/หน่วยงานที่เกี่ยวข้อง), 1 ระดับ (1 ป้ายโฆษณา 1 หมวดหมู่), ไม่มี SEO/หลายภาษาต่อรูป | หมวดหมู่: สถานะ (ร่วม) + ชื่อ/ข้อความเกริ่นนำ (แยกภาษา) · ป้ายโฆษณา: หมวดหมู่+รูปภาพ (ร่วม ใช้รูปเดียวกันทุกภาษา)+ลิงก์+เป้าหมายลิงก์+ช่วงเวลาแสดง+ลำดับ+สถานะ (ร่วม) + ชื่อ/ข้อความเกริ่นนำ (แยกภาษา) + จำนวนคลิก (เก็บไว้ใช้อนาคต ยังไม่มี UI) | หมวดหมู่: list, form (เสร็จ) · ป้ายโฆษณา: list, add, edit (เสร็จ) · ตั้งค่า: placeholder เท่านั้น | `banner.category.view/manage/delete` `banner.item.view/manage/delete` `banner.setting.manage` (seed แล้ว) |
| **popup** | ป๊อปอัปประกาศเมื่อเข้าเว็บ | รูป/เนื้อหา, ลิงก์, ช่วงเวลาแสดง, เงื่อนไขแสดง (หน้าไหน/ความถี่), สถานะ | list, form | `popup.view` `popup.create` `popup.delete` |
| **intropage** | หน้าคั่นก่อนเข้าเว็บ (splash/โปรโมชัน) | สี/รูป/วิดีโอพื้นหลัง, ข้อความต้อนรับต่อภาษา, ปุ่ม (home + เพิ่มเอง), ช่วงเวลาประกาศ, สถานะ | list, form (บันทึกได้หลายรายการ แสดงจริงได้ทีละ 1 — คำนวณที่หน้าบ้าน) | `intropage.item.view` `intropage.item.manage` `intropage.item.delete` |
| **page (หน้าเดี่ยว)** — [PRD-page.md](PRD-page.md) | หน้าเดี่ยวที่แสดงหลาย section เช่น หน้าแรก จัดโครงสร้างแบบ แถว → คอลัมน์ → widget ด้วย grid 12 | ข้อมูลทั่วไป: รูปแทนหน้า+พื้นหลัง+สถานะ (ร่วม) + ชื่อ/เกริ่นนำ/slug/SEO (แยกภาษา) · โครงสร้าง: แถว (พื้นหลัง, container) → คอลัมน์ (ความกว้าง 1-12, พื้นหลัง) → widget (ประเภท + ตั้งค่าเฉพาะประเภทในตารางของประเภทนั้น — เสร็จ `slideshowbanner`, `slideshowarticle`, `slidesetarticle`, `slidesetbanner` อีก 3 ประเภทรอทำ) พร้อมชื่อ/เกริ่นนำแยกภาษาทุกชั้น | list, add, edit (แท็บ ข้อมูลทั่วไป / โครงสร้าง) — เสร็จ | `page.item.view` `page.item.manage` `page.item.delete` (seed แล้ว) |
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

## 5. แบบแผนหน้าจอหลังบ้าน (Back-office screen blueprint)

ทุกโมดูลหลังบ้าน (เนื้อหาและจัดการระบบ) ให้ยึดแบบแผนนี้เพื่อให้ประสบการณ์ใช้งานเหมือนกันทั้งระบบ
**ต้นแบบอ้างอิง:** โมดูล `system.user` — `app/Http/Controllers/Admin/System/UserController.php`
+ `resources/js/Pages/Admin/System/User/*` + `app/Http/Requests/Admin/System/*`

### 5.1 โครง route ต่อ 1 โมดูล

ตั้งชื่อ **เอกพจน์** ให้ URL segment ตรงกับ segment ในชื่อ route และจัดกลุ่มด้วย `Route::prefix('<section>/<thing>')`

| การกระทำ | Method + URI | ชื่อ route | สิทธิ์ที่เช็ก | ไม่ผ่าน → |
|---|---|---|---|---|
| รายการ | GET `/admin/<s>/<t>` | `admin.<s>.<t>.index` | `<code>.view` | redirect dashboard |
| ฟอร์มเพิ่ม | GET `/admin/<s>/<t>/add` | `admin.<s>.<t>.add` | `<code>.manage` | redirect index |
| บันทึกเพิ่ม | POST `/admin/<s>/<t>` | `admin.<s>.<t>.store` | `<code>.manage` | redirect index |
| ฟอร์มแก้ไข | GET `/admin/<s>/<t>/{id}/edit` | `admin.<s>.<t>.edit` | `<code>.view` | redirect index |
| บันทึกแก้ไข | PUT `/admin/<s>/<t>/{id}` | `admin.<s>.<t>.update` | `<code>.manage` | redirect index |
| ลบ | DELETE `/admin/<s>/<t>/{id}` | `admin.<s>.<t>.destroy` | `<code>.delete` | redirect index |
| sub-action (ถ้ามี) | GET/PUT `/admin/<s>/<t>/{id}/<action>` | `admin.<s>.<t>.<action>[.update]` | `<code>.<action>` | redirect index |

- รับ `{id}` เป็น **id ดิบ** (ไม่ใช้ route-model-binding) → ใน controller `Model::where(...)->find($id)` เอง
  ถ้าไม่พบ / ถูก soft-delete → `redirect()->route('admin.<s>.<t>.index')` (ไม่ใช่ 404)
- `sys_menu.route_name` ของเมนู sidebar ตั้งเป็น `admin.<s>.<t>.index` เสมอ → `HandleInertiaRequests::adminMenu()`
  จะส่ง `activePattern` = `admin.<s>.<t>.*` ทำให้เมนูย่อยไฮไลต์ครอบทุกหน้าในโมดูล และหัวข้อกลุ่มไฮไลต์อัตโนมัติ

### 5.2 การตรวจสอบสิทธิ์

- **เช็กในทุก method ของ controller** ด้วย `$request->user()->hasPermission('<code>')` — ไม่ผ่าน `return redirect()->route(...)`
  ตามตาราง 5.1 (**ไม่ใช้ `abort(403)`** สำหรับการเข้าหน้าปกติ)
- controller ส่ง prop `can` = map ของ boolean (`['manage' => …, 'delete' => …, …]`) → Vue เช็ก `v-if="can.xxx"`
  เพื่อซ่อน/แสดงปุ่ม แท็บ ลิงก์
- action code รูปแบบ `<module>.<sub>.<action>` โดย `<action>` = `view` / `manage` (เพิ่ม+แก้รวมกัน) / `delete` /
  sub-action อื่น เช่น `password` — ต้อง seed ไว้ใน `DatabaseSeeder` (tree: `manage` เป็นลูกของ `view`, ที่เหลือลูกของ `manage`)
- self-guard (กันทำกับบัญชีตัวเอง เช่น ระงับ/ลบ/เปลี่ยนกลุ่มตัวเอง) เช็กใน controller หลัง validate →
  `return back()->withErrors([...])`; ฝั่ง Vue ก็ disable ตัวเลือกที่เกี่ยวข้องเมื่อ `isSelf`

### 5.3 หน้ารายการ (index)

- **Controller ส่ง props:** `items` (LengthAwarePaginator `->paginate()->withQueryString()->through(fn ($m) => [...])`
  map เฉพาะฟิลด์ที่ตารางใช้), `filters` (ค่าที่กรองอยู่ตอนนี้), `sort` + `direction`, `*Options` สำหรับ dropdown,
  `can` (อย่างน้อย `manage`)
- **UI:** `<AdminLayout>` → `#header` = `<PageHeader :title :breadcrumbs>` (breadcrumb เริ่มจาก Dashboard, ตัวสุดท้ายเป็นข้อความ)
- **กล่องกรอง** (การ์ดขาว): `<TextInput>` ค้นหา (`@keyup.enter`), `<SearchableSelect>` ต่อ 1 ตัวกรอง,
  ปุ่ม **"ค้นหา"** (icon `Search`) + **"เริ่มใหม่"** (icon `RotateCcw`); ปุ่ม **"เพิ่ม…"** (icon `Plus`, `v-if="can.manage"`)
  ต่อท้ายในแถวเดียวกันโดยมี divider (`w-px bg-gray-200`) คั่น
- **ยิงค้นหา/กรอง/เรียง/เปลี่ยนหน้า:** `router.get(route('...index'), params, { preserveState: true, preserveScroll: true, replace: true })`
- **ตาราง:** หัวคอลัมน์กดเรียงได้ (toggle asc/desc) + ไอคอน `ArrowUp` / `ArrowDown` (คอลัมน์ที่เรียงอยู่) /
  `ArrowUpDown` (ยังไม่เรียง); คลิกทั้งแถว → ไปหน้าแก้ไข; คอลัมน์สถานะใช้ `<StatusBadge :status>`;
  วันที่ format ด้วย `@/utils/date` (`formatDate` / `formatDateTime`)
- **ค่าเริ่มต้น:** เรียงจาก `created_at` มากไปน้อย
- **ท้ายตาราง:** ข้อความ "แสดง X–Y จาก Z รายการ" + `<SearchableSelect>` จำนวนต่อหน้า (`[10, 25, 50, 100]`) + `<Pagination :links="items.links">`

### 5.4 หน้าแบบฟอร์ม (add / edit)

- **FormRequest แยก** ที่ `app/Http/Requests/Admin/<Section>/{Store,Update}<Thing>Request.php`
  - unique ที่ต้อง scope: `Rule::unique(Model::class)->where(fn ($q) => $q->where(...)->whereNull('deleted_at'))`
    (+ `->ignore($this->route('<param>'))` ในตัว Update)
  - รหัสผ่าน: `Password::min(8)->mixedCase()->numbers()->symbols()` + แสดง hint ใต้ช่อง
- **UI:** `<AdminLayout>` → `#header` `<PageHeader>`; ฟอร์ม**เต็มความกว้าง** (ไม่จำกัด `max-width`);
  แบ่งเป็นการ์ด `rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8`
- **field block:** `<InputLabel :required>` (ใส่ `required` → มี `*` สีแดงหลัง label) → `<TextInput>` / `<SearchableSelect>` →
  `<InputError :message="form.errors.<field>">` (ข้อความสีแดงใต้ช่อง)
- **ปุ่ม** อยู่**นอกการ์ด** แถวล่างสุด: **"บันทึก"** (`v-if="can.manage"`, icon `Save`, `type="submit"`) +
  **"ลบ"** (`v-if="can.delete && !isSelf"`, `<DangerButton>` icon `Trash2` → เปิด `<ConfirmDialog>`) + ลิงก์ **"ยกเลิก/กลับไปหน้ารายการ"**
- **แท็บ** เมื่อหน้าแก้ไขมี sub-action (เช่น เปลี่ยนรหัสผ่าน): `<TabNav :tabs>` — แสดงเฉพาะเมื่อผู้ใช้มีสิทธิ์ sub-action นั้น
  (ถ้าเหลือแท็บเดียวก็ไม่ต้องแสดง)
- **บันทึกสำเร็จ:** controller `return redirect()->route('admin.<s>.<t>.edit', $id)->with('success', 'ข้อความไทย')`
  (หน้า add ก็ redirect ไปหน้า edit ของ record ที่เพิ่งสร้าง)

### 5.5 Dialog

| ประเภท | ใช้ component | พฤติกรรม |
|---|---|---|
| **แจ้งผลสำเร็จ** | ไม่ต้องทำเอง — controller `->with('success', '…')` แล้ว `AdminLayout` เด้ง `<SuccessDialog>` ให้อัตโนมัติ | modal กลางจอ ไอคอนเช็กเขียว + นับถอยหลัง 5 วิ แล้วปิดเอง (กด Esc / คลิกนอกกล่อง / ปุ่ม "ปิดตอนนี้" ได้) |
| **ยืนยันการลบ / การกระทำเสี่ยง** | `<ConfirmDialog :show :title :processing @confirm @cancel>` (ข้อความอยู่ใน default slot) | overlay teleport ธรรมดา, ปุ่มยกเลิก + ยืนยัน (แดง), กด Esc ได้ |

- กลไก flash: shared prop `flash.success` + `flash.successId` (uuid ใหม่ทุกครั้ง) ใน `HandleInertiaRequests::share()`
- **ห้ามใช้ `resources/js/Components/Modal.vue` (native `<dialog>`) กับ dialog ใหม่** — มีปัญหา overlay ค้างกดไม่ได้
  ให้ใช้ `SuccessDialog` / `ConfirmDialog` เป็นต้นแบบ (teleport + `<Transition>` + `z-[100]`)

### 5.6 Component / helper กลางที่ต้องใช้ซ้ำ

- **shared** (`resources/js/Components/`): `PageHeader`, `Breadcrumbs`, `TabNav` (อยู่ใต้ `Admin/`);
  `SearchableSelect` (dropdown พิมพ์ค้นหาได้ — ใช้แทน `<select>` ทุกจุดในระบบ ดู §5.7), `Pagination`, `StatusBadge`,
  `SuccessDialog`, `ConfirmDialog`, `InputLabel` (prop `required`),
  `InputError`, `TextInput`, `PrimaryButton` / `SecondaryButton` / `DangerButton`
- **helper:** `@/utils/date` → `formatDate(iso)` / `formatDateTime(iso)` (คืน `'-'` เมื่อว่าง)
- **เทส:** helper `actingAsUserWithPermissions([...codes])` ใน `tests/Pest.php` (สร้าง usergroup + attach action + `actingAs`);
  seed `DatabaseSeeder` ใน `beforeEach`; assert `->assertRedirect(...)` สำหรับเคสไม่มีสิทธิ์,
  `->assertInertia(fn (Assert $page) => …)` สำหรับ props, `->assertSessionHas('success')` หลังบันทึก

### 5.7 Dropdown — ใช้ `SearchableSelect.vue` เสมอ (ไม่มี `SelectInput.vue`/`<select>` ธรรมดาแล้ว)

`resources/js/Components/SearchableSelect.vue` แทนที่ `<select>`/`SelectInput.vue` เดิมทุกจุดในระบบแล้ว
(ลบไฟล์ `SelectInput.vue` ออกไปทั้งหมด) — dropdown ใหม่ทุกจุดในอนาคต **ต้องใช้ตัวนี้** ไม่ใช่ native `<select>`

- **v-model** เป็น `string` เสมอ เทียบเคียงของเดิม — ผูกกับ `option.value` ที่ตรงกันเท่านั้น
- **prop `options`**: `{ value: string; label: string; disabled?: boolean }[]` (แทนการเขียน `<option>` ลูก)
  ถ้าตัวเลือกมาจาก prop/array ที่มีอยู่แล้ว (เช่นรายการหมวดหมู่, กลุ่มผู้ใช้งาน) แปลงเป็น `computed(() => ...map(...))`
  ก่อนส่งเข้า `:options` — ถ้าเป็นชุดคงที่ (เช่นสถานะ Y/N) เขียนเป็น literal array ในเทมเพลตตรง ๆ ได้เลย
  ค่า Y/N มาตรฐานมี `STATUS_OPTIONS`/`STATUS_FILTER_OPTIONS` ให้ใช้ร่วมกันแล้วที่ `@/utils/options`
- **prop `placeholder`**: ข้อความตอนยังไม่มีค่าเลือก (เทียบเคียง `<option disabled>` เดิม) — ไม่ต้องใส่ถ้า option
  ที่มี `value: ''` มีอยู่แล้วในชุดตัวเลือกเอง (เช่น dropdown กรองที่มี "ทุกสถานะ" เป็นตัวเลือกจริง)
- **prop `disabled`**: ปิดทั้งฟิลด์ (เทียบเคียง `<select disabled>`) ส่วน `option.disabled` ปิดเฉพาะตัวเลือกเดียว
  (เทียบเคียง `<option disabled>` เดิม เช่น ห้ามระงับบัญชีของตัวเอง)
- **`id` prop**: ผูกกับ `<label for="...">` ได้เหมือนเดิม (component จัดการ bind ให้ปุ่ม/ช่องค้นหาที่กำลังแสดงอยู่เอง
  ไม่ใช่ div ครอบนอก) — ต้องส่งผ่าน prop `id` ไม่ใช่ปล่อยให้ fallthrough attrs ทำเอง
- **event ที่ยิงตอนค่าเปลี่ยน**: ใช้ `@update:model-value="..."` เสมอ (ไม่มี native `change` event ให้ฟังอีกต่อไป
  เพราะ root ไม่ใช่ `<select>` จริง) — จุดที่เคย `@change="search"` (เช่น per_page dropdown ท้ายตาราง) ให้เปลี่ยนเป็น
  `@update:model-value="search"` แทน ทำงานเหมือนเดิมเพราะ `defineModel` ก็ยิง event นี้ตอนค่าเปลี่ยนอยู่แล้ว

## 6. สถานะปัจจุบัน vs เป้าหมาย

| ส่วน | ปัจจุบัน | เป้าหมาย |
|------|----------|----------|
| Auth หลังบ้าน (login/register/reset/verify) | ✅ มี (Breeze ย้ายมาใต้ `/admin`) + เช็ก `user_type='back'` / `status='Y'` + บันทึกสถิติ login แล้ว | เพิ่มล็อกบัญชีอัตโนมัติเมื่อ login ผิดเกินเกณฑ์ (อ่านจาก `sys_setting`) |
| Layout หลังบ้าน (sidebar/header มืด) | ✅ มี (สไตล์ TailAdmin) | ต่อเมนูโมดูล/จัดการระบบเข้า sidebar |
| ระบบสิทธิ์ (`sys_*`) | ✅ ตาราง + model + `hasPermission()` + seeder ตัวอย่าง | หน้าจัดการกลุ่ม/สิทธิ์แบบ tree + middleware บังคับสิทธิ์ |
| จัดการผู้ใช้งานหลังบ้าน (CRUD `sys_user` `user_type='back'`) | ✅ เสร็จแล้ว — list (ค้นหา/กรอง/เรียง/paging) + add + edit + เปลี่ยนรหัสผ่าน; เป็น **ต้นแบบตาม §5** | — |
| จัดการกลุ่มผู้ใช้งาน (CRUD `sys_usergroup` + กำหนดสิทธิ์) | ✅ list + add + edit + **หน้ากำหนดสิทธิ์** (tree `sys_action_group`/`sys_action`, checkbox parent→ลูก, เลือก/ไม่เลือกทั้งหมดต่อกลุ่ม, บันทึกแบบ detach+attach); `can_edit`/`can_delete`, guard ชื่อซ้ำ/มีสมาชิก | — |
| profile | ✅ มี (แก้ชื่อ/ช่องทางติดต่อ/อีเมล) | เพิ่มอัปโหลดรูปโปรไฟล์ (อนาคต) |
| dashboard | 🟡 placeholder (การ์ดสถิติ "—") | ต่อสถิติจริงเมื่อมีโมดูล |
| โมดูลเนื้อหาทั้ง 6 | 🟡 บทความ (article) เสร็จครบ — หมวดหมู่ ตัวบทความ (list/add/edit + part editor + แท็ก) และหน้าตั้งค่าโมดูล เสร็จครบ; banner เสร็จหมวดหมู่+ป้ายโฆษณา (ตั้งค่ายังเป็น placeholder); intropage เสร็จ list/add/edit + ปุ่มแบบเรียงลำดับ; page เสร็จ list/add/edit + จัดโครงสร้างแถว/คอลัมน์/widget (ประเภท widget เสร็จ `slideshowbanner`/`slideshowarticle`/`slidesetarticle`/`slidesetbanner`, ที่เหลือรอทำ); popup/contact us ยังไม่มี | ทยอยทำ |
| จัดการเมนูหลังบ้าน (`sys_menu_group`/`sys_menu`) | 🟢 ตาราง + seed + `AppSidebar` อ่านจาก DB (กรองตามสิทธิ์) | หน้า CRUD จัดเมนู |
| จัดการเมนูหน้าบ้าน (`front_menu_info`/`front_menu_detail`) — [PRD-system-frontmenu.md](PRD-system-frontmenu.md) | 🟡 schema + admin CRUD (list/tree, add/edit dialog, เรียงลำดับแบบลาก, แสดง/ซ่อน, ลบ) เสร็จแล้ว | ยังไม่ render จริงที่หน้าบ้าน (nav level 1 แนวนอน, level 2+ แนวตั้ง) |
| template | ❌ ยังไม่มี | ทยอยทำ (ดู PRD-system.md) |
| ประวัติ (`log_back_*`) | ✅ เสร็จ (access/login/action + หน้ารายการทั้ง 3) | log ฝั่งหน้าบ้านยังไม่ทำ |
| file management | ✅ เสร็จ (list/upload/folder/picker) | — |
| ตั้งค่าระบบ (`sys_setting`) | 🟡 มีตาราง + seed ตัวอย่างแล้ว | หน้า UI จัดการ + helper อ่านค่า |

## 7. การปรับ schema รอบนี้ (เฟส 0)

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

## 8. Roadmap (ร่าง)

| เฟส | ขอบเขต |
|-----|--------|
| **0 — schema base** *(รอบนี้)* | ปรับ `sys_user`/`sys_usergroup`/`sys_action*` + สร้าง `sys_setting` + เปิด SoftDeletes + auth หลังบ้านเช็ก `user_type`/`status` + บันทึกสถิติ login + ปรับ seeder/factory/profile/register/เทส |
| 1 — จัดการผู้ใช้ & สิทธิ์ | ~~CRUD `sys_user`~~ ✅ · ~~CRUD `sys_usergroup` + หน้ากำหนดสิทธิ์แบบ tree~~ ✅; เหลือ middleware บังคับสิทธิ์, ล็อกบัญชีเมื่อ login ผิดเกินเกณฑ์ (`sys_setting`), self-guard เปลี่ยนกลุ่มบัญชีตัวเอง |
| 2 — ตั้งค่าระบบ & template & เมนู | หน้า `sys_setting`, `sys_template`; หน้า CRUD เมนูหลังบ้าน (`AppSidebar` อ่านจาก DB แล้ว); `sys_front_menu` (tree) สำหรับหน้าบ้าน |
| 3 — โมดูลเนื้อหาแรก | บทความ (article) + page (หน้าเดี่ยว) + file management (`sys_file`) |
| **4 — โมดูลที่เหลือ** *(banner เสร็จหมวดหมู่+ป้ายโฆษณาแล้ว, intropage เสร็จแล้ว, page เสร็จแล้ว)* | ~~banner~~ ✅ (ตั้งค่ายังเป็น placeholder); ~~intropage~~ ✅; ~~page~~ ✅ (ประเภท widget เสร็จ `slideshowbanner`/`slideshowarticle`/`slidesetarticle`/`slidesetbanner`, ที่เหลือรอทำ); เหลือ popup, contact us |
| 5 — ประวัติ & dashboard จริง | `sys_log_login` / `sys_log_visit` / `sys_log_action` + สถิติ dashboard |
