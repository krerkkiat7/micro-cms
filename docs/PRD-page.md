# PRD — โมดูล Page (หน้าเพจ)

เอกสารนี้ลงรายละเอียดของโมดูล **Page** ในหลังบ้าน — หน้าเดี่ยวที่แสดงข้อมูลหลายส่วน (หลาย section) เหมาะกับหน้าแรก
หรือหน้าที่แสดงข้อมูลหลากหลาย โดยจัดโครงสร้างการแสดงผลเป็น **แถว (row) → คอลัมน์ (column) → widget** ด้วย grid 12
ภาพรวมทั้งระบบดูที่ [PRD-overview.md](PRD-overview.md) §5

สถานะ: 🟢 มีแล้ว · 🟡 มีบางส่วน · 🔴 ยังไม่มี

| # | หัวข้อ | ตารางหลัก | สถานะ |
|---|--------|-----------|-------|
| 1 | หน้าเพจ (ข้อมูลทั่วไป) | `page_item_info`, `page_item_detail` | 🟢 schema + controller/route/UI (list, add, edit) เสร็จครบ |
| 2 | โครงสร้าง แถว → คอลัมน์ → widget | `page_item_row/column/widget` + `*_detail` | 🟢 schema + หน้าจัดโครงสร้างแบบเห็นผลจริง + บันทึกเสร็จ |
| 3 | ประเภท widget และการตั้งค่าเฉพาะประเภท | `page_item_widget.widget_type`/`setting` | 🔴 ยังไม่ได้กำหนด — ตอนนี้มี `placeholder` ประเภทเดียว (§3) |
| 4 | การแสดงผลหน้าบ้านตาม slug/โครงสร้าง | (front-office) | 🔴 ยังไม่ได้ทำ |

---

## 0. ภาพรวมโมดูล

- 1 หน้าเพจ = ข้อมูลทั่วไป (ชื่อ/ข้อความเกริ่นนำ/SEO/พื้นหลัง/รูปแทนหน้า) + **โครงสร้าง** ที่ประกอบด้วยแถวเรียงลำดับ
  แต่ละแถวมีคอลัมน์เรียงลำดับ (ความกว้างตาม grid 12) และแต่ละคอลัมน์มี widget เรียงลำดับ
- โครงสร้างเดิมเก็บเป็น JSON ก้อนเดียวในคอลัมน์เดียว (ใหญ่มาก จัดการ/ตรวจสอบตรง ๆ ไม่ได้ เช่นเช็ก `file_id`) — โมดูลนี้
  แยกเป็นตารางจริงทุกชั้น
- รองรับ**หลายภาษาตามที่ระบบตั้งค่าไว้** เหมือนโมดูลอื่น (`App\Support\Setting::selectedLanguages()`/`defaultLanguage()`)
  — ชื่อหน้า **required เฉพาะภาษาหลัก**; ชื่อ/ข้อความเกริ่นนำของแถว/คอลัมน์/widget เป็น optional ทั้งหมด
- **ไม่มีหมวดหมู่** — สิทธิ์/เมนูที่ seed ไว้มี tier เดียว (`page.item.*`)
- หน้าแก้ไขแบ่งเป็น 2 แท็บ (`TabNav`) — **ข้อมูลทั่วไป** (`admin.page.item.edit`) กับ **โครงสร้าง**
  (`admin.page.item.layout`) บันทึกแยกกัน (เปลี่ยนแท็บโดยยังไม่บันทึกโครงสร้าง จะมี confirm เตือน)

---

## 1. หน้าเพจ (ข้อมูลทั่วไป)

**Data model — `page_item_info`** (ข้อมูลร่วม ไม่แยกภาษา)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK autoincrement | |
| `intro_image_id` | bigint FK → `file_info.id` null (nullOnDelete) | รูปแทนทั้งหน้า (ใช้เป็นโลโก้ตอนทำ SEO ได้) ไม่บังคับ |
| `background_color` | `varchar(20)` null | สีพื้นหลังของทั้งหน้า — hex หรือ `transparent` |
| `background_image_id` | bigint FK → `file_info.id` null (nullOnDelete) | รูปภาพพื้นหลัง |
| `background_repeat` / `background_size` / `background_attachment` / `background_position` | `varchar(50)` null | CSS background เหมือน Intropage (`repeat`…, `auto`/`cover`/`contain`, `scroll`/`fixed`, preset ตำแหน่ง) |
| `layout_updated_at` | `datetime` null | บันทึกโครงสร้างล่าสุดเมื่อ (ตั้งตอนบันทึกที่แท็บโครงสร้างเท่านั้น) |
| `layout_updated_by` | bigint null (`sys_user.id`, ไม่มี FK) | ผู้บันทึกโครงสร้างล่าสุด |
| `status` | `char(1)` default `Y` | `Y` = เปิดใช้งาน, `N` = ปิด |
| `is_temp` | `char(1)` default `N` | `Y` = ข้อมูลตัวอย่าง/ตั้งต้น (ตั้งตอน seed — ลบทิ้งภายหลังได้ หรือจะใช้ต่อไปก็ได้) |
| `created_by`/`updated_by`/`deleted_by`, `timestamps`, `deleted_at` | | `SoftDeletes` |

**Data model — `page_item_detail`** (ข้อมูลแยกตามภาษา — PK = `id` + `lang`)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `unsigned bigint` | = `page_item_info.id` (FK, cascadeOnDelete) |
| `lang` | `char(2)` | ส่วนหนึ่งของ PK |
| `title` | `varchar(500)` null | ชื่อ — required เฉพาะภาษาหลัก |
| `intro_text` | `varchar(2000)` null | ข้อความเกริ่นนำ |
| `slug` | `varchar(250)` null | unique ต่อภาษา (`unique(lang, slug)`) |
| `meta_title`, `meta_description`, `meta_keywords`, `og_title`, `og_description` | `varchar` null | SEO / AEO / GEO (ขนาดเท่า `article_item_detail`) |
| `status` | `char(1)` default `Y` | |
| `created_by`/`updated_by`/`deleted_by`, `timestamps`, `deleted_at` | | |

> ตั้งชื่อคอลัมน์ข้อความเกริ่นนำว่า `intro_text` (ไม่ใช่ `introtext`) ให้ตรงกับ `article_item_detail`/`banner_item_detail`
> — ตกลงกับผู้ใช้แล้ว ใช้แบบเดียวกันทั้ง 4 ตาราง `*_detail`
>
> Eloquent ไม่รองรับ composite primary key เต็มรูปแบบ — ค้นด้วย `PageItemDetail::where('id', ...)->where('lang', ...)`
> เสมอ ห้ามใช้ `find()`/`save()` (save จะ update ด้วย `id` อย่างเดียวทุกภาษา) ให้เช็ก `exists()` แล้ว `update()` ผ่าน query
> builder หรือ `create()`

**Slug ซ้ำ / ใช้ซ้ำหลังลบ** — validation เช็ก slug ซ้ำต่อภาษาโดยนับเฉพาะหน้าที่ยังไม่ถูกลบ (ผ่าน `page_item_info.deleted_at`
เพราะ `page_item_detail` ไม่ถูก soft delete พร้อมพาเรนต์) และตอนลบหน้า `destroy()` จะเคลียร์ `slug` ของ `page_item_detail`
เป็น null เพื่อไม่ให้ unique index ระดับ DB ชนเมื่อนำ slug เดิมไปใช้กับหน้าใหม่

**Model** — `App\Models\PageItemInfo` (`SoftDeletes`; `introImage()`/`backgroundImage()` belongsTo `FileInfo`; `details()`
hasMany; `rows()` hasMany เรียง `sort_order`), `App\Models\PageItemDetail` (`SoftDeletes`, `$incrementing = false`)

**หน้าจอ** — `Admin/Page/Item/{Index,Add,Edit,Layout}.vue`
- **Index**: ค้นหาชื่อ, กรองสถานะ, เรียงชื่อ/วันที่สร้าง/สถานะ, paging — คอลัมน์ **ชื่อ, วันที่สร้าง, สถานะ**
  (ชื่อที่แสดงเป็นของภาษาหลักเสมอ)
- **Add / Edit (ข้อมูลทั่วไป)**: ใช้ข้อมูลจาก `page_item_info` + `page_item_detail` — 3 การ์ดผ่าน component ร่วม
  `Components/Admin/PageItem/PageItemFormFields.vue`: ข้อมูลทั่วไป (สถานะ, รูปแทนหน้า, พื้นหลังของทั้งหน้า), ข้อมูลหน้าเพจ
  แยกภาษา (ชื่อ/ข้อความเกริ่นนำ ผ่าน `LangFieldGroup`), SEO / AEO / GEO (slug, meta title/description/keywords, og title/description)
  — หลังเพิ่มสำเร็จ redirect ไปหน้าแก้ไข ให้ไปแท็บ "โครงสร้าง" ต่อได้

**Permission code** (seed ไว้แล้วใน `DatabaseSeeder.php`) — `page.item.view`, `page.item.manage`, `page.item.delete`
(เมนู "หน้าเพจ" → `admin.page.item.index` seed ไว้แล้วใน `MenuSeeder.php`)

| การกระทำ | สิทธิ์ที่ต้องมี |
|----------|----------------|
| เข้าหน้ารายการ / แก้ไข (ดู) / ดูโครงสร้าง | `page.item.view` |
| เพิ่ม / บันทึกข้อมูลทั่วไป / บันทึกโครงสร้าง | `page.item.manage` |
| ลบหน้าเพจ | `page.item.delete` |

**Route** (`routes/web.php`, prefix `admin/page/item`, controller `App\Http\Controllers\Admin\Page\PageItemController`)

| Route name | Method / URL | หมายเหตุ |
|------------|--------------|----------|
| `admin.page.item.index` | GET `/` | |
| `admin.page.item.add` / `.store` | GET `/add` / POST `/` | |
| `admin.page.item.edit` / `.update` | GET `/{item}/edit` / PUT `/{item}` | ข้อมูลทั่วไป |
| `admin.page.item.destroy` | DELETE `/{item}` | soft delete |
| `admin.page.item.layout` / `.layout.update` | GET / PUT `/{item}/layout` | โครงสร้าง |

**Log action** — `LogBackAction::record()`:
`module_code = "page.item"` (create/view/update/delete ของข้อมูลทั่วไป) และ `module_code = "page.item.layout"`
(`view` ตอนเปิดหน้าโครงสร้าง, `update` ตอนบันทึกโครงสร้าง) + `LogBackAccess::record()` ตาม pattern ของโมดูลอื่น
(หน้ารายการเรียกเฉพาะตอนไม่มี query string)

**Migration** — `database/migrations/2026_09_20_000001_create_page_item_tables.php` (สร้างทั้ง 8 ตารางของโมดูล) และ
`2026_09_20_000002_add_text_style_to_page_item_layout_tables.php` (เพิ่มหัวเรื่องรอง + การจัดรูปแบบตัวอักษร + พื้นหลังของ widget — §2)

**Seeder** — `database/seeders/PageSeeder.php` (เรียกจาก `DatabaseSeeder`) สร้างหน้าเพจตัวอย่าง 1 หน้า (`is_temp='Y'`,
slug `sample-page`) พร้อมโครงสร้าง 3 แถว: hero (คอลัมน์ 12, เต็มความกว้าง + สีพื้นหลัง), เนื้อหา (คอลัมน์ 8 + 4 ใน container),
ส่วนท้ายมีสีพื้นหลัง (คอลัมน์ 4 + 4 + 4) — สองแถวแรกเปิด "แสดงหัวเรื่อง" พร้อมหัวเรื่องรอง/ข้อความเกริ่นนำ, ทุกคอลัมน์มี widget `placeholder` 1 ตัว; `updateOrCreate` resolve หน้าเดิมจากชื่อ
ของภาษาหลัก และสร้างโครงสร้างเฉพาะเมื่อหน้านั้นยังไม่มีแถว จึงรันซ้ำได้ (`php artisan db:seed --class=PageSeeder`)

---

## 2. โครงสร้าง แถว → คอลัมน์ → widget

ตารางทุกตารางมี `status` (`Y` แสดง / `N` ซ่อน), audit `created_by`/`updated_by`/`deleted_by`, `timestamps`, `deleted_at`
(`SoftDeletes`) ตามรูปแบบโปรเจกต์ และตาราง `*_detail` แยกภาษาด้วย PK = `id` + `lang` (FK cascade ไปยังตารางแม่)

| ตาราง | คอลัมน์หลัก |
|-------|-------------|
| `page_item_row` | `page_item_info_id` FK, `sort_order`, `show_title` (`Y`/`N`), `use_container` (`Y` = อยู่ใน container, `N` = เต็มความกว้าง), `background_color`, `background_image_id` + `background_repeat/size/attachment/position` (ชุดเดียวกับหน้า), การจัดรูปแบบตัวอักษร 12 คอลัมน์ (ด้านล่าง), `status` |
| `page_item_row_detail` | `id`, `lang`, `title` `varchar(250)`, `subtitle` `varchar(250)` (หัวเรื่องรอง), `intro_text` `varchar(2000)` |
| `page_item_column` | `page_item_row_id` FK, `sort_order`, `show_title`, `column_size` (1 - 12), `background_*` (ชุดเดียวกับแถว), การจัดรูปแบบตัวอักษร 12 คอลัมน์, `status` |
| `page_item_column_detail` | `id`, `lang`, `title`, `subtitle`, `intro_text` |
| `page_item_widget` | `page_item_column_id` FK, `sort_order`, `show_title`, `widget_type` `varchar(20)`, `setting` json, `background_*` (ชุดเดียวกับแถว/คอลัมน์), การจัดรูปแบบตัวอักษร 12 คอลัมน์, `status` |
| `page_item_widget_detail` | `id`, `lang`, `title`, `subtitle`, `intro_text` |

### การจัดรูปแบบตัวอักษร (หัวเรื่อง / หัวเรื่องรอง / ข้อความเกริ่นนำ)

แถว/คอลัมน์/widget มีข้อความ 3 ส่วนที่จัดรูปแบบได้เหมือนกัน — **หัวเรื่อง** (`title`), **หัวเรื่องรอง** (`subtitle`) และ
**ข้อความเกริ่นนำ** (`intro_text`) — แต่ละส่วนมีค่าตั้งได้ 4 ค่า เก็บเป็นคอลัมน์จริง (ไม่ใช้ JSON) ชื่อ `<ส่วน>_<ค่า>`
รวม 12 คอลัมน์ต่อตาราง (`title_font_size`, `title_font_family`, `title_align`, `title_color`, `subtitle_*`, `intro_text_*`):

| ค่า | ชนิด | ค่าเริ่มต้น / เงื่อนไข |
|-----|------|------------------------|
| `*_font_size` | `unsigned smallint` (px) | หัวเรื่อง/หัวเรื่องรอง/เกริ่นนำ — แถว 32/20/16, คอลัมน์ 24/18/16, widget 20/16/14; รับ 8 - 120 (dropdown มีขนาดสำเร็จรูป 12 - 96) |
| `*_font_family` | `varchar(50)` | **Sarabun**; ต้องอยู่ในรายการฟอนต์ไทย (ด้านล่าง) |
| `*_align` | `varchar(10)` | **`center`** (กึ่งกลาง); `left` ชิดซ้าย / `center` กึ่งกลาง / `right` ชิดขวา |
| `*_color` | `varchar(20)` | **`#000000`** (ดำ); รหัส hex เท่านั้น — **ไม่มี transparent** (ต่างจากสีพื้นหลัง) |

- `App\Support\PageTextStyle` เป็นแหล่งเดียวของรายการคอลัมน์/ค่าที่อนุญาต/ฟอนต์ (model ใช้ trait `HasPageTextStyle` เติม `$fillable`,
  validation ใช้ `textStyleRules()`, `PageLayoutSync` ใช้ `PageTextStyle::fromInput()`); หน้าจอสร้างค่าเริ่มต้นใน
  `utils/pageLayout.ts` (`defaultTextStyles()` — ขนาดต้องตรงกับ default ใน migration)
- **ฟอนต์ไทยที่นิยมใช้ทำหัวเรื่อง 29 ตัว** (ฟอนต์ฟรีจาก Google Fonts ที่รองรับภาษาไทย — dropdown เรียงตามตัวอักษรภาษาอังกฤษ
  A-Z ผ่าน `PageTextStyle::fontNames()`, ค่าเริ่มต้นคือ Sarabun): Anuphan, Athiti, Bai Jamjuree, Chakra Petch, Charmonman, Chonburi,
  Fahkwang, IBM Plex Sans Thai, Itim, K2D, Kanit, Kodchasan, KoHo, Krub, Maitree, Mali, Mitr, Niramit, Noto Sans Thai,
  Noto Serif Thai, Pattaya, Pridi, Prompt, Sarabun, Sriracha, Srisakdi, Taviraj, Thasadith, Trirong — หน้าโครงสร้างโหลดสไตล์ชีตของทุกตัวจาก Bunny Fonts (มิเรอร์ Google Fonts ที่ `app.blade.php` ใช้โหลด Sarabun อยู่แล้ว —
  `PageTextStyle::fontsStylesheetUrl()`) เบราว์เซอร์ดาวน์โหลดไฟล์ฟอนต์เฉพาะตัวที่ถูกใช้จริง; เพิ่มฟอนต์ใหม่ที่ `PageTextStyle::FONTS`
  (ต้องเช็กว่ามีน้ำหนักตัวอักษรที่ระบุจริง ไม่งั้นสไตล์ชีตทั้งชุดโหลดไม่ขึ้น)
- **การแสดงผล**: เมื่อ `show_title = Y` แสดงหัวเรื่อง + หัวเรื่องรอง + ข้อความเกริ่นนำ (เฉพาะส่วนที่กรอกแล้ว) ตามที่ตั้งค่า —
  หัวเรื่องใช้แท็ก **`<h2>` (แถว) / `<h3>` (คอลัมน์) / `<h4>` (widget)** ส่วนหัวเรื่องรองและข้อความเกริ่นนำเป็น `<div>` ธรรมดาเสมอ
  (ตัวอย่างในหน้าโครงสร้างคือ `LayoutTexts.vue`; หน้าบ้านต้อง render ให้ตรงกัน)
- แถว/คอลัมน์/widget ที่ **เพิ่มใหม่** ตั้ง `background_color = transparent` เป็นค่าเริ่มต้น

**Model** — `PageItemRow`/`PageItemColumn`/`PageItemWidget` (+ `*Detail`) ตาม pattern ข้างบน: `rows()`/`columns()`/`widgets()`
เรียง `sort_order` (แล้ว `id`), `PageItemWidget::TYPES` = รายการ `widget_type` ที่รองรับ (validation ใช้ค่านี้)

### หน้าโครงสร้าง (`Admin/Page/Item/Layout.vue`)

ทำให้เหมือนการสร้างโครงสร้างหน้าจอจริง — เห็นผลการจัดการที่หน้าจอทันที แต่ละชั้นมี**แถบจัดการของตัวเองที่มุมซ้ายบน**
(สีต่างกันตามชนิด: แถว = น้ำเงิน, คอลัมน์ = เขียว, widget = เหลือง):

| ชั้น | แถบจัดการ |
|------|-----------|
| **แถว** | ไอคอนเรียงลำดับ (กดแล้วเปิด dialog เรียงแถว — `RowReorderDialog`) · เฟือง (dialog ตั้งค่า) · ลูกตา (แสดง/ซ่อน) · **ถังขยะ (ลบ)** · ไอคอนบวก + "เพิ่มคอลัมน์" · ชื่อหัวเรื่องของแถว |
| **คอลัมน์** | ไอคอนเรียงลำดับ (**ลากในหน้าจอเลย** ที่จับ grip — สลับลำดับในแถวเดียวกัน คล้ายกลุ่มรูปภาพในบทความ) · เฟือง · ลูกตา · **ถังขยะ (ลบ)** · ไอคอนบวก + "เพิ่ม Widget" · ชื่อหัวเรื่องของคอลัมน์ |
| **widget** | ไอคอนเรียงลำดับ (**ลากในหน้าจอ** — สลับในคอลัมน์เดียวกัน และ**ย้ายข้ามคอลัมน์ได้**) · เฟือง · ลูกตา · **ถังขยะ (ลบ)** · ชื่อหัวเรื่อง |

- ชื่อบนแถบ = ชื่อของภาษาหลัก ถ้ายังไม่ได้กรอกใช้ "แถวที่ N"/"คอลัมน์ที่ N"/"(ไม่มีชื่อ)"; รายการที่ซ่อน (`status = N`) แสดงจางลง
- ปุ่ม "เพิ่มแถว" อยู่ในกรอบ canvas ทั้งด้านบน (ก่อนแถวแรก) และด้านล่าง (หลังแถวสุดท้าย) สร้างแถวต่อท้ายแถวอื่นทั้งหมด
  พร้อมคอลัมน์ขนาด 12 อยู่ข้างใน 1 คอลัมน์; ด้านล่างสุดมี "บันทึกโครงสร้าง" และลิงก์ **"กลับไปหน้ารายการ"** เหมือนหน้าข้อมูลทั่วไป (ค่าเริ่มต้นของ "เพิ่มคอลัมน์" =
  ช่องที่เหลือในแถว `12 - ผลรวม` ถ้าเต็มแล้วใช้ 12 = ตกบรรทัดใหม่)
- กรอบของแถว/คอลัมน์เป็นเส้นปะให้เห็นขอบเขต และแสดง**พื้นหลังตามการตั้งค่า**จริง (สี/รูป + repeat/size/attachment/position);
  พื้นหลังของทั้งหน้า (จากแท็บข้อมูลทั่วไป) แสดงเป็นพื้นของ canvas; คอลัมน์แสดงความกว้างตาม `column_size` ด้วย CSS grid 12
  (ผลรวมความกว้างในแถวเกิน 12 ไม่ถูกบังคับ — คอลัมน์ที่เกินขึ้นบรรทัดใหม่ และแถวจะขึ้นข้อความเตือน); `use_container = Y`
  จำกัดความกว้างเนื้อหาไว้ตรงกลางเหมือนที่หน้าบ้านจะแสดง
- **dialog ตั้งค่า** (แก้บนสำเนา กด "ตกลง" จึงมีผล, "ยกเลิก" = ไม่เปลี่ยน): ทุกชั้นมี แสดงหัวเรื่อง + ข้อความ 3 ส่วน
  (หัวเรื่อง / หัวเรื่องรอง / ข้อความเกริ่นนำ — กรอกแยกภาษา แล้วตามด้วยการจัดรูปแบบตัวอักษร: ขนาด, ฟอนต์, จัดตำแหน่ง, สี
  ผ่าน `TextFieldsSection.vue`/`TextStyleFields.vue`) + พื้นหลัง; แถวเพิ่ม การแสดงเนื้อหา (container/เต็มจอ); คอลัมน์เพิ่ม
  ความกว้าง 1 - 12 (ปุ่มตัวเลข + แถบแสดงสัดส่วน); widget เพิ่ม ประเภท
- **การลบ**: ไอคอนถังขยะบนแถบจัดการเปิด `ConfirmDialog` ยืนยันก่อนลบ (ข้อความเดียวกับปุ่ม "ลบ" ที่อยู่ใน dialog ตั้งค่า ซึ่งยังมีอยู่) —
  ลบแล้วหายจากหน้าจอทันที แต่มีผลกับฐานข้อมูลเมื่อกด "บันทึกโครงสร้าง"
- เมื่อเปิด "แสดงหัวเรื่อง" ตัวอย่างในหน้าจอจะแสดงหัวเรื่อง/หัวเรื่องรอง/ข้อความเกริ่นนำในพื้นที่ของแถว (เหนือคอลัมน์) /
  คอลัมน์ (เหนือ widget) / widget ตามที่ตั้งค่าไว้ (`LayoutTexts.vue`)
- **การเลือกสี** ใช้ `Components/Admin/ColorPickerInput.vue` (ชุดสีสำเร็จรูป + "กำหนดเอง") เพิ่ม prop `transparent` = ตัวเลือก
  "โปร่งใส" (เก็บค่า `transparent`) — พื้นหลัง 4 ค่า CSS ใช้ picker เห็นภาพชุดเดียวกับ Intropage
  (`Components/Admin/IntropageBackground/*`) และจะแสดงต่อเมื่อเลือกรูปพื้นหลังแล้ว; รวมเป็น
  `Components/Admin/PageLayout/BackgroundFields.vue` ใช้ทั้ง dialog แถว/คอลัมน์ และฟอร์มข้อมูลทั่วไป
- ผู้ที่ไม่มี `page.item.manage` เห็นโครงสร้างแบบอ่านอย่างเดียว (ซ่อนแถบจัดการ/ปิดการลาก)
- โครงสร้างแก้ในหน่วยความจำของหน้า แล้วกด **"บันทึกโครงสร้าง"** จึงส่งทั้งชุดไปบันทึก (มีตัวบอก "มีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก"
  และ confirm ก่อนออกจากหน้า) — state/การเรียก dialog อยู่ที่ `Layout.vue` และส่งให้บล็อกลูกผ่าน provide/inject
  (`composables/usePageLayoutEditor.ts`); ข้อมูล/ฟังก์ชันสร้าง-แปลงอยู่ที่ `utils/pageLayout.ts`;
  component อยู่ที่ `Components/Admin/PageLayout/{RowBlock,ColumnBlock,WidgetBlock,LayoutToolbar,LayoutDialog,...}.vue`

### การบันทึกโครงสร้าง (`PUT admin.page.item.layout.update`)

- ส่ง `rows[]` ซ้อน `columns[]` ซ้อน `widgets[]` ทั้งชุด (แต่ละชั้นมีค่าจัดรูปแบบตัวอักษรแบบแบน 12 ค่า + พื้นหลัง + `detail.<lang>.{title,subtitle,intro_text}`)
  — ลำดับใน array = `sort_order`; ตรวจสอบที่
  `UpdatePageItemLayoutRequest`: `column_size` 1 - 12, ค่าจัดรูปแบบตัวอักษรครบ 12 ค่า (`PageTextStyle`), `widget_type` ต้องอยู่ใน `PageItemWidget::TYPES`, สีต้องเป็น hex หรือ
  `transparent`, ไฟล์ต้องมีอยู่จริงใน `file_info`, และ **id ของแถว/คอลัมน์/widget ที่ส่งมาต้องเป็นของหน้านี้จริง** (กัน id ปลอมแก้ข้ามหน้า)
- `App\Support\PageLayoutSync` ทำงานใน transaction เดียว และ **คง id เดิมไว้** (ต่างจากปุ่มของ Intropage ที่ลบสร้างใหม่ทั้งชุด —
  เพราะ widget จะอ้างไฟล์/ข้อมูลอื่นในอนาคต): รายการที่มี `id` ของหน้านี้ = update, ไม่มี `id` = สร้างใหม่, id เดิมที่ไม่อยู่ในข้อมูล
  ที่ส่งมาแล้ว = **soft delete พร้อมบันทึก `deleted_by`** (เทียบทั้งหน้าในแต่ละชั้น ไม่ผูกกับพาเรนต์เดิม จึงรองรับ widget ที่ย้ายข้ามคอลัมน์
  โดยไม่เสีย id และลูกของแถว/คอลัมน์ที่ถูกลบถูกลบตามไปเอง); ข้อมูลแยกภาษาไม่ถูก soft delete ตามพาเรนต์ (ตาม convention เดิม)
- จบด้วยการตั้ง `page_item_info.layout_updated_at` = ตอนนี้ และ `layout_updated_by` = ผู้บันทึก แล้ว log
  `LogBackAction` `page.item.layout` / `update`
- ส่ง `rows` ว่าง = ล้างโครงสร้างทั้งหน้า (soft delete ทุกแถว)

---

## 3. ประเภท widget (ยังรอกำหนด)

ผู้ใช้ให้เตรียมโครงสร้างไว้ก่อน แล้วค่อยกำหนดว่ามี widget ประเภทไหนบ้างและจัดการแต่ละประเภทอย่างไรในรอบถัดไป — ตอนนี้จึงมี
`widget_type = placeholder` ("Widget (รอกำหนดประเภท)") ประเภทเดียว ให้ทดสอบโครงสร้าง/ลาก/บันทึกได้ครบ; `setting` (json) มีในตารางแล้ว
ตอนนี้ส่งผ่านตามที่ได้รับ (default `[]`) ยังไม่มี UI ให้แก้

เมื่อเพิ่มประเภทใหม่ต้องแก้ 2 จุดให้ตรงกัน: `PageItemWidget::TYPES` (validation ฝั่ง backend) และ `WIDGET_TYPES` ใน
`resources/js/utils/pageLayout.ts` (ตัวเลือกใน dialog + ป้ายที่แสดงบนการ์ด) แล้วเพิ่มฟอร์มตั้งค่าเฉพาะประเภทใน
`WidgetSettingsDialog.vue`

---

## Roadmap

| รอบ | ขอบเขต | สถานะ |
|-----|--------|-------|
| 0 — schema | `page_item_info/detail` + `page_item_row/column/widget` + `*_detail` + `PageSeeder` ตัวอย่าง | ✅ เสร็จ |
| 1 — CRUD หน้าเพจ + จัดโครงสร้าง | list/add/edit (ข้อมูลทั่วไป + SEO) + แท็บโครงสร้าง (แถว/คอลัมน์/widget, ลากเรียง, ตั้งค่า, พื้นหลัง, บันทึก) | ✅ เสร็จ |
| **2 — ประเภท widget** *(รอบถัดไป)* | กำหนดประเภท widget + การตั้งค่าเฉพาะประเภท (`setting`) | 🔴 ยังไม่เริ่ม |
| 3 — หน้าบ้าน | แสดงหน้าเพจตาม slug + โครงสร้าง (grid 12) ที่ `front.*` | 🔴 ยังไม่เริ่ม |
