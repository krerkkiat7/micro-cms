# PRD — โมดูลเมนูหน้าบ้าน (Front Menu)

> สถานะ: 🟡 schema + admin CRUD เสร็จแล้ว — การ render เมนูจริงที่หน้าบ้านยังไม่ทำ (ดู §Roadmap ท้ายเอกสาร)
> ลิงก์กลับ: [PRD-overview.md](PRD-overview.md) §6, [PRD-system.md](PRD-system.md) §3.2

## 0. ภาพรวมโมดูล

จัดโครงเมนูนำทางของเว็บหน้าบ้าน (แยกจากเมนู sidebar หลังบ้าน `sys_menu`/`sys_menu_group` ซึ่งเป็นคนละเรื่องกัน —
ดู PRD-system.md §3.1) เป็นโครงสร้างแบบ **tree ไม่จำกัดระดับ** ผ่าน `parent_id` ชี้ตัวเอง เมนูแต่ละรายการผูกได้กับ
หน้าเพจ, หมวดหมู่บทความ, บทความ, หรือ URL ภายนอก 1 อย่าง หรือเป็นแค่ "เมนูหัวข้อ" (จัดกลุ่ม ไม่มีลิงก์ของตัวเอง)
พร้อมชุดตั้งค่า "หัวเรื่องของหน้าเป้าหมาย" (รูปพื้นหลัง + หัวเรื่อง/หัวเรื่องรองที่ปรับฟอนต์/สี/ตัวหนาได้ + ตำแหน่งบล็อก
ข้อความแบบ 9 ทิศ) สำหรับใช้เป็นส่วนหัวของหน้าที่เมนูนั้นลิงก์ไป

รากฐานตารางอ้างอิงจากระบบเดิม (`menu_info`/`menu_detail`) ที่เจ้าของโปรเจกต์ให้มา แต่ปรับโครงสร้างใหม่ให้ตรงกับ
convention `_info`/`_detail` ที่ใช้ทั่วทั้งระบบตอนนี้ (เหมือน `article_category_info/detail`, `page_item_info/detail`)
แทนตาราง `menu_info`/`menu_detail` แบบเดิม และตัดคอลัมน์ `DisplayType`/`DisplayIcon`/`DisplayImageID` ออก
(ยังไม่ทำการแสดงไอคอน/รูปคู่ข้อความเมนู)

## 1. Data model

Migration: `database/migrations/2026_09_29_000001_create_front_menu_tables.php`

### `front_menu_info` (ข้อมูลร่วม ไม่แยกภาษา)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| `id` | `id()` | |
| `parent_id` | `foreignId` nullable → `front_menu_info` | tree, `nullOnDelete`; เลือกได้เฉพาะเมนูประเภท `heading` (เช็กใน FormRequest) |
| `menu_type` | `varchar(20)` | ดู §2 |
| `target_article_category_id` | `foreignId` nullable → `article_category_info` | `menu_type=article_category` |
| `target_article_item_id` | `foreignId` nullable → `article_item_info` | `menu_type=article_item` |
| `target_page_item_id` | `foreignId` nullable → `page_item_info` | `menu_type=page` |
| `url` | `varchar(500)` nullable | `menu_type=external` |
| `link_target` | `varchar(10)` default `_self` | `_self`/`_blank` |
| `is_home` | `char(1)` default `N` | มีได้แถวเดียวทั้งระบบที่เป็น `Y` — บังคับในโค้ด (ตั้งใหม่แล้วสลับเดิมให้อัตโนมัติในทรานแซกชันเดียว) |
| `show_header_image` / `header_image_id` | `char(1)` / `foreignId` nullable → `file_info` | รูปพื้นหลังส่วนหัวของหน้าเป้าหมาย |
| `show_title` + `title_font_size`/`title_font_family`/`title_color`/`title_bold` | `char(1)` + สไตล์ | ข้อความหัวเรื่องอยู่ที่ `front_menu_detail.title` (แยกภาษา) สไตล์ใช้ร่วมทุกภาษา |
| `show_subtitle` + `subtitle_font_size`/`subtitle_font_family`/`subtitle_color`/`subtitle_bold` | เช่นเดียวกับหัวเรื่อง | ข้อความอยู่ที่ `front_menu_detail.subtitle` |
| `header_content_align` | `varchar(20)` default `center` | ตำแหน่งบล็อกหัวเรื่อง+หัวเรื่องรองในพื้นที่ header (9 ทิศ) — ใช้ value set เดียวกับ CSS `background-position` ที่ `BACKGROUND_POSITION_STYLES` (`resources/js/utils/intropageBackground.ts`) ใช้อยู่แล้ว เพื่อ reuse `PositionPicker.vue` ตรง ๆ |
| `use_container` | `char(1)` default `Y` | Y = จำกัดความกว้างใน container, N = เต็มจอ — ชื่อ/ความหมายตรงกับ `page_item_row.use_container` |
| `show_breadcrumb` | `char(1)` default `Y` | |
| `sort_order` | `unsignedInteger` default `0` | |
| `status` | `char(1)` default `Y` | แสดง/ซ่อนเมนู |
| `is_temp` | `char(1)` default `N` | สำหรับ `FrontMenuSeeder` (ดู §5) |
| audit + timestamps + softDeletes | | `created_by`/`updated_by`/`deleted_by` (`sys_user.id`, ไม่มี FK) ตาม convention |

### `front_menu_detail` (แยกภาษา, PK = `[id, lang]`, FK `cascadeOnDelete` ไป `front_menu_info`)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| `id` | `unsignedBigInteger` | = `front_menu_info.id` |
| `lang` | `char(2)` | |
| `name` | `varchar(150)` | ชื่อเมนูที่แสดงในนำทาง — required เฉพาะภาษาหลัก |
| `title` / `subtitle` | `varchar(500)` nullable | ข้อความหัวเรื่อง/หัวเรื่องรองของหน้าเป้าหมาย |
| `status` + audit + timestamps + softDeletes | | |

> **ข้อควรระวัง** (memory `detail-table-soft-delete-gap`): soft delete `front_menu_info` ไม่ cascade ไปยัง
> `front_menu_detail` เอง (แถว detail ยังอยู่ `deleted_at=null`) — โมดูลนี้ไม่มี unique constraint บน `name`/`title`
> เลยไม่กระทบตอนนี้ แต่ query หน้าบ้านในอนาคตต้อง join กรอง `front_menu_info.deleted_at`/`status` เสมอ

### Model

- `App\Models\FrontMenuInfo` — `SoftDeletes`; `parent()` belongsTo self, `children()` hasMany self (`orderBy sort_order`),
  `details()` hasMany `FrontMenuDetail`, `headerImage()`/`targetArticleCategory()`/`targetArticleItem()`/`targetPageItem()` belongsTo
- `App\Models\FrontMenuDetail` — `SoftDeletes`, `$incrementing=false`, composite key — ค้นด้วย `where('id',...)->where('lang',...)` เสมอ ห้าม `find()`

### Support class

`App\Support\FrontMenuType` — const `NONE`/`HEADING`/`EXTERNAL`/`ARTICLE_CATEGORY`/`ARTICLE_ITEM`/`PAGE` + `OPTIONS`
(label ไทย) ใช้ร่วมกันทั้ง FormRequest/Controller/Vue (ฝั่ง Vue มีค่าคงที่ชุดเดียวกันซ้ำอยู่ที่ `resources/js/utils/frontMenu.ts`
export `FrontMenuType` — **ต้องแก้ทั้งสองฝั่งพร้อมกัน** ถ้าจะเพิ่ม/ลดประเภทเมนู)

ฟอนต์: reuse `App\Support\PageTextStyle::fontNames()`/`fontsStylesheetUrl()`/`FONT_SIZE_MIN`/`FONT_SIZE_MAX` ของโมดูล
Page ตรง ๆ (รายชื่อฟอนต์ไทยเป็น registry กลาง ไม่ได้ผูกกับโมดูล Page โดยเฉพาะ) ไม่สร้างรายการฟอนต์ซ้ำ

## 2. ประเภทเมนู (`menu_type`)

| ค่า | ความหมาย | ฟิลด์เป้าหมายที่เกี่ยวข้อง |
|---|---|---|
| `none` | ไม่กำหนด (ไม่มีลิงก์) | — |
| `heading` | เมนูหัวข้อ — ใช้จัดกลุ่มเมนูลูกเท่านั้น เป็น parent ได้อย่างเดียว ไม่มีลิงก์ของตัวเอง | — |
| `external` | ลิงก์ภายนอก | `url`, `link_target` |
| `article_category` | บทความ - รายการบทความตามหมวดหมู่ | `target_article_category_id` (เลือกผ่าน dropdown ธรรมดา — จำนวนหมวดหมู่มีจำกัด) |
| `article_item` | บทความ - รายละเอียดบทความ | `target_article_item_id` (เลือกผ่าน dialog picker ค้นหา+paginate) |
| `page` | หน้าเพจ | `target_page_item_id` (เลือกผ่าน dialog picker ค้นหา+paginate) |
| `contactus` | ติดต่อเรา — ลิงก์ไป `/{lang}/contactus` (หน้าเดียวทั้งระบบ ดู [PRD-contactus.md](PRD-contactus.md)) | — (ไม่มี id ปลายทาง; มีชุดตั้งค่าส่วนหัวเหมือนเมนูเนื้อหา, popup/ปุ่มอ่านทั้งหมดเลือกได้) |

เฉพาะเมนูประเภท `heading` เท่านั้นที่เลือกเป็น **Parent Menu** ของเมนูอื่นได้ (บังคับใน `StoreFrontMenuRequest`/
`UpdateFrontMenuRequest` ผ่าน `Rule::exists('front_menu_info','id')->where('menu_type', FrontMenuType::HEADING)`)

## 3. เงื่อนไข/การทำงาน

- **หน้าหลัก** (`is_home`) — มีได้แถวเดียวทั้งระบบ (ใช้ร่วมกันทุกภาษา เพราะ tree/target เป็นข้อมูลร่วม ต่างกันแค่
  `name`/`title`/`subtitle` ต่อภาษา) ตั้งเมนูใหม่เป็นหน้าหลักแล้วเมนูเดิมที่เคยเป็นจะถูกปลดให้อัตโนมัติในทรานแซกชันเดียว
  (`FrontMenuController::store()`/`update()`)
- **ซ่อน/แสดงเมนู** (ไอคอนลูกตา, `PUT admin.system.menu.status`) — ซ่อนเมนูประเภท `heading` จะ set `status=N`
  ให้เมนูลูกทุกระดับไปด้วยในคำสั่งเดียว (`FrontMenuController::descendantIds()`); กด "แสดง" กลับมาแก้เฉพาะแถวนั้นแถว
  เดียว **ไม่ cascade คืนสถานะให้ลูก** (ลูกที่เคยถูกซ่อนแยกไว้ก่อนหน้านี้จะยังซ่อนอยู่ ต้องกดแสดงเองทีละรายการ)
- **เมนูที่ไม่แสดง** (`status=N` หรือพาเรนต์ถูกซ่อน) = ไม่อยู่ในแถบเมนูหน้าบ้านเท่านั้น — หน้าที่ตรงกับเมนูนี้ยังใช้ตั้งค่าเมนู
  (รูปภาพ/หัวเรื่อง/หัวเรื่องรอง/breadcrumb) ตามปกติ แต่ breadcrumb = **หน้าแรก > เมนูตัวเอง** (ไม่ไล่พาเรนต์); ถ้ามีเมนูที่แสดงอยู่ชี้
  ปลายทางเดียวกัน ใช้เมนูที่แสดงก่อน; popup แบบเลือกเมนูทำงานกับเมนูนี้ได้; ปุ่ม "อ่านทั้งหมด" ที่ชี้เมนูนี้ไม่แสดง
  (`FrontMenuResolver::build()`/`findFor()`/`linkOf()`)
- **sitemap.xml อิงตามเมนูที่เผยแพร่** — ปลายทางของเมนูที่แสดงอยู่ (และบทความในหมวดหมู่ที่มีเมนู) เท่านั้นที่อยู่ใน sitemap; ซ่อนเมนู = ปลายทางหายจาก
  sitemap (ถ้าไม่มีเมนูที่แสดงอื่นชี้อยู่) — `FrontMenuResolver::publishedTargets()`, ดู [PRD-front.md](PRD-front.md) §6.1
- **ลบเมนู** — ลบได้เฉพาะเมนูที่ไม่มีเมนูลูก (เช็ก `FrontMenuInfo::where('parent_id', $id)->exists()`) — ไอคอนถังขยะ
  ที่หน้ารายการ disable ไว้ล่วงหน้าเมื่อมีลูก, backend เช็กซ้ำอีกชั้น
- **กัน cycle** — ทั้งตอนแก้ไข parent ของเมนูเดียว (`UpdateFrontMenuRequest::withValidator()`) และตอนเรียงลำดับทั้ง
  tree (`FrontMenuController::reorder()`) เช็กว่า parent ใหม่ไม่ใช่ตัวเองหรือเมนูลูกหลานของตัวเอง
- **เปลี่ยนประเภทออกจาก `heading`** ทั้งที่ยังมีเมนูลูกอยู่ — บล็อกไว้ (`UpdateFrontMenuRequest::withValidator()`)
  เพราะจะทำให้เมนูลูกกลายเป็นเมนูที่ parent ไม่ใช่ `heading` ซึ่งผิดกติกา §2

## 4. หน้าจอจัดการ (admin)

หน้าเดียว `admin.system.menu.index` → `Pages/Admin/System/FrontMenu/Index.vue` — **ไม่มีหน้า add/edit แยก**
ทุกการเพิ่ม/แก้ไขทำผ่าน dialog ตามที่ระบุไว้ในสเปก:

- **รายการแบบ tree** — `Components/Admin/FrontMenu/MenuTreeNode.vue` (recursive, เยื้องตามระดับ) ต่อแถวมี: ไอคอน
  หน้าหลัก (ถ้า `is_home=Y`), ชื่อเมนู (คลิกเปิดฟอร์มแก้ไข), badge ประเภทเมนู, ชื่อเป้าหมาย (ถ้ามี — เช่นชื่อหมวดหมู่/
  บทความ/หน้าเพจที่ผูกอยู่, คำนวณที่ `FrontMenuController::targetLabel()`), ไอคอนแก้ไข/ลูกตา(สลับสถานะ)/ถังขยะ
  (ลบได้เฉพาะไม่มีลูก)
- **ฟอร์มเพิ่ม/แก้ไข** — `Components/Admin/FrontMenu/MenuFormDialog.vue` (dialog ยาว ใช้ `LayoutDialog.vue` เป็นเปลือก
  เหมือนโมดูล Page) ฟิลด์ครบตามสเปก (Parent Menu, ประเภทเมนู, ฟิลด์ตามประเภท, Link Target, เป็นหน้าหลัก, ชื่อเมนู
  แยกภาษา, ชุดตั้งค่าหัวเรื่อง/หัวเรื่องรองพร้อมสไตล์, ตำแหน่ง 9 ทิศ, ความกว้าง, breadcrumb, สถานะ) — reuse component
  ที่มีอยู่แล้วทั้งหมด ไม่สร้างใหม่ซ้ำ: `FilePickerField.vue` (รูปพื้นหลังส่วนหัว), `ColorPickerInput.vue` (สีตัวอักษร),
  `Components/Admin/IntropageBackground/PositionPicker.vue` (ตำแหน่ง 9 ทิศ — ค่า `header_content_align` ใช้ value
  set เดียวกับ background-position จึงยืมคอมโพเนนต์นี้มาได้ตรง ๆ โดยไม่ต้องสร้างตัวใหม่), `LangFieldGroup.vue`
  (ฟิลด์แยกภาษา), `SearchableSelect`/`STATUS_OPTIONS`/`SHOW_OPTIONS`/`CONTAINER_OPTIONS`/`LINK_TARGET_OPTIONS`/
  `FONT_SIZE_OPTIONS` (จาก `utils/options.ts`/`utils/pageLayout.ts`)
- **เลือกบทความ/หน้าเพจ** — `Components/Admin/FrontMenu/ArticleItemPickerDialog.vue` /
  `PageItemPickerDialog.vue` (dialog ใหม่ — ไม่มีของเดิมให้ reuse — โครงเทียบเคียง `FilePickerDialog.vue` แบบง่าย
  กว่า: ค้นหา 1 ช่อง + รายการ + `Pagination.vue`) ดึงข้อมูลผ่าน `GET admin.system.menu.pick.articles`/`.pick.pages`
  (คืน id + ชื่อภาษาหลักเท่านั้น เพียงพอสำหรับเลือก)
- **เรียงลำดับ** — ปุ่ม "เรียงลำดับ" เปิด `Components/Admin/FrontMenu/MenuReorderDialog.vue` — ทำงานบนสำเนา
  (`workingTree`, `cloneDeep` จาก `utils/pageLayout.ts`) ใช้ `vuedraggable` ซ้อนกันแบบ recursive ผ่าน
  `MenuReorderNode.vue`: **เฉพาะเมนูประเภท `heading` เท่านั้นที่ render กล่องลูกให้ลาก** (เมนูประเภทอื่นไม่มี
  `<draggable>` ของลูกเลย จึงวางเมนูอื่นลงไปไม่ได้โดยธรรมชาติ ไม่ต้องเช็กเพิ่มระดับ UI) มี `:move` callback กัน
  ลากเมนูหัวข้อไปวางใต้เมนูลูกของตัวเอง (กัน cycle ฝั่ง client ก่อนส่ง — backend เช็กซ้ำอีกชั้นเสมอ) กด "ยืนยันลำดับ"
  ถึง flatten เป็น `{id, parent_id, sort_order}[]` (`utils/frontMenu.ts` → `flattenForReorder()`) ส่งไปบันทึกจริง

## 5. Permission / Route / Log

**Permission** — `system.menu.view`/`manage`/`delete` (seed ไว้แล้วใน `DatabaseSeeder` ตั้งแต่ก่อนโมดูลนี้เริ่มทำ,
`system021`-`023`) `manage` ครอบคลุมทุก action ที่แก้ข้อมูล (store/update/reorder/toggleStatus) ไม่ได้แยก action
ย่อยเพิ่มจากที่ seed ไว้เดิม

**Route** — `routes/web.php`, กลุ่ม `Route::prefix('system/menu')` (แทรกระหว่าง `system/usergroup` กับ
`system/backlog`):

| Method | Path | Route name | Controller action |
|---|---|---|---|
| GET | `/system/menu` | `admin.system.menu.index` | `index` |
| POST | `/system/menu` | `admin.system.menu.store` | `store` |
| GET | `/system/menu/pick/articles` | `admin.system.menu.pick.articles` | `pickArticles` |
| GET | `/system/menu/pick/pages` | `admin.system.menu.pick.pages` | `pickPages` |
| PUT | `/system/menu/reorder` | `admin.system.menu.reorder` | `reorder` |
| PUT | `/system/menu/{menu}` | `admin.system.menu.update` | `update` |
| DELETE | `/system/menu/{menu}` | `admin.system.menu.destroy` | `destroy` |
| PUT | `/system/menu/{menu}/status` | `admin.system.menu.status` | `toggleStatus` |

เมนู sidebar หลังบ้าน `system-menu` (`database/seeders/MenuSeeder.php`) ชี้มาที่ `admin.system.menu.index` อยู่แล้ว
ตั้งแต่ก่อนโมดูลนี้มีโค้ดจริง — ไม่ต้องแก้ seeder

**Log** — `LogBackAccess::record('จัดการเมนูหน้าบ้าน')` ที่ `index()` (เฉพาะตอนไม่มี query filter) +
`LogBackAction::record('system.menu', 'create'|'update'|'delete', ...)` ทุกจุดที่แก้ข้อมูล (รวม `reorder()` ที่บันทึก
เป็น `update` โดยไม่มี `ref_id` เพราะเป็นการแก้ทั้งชุด)

## 6. ข้อมูลตัวอย่าง (`FrontMenuSeeder`)

`database/seeders/FrontMenuSeeder.php` — เรียกจาก `DatabaseSeeder` **ต่อจาก** `ArticleSeeder`/`PageSeeder` เพราะต้อง
อ้าง id ของหมวดหมู่บทความ/หน้าเพจตัวอย่าง (resolve ผ่านชื่อภาษาหลักที่ทั้งสอง seeder สร้างไว้ ไม่ใช่ slug ตรง ๆ
เพราะลำดับภาษาที่ไม่มี suffix ใน `ArticleSeeder` ไม่ได้การันตีว่าเป็นภาษาหลักเสมอไป) สร้าง:

- เมนู "หน้าแรก" ตั้งเป็น `is_home='Y'` ประเภท `page` ชี้หน้าเพจตัวอย่างของ `PageSeeder`
- เมนูหัวข้อ (`heading`) "บทความ" มีลูก 3 รายการประเภท `article_category` ชี้หมวดหมู่ตัวอย่างของ `ArticleSeeder`
  (news/activities/articles)

ทุกแถว `is_temp='Y'`, ใช้ `updateOrCreate` โดย resolve id เดิมผ่านชื่อเมนูภาษาหลัก (เทียบเคียง `PageSeeder`) — รันซ้ำได้
(`php artisan db:seed --class=FrontMenuSeeder` ต้องรัน `ArticleSeeder`/`PageSeeder` มาก่อนแล้ว)

## Roadmap — การแสดงผลหน้าบ้าน (✅ ทำแล้วใน branch `front-init` — ดู [PRD-front.md](PRD-front.md) §3, `App\Support\Front\FrontMenuResolver`)

ขอบเขตรอบที่ทำนี้เป็น **เฉพาะฝั่งจัดการ** (schema + seeder ตัวอย่าง + admin CRUD) ตามที่ตกลงกับเจ้าของโปรเจกต์ —
การ render เมนูจริงที่หน้าบ้านยังไม่ได้ทำ งานที่เหลือ:

- **Front controller** อ่าน tree ของ `front_menu_info`/`front_menu_detail` ตาม `lang` ปัจจุบัน (กรอง `status='Y'`
  ทั้งพาเรนต์และตัวเอง — ระวัง soft-delete gap ตาม §1) แล้วแชร์เป็น prop ให้ layout หน้าบ้าน (`FrontLayout.vue`)
- **Vue component หน้าบ้าน** (`resources/js/Components/Front/`) เรนเดอร์ตามกติกาที่ผู้ใช้ระบุไว้:
  - **level 1** เรียงตามแนวนอน เมนูลูก (level 2 เป็นต้นไป) กางลงมาด้านล่างเมื่อ hover/คลิกเมนูระดับบน
  - **level 2 เป็นต้นไป** เรียงลงมาตามแนวตั้ง เมนูลูกของระดับนั้นกางออกไปทางด้านข้าง (flyout)
- ใช้ค่าที่ตั้งไว้แล้วในฝั่งจัดการ (รูปพื้นหลังส่วนหัว, หัวเรื่อง/หัวเรื่องรองพร้อมสไตล์, ตำแหน่ง 9 ทิศ, container/เต็มจอ,
  breadcrumb) เป็นส่วนหัวของหน้าเป้าหมายเมื่อคลิกเข้าไปตามเมนูนั้น
- resolve URL จริงของแต่ละประเภทเมนู (`external` ใช้ `url` ตรง ๆ, `article_category`/`article_item`/`page` ต้อง map
  ไปยัง route หน้าบ้านของโมดูลนั้น ๆ ซึ่งบางโมดูล เช่น หน้ารายละเอียดบทความ/หมวดหมู่บทความที่หน้าบ้าน ยังไม่มี route
  จริงในระบบตอนนี้ด้วยซ้ำ — ต้องทำคู่กัน)
