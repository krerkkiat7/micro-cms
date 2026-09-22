# PRD — โมดูล Page (หน้าเพจ)

เอกสารนี้ลงรายละเอียดของโมดูล **Page** ในหลังบ้าน — หน้าเดี่ยวที่แสดงข้อมูลหลายส่วน (หลาย section) เหมาะกับหน้าแรก
หรือหน้าที่แสดงข้อมูลหลากหลาย โดยจัดโครงสร้างการแสดงผลเป็น **แถว (row) → คอลัมน์ (column) → widget** ด้วย grid 12
ภาพรวมทั้งระบบดูที่ [PRD-overview.md](PRD-overview.md) §5

สถานะ: 🟢 มีแล้ว · 🟡 มีบางส่วน · 🔴 ยังไม่มี

| # | หัวข้อ | ตารางหลัก | สถานะ |
|---|--------|-----------|-------|
| 1 | หน้าเพจ (ข้อมูลทั่วไป) | `page_item_info`, `page_item_detail` | 🟢 schema + controller/route/UI (list, add, edit) เสร็จครบ |
| 2 | โครงสร้าง แถว → คอลัมน์ → widget | `page_item_row/column/widget` + `*_detail` | 🟢 schema + หน้าจัดโครงสร้างแบบเห็นผลจริง + บันทึกเสร็จ |
| 3 | ประเภท widget และการตั้งค่าเฉพาะประเภท | `page_item_widget.widget_type` + `page_item_widget_<ประเภท>` | 🟡 โครงระบบเสร็จ + ประเภท `slideshowbanner`, `slideshowarticle`, `slidesetarticle`, `slidesetbanner`, `gridarticle`, `gridbanner` เสร็จ; เหลือ `customtext` (§3) |
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
ส่วนท้ายมีสีพื้นหลัง (คอลัมน์ 4 + 4 + 4) — สองแถวแรกเปิด "แสดงหัวเรื่อง" พร้อมหัวเรื่องรอง/ข้อความเกริ่นนำ, ทุกคอลัมน์มี widget `slideshowbanner` 1 ตัว (หมวดหมู่ banner ตัวอย่างจาก `BannerSeeder` — ไม่มี banner ตัวอย่าง จึงเห็นสถานะว่างในตัวอย่าง); `updateOrCreate` resolve หน้าเดิมจากชื่อ
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
| `page_item_widget` | `page_item_column_id` FK, `sort_order`, `show_title`, `widget_type` `varchar(20)` (ตั้งค่าเฉพาะประเภทอยู่ในตาราง `page_item_widget_<ประเภท>` — §3), `background_*` (ชุดเดียวกับแถว/คอลัมน์), การจัดรูปแบบตัวอักษร 12 คอลัมน์, `status` |
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
เรียง `sort_order` (แล้ว `id`), `PageItemWidget::allowedTypes()` = รายการ `widget_type` ที่บันทึกได้ (ประเภทในทะเบียน + `placeholder` ประเภทเดิม — validation ใช้ค่านี้)

### หน้าโครงสร้าง (`Admin/Page/Item/Layout.vue`)

ทำให้เหมือนการสร้างโครงสร้างหน้าจอจริง — เห็นผลการจัดการที่หน้าจอทันที แต่ละชั้นมี**แถบจัดการของตัวเองที่มุมซ้ายบน**
(สีต่างกันตามชนิด: แถว = น้ำเงิน, คอลัมน์ = เขียว, widget = เหลือง):

| ชั้น | แถบจัดการ |
|------|-----------|
| **แถว** | ไอคอนเรียงลำดับ (กดแล้วเปิด dialog เรียงแถว — `RowReorderDialog`) · เฟือง (dialog ตั้งค่า) · ลูกตา (แสดง/ซ่อน) · **ถังขยะ (ลบ)** · ไอคอนบวก + "เพิ่มคอลัมน์" · ชื่อหัวเรื่องของแถว |
| **คอลัมน์** | ไอคอนเรียงลำดับ (กดแล้วเปิด dialog เรียงคอลัมน์ — `ColumnReorderDialog`) · เฟือง · ลูกตา · **ถังขยะ (ลบ)** · ไอคอนบวก + "เพิ่ม Widget" · ชื่อหัวเรื่องของคอลัมน์ |
| **widget** | ไอคอนเรียงลำดับ (กดแล้วเปิด dialog เรียง Widget — `WidgetReorderDialog`) · เฟือง · ลูกตา · **ถังขยะ (ลบ)** · ชื่อหัวเรื่อง |

**เรียงลำดับ/ย้ายทุกชั้นผ่าน dialog ทั้งหมด** (ไม่มีการลากสลับตรงในหน้าจอ canvas อีกแล้ว — เดิมคอลัมน์/widget ลากสลับในหน้าจอได้เลย
แต่ widget แสดงตัวอย่างจริงตามการตั้งค่า ยิ่งมีหลายประเภท/ตั้งค่าเยอะขึ้นก็ยิ่งสูง ทำให้ลากยาก) รูปแบบเดียวกันทั้ง 3 dialog: แก้บนสำเนา
กด "ยืนยันลำดับ" ค่อยมีผล, "ยกเลิก"/ปิด = ไม่เปลี่ยน (`ColumnReorderDialog`/`WidgetReorderDialog.vue`):

- **เรียงคอลัมน์** (`ColumnReorderDialog`) — แสดงกล่องของ**ทุกแถวแบบ fix ไว้** (ลำดับแถวเปลี่ยนที่นี่ไม่ได้ ใช้ "เรียงลำดับแถว" แยกต่างหาก)
  แต่ละแถวมีกล่องคอลัมน์ข้างในที่**ลากสลับหรือย้ายข้ามแถวได้** (ทุกแถวใช้ vuedraggable group เดียวกัน)
- **เรียง Widget** (`WidgetReorderDialog`) — แสดงกล่องของทุกแถวและทุกคอลัมน์แบบ fix ไว้ (ลำดับแถว/คอลัมน์เปลี่ยนที่นี่ไม่ได้) แล้วกล่อง widget
  ข้างในคอลัมน์**ลากสลับหรือย้ายข้ามคอลัมน์/แถวได้**

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
  ความกว้าง 1 - 12 (ปุ่มตัวเลข + แถบแสดงสัดส่วน); widget เพิ่มกล่องตั้งค่าเฉพาะประเภทไว้บนสุด (§3)
- **การลบ**: ไอคอนถังขยะบนแถบจัดการเปิด `ConfirmDialog` ยืนยันก่อนลบ (ข้อความเดียวกับปุ่ม "ลบ" ที่อยู่ใน dialog ตั้งค่า ซึ่งยังมีอยู่) —
  ลบแล้วหายจากหน้าจอทันที แต่มีผลกับฐานข้อมูลเมื่อกด "บันทึกโครงสร้าง"
- เมื่อเปิด "แสดงหัวเรื่อง" ตัวอย่างในหน้าจอจะแสดงหัวเรื่อง/หัวเรื่องรอง/ข้อความเกริ่นนำในพื้นที่ของแถว (เหนือคอลัมน์) /
  คอลัมน์ (เหนือ widget) / widget ตามที่ตั้งค่าไว้ (`LayoutTexts.vue`)
- **การเลือกสี** ใช้ `Components/Admin/ColorPickerInput.vue` (ชุดสีสำเร็จรูป + "กำหนดเอง") เพิ่ม prop `transparent` = ตัวเลือก
  "โปร่งใส" (เก็บค่า `transparent`) — พื้นหลัง 4 ค่า CSS ใช้ picker เห็นภาพชุดเดียวกับ Intropage
  (`Components/Admin/IntropageBackground/*`) และจะแสดงต่อเมื่อเลือกรูปพื้นหลังแล้ว; รวมเป็น
  `Components/Admin/PageLayout/BackgroundFields.vue` ใช้ทั้ง dialog แถว/คอลัมน์ และฟอร์มข้อมูลทั่วไป
- ผู้ที่ไม่มี `page.item.manage` เห็นโครงสร้างแบบอ่านอย่างเดียว (ซ่อนแถบจัดการทั้งหมด รวมปุ่มเรียงลำดับ)
- โครงสร้างแก้ในหน่วยความจำของหน้า แล้วกด **"บันทึกโครงสร้าง"** จึงส่งทั้งชุดไปบันทึก (มีตัวบอก "มีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก"
  และ confirm ก่อนออกจากหน้า) — state/การเรียก dialog อยู่ที่ `Layout.vue` และส่งให้บล็อกลูกผ่าน provide/inject
  (`composables/usePageLayoutEditor.ts`); ข้อมูล/ฟังก์ชันสร้าง-แปลงอยู่ที่ `utils/pageLayout.ts`;
  component อยู่ที่ `Components/Admin/PageLayout/{RowBlock,ColumnBlock,WidgetBlock,LayoutToolbar,LayoutDialog,...}.vue`

### การบันทึกโครงสร้าง (`PUT admin.page.item.layout.update`)

- ส่ง `rows[]` ซ้อน `columns[]` ซ้อน `widgets[]` ทั้งชุด (แต่ละชั้นมีค่าจัดรูปแบบตัวอักษรแบบแบน 12 ค่า + พื้นหลัง + `detail.<lang>.{title,subtitle,intro_text}`)
  — ลำดับใน array = `sort_order`; ตรวจสอบที่
  `UpdatePageItemLayoutRequest`: `column_size` 1 - 12, ค่าจัดรูปแบบตัวอักษรครบ 12 ค่า (`PageTextStyle`), `widget_type` ต้องอยู่ใน `PageItemWidget::allowedTypes()` (widget เดิมเปลี่ยนประเภทไม่ได้) และ `setting` ต้องผ่านกฎของประเภทนั้น (§3), สีต้องเป็น hex หรือ
  `transparent`, ไฟล์ต้องมีอยู่จริงใน `file_info`, และ **id ของแถว/คอลัมน์/widget ที่ส่งมาต้องเป็นของหน้านี้จริง** (กัน id ปลอมแก้ข้ามหน้า)
- `App\Support\PageLayoutSync` ทำงานใน transaction เดียว และ **คง id เดิมไว้** (ต่างจากปุ่มของ Intropage ที่ลบสร้างใหม่ทั้งชุด —
  เพราะ widget จะอ้างไฟล์/ข้อมูลอื่นในอนาคต): รายการที่มี `id` ของหน้านี้ = update, ไม่มี `id` = สร้างใหม่, id เดิมที่ไม่อยู่ในข้อมูล
  ที่ส่งมาแล้ว = **soft delete พร้อมบันทึก `deleted_by`** (เทียบทั้งหน้าในแต่ละชั้น ไม่ผูกกับพาเรนต์เดิม จึงรองรับ widget ที่ย้ายข้ามคอลัมน์
  โดยไม่เสีย id และลูกของแถว/คอลัมน์ที่ถูกลบถูกลบตามไปเอง); ข้อมูลแยกภาษาไม่ถูก soft delete ตามพาเรนต์ (ตาม convention เดิม)
- widget แต่ละตัวบันทึกค่า `setting` ลงตารางของประเภทนั้น (PK = id ของ widget) ต่อจากบันทึกตัว widget เอง และ widget ที่ถูก soft delete ก็ soft delete แถวตั้งค่าของมันด้วย; จบด้วยการตั้ง `page_item_info.layout_updated_at` = ตอนนี้ และ `layout_updated_by` = ผู้บันทึก แล้ว log
  `LogBackAction` `page.item.layout` / `update`
- ส่ง `rows` ว่าง = ล้างโครงสร้างทั้งหน้า (soft delete ทุกแถว)

---

## 3. ประเภท widget

**หลักการ** — แต่ละประเภทมี**ตารางตั้งค่าของตัวเอง** `page_item_widget_<ประเภท>` โดย PK `id` = `page_item_widget.id` (1 widget = 1 แถว) แทน JSON
`setting` ก้อนเดียว (เลิกใช้คอลัมน์ `page_item_widget.setting` แล้ว — ประเภทอย่าง Custom Text ที่กรอกอะไรก็ได้ไม่ควรรวมเป็น JSON ก้อนเดียว)
`page_item_widget.widget_type` เก็บชื่อประเภท เช่น `slideshowbanner` ไว้ชี้ว่าต้องอ่านตารางไหน; ส่วน "หัวเรื่อง/หัวเรื่องรอง/ข้อความเกริ่นนำ + พื้นหลัง"
ของ widget (§2) ยังอยู่ที่ตัว widget เหมือนเดิมและใช้ร่วมทุกประเภท (ฟิลด์ `setting` ที่หน้าจอ/ payload ใช้คือค่าจากตารางประเภทนั้น)

| ประเภท (`widget_type`) | การแสดงผล / แหล่งข้อมูล | สถานะ |
|------------------------|--------------------------|-------|
| `slideshowbanner` | Slideshow — ภาพเต็มภาพเดียวสไลด์ได้ จาก banner | 🟢 เสร็จ |
| `slideshowarticle` | Slideshow — ภาพเต็มภาพเดียวสไลด์ได้ จาก article (ตั้งค่าเหมือน `slideshowbanner`) | 🟢 เสร็จ |
| `slidesetarticle` | Slideset — การ์ดบทความที่เลื่อนได้ (รูป/หัวเรื่อง/เกริ่นนำ/วันที่/เข้าชม) จาก article | 🟢 เสร็จ |
| `slidesetbanner` | Slideset — การ์ดป้ายโฆษณาที่เลื่อนได้ (รูป/หัวเรื่อง/เกริ่นนำ) จาก banner | 🟢 เสร็จ |
| `gridarticle` | Grid — กล่องเรียงต่อเนื่องหลายคอลัมน์ (ไม่เลื่อน) จาก article | 🟢 เสร็จ |
| `gridbanner` | Grid — กล่องเรียงต่อเนื่องหลายคอลัมน์ (ไม่เลื่อน) จาก banner | 🟢 เสร็จ |
| `customtext` | Custom Text — กรอกเนื้อหาเอง คล้าย part ของบทความ (ตารางแยก + ข้อมูลแยกภาษา) | 🔴 เร็ว ๆ นี้ |
| `placeholder` | ประเภทเดิมก่อนมีประเภทจริง (**legacy**) — ไม่มีตารางตั้งค่า โหลด/บันทึกได้แต่เลือกสร้างใหม่ไม่ได้ | — |

### ขั้นตอนเพิ่ม/แก้ widget ในหน้าโครงสร้าง

1. กด "+ เพิ่ม Widget" บนแถบคอลัมน์ → `WidgetTypePickerDialog.vue` แสดงการ์ด SVG ครบทุกประเภท (ตาม pattern `ArticlePart/ImagesDisplayTypePicker.vue`);
   ประเภทที่ยังไม่พร้อมติดป้าย "เร็ว ๆ นี้" เลือกไม่ได้; เลือกแล้วกด "ต่อไป"
2. `WidgetSettingsDialog.vue` (`mode = add`) — **กล่องตั้งค่าเฉพาะประเภทอยู่บนสุด** (กรอบ + ชื่อประเภทที่เลือก) ตามด้วย**กรอบ "การตั้งค่าการแสดงผล Widget"** ทรงเดียวกันแต่โทนสีส้มอ่อน (กล่องประเภทเป็นโทนน้ำเงิน) เพื่อแยกสองส่วนออกจากกัน
   (แสดงหัวเรื่อง, ข้อความ 3 ส่วน, พื้นหลัง — ใช้ร่วมทุกประเภท); ปุ่ม "ย้อนกลับ" กลับไปเลือกประเภท, "ยกเลิก" ไม่เพิ่มอะไร, ค่าที่ต้องกรอกไม่ครบ (เช่น ไม่เลือกหมวดหมู่)
   → แจ้ง error ที่ช่องและไม่ปิด dialog
3. **widget ถูกเพิ่มลงคอลัมน์เมื่อกด "เพิ่ม Widget" ใน dialog ขั้น 2 เท่านั้น** (ก่อนหน้านั้นเป็นแค่ draft ที่ยังไม่อยู่ในโครงสร้าง) และยังต้องกด "บันทึกโครงสร้าง"
   จึงลงฐานข้อมูล
4. กดเฟืองของ widget = dialog เดียวกัน (`mode = edit`) แต่**เปลี่ยนประเภทไม่ได้** (แสดงชื่อประเภทอย่างเดียว; backend ก็ปฏิเสธถ้า widget เดิมส่งประเภทต่างจากที่บันทึกไว้)
5. widget แสดง**ตัวอย่างการแสดงผล**ใต้หัวเรื่องบนการ์ดเสมอ (ข้อมูลจริง ตามค่าตั้งค่าที่กำลังแก้แม้ยังไม่บันทึก) — ลิงก์เป็นแค่ป้ายบอกว่ามีลิงก์ กดไม่ได้และไม่มี URL
   (endpoint ตัวอย่างไม่ส่ง URL มาเลย); ดึงผ่าน `GET admin.page.item.widget.preview` (สิทธิ์ `page.item.view`, ไม่บันทึก log) โดย `composables/useWidgetPreview.ts`
   (cache ตามค่าตั้งค่า, ล้างเมื่อเข้าหน้าโครงสร้างใหม่)

### `slideshowbanner` — Slideshow จาก banner (และ `slideshowarticle` — ดูหัวข้อถัดไป)

ตาราง `page_item_widget_slideshowbanner` (migration `2026_09_21_000001_*` + `2026_09_22_000001_*` เพิ่ม `max_items` และตัวอักษรบนภาพ; มี audit + timestamps + softDeletes ไม่มี `status` เพราะอยู่ที่ตัว widget):

| คอลัมน์ | ค่า / ความหมาย |
|---------|----------------|
| `banner_category_info_id` | FK → `banner_category_info` (nullOnDelete) — **จำเป็นต้องเลือก** (ต้องมีอยู่จริง เปิดใช้งาน ไม่ถูกลบ ตรวจตอนบันทึก) |
| `sort_by` | `publish_desc` วันที่เผยแพร่ล่าสุด (default) · `publish_asc` เก่าสุด · `order_asc` ลำดับน้อยไปมาก · `order_desc` มากไปน้อย (วันที่ใช้ `COALESCE(publish_date, created_at)`) |
| `max_items` | จำนวนที่แสดงสูงสุด (จำนวนเต็ม 0 - 1000, default 0) — **ไม่กรอกหรือเป็น 0 = แสดงทั้งหมด** (หน้าจอส่งช่องว่างมาเป็น 0) |
| `show_arrows` / `show_dots` / `autoplay` | `Y`/`N` — ลูกศรกดเลื่อน · จุดด้านล่าง (อยู่ในกรอบภาพ) · เลื่อนอัตโนมัติ (default `Y`/`Y`/`Y`) |
| `autoplay_interval` | ระยะค้างต่อภาพ (วินาที 1 - 60, default 5) — ใช้เมื่อเปิด autoplay |
| `transition_speed` | ความเร็วเปลี่ยนภาพ (มิลลิวินาที 100 - 3000, default 500) |
| `transition_effect` | `slide` (default) / `fade` / `zoom` |
| `aspect_ratio` | `16:9` (default) / `21:9` / `4:3` / `1:1` — ภาพครอปแบบ cover ให้เต็มกรอบ (คอลัมน์นี้เพิ่มนอกเหนือจากที่ผู้ใช้ระบุ เพื่อให้กรอบไม่กระโดดเวลาเลื่อน) |
| `is_clickable` / `link_target` | กดลิงก์ของ banner ได้ (banner ที่ไม่มีลิงก์กดไม่ได้) · `_self` / `_blank` |
| `show_title` / `show_intro_text` | แสดงหัวเรื่อง/ข้อความเกริ่นนำของ **banner แต่ละใบ**ซ้อนบนภาพ (`<div>` ไม่ใช้ h1/h2, ตัวอักษรขาวบนเฉดดำด้านล่าง) — คนละอย่างกับ "แสดงหัวเรื่อง" ของ widget เอง (H4 เหนือ widget) |
| `text_align` / `text_width` | `left`/`center`/`right` · `full` เต็มความกว้าง / `container` จำกัดตาม container (`max-w-5xl` เหมือนแถวที่ใช้ container) |
| `title_font_size` / `title_font_family` / `title_color` | ตัวอักษรของหัวเรื่องบนภาพ: ขนาด px (8 - 120, default 20) · ฟอนต์ (ชุดเดียวกับหัวเรื่องของแถว/คอลัมน์ `PageTextStyle::fontNames()`, default Sarabun) · สี hex (**default ขาว `#FFFFFF`**, ไม่มี transparent) — ตั้งชื่อคอลัมน์ตาม `PageTextStyle` (`<part>_font_size` ฯลฯ) |
| `intro_text_font_size` / `intro_text_font_family` / `intro_text_color` | เหมือนกันสำหรับข้อความเกริ่นนำบนภาพ (default 16 / Sarabun / ขาว) — ไม่มีการจัดตำแหน่งรายส่วน ใช้ `text_align` ร่วมกัน; ฟอร์มแสดงชุดตั้งค่าของแต่ละส่วนเมื่อเปิดแสดงส่วนนั้นเท่านั้น (`TextStyleFields.vue` ซ่อนตัวเลือกจัดตำแหน่งด้วย `show-align=false`) |

- **ข้อมูลที่แสดง** (`SlideshowBannerWidget::preview()`, ตัวกรองเดียวกับที่หน้าบ้านจะใช้): banner ของหมวดหมู่นั้นที่ `status = Y`, ไม่ถูกลบ, มีรูป (ไฟล์ใช้งานได้),
  อยู่ในช่วงเผยแพร่ (`publish_date` ว่างหรือ ≤ ตอนนี้ และ `publish_down` ว่างหรือ > ตอนนี้), ชื่อ/เกริ่นนำเป็นของภาษาหลัก — ตัวอย่างในหน้าโครงสร้างแสดงสูงสุด 10 ใบ (`max_items` ที่น้อยกว่า 10 ถูกใช้ตามนั้น; ถ้าตั้งไว้ 0 หรือมากกว่า 10 และมีครบ 10 ใบ ตัวอย่างจะมีข้อความเล็ก ๆ บอกว่าแสดง 10 รายการแรก)
- ตัวอย่าง (`widgets/SlideshowPreview.vue` ใช้ร่วมกับ `slideshowarticle`): กรอบตามสัดส่วนภาพ **มุมเหลี่ยม**, ลูกศร/จุดกดเลื่อนดูได้, effect ด้วย CSS transition, เลื่อนอัตโนมัติตามระยะค้าง, ข้อความเกริ่นนำ**คงการขึ้นบรรทัดใหม่**ตามที่พิมพ์ (`whitespace-pre-line`) และใช้ขนาด/ฟอนต์/สีที่ตั้งไว้,
  สถานะ "ยังไม่ได้เลือกหมวดหมู่" / "ไม่มี banner ที่เผยแพร่อยู่ในหมวดหมู่นี้" / กำลังโหลด; ฟอร์มตั้งค่าอยู่ที่ `widgets/SlideshowFields.vue` (ใช้ร่วมกับ `slideshowarticle`)
- ตัวเลือก/ช่วงค่า/ค่าเริ่มต้นต้องตรงกันระหว่าง `App\Support\PageWidget\SlideshowWidget` (คลาสแม่ที่ `SlideshowBannerWidget`/`SlideshowArticleWidget` สืบทอด) และ `resources/js/utils/pageWidget.ts`
  (`SLIDESHOW_TYPES` = คีย์หมวดหมู่/ตัวเลือกการเรียงลำดับ/ข้อความของแต่ละแหล่งข้อมูล)

### `slideshowarticle` — Slideshow จาก article

ตาราง `page_item_widget_slideshowarticle` (migration `2026_09_21_000002_*`) โครงเดียวกับ `slideshowbanner` ทุกคอลัมน์ ต่างกัน 3 เรื่อง:

- หมวดหมู่ = `article_category_info_id` (FK → `article_category_info`, ตั้งชื่อ FK เอง `pi_widget_slideshowarticle_category_foreign` เพราะชื่ออัตโนมัติยาวเกิน 64 ตัวอักษรของ MySQL);
  รายการหมวดหมู่ที่ให้เลือกเรียงตาม `sort_order` ของหมวดหมู่ แล้วชื่อ
- **เรียงลำดับได้เฉพาะ `publish_desc` / `publish_asc`** — บทความไม่มีคอลัมน์ "ลำดับ" ต่อรายการ (มีแต่หมวดหมู่) จึงไม่มีตัวเลือก `order_asc`/`order_desc` (backend ปฏิเสธ); ถ้าต้องการเรียงตามลำดับ
  ต้องเพิ่ม `sort_order` ให้ `article_item_info` ก่อน
- **ข้อมูลที่แสดง** (`SlideshowArticleWidget::preview()`): บทความของหมวดหมู่นั้นที่ `status = Y`, ไม่ถูกลบ, **มีรูปหน้าปก** (`intro_image_id` ที่ไฟล์ใช้งานได้ — บทความไม่มีรูปหน้าปกไม่ถูกแสดง),
  อยู่ในช่วงเผยแพร่, ชื่อ/เกริ่นนำเป็นของภาษาหลัก; "กดลิงก์ได้" = ลิงก์ไปหน้าบทความ — **บทความทุกใบมีลิงก์ ไม่ขึ้นกับว่ามี slug หรือไม่** (`has_link` เป็น true เสมอ; หน้าบ้านยังไม่ทำ ตัวอย่างจึงแสดงแค่ป้าย "ลิงก์")

### `slidesetarticle` — Slideset จาก article

การ์ดบทความหลายใบที่เลื่อนดูได้ — ตาราง `page_item_widget_slidesetarticle` (migration `2026_09_23_000001_*`; FK หมวดหมู่ตั้งชื่อเอง `pi_widget_slidesetarticle_category_foreign`)
class `SlidesetArticleWidget extends CategoryListWidget` (ใช้ trait `ReadsArticles` ร่วมกับ `slideshowarticle`: หมวดหมู่ article, เรียงได้เฉพาะวันที่เผยแพร่, query บทความที่เผยแพร่อยู่)

**หน้าตั้งค่า** (`widgets/SlidesetFields.vue`) มีฟิลด์มาก จึงแบ่งเป็นการ์ด (`SettingSection.vue`): ข้อมูลที่แสดง · จำนวนที่แสดงต่อแถว (ตามขนาดหน้าจอ) · การเลื่อน ·
กล่องของการ์ด (เส้นขอบ+สีเส้นขอบ/มุมมน/สีพื้นหลังของแต่ละรายการ) ·
ส่วนของการ์ดที่ **ติ๊กแสดง/ซ่อนได้ทีละส่วน** (ซ่อนแล้วพับรายละเอียดทิ้ง): รูปภาพ · หัวเรื่อง · ข้อความเกริ่นนำ · วันที่เผยแพร่ · จำนวนเข้าชม · การเปิดลิงก์

| กลุ่ม | คอลัมน์ (ค่าเริ่มต้น) |
|-------|------------------------|
| ข้อมูล | `article_category_info_id` (**จำเป็นต้องเลือก**), `sort_by` (`publish_desc`/`publish_asc`), `max_items` (0 - 1000, **ว่างหรือ 0 = แสดงทั้งหมด**) |
| จำนวนต่อแถว | `per_row_pc` (4) / `per_row_notebook` (3) / `per_row_tablet` (2) / `per_row_mobile` (1) — 1 - 6; breakpoint: PC ≥ 1280 px, Notebook 1024 - 1279, Tablet 768 - 1023, Mobile < 768 (`SLIDESET_DEVICES`) |
| การเลื่อน | `show_arrows` (Y), `show_dots` (Y — จุดอยู่ใต้การ์ด เป็นพื้นที่ด้านล่าง ไม่ซ้อนบนการ์ด), `autoplay` (N), `autoplay_interval` (5 วินาที, 1 - 60), `transition_speed` (500 ms, 100 - 3000) |
| กล่องของการ์ด | `show_border` (**Y** — เส้นขอบบาง ๆ รอบการ์ด), `border_color` (**`#E5E7EB`** เทาอ่อน — เลือกได้เมื่อแสดงเส้นขอบเท่านั้น), `rounded_corners` (**Y** — มุมมน; ปิด = มุมเหลี่ยมทั้งการ์ดและรูป), `item_background` (**`#FFFFFF`** ขาว — สีพื้นหลังของแต่ละรายการ เลือก transparent ได้) — `CategoryListWidget::cardBoxFields()` ใช้ร่วมกับ Grid ทั้ง 2 แหล่งข้อมูล ด้วย (migration `2026_09_25_000001_*` + `2026_09_26_000001_*`) |
| รูปภาพ | `show_image` (Y), `aspect_ratio` (16:9 / 21:9 / 4:3 / 1:1), `image_fit` (`cover`/`contain`), `image_background` (`#F3F4F6` — ใช้เมื่อ contain, เลือก transparent ได้), `image_clickable` (Y) |
| หัวเรื่อง | `show_title` (Y), `title_font_size` (18), `title_bold` (Y), `title_font_family` (Sarabun), `title_color` (**`#000000`**), `title_align` (left), `title_clickable` (Y), `title_lines` (**1**, ช่วง 1 - 3 เกินตัดด้วย ...) |
| ข้อความเกริ่นนำ | `show_intro_text` (Y), `intro_text_font_size` (14), `intro_text_bold` (N), `intro_text_font_family`, `intro_text_color` (**`#000000`**), `intro_text_align` (left), `intro_text_clickable` (N), `intro_text_lines` (**2**, 1 - 3) |
| วันที่เผยแพร่ | `show_date` (Y), `date_font_size` (12), `date_bold` (N), `date_font_family`, `date_color` (**เทา `#667085`**) |
| จำนวนเข้าชม | `show_views` (N), `views_font_size` (12), `views_bold` (N), `views_font_family`, `views_color` (**เทา `#667085`**) |
| ลิงก์ | `link_target` (`_self`/`_blank`) — **ใช้ร่วมกัน**ทั้งรูป/หัวเรื่อง/ข้อความเกริ่นนำที่ตั้งให้กดลิงก์ได้ (ลิงก์ไปหน้าบทความ) |

**ปุ่ม "อ่านทั้งหมด"** (เฉพาะ `slidesetarticle`; migration `2026_09_24_000001_*`) — การ์ดตั้งค่า "ปุ่มอ่านทั้งหมด" ติ๊กแสดง/ซ่อนได้ (default ซ่อน):

| คอลัมน์ | ความหมาย |
|---------|----------|
| `show_read_all` | `Y`/`N` (default `N`) |
| `read_all_position` | `top_left` / `top_center` / `top_right` / `bottom_left` / `bottom_center` (default) / `bottom_right` — บนอยู่เหนือแถวการ์ด, ล่างอยู่ใต้จุด/การ์ด, จัดชิดซ้าย/กึ่งกลาง/ขวา (ฟอร์มเลือกด้วยการ์ดภาพจำลองตำแหน่ง) |
| `read_all_icon` | `none` (ไม่เลือก) / `plus` / `plus_circle` / `arrow_right` (default) / `arrow_right_circle` / `chevron_right` / `chevron_right_circle` / `arrow_up_right` — ฟอร์มเลือกด้วย**การ์ดที่แสดงไอคอนคู่กับชื่อ** (`utils/readAllButton.ts`) |
| `read_all_icon_position` | `before` / `after` (default) หน้า/หลังข้อความ — ซ่อนตัวเลือกเมื่อไอคอน = `none` |
| `read_all_style` | `button` (default, ปุ่มสี่เหลี่ยมมุมมน) / `link` (ลิงก์ข้อความขีดเส้นใต้) / `pill` (ปุ่มมนใหญ่ คล้ายวงรี) — ฟอร์มเลือกด้วยการ์ดตัวอย่างปุ่มจริง (การ์ดแสดงหน้าตาพื้นฐานของแต่ละแบบ ไม่ตามสีที่ตั้ง) |
| `read_all_font_size` / `read_all_font_family` / `read_all_color` | ตัวอักษรของปุ่ม (ทุกรูปแบบ): ขนาด (14 px) / ฟอนต์ (Sarabun) / สี (`#FFFFFF`) — ใช้ `TextStyleFields` ชุดเดียวกับข้อความส่วนอื่น (ไม่มีจัดตำแหน่ง) |
| `read_all_background` | สีพื้นหลังปุ่ม `#1F2937` (เทาเข้ม) — **ใช้เฉพาะรูปแบบปุ่ม / ปุ่มมนใหญ่** (ฟอร์มซ่อนช่องนี้เมื่อเลือกลิงก์ข้อความ); ไม่มีตัวเลือกโปร่งใส |
| _สีตัวอักษรตามรูปแบบ_ | ค่าเริ่มต้นของสีตัวอักษรต่างกันตามรูปแบบ (ปุ่ม = ขาว, ลิงก์ = น้ำเงิน `#2563EB` — `READ_ALL_DEFAULT_COLORS`) เปลี่ยนรูปแบบในฟอร์มแล้วถ้าสียังเป็นค่าเริ่มต้นของแบบเดิมจะสลับให้ตามแบบใหม่ (สีที่ผู้ใช้ตั้งเองไม่ถูกแตะ); migration ปรับแถวเดิมที่เป็นลิงก์ข้อความให้เป็นสีน้ำเงิน |
| `read_all_url` | ลิงก์ปลายทาง `varchar(500)` — **จำเป็นต้องกรอกเมื่อแสดงปุ่ม** รับ `http(s)://…`, `/path`, `#anchor`, `mailto:`, `tel:` (ภายหลังอาจเลือกจากเมนูหน้าบ้านแทนการกรอก URL) |
| `read_all_link_target` | `_self` / `_blank` (แยกจาก `link_target` ของรูป/หัวเรื่อง/เกริ่นนำ) |
| **ข้อความแทน** | **แยกภาษา** เก็บในตาราง `page_item_widget_slidesetarticle_detail` (PK = `id` + `lang`, คอลัมน์ `read_all_text` ≤ 100 ตัวอักษร) — ว่าง = ใช้ข้อความมาตรฐาน "อ่านทั้งหมด"; ใน setting เป็น map `read_all_text: {th, en}` |

ฟิลด์แยกภาษาประกาศใน `detailFields()` + `detailModel()` ของ `SettingsWidget` (บันทึกด้วย where(id, lang) → update/create ตาม pattern ตาราง detail ทั่วไป) และ eager load ผ่าน `eagerRelations()`

**ตัวอย่างของ Slideset** — ส่วนล่างของการ์ด (หัวเรื่อง/เกริ่นนำ/วันที่/เข้าชม) จะ**ไม่ถูกวาดเลย**เมื่อทุกส่วนถูกซ่อนหรือไม่มีข้อมูล (ไม่เหลือแถบว่างสีขาวใต้รูป — `hasBody()` ใน `SlidesetPreview.vue`)

**สีพื้นหลังของรูป** — `image_background` (default `#F3F4F6` เทาอ่อน = ที่เห็นในกรอบรูปตอนนี้; เลือกสีอื่นหรือ `transparent` ได้) แสดงเมื่อ `image_fit = contain`

- ชื่อคอลัมน์ตัวอักษรตาม `PageTextStyle` (`<part>_font_size` ฯลฯ) ฟอนต์ = ชุดเดียวกับหัวเรื่องของแถว/คอลัมน์ สีเป็น hex เท่านั้น; วันที่/จำนวนเข้าชมไม่มีจัดตำแหน่ง — แถววันที่/เข้าชมใช้ตำแหน่งเดียวกับ `title_align`
- **ข้อมูลที่แสดง** (`SlidesetArticleWidget::preview()`): บทความที่เผยแพร่อยู่ของหมวดหมู่ (เหมือน slideshowarticle) แต่ **ไม่บังคับมีรูปหน้าปก** (ไม่มีรูปแสดงกรอบเทา + ไอคอน);
  ส่ง `id, image, title, intro_text, date (วันที่เผยแพร่ Y-m-d, ถ้าไม่มีใช้วันที่สร้าง), views (view_amount)` — ไม่ส่ง URL
- **ตัวอย่าง** (`widgets/SlidesetPreview.vue`): การ์ดตามค่าตั้งค่าทั้งหมด; เลื่อนทีละ "หน้า" (ครั้งละเท่าจำนวนต่อแถว, หน้าสุดท้ายถอยให้เต็มแถว, จุด = จำนวนหน้า, วนกลับหน้าแรก);
  มีตัวเลือก **ดูตามขนาดหน้าจอ** (PC/Notebook/Tablet/Mobile — ค่าเริ่มต้นตามความกว้างหน้าต่างที่เปิดอยู่) เพื่อดูจำนวนการ์ดต่อแถวของแต่ละขนาด; ตัดข้อความด้วย `line-clamp-1/2/3`,
  ข้อความเกริ่นนำคงการขึ้นบรรทัดใหม่; ลิงก์เป็นแค่ไอคอน (กดไม่ได้)
- โครงคลาสของ widget กลุ่มที่ดึงจากหมวดหมู่: `App\Support\PageWidget\SettingsWidget` (ประกาศฟิลด์ครั้งเดียวใน `fields()` → ได้ rules/messages/defaults/บันทึก/แปลงข้อมูลให้เอง
  ข้อความ error ใช้ป้ายชื่อของฟิลด์) → `CategoryListWidget` (หมวดหมู่ + เรียงลำดับ + จำนวนสูงสุด + carousel + preview) → `SlideshowWidget` / `SlidesetArticleWidget`;
  เทสต์ตรวจว่าฟิลด์ทุกตัวมีคอลัมน์และอยู่ใน `$fillable` (กันหลุดเมื่อเพิ่มฟิลด์)

### `slidesetbanner` — Slideset จาก banner

การ์ดป้ายโฆษณาหลายใบที่เลื่อนดูได้ — ตาราง `page_item_widget_slidesetbanner` (migration `2026_09_24_000002_*`; FK ตั้งชื่อเอง `pi_widget_slidesetbanner_category_foreign`)
class `SlidesetBannerWidget extends SlidesetWidget` (ใช้ trait `ReadsBanners` ร่วมกับ `slideshowbanner`) ตั้งค่าเหมือน `slidesetarticle` ทุกอย่าง ยกเว้น:

- **ไม่มี** ปุ่ม "อ่านทั้งหมด", วันที่เผยแพร่ และจำนวนเข้าชม (ทั้งคอลัมน์และส่วนตั้งค่าในฟอร์ม)
- **"แสดงข้อความเกริ่นนำ" ซ่อนเป็นค่าเริ่มต้น** (`introShownByDefault()` = `N`)
- **เรียงลำดับได้ 4 แบบ**: `publish_desc` / `publish_asc` / `order_asc` ลำดับน้อยไปมาก / `order_desc` ลำดับมากไปน้อย (`sort_order` ของ banner)
- หมวดหมู่ = `banner_category_info_id`; ข้อมูลที่แสดง = banner ที่เผยแพร่อยู่ **และมีรูป** (เหมือน slideshowbanner); ลิงก์ของการ์ด = url ของ banner (banner ที่ไม่มี url กดไม่ได้ —
  ตัวอย่างแสดงป้ายลิงก์เฉพาะใบที่มีลิงก์)

### `gridarticle` — Grid จาก article

กล่องเรียงต่อเนื่องหลายคอลัมน์ของบทความ **ไม่เลื่อน** (ต่างจาก Slideset) — ตาราง `page_item_widget_gridarticle` (+ `_detail` เก็บข้อความปุ่มแยกภาษา;
migration `2026_09_25_000003_*`; FK หมวดหมู่ตั้งชื่อเอง `pi_widget_gridarticle_category_foreign`) class `GridArticleWidget extends CategoryListWidget`
(ใช้ trait `ReadsArticles` เหมือน `slideshowarticle`/`slidesetarticle`) ส่วนของรายการ (รูป/หัวเรื่อง/ข้อความเกริ่นนำ/วันที่/จำนวนเข้าชม) และปุ่ม "อ่านทั้งหมด"
ใช้ฟิลด์/ตัวช่วยชุดเดียวกับ `slidesetarticle` (`textFields()`/`metaFields()` ย้ายขึ้นไปอยู่ที่ `CategoryListWidget` ให้ใช้ร่วมกันได้; ปุ่ม "อ่านทั้งหมด"
แยกเป็น trait `HasReadAllButton` ใช้ร่วมกับ `SlidesetArticleWidget` แทนที่จะประกาศซ้ำ; กล่องของการ์ดใช้ `CategoryListWidget::cardBoxFields()` ร่วมกับ Slideset
และ `gridbanner`) **ไม่มี** carousel (ลูกศร/จุด/เลื่อนอัตโนมัติ) เพราะเป็น grid นิ่ง ๆ

**รูปแบบการแสดงผล** (`display_type`, ฟอร์มเลือกด้วยการ์ด SVG `GridFields.vue`) มี 3 แบบ:

| ค่า | หน้าตา | ข้อบังคับ |
|-----|--------|-----------|
| `card` (default) | การ์ดแนวตั้ง รูปบน ข้อมูลล่าง | ทุกส่วนเปิด/ปิดเองได้ตามปกติ |
| `row_image` | แถวแนวนอน 2 ส่วน: รูป (กว้างเป็น % ของการ์ด) ซ้าย + ข้อมูลขวา | **บังคับแสดงหัวเรื่องเสมอ** (ฟอร์มซ่อนช่องติ๊ก, backend บังคับ `show_title = 'Y'` ตอนบันทึกด้วย) |
| `row_date` | แถวแนวนอน 2 ส่วน: กล่องวันที่ (เลขวันที่บรรทัดใหญ่ + เดือนย่อ/ปี บรรทัดเล็ก) แทนรูป ซ้าย + ข้อมูลขวา | **บังคับแสดงหัวเรื่องและวันที่เผยแพร่เสมอ** (เหตุผลเดียวกับ `row_image` — backend บังคับทั้ง `show_title`/`show_date`) — เฉพาะ `gridarticle` เท่านั้น `gridbanner` ไม่มีตัวเลือกนี้เลย |

`image_width_percent` (5 - 50%, default 20) ใช้เฉพาะ `row_image` — ปิด "แสดงรูปภาพ" ในรูปแบบนี้แล้วพื้นที่รูปจะหายไป ส่วนข้อมูลขยายเต็มแทน;
`row_date` ไม่มีส่วนรูปภาพเลย (ฟอร์มซ่อนกล่อง "รูปภาพ" ทั้งหมดเมื่อเลือกรูปแบบนี้) และซ่อนกล่อง "วันที่เผยแพร่" (สไตล์วันที่บรรทัดเดียวที่ใช้กับ
`card`/`row_image`) ไปด้วย เพราะ `row_date` ไม่ได้ใช้สไตล์ชุดนั้นเลย (ใช้ "กล่องวันที่เผยแพร่" ด้านล่างแทนทั้งหมด) — ค่า `show_date`/`date_font_size`
ฯลฯ ยังถูกบังคับ/เก็บไว้เหมือนเดิมที่ backend (ไม่กระทบข้อมูล) แค่ไม่มีช่องให้แก้ในฟอร์มเมื่ออยู่ในรูปแบบนี้

**กล่องวันที่เผยแพร่** (เฉพาะ `row_date`) แยกตัวอักษรเป็น 2 ส่วนคนละกลุ่ม (`GridArticleWidget::dateBoxFields()`) เพราะแสดงคนละบรรทัด/ขนาดกัน:

| กลุ่ม | คอลัมน์ (ค่าเริ่มต้น) |
|-------|------------------------|
| วัน (เลขวันที่ตัวใหญ่) | `date_day_font_size` (18), `date_day_bold` (**Y**), `date_day_font_family` (Sarabun), `date_day_color` (**เทาเข้ม `#374151`**) |
| เดือน/ปี (ตัวเล็ก) | `date_month_font_size` (11), `date_month_bold` (N), `date_month_font_family` (Sarabun), `date_month_color` (**เทาอ่อน `#9CA3AF`**) |
| พื้นหลังกล่อง | `date_box_background` (**เทาจาง `#F3F4F6`**, เลือก transparent ได้) |

| กลุ่ม | คอลัมน์ (ค่าเริ่มต้น) |
|-------|------------------------|
| ข้อมูล | `article_category_info_id` (**จำเป็นต้องเลือก**), `sort_by` (`publish_desc`/`publish_asc`), `max_items` (0 - 1000, **ว่างหรือ 0 = แสดงทั้งหมด**), `display_type` |
| จำนวนคอลัมน์ต่อแถว | `per_row_pc` (4) / `per_row_notebook` (3) / `per_row_tablet` (2) / `per_row_mobile` (1) — 1 - 6 (breakpoint เดียวกับ Slideset, `SLIDESET_DEVICES`) |
| รูปภาพ | `show_image` (Y), `image_width_percent` (20, 5 - 50 — เฉพาะ `row_image`), `aspect_ratio`, `image_fit`, `image_background` (`#F3F4F6`), `image_clickable` (Y) |
| กล่องของการ์ด | `show_border` (Y), `border_color` (`#E5E7EB`), `rounded_corners` (Y), `item_background` (`#FFFFFF`) — ดูตารางในหัวข้อ §3 บนสุด |
| หัวเรื่อง | เหมือน `slidesetarticle` ทุกอย่าง (default: แสดง, 18px, ตัวหนา, ดำ, ซ้าย, กดลิงก์ได้, 1 บรรทัด) |
| ข้อความเกริ่นนำ | เหมือน `slidesetarticle` แต่ **default ซ่อน** (`show_intro_text = N`, ต่างจาก `slidesetarticle` ที่ default แสดง) |
| วันที่เผยแพร่ / จำนวนเข้าชม | เหมือน `slidesetarticle` ทุกอย่าง (วันที่ default แสดง, เข้าชม default ซ่อน, สีเทา `#667085`) |
| ปุ่มอ่านทั้งหมด | ฟิลด์ชุดเดียวกับ `slidesetarticle` ทั้งหมด (ตำแหน่ง/ไอคอน/รูปแบบ/ตัวอักษร/พื้นหลัง/ลิงก์ปลายทาง) ผ่าน `HasReadAllButton` |
| ลิงก์ | `link_target` (`_self`/`_blank`) — ใช้ร่วมกันทั้งรูป/หัวเรื่อง/ข้อความเกริ่นนำที่ตั้งให้กดลิงก์ได้ |

**ตัวอย่าง** (`widgets/GridPreview.vue`) — จัดรายการเป็น CSS grid ตามจำนวนคอลัมน์ของขนาดหน้าจอที่เลือกดู (ไม่เลื่อนเหมือน Slideset จึงไม่มีลูกศร/จุด/หน้า);
หน้าตาของแต่ละรายการเปลี่ยนตาม `display_type` (การ์ดแนวตั้ง/แถวรูป/แถววันที่ ตามตารางข้างบน); การ์ด (`card`) ซ่อนพื้นที่ข้อมูลทั้งหมดเมื่อไม่มีอะไรแสดง
เหมือน Slideset (`hasBody()`) ส่วนรูปแบบแถวไม่มีทางว่างเพราะหัวเรื่องบังคับแสดงเสมอ; ลิงก์เป็นแค่ไอคอน/ป้ายบอกว่ามีลิงก์ (กดไม่ได้)

### `gridbanner` — Grid จาก banner

กล่องเรียงต่อเนื่องหลายคอลัมน์ของ banner **ไม่เลื่อน** โครงเดียวกับ `gridarticle` ทุกอย่าง (รวมกล่องของการ์ด) แต่ตัดส่วนที่ผูกกับบทความออก —
ตาราง `page_item_widget_gridbanner` (migration `2026_09_26_000003_*`; FK หมวดหมู่ตั้งชื่อเอง `pi_widget_gridbanner_category_foreign`;
**ไม่มี** `_detail` เพราะไม่มีฟิลด์แยกภาษา) class `GridBannerWidget extends CategoryListWidget` (ใช้ trait `ReadsBanners` เหมือน
`slideshowbanner`/`slidesetbanner`) ต่างจาก `gridarticle`:

- **ไม่มี** วันที่เผยแพร่, จำนวนเข้าชม, ปุ่ม "อ่านทั้งหมด" (ทั้งคอลัมน์และส่วนตั้งค่าในฟอร์ม) และ**ไม่มี** รูปแบบ "แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ"
  (banner ไม่มีแนวคิด "วันที่เผยแพร่ที่แสดงต่อผู้ชม" เหมือนบทความ) — `display_type` มีแค่ `card`/`row_image`
- **เรียงลำดับได้ 4 แบบ**: `publish_desc` / `publish_asc` / `order_asc` ลำดับน้อยไปมาก / `order_desc` ลำดับมากไปน้อย (`sort_order` ของ banner,
  เหมือน `slidesetbanner`)
- หมวดหมู่ = `banner_category_info_id`; ข้อมูลที่แสดง = banner ที่เผยแพร่อยู่ **และมีรูป**; ลิงก์ของการ์ด = url ของ banner (banner ที่ไม่มี url กดไม่ได้)
- ข้อความเกริ่นนำ **default ซ่อน** เหมือน `gridarticle`

### โครงระบบประเภท widget (เพิ่มประเภทใหม่)

ทุกอย่างของแต่ละประเภทรวมอยู่ในคลาสเดียว `App\Support\PageWidget\<ชื่อ>Widget` (extends `SettingsWidget` / `CategoryListWidget` ซึ่ง implements `PageWidgetType`) (กฎ validation, ค่าเริ่มต้น, บันทึก/ลบ, แปลงเป็นข้อมูลส่งหน้าจอ,
ตัวเลือกประกอบฟอร์ม เช่น รายการหมวดหมู่, ข้อมูลตัวอย่าง) ลงทะเบียนใน `PageWidgetRegistry` — `PageLayoutSync`, `UpdatePageItemLayoutRequest`, `PageItemController` เรียกผ่านทะเบียนนี้
ไม่รู้จักประเภทใดโดยเฉพาะ. เพิ่มประเภทใหม่:

1. migration ตาราง `page_item_widget_<ประเภท>` (PK = `page_item_widget.id`, FK cascade) + model + relation บน `PageItemWidget`
2. คลาส `<ชื่อ>Widget` (ประกาศฟิลด์ใน `fields()`) + ลงทะเบียนใน `PageWidgetRegistry::all()` — ตารางประเภทที่มีฟิลด์มาก ๆ ให้ตั้งชื่อ FK เอง (ชื่ออัตโนมัติยาวเกิน 64 ตัวอักษรของ MySQL)
3. `resources/js/utils/pageWidget.ts`: เปลี่ยน `available: true` ใน `WIDGET_TYPE_DEFS`, เพิ่ม interface/ค่าเริ่มต้น (`defaultSetting`)/ตัวตรวจ (`validateSetting`)
4. component ฟอร์มตั้งค่า + ตัวอย่างใน `Components/Admin/PageLayout/widgets/` แล้วผูกใน `WidgetSettingsDialog.vue` / `WidgetBlock.vue`
5. เทสต์ (ดู `tests/Feature/Admin/Page/PageItemWidgetTest.php`) + อัปเดตตารางด้านบน

---

## Roadmap

| รอบ | ขอบเขต | สถานะ |
|-----|--------|-------|
| 0 — schema | `page_item_info/detail` + `page_item_row/column/widget` + `*_detail` + `PageSeeder` ตัวอย่าง | ✅ เสร็จ |
| 1 — CRUD หน้าเพจ + จัดโครงสร้าง | list/add/edit (ข้อมูลทั่วไป + SEO) + แท็บโครงสร้าง (แถว/คอลัมน์/widget, เรียงลำดับผ่าน dialog, ตั้งค่า, พื้นหลัง, บันทึก) | ✅ เสร็จ |
| **2 — ประเภท widget** | โครงระบบประเภท widget (ตารางแยกต่อประเภท + registry + dialog เลือกประเภท/ตั้งค่า + ตัวอย่าง) + `slideshowbanner` + `slideshowarticle` | ✅ เสร็จ |
| 2.1 — ประเภท widget ที่เหลือ | `gridarticle` ✅ เสร็จ, `gridbanner` ✅ เสร็จ, `customtext` | 🟡 กำลังทำ |
| 3 — หน้าบ้าน | แสดงหน้าเพจตาม slug + โครงสร้าง (grid 12) ที่ `front.*` | 🔴 ยังไม่เริ่ม |
