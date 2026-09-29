# PRD — โมดูลบทความ (Article)

เอกสารนี้ลงรายละเอียดของโมดูล **บทความ** (หมวดหมู่ + บทความ + ตั้งค่า) ในหลังบ้าน ภาพรวมทั้งระบบดูที่
[PRD-overview.md](PRD-overview.md) §3

สถานะ: 🟢 มีแล้ว · 🟡 มีบางส่วน · 🔴 ยังไม่มี

| # | หัวข้อ | ตารางหลัก | สถานะ |
|---|--------|-----------|-------|
| 1 | หมวดหมู่บทความ | `article_category_info`, `article_category_detail` | 🟢 schema + controller/route/UI (list, add, edit) เสร็จครบ |
| 2 | บทความ | `article_item_info`, `article_item_detail`, `article_item_part`, `article_item_part_file`, `article_item_part_detail` | 🟢 schema + controller/route/UI (list, add, edit) พร้อม part editor เสร็จครบ |
| 2.1 | แท็กบทความ | `article_tag_info`, `article_tag_detail`, `article_item_tag` (pivot) | 🟢 schema + หน้าจัดการเต็มรูปแบบ (list, add, edit) + เลือก/สร้างแท็กแบบ autocomplete จากในฟอร์มบทความ เสร็จครบ |
| 3 | ตั้งค่าโมดูลบทความ | `sys_setting` (group = `article`) | 🟢 หน้าตั้งค่า + ล้างแคช เสร็จครบ |

---

## 0. ภาพรวมโมดูล

- **หมวดหมู่มีระดับเดียว** (ไม่มีหมวดหมู่ย่อย) และ **1 บทความมีได้เพียง 1 หมวดหมู่** — ออกแบบเพื่อความง่ายต่อการใช้งาน
- ทั้งหมวดหมู่และบทความรองรับ **SEO / AEO / GEO** (slug, meta title/description/keywords, og title/description
  สำหรับแชร์โซเชียล — โครงสร้างข้อมูลเน้นความชัดเจนและครบถ้วน ซึ่งเอื้อต่อทั้ง search engine, answer engine
  และ generative engine โดยไม่ต้องมีคอลัมน์พิเศษเพิ่มเติมนอกเหนือจากฟิลด์ SEO มาตรฐาน + เนื้อหาที่มีโครงสร้างดี)
- รองรับ**หลายภาษาตามที่ระบบตั้งค่าไว้** — อ่านรายการภาษาที่เปิดใช้จาก `sys_setting`
  (`site.lang_selected`/`site.lang_default` ผ่าน `App\Support\Setting::selectedLanguages()`/`defaultLanguage()`)
  ไม่ hardcode `th`/`en` ฟิลด์ที่แยกตามภาษาจะ **required เฉพาะภาษาหลัก** (`lang_default`) ภาษาอื่น กรอกหรือไม่ก็ได้
  — บังคับที่ FormRequest ตอนบันทึก ไม่ใช่ที่ระดับ schema (คอลัมน์ทุกตัวเป็น nullable)

### รูปแบบการกรอกข้อมูล (ทั้งหมวดหมู่และบทความ)

ฟอร์มแบ่งข้อมูลเป็น 2 กลุ่มเสมอ:

- **กลุ่มข้อมูลร่วม** — ไม่แยกภาษา เช่น รูปภาพหน้าปก, หมวดหมู่ (ของบทความ), สถานะ, ลำดับ
- **กลุ่มข้อมูลแยกภาษา** — กรอกพร้อมกันทุกภาษาที่ระบบเปิดใช้ในหน้าเดียว โดยมีกรอบ (fieldset) ครอบแต่ละฟิลด์
  เพื่อให้เห็นชัดว่าอินพุตของแต่ละภาษาเป็นข้อมูล "ฟิลด์เดียวกัน" เช่น หัวเรื่อง (ไทย) กับ หัวเรื่อง (อังกฤษ)
  อยู่ในกรอบเดียวกัน คนละแถบ/แท็บย่อย

### เนื้อหาบทความแบบแบ่ง part

เนื้อหาบทความ (ต่างจากหมวดหมู่ ซึ่งมีแค่ฟิลด์ `detail` เดียว) ประกอบด้วย **หลาย part เรียงลำดับได้**
(ลากสลับลำดับ, เริ่มต้นบทความใหม่ด้วย part ข้อความ 1 อันเสมอ) part แต่ละแบบ:

| ประเภท part (`part_type`) | รายละเอียด |
|-------------|-----------|
| `text` | text editor (rich text) — **ไม่รองรับแทรกรูปภาพ/วิดีโอ/media** ในตัวข้อความ (ให้ใช้ part ประเภทอื่นแทน) — เนื้อหาอยู่ที่ `article_item_part_detail.detail` (แยกภาษา) |
| `image` | รูปภาพเดี่ยว + alt_text แยกภาษา — 1 แถวใน `article_item_part_file` |
| `images` | กลุ่มรูปภาพ ลาก/วางสลับลำดับได้ แต่ละรูปมี alt_text แยกภาษา — หลายแถวใน `article_item_part_file` + เลือกรูปแบบแสดงผล (`images_display_type`) |
| `video` | วิดีโอเดี่ยว + ตั้งค่ารูปภาพหน้าปก (`cover_image_id`) |
| `document` / `documents` | เอกสารเดี่ยวหรือกลุ่ม — ถ้าเป็น PDF เลือกเปิด/ปิด preview ได้ (เก็บใน `article_item_part.setting`) |

part ที่เกี่ยวกับรูปภาพ/เอกสาร/วิดีโอ ตั้งหัวข้อได้ (`article_item_part_detail.title` แยกภาษา)

**รูปแบบแสดงผลของ `images`** (`images_display_type`, ค่าที่เก็บเป็น slug ภาษาอังกฤษ ≤ 20 ตัวอักษร):

| ชื่อ | ค่าที่เก็บ |
|------|-----------|
| Thumbnail Carousel | `thumbnail_carousel` |
| Multi-item Carousel | `multi_carousel` |
| Grid Gallery with Lightbox | `grid_lightbox` |
| Full-width Slider | `full_width_slider` |
| Masonry Grid | `masonry_grid` |
| Justified Grid | `justified_grid` |
| Stacked / Overlapping Cards | `stacked_cards` |

ฟิลด์เลือกรูปแบบแสดงผลใน `PartImages.vue` ไม่ได้ใช้ dropdown ธรรมดา — ใช้ `ImagesDisplayTypePicker.vue` แสดงเป็น
การ์ดเลือกได้ 7 ใบ แต่ละใบมีไดอะแกรม SVG อย่างง่าย (วาดเอง ไม่มีรูปตัวอย่างจริงในระบบ) สื่อโครงสร้างคร่าว ๆ ของ
รูปแบบนั้น พร้อมคำอธิบายภาษาไทยสั้น ๆ ใต้ชื่อ (`IMAGES_DISPLAY_TYPES` ใน `utils/articleParts.ts` เพิ่มฟิลด์
`description` ต่อรายการ)

โครงสร้างตาราง (`article_item_part`, `article_item_part_file`, `article_item_part_detail`) อิง `file_info`
(โมดูลจัดการไฟล์ที่มีอยู่แล้ว) เป็นที่เก็บไฟล์จริง ผ่าน `FilePickerField.vue`/`FilePickerDialog.vue` — ดูรายละเอียด
เต็มที่ §2

---

### ลำดับการ์ดในฟอร์มเพิ่ม/แก้ไข

หน้าเพิ่ม/แก้ไขใช้ component ร่วมกัน (pattern เดียวกับ `PageItemFormFields.vue` ของโมดูล page) — `useForm` อยู่ในหน้า, รูปหน้าปกผูก `v-model:intro-image`
- หมวดหมู่ `Components/Admin/ArticleForm/ArticleCategoryFormFields.vue`: ข้อมูลหมวดหมู่ (ชื่อ + ข้อความเกริ่นนำ + รายละเอียด) → รูปภาพหน้าปกและลำดับ
  → SEO / AEO / GEO → สถานะ
- บทความ `Components/Admin/ArticleForm/ArticleItemFormFields.vue`: ข้อมูลบทความ (ชื่อ + ข้อความเกริ่นนำ) → การจัดกลุ่ม (แท็ก + หมวดหมู่) → รูปภาพหน้าปก
  → เนื้อหา → SEO / AEO / GEO → การเผยแพร่ (วันที่เผยแพร่ + วันที่ปิดการเผยแพร่) → สถานะ
- การ์ด SEO ใช้ร่วม `ArticleSeoFields.vue` แบ่งหัวข้อย่อย ลิงก์ของหน้า / การแสดงผลในผลการค้นหา / การแชร์ไปโซเชียลมีเดีย; type ฟอร์มอยู่ `utils/articleForm.ts`

## 1. หมวดหมู่บทความ

**วัตถุประสงค์** — จัดกลุ่มบทความให้เลือกได้ตอนสร้าง/แก้ไขบทความ 1 ระดับ (ไม่มีหมวดหมู่ย่อย)

**Data model — `article_category_info`** (ข้อมูลร่วม ไม่แยกภาษา)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK autoincrement | |
| `intro_image_id` | bigint FK → `file_info.id` null (nullOnDelete) | รูปภาพหน้าปกหมวดหมู่ (เลือกจากโมดูลจัดการไฟล์) |
| `sort_order` | `unsigned int` default 0 | ลำดับการแสดงผล |
| `status` | `char(1)` default `Y` | `Y` = เปิดใช้งาน, `N` = ปิด |
| `is_temp` | `char(1)` default `N` | `Y` = ข้อมูลตัวอย่าง/ตั้งต้น (เผื่อทำระบบลบข้อมูลตัวอย่างออกภายหลัง — จะใช้ต่อก็ได้) |
| `created_by` / `updated_by` / `deleted_by` | bigint null (`sys_user.id`, ไม่มี FK) | ผู้สร้าง / ผู้แก้ไขล่าสุด / ผู้ลบ |
| `timestamps`, `deleted_at` | | `SoftDeletes` |

**Data model — `article_category_detail`** (ข้อมูลแยกตามภาษา — PK = `id` + `lang`)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `unsigned bigint` | = `article_category_info.id` (FK, cascadeOnDelete) — ส่วนหนึ่งของ PK |
| `lang` | `char(2)` | เช่น `th`, `en` — ส่วนหนึ่งของ PK |
| `title` | `varchar(250)` null | ชื่อหมวดหมู่ — required เฉพาะ `lang_default` (เช็กที่ FormRequest) |
| `intro_text` | `varchar(2000)` null | คำโปรยสั้น |
| `detail` | `text` null | คำอธิบาย/รายละเอียดหมวดหมู่ |
| `slug` | `varchar(250)` null | สำหรับ URL — **unique ต่อภาษา** (`unique(['lang','slug'])`) |
| `meta_title` | `varchar(250)` null | SEO |
| `meta_description` | `varchar(500)` null | SEO |
| `meta_keywords` | `varchar(250)` null | SEO |
| `og_title` | `varchar(250)` null | สำหรับแชร์โซเชียล (og:title) |
| `og_description` | `varchar(500)` null | สำหรับแชร์โซเชียล (og:description) — og:image ใช้รูปหน้าปกร่วม (`intro_image_id`) |
| `status` | `char(1)` default `Y` | เปิด/ปิดการแสดงผลต่อภาษา |
| `created_by` / `updated_by` / `deleted_by` | bigint null (`sys_user.id`, ไม่มี FK) | |
| `timestamps`, `deleted_at` | | `SoftDeletes` |

> Eloquent ไม่รองรับ composite primary key เต็มรูปแบบ — ค้นด้วย
> `ArticleCategoryDetail::where('id', ...)->where('lang', ...)` เสมอ (เทียบเคียง `SysSetting`) ห้ามใช้ `find()`

**Model**
- `App\Models\ArticleCategoryInfo` — `SoftDeletes`; `introImage()` belongsTo `FileInfo`; `details()` hasMany `ArticleCategoryDetail`
- `App\Models\ArticleCategoryDetail` — `SoftDeletes`, `$incrementing = false`; `category()` belongsTo `ArticleCategoryInfo`

**Migration** — `database/migrations/2026_09_15_000001_create_article_category_tables.php`

**Seeder** — `database/seeders/ArticleSeeder.php` (เรียกจาก `DatabaseSeeder`) สร้างหมวดหมู่ตัวอย่าง 3 รายการ
(ข่าวสาร/News, กิจกรรม/Activities, บทความทั่วไป/Articles) ทำเครื่องหมาย `is_temp = 'Y'` ทุกแถว อ่านรายการภาษาจาก
`Setting::selectedLanguages()` (fallback `th,en`) — รันซ้ำได้ (`updateOrCreate`)

**หน้าจอ** — เสร็จแล้ว (`Admin/Article/Category/{Index,Add,Edit}.vue`)
- list หมวดหมู่ (ค้นหาชื่อ+ข้อความเกริ่นนำ, กรองสถานะ, เรียงลำดับได้ทุกคอลัมน์ default เรียงชื่อ, paging)
- form เพิ่ม/แก้ไข ตามรูปแบบ "กลุ่มข้อมูลร่วม" + "กลุ่มข้อมูลแยกภาษา" (§0) พร้อม rich text editor (ไม่มี media) และ SEO card

**Permission code** (seed ไว้แล้วใน `DatabaseSeeder.php`) — `article.category.view`, `article.category.manage`,
`article.category.delete`

**Route** — `admin.article.category.{index,add,store,edit,update,destroy}` (ผูกไว้ใน `MenuSeeder.php` และมี route
จริงรองรับครบแล้วใน `routes/web.php`, controller: `App\Http\Controllers\Admin\Article\ArticleCategoryController`)
(ตาม convention URL เอกพจน์ `/admin/article/category` — ดู `docs/PRD-overview.md` §5.1)

---

## 2. บทความ

**วัตถุประสงค์** — เนื้อหาข่าว/บทความ ผูกกับหมวดหมู่เดียว รองรับ SEO/AEO/GEO และเนื้อหาแบบแบ่ง part (§0)

**Data model — `article_item_info`** (ข้อมูลร่วม ไม่แยกภาษา)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK autoincrement | |
| `article_category_info_id` | bigint FK → `article_category_info.id` null (nullOnDelete) | หมวดหมู่ของบทความ — **ยัง nullable ระดับ DB** (บังคับเลือกที่ FormRequest ตอนทำ CRUD) |
| `intro_image_id` | bigint FK → `file_info.id` null (nullOnDelete) | รูปภาพหน้าปกบทความ |
| `publish_date` | `datetime` null | วันที่เผยแพร่ |
| `publish_down` | `datetime` null | วันที่ปิดการเผยแพร่ |
| `view_amount` | `unsigned int` default 0 | จำนวนเข้าดูทั้งหมด |
| `status` | `char(1)` default `Y` | `Y` = เปิดใช้งาน, `N` = ปิด |
| `is_temp` | `char(1)` default `N` | `Y` = ข้อมูลตัวอย่าง/ตั้งต้น |
| `created_by` / `updated_by` / `deleted_by` | bigint null (`sys_user.id`, ไม่มี FK) | |
| `timestamps`, `deleted_at` | | `SoftDeletes` |

**Data model — `article_item_detail`** (ข้อมูลแยกตามภาษา — PK = `id` + `lang`; **ไม่มีฟิลด์ `detail`** เนื้อหาเต็มอยู่ที่ระบบ part แทน)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `unsigned bigint` | = `article_item_info.id` (FK, cascadeOnDelete) |
| `lang` | `char(2)` | ส่วนหนึ่งของ PK |
| `title` | `varchar(500)` null | หัวเรื่อง — required เฉพาะ `lang_default` |
| `intro_text` | `varchar(2000)` null | ข้อความเกริ่นนำ |
| `slug` | `varchar(250)` null | unique ต่อภาษา |
| `meta_title` | `varchar(250)` null | SEO — ขนาดคอลัมน์เดียวกับ `article_category_detail` |
| `meta_description` | `varchar(500)` null | SEO |
| `meta_keywords` | `varchar(250)` null | SEO |
| `og_title` | `varchar(250)` null | แชร์โซเชียล |
| `og_description` | `varchar(500)` null | แชร์โซเชียล — og:image ใช้ `intro_image_id` ร่วม |
| `status` | `char(1)` default `Y` | |
| `created_by` / `updated_by` / `deleted_by`, `timestamps`, `deleted_at` | | เหมือนหมวดหมู่ |

**Data model — `article_item_part`** (แต่ละ part ของเนื้อหา เรียงด้วย `sort_order`)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK autoincrement | |
| `article_item_info_id` | bigint FK → `article_item_info.id` (cascadeOnDelete) | |
| `sort_order` | `unsigned int` default 0 | ลำดับ part (ลากสลับได้) |
| `part_type` | `varchar(10)` | `text`/`image`/`images`/`video`/`document`/`documents` |
| `images_display_type` | `varchar(20)` null | เฉพาะ `part_type = images` — ดูตาราง slug ที่ §0 |
| `show_title` | `char(1)` default `Y` | แสดงหัวเรื่องของ part นี้ที่หน้าบ้านหรือไม่ (checkbox ในฟอร์ม) |
| `status` | `char(1)` default `Y` | แสดง/ซ่อน part นี้ทั้งอันที่หน้าบ้าน (ไอคอนรูปตา/ตาขีดทับในฟอร์ม) — คนละความหมายกับ soft delete |
| `setting` | `json` null | ตั้งค่าเพิ่มเติมตามประเภท เช่น เปิด/ปิด pdf preview — cast เป็น `array` ใน model |
| `created_by` / `updated_by` / `deleted_by`, `timestamps`, `deleted_at` | | |

**Data model — `article_item_part_file`** (ไฟล์ของแต่ละ part — รูปภาพ/เอกสาร/วิดีโอ เรียงด้วย `sort_order`)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK autoincrement | |
| `article_item_part_id` | bigint FK → `article_item_part.id` (cascadeOnDelete) | |
| `sort_order` | `unsigned int` default 0 | ลำดับไฟล์ในกลุ่ม (`images`/`documents`) |
| `file_id` | bigint FK → `file_info.id` null (nullOnDelete) | ไฟล์หลักของแถวนี้ |
| `cover_image_id` | bigint FK → `file_info.id` null (nullOnDelete) | รูปภาพหน้าปก (เฉพาะ `part_type = video`) |
| `description` | `json` null | รายละเอียด/alt_text แยกภาษา เช่น `{"th": "...", "en": "..."}` — cast เป็น `array` ใน model |
| `created_by` / `updated_by` / `deleted_by`, `timestamps`, `deleted_at` | | |

**Data model — `article_item_part_detail`** (หัวข้อ/เนื้อหาข้อความของแต่ละ part แยกภาษา, PK = `id` + `lang`)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `unsigned bigint` | = `article_item_part.id` (FK, cascadeOnDelete) |
| `lang` | `char(2)` | ส่วนหนึ่งของ PK |
| `title` | `varchar(250)` null | หัวข้อของ part — ใส่ได้ทุกประเภท ยกเว้นข้อความล้วน |
| `detail` | `text` null | เนื้อหาของ part ประเภท `text` (rich text) |
| `created_by` / `updated_by` / `deleted_by`, `timestamps`, `deleted_at` | | |

**Model** (ทั้งหมดใน `app/Models/`)
- `App\Models\ArticleItemInfo` — `SoftDeletes`; `category()`/`introImage()` belongsTo; `details()`/`parts()` hasMany (`parts()` เรียง `sort_order`); `tags()` belongsToMany ผ่าน `article_item_tag`
- `App\Models\ArticleItemDetail` — `SoftDeletes`, `$incrementing = false`; `item()` belongsTo
- `App\Models\ArticleItemPart` — `SoftDeletes`; `casts(['setting' => 'array'])`; `item()` belongsTo; `files()`/`details()` hasMany
- `App\Models\ArticleItemPartFile` — `SoftDeletes`; `casts(['description' => 'array'])`; `part()`/`file()`/`coverImage()` belongsTo
- `App\Models\ArticleItemPartDetail` — `SoftDeletes`, `$incrementing = false`; `part()` belongsTo

> ตารางที่มี PK แบบ `id`+`lang` ต้อง query ด้วย `where('id', ...)->where('lang', ...)` เสมอ ห้ามใช้ `find()`/`save()`
> กับแถวที่ดึงมา (bug composite key ที่เคยเจอตอนทำ `ArticleCategoryDetail::update()`)

**Seeder** — ขยาย `database/seeders/ArticleSeeder.php` (ไฟล์เดิม) สร้างบทความตัวอย่าง 5 รายการกระจายในหมวดหมู่
ตัวอย่างที่มีอยู่ ทำเครื่องหมาย `is_temp = 'Y'` แต่ละบทความมี part ประเภท `text` 1 อัน (ยังไม่มี part
รูปภาพ/วิดีโอ/เอกสารตัวอย่าง เพราะไม่มีไฟล์ตัวอย่างใน `file_info` — โมดูลจัดการไฟล์เป็นพื้นที่ส่วนตัวต่อผู้ใช้)

**หน้าจอ** — เสร็จแล้ว (`Admin/Article/Item/{Index,Add,Edit}.vue`)
- list บทความ (ค้นหาชื่อ+ข้อความเกริ่นนำ, กรองหมวดหมู่+สถานะ, เรียงลำดับได้ทุกคอลัมน์ default เรียงชื่อ, paging)
- form เพิ่ม/แก้ไข ตามรูปแบบ "กลุ่มข้อมูลร่วม" + "กลุ่มข้อมูลแยกภาษา" (§0) ใช้ `FilePickerField.vue` เลือกรูป/เอกสาร/วิดีโอ
  และ `TagPicker.vue` (§2.1) เลือก/สร้างแท็ก — ฟิลด์ "หมวดหมู่" (และ dropdown อื่นทุกจุดในระบบ) ใช้
  `Components/SearchableSelect.vue` (พิมพ์ค้นหากรองตัวเลือกได้ v-model เป็น string) แทน `SelectInput.vue` เดิม
  (ลบไฟล์นี้ออกแล้ว) — เริ่มทดลองที่ฟิลด์หมวดหมู่ก่อน แล้วเปลี่ยน dropdown ทั้งระบบตามมาในรอบถัดมา
- part editor (`Components/Admin/ArticlePart/*`) — ลากสลับลำดับรูปภาพ/เอกสารภายในกลุ่มโดยตรง (`vuedraggable`,
  ghost placeholder แบบ SortableJS Simple List ให้เห็นขอบเขตตำแหน่งที่จะวางชัดเจน) แต่การสลับลำดับ **part**
  ทำผ่าน dialog แยก (`PartReorderDialog.vue`) แทนการลากตรง ๆ ในหน้าฟอร์ม — เพราะ part แต่ละอันสูงมาก ลากข้ามที่ไกล ๆ
  ยาก dialog แสดงแค่ `[ประเภท] : [หัวเรื่อง]` แถวเตี้ย ๆ เห็นภาพรวมทั้งหมด ลากสลับในนั้นแล้วกด "ยืนยันลำดับ"
  ถึงจะเปลี่ยนลำดับจริง (ยกเลิกได้โดยไม่กระทบ) — แต่ละ part มีปุ่ม checkbox "แสดงหัวเรื่องที่หน้าบ้าน" (`show_title`)
  และไอคอนตา/ตาขีดทับสลับ แสดง/ซ่อน part (`status`) ก่อนไอคอนลบ ไอคอนของแต่ละประเภท part (`PART_TYPE_ICONS` ใน
  `utils/articleParts.ts`) ใช้ร่วมกัน 3 ที่: หัวการ์ด part (`PartCard.vue`), ปุ่ม "เพิ่ม part" แต่ละประเภท
  (`PartList.vue` — ไอคอนบวกนำหน้าไอคอนประเภท เพื่อสื่อว่าเป็นปุ่มเพิ่ม), และแถวในรายการของ dialog จัดลำดับ
  (`PartReorderDialog.vue`)

**Permission code** (seed ไว้แล้ว) — `article.item.view`, `article.item.manage`, `article.item.delete`

**Route** (ผูกไว้ใน `MenuSeeder.php` แต่ **ยังไม่มี route จริงรองรับ**) — `admin.article.item.index`

## 2.1 แท็กบทความ

**วัตถุประสงค์** — ชุดแท็กกลาง (เหมือนหมวดหมู่ แต่ไม่มีรูปหน้าปก/ไม่มีการเรียงมือ) ให้บทความเลือกผูกได้หลายแท็ก

**Data model — `article_tag_info`** (ชุดแท็กกลาง ไม่แยกภาษา)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK autoincrement | |
| `status` | `char(1)` default `Y` | |
| `is_temp` | `char(1)` default `N` | |
| `created_by` / `updated_by` / `deleted_by`, `timestamps`, `deleted_at` | | |

**Data model — `article_tag_detail`** (ชื่อแท็กแยกตามภาษา, PK = `id` + `lang`)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `unsigned bigint` | = `article_tag_info.id` (FK, cascadeOnDelete) |
| `lang` | `char(2)` | ส่วนหนึ่งของ PK |
| `name` | `varchar(100)` null | ชื่อแท็ก — ห้ามซ้ำภายในภาษาเดียวกัน (validate ที่ FormRequest ไม่ใช่ DB unique index — ดูหมายเหตุด้านล่าง) ไม่ว่าแท็กที่ชื่อซ้ำจะสถานะใช้งานหรือไม่ก็ตาม |
| `slug` | `varchar(100)` null | unique ต่อภาษา |
| `status` | `char(1)` default `Y` | |
| `created_by` / `updated_by` / `deleted_by`, `timestamps`, `deleted_at` | | |

**Data model — `article_item_tag`** (pivot บทความ↔แท็ก, เทียบเคียง `sys_usergroup_action` — ไม่มี soft delete)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `article_item_info_id` | bigint FK → `article_item_info.id` (cascadeOnDelete) | ส่วนหนึ่งของ PK |
| `article_tag_info_id` | bigint FK → `article_tag_info.id` (cascadeOnDelete) | ส่วนหนึ่งของ PK |
| `created_by` / `updated_by` | bigint null | |
| `timestamps` | | ไม่มี `deleted_at` |

**Model** — `App\Models\ArticleTagInfo` (`details()` hasMany, `items()` belongsToMany), `App\Models\ArticleTagDetail`
(`$incrementing = false`, `tag()` belongsTo)

**Permission code** — ใช้ชุดเดียวกับบทความ `article.item.view`/`article.item.manage`/`article.item.delete`
ไม่แยกสิทธิ์ `article.tag.*` ต่างหาก เพราะแท็กสร้างใหม่ได้จากในฟอร์มบทความอยู่แล้ว (ดู quickStore() ด้านล่าง)
จึงต้องสัมพันธ์กับสิทธิ์ของบทความเสมอ

**Seeder** — สร้างแท็กตัวอย่าง 4 แท็ก (ประชาสัมพันธ์/กิจกรรม/ความรู้/อัปเดต) ใน `ArticleSeeder.php`

**หน้าจัดการแท็ก** (`Admin\Article\ArticleTagController` + `Pages/Admin/Article/Tag/{Index,Add,Edit}.vue`) —
list/add/edit ตามต้นแบบหมวดหมู่บทความ แต่ตัดฟิลด์ที่ไม่มีในตาราง (รูปหน้าปก/ลำดับ) ออก และ **ไม่มีฟิลด์ slug
ในฟอร์ม** — หน้าบ้านที่จะดึงบทความตามแท็กใช้ชื่อแท็กตรง ๆ ไม่ผ่าน slug คอลัมน์ `slug` ในตารางจึงถูกปล่อยว่างจากทางนี้
(มีค่าเฉพาะแท็กที่สร้างผ่านตัวเลือกด้านล่าง) หน้ารายการมีคอลัมน์ "จำนวนบทความ" (นับจาก `article_item_tag` ผ่าน
`withCount('items')`) ช่วยตัดสินใจก่อนลบ. `route` ใช้ชื่อมาตรฐาน `admin.article.tag.{index,add,store,edit,update,destroy}`
log action module_code = `article.tag`

**ชื่อแท็กห้ามซ้ำ** — `Store`/`UpdateArticleTagRequest` ตรวจ `Rule::unique('article_tag_detail','name')->where('lang', $lang)`
ต่อภาษา (update `ignore()` แถวของตัวเอง) ครอบคลุมทั้งแท็กที่ใช้งาน (`status='Y'`) และไม่ใช้งาน (`status='N'`)
เพราะเงื่อนไขไม่ได้กรองด้วยคอลัมน์ `status` เลย — endpoint `quickStore()` (ด้านล่าง) ก็ใช้กฎเดียวกัน
เพื่อกันแท็กชื่อซ้ำที่สร้างจากในฟอร์มบทความด้วย **แต่ไม่นับแท็กที่ถูกลบไปแล้ว** (`article_tag_info.deleted_at`
ไม่เป็น null) — เงื่อนไข unique เพิ่ม `whereIn('id', ...)` กรองเฉพาะ id ที่ยังอยู่ใน `article_tag_info` (ไม่ถูกลบ)
เพราะ `destroy()` soft delete แค่แถว `article_tag_info` เท่านั้น ไม่ได้ลบ `article_tag_detail` ตามไปด้วย (ต่างจาก
`article_item_part` ที่ resync ทั้งชุดทุกครั้ง) จึงต้องเช็กผ่านสถานะของพาเรนต์แทนที่จะเช็ก `deleted_at` ของตัวเอง
ผลคือ ลบแท็กแล้วสร้างชื่อเดิมใหม่ได้ตามที่ควรจะเป็น

**เลือก/สร้างแท็กด่วนจากในฟอร์มบทความ** — แยกจาก CRUD หลักข้างต้น ใช้เฉพาะจาก
`Components/Admin/ArticleTag/TagPicker.vue`: ไม่มีรายการมาให้ล่วงหน้า พิมพ์ค้นหาจากชื่อภาษาหลักแบบ autocomplete
(ajax `admin.article.tag.search`) เลือกจากผลลัพธ์ = ใช้แท็กเดิม แสดงเป็นกล่องข้อความ (chip) ลบออกได้; พิมพ์แล้วกด
"เพิ่ม" โดยไม่เลือก — ถ้ามีชื่อตรงกับแท็กที่มีอยู่แล้วก็ใช้ตัวนั้นเหมือนกัน แต่ถ้ายังไม่มีจะเปิด
`NewTagDialog.vue` ให้กรอกชื่อแท็กใหม่ครบทุกภาษาก่อนสร้างจริง (ajax `admin.article.tag.quickStore`,
สร้างทันทีไม่รอบันทึกฟอร์มบทความ — สร้าง `slug` อัตโนมัติจากชื่อเพราะไม่มีฟอร์มให้กรอกเอง)
— ทั้งสอง endpoint (`search`/`quickStore`) อยู่ใน `Admin\Article\ArticleTagController` เช่นกัน แต่ตรวจสิทธิ์
`article.item.manage` แทน เพราะเป็นการกระทำที่เกิดขึ้นจากในฟอร์มบทความเท่านั้น

`search()` คืนทั้งแท็กที่ใช้งานและไม่ใช้งาน (ไม่กรอง `status` แล้ว) พร้อมส่งคอลัมน์ `status` ติดไปกับผลลัพธ์แต่ละ
รายการ — `TagPicker.vue` ใช้ค่านี้แสดงทั้งตัวเลือกใน autocomplete และกล่องข้อความ (chip) ที่เลือกไว้แล้วเป็น
โทนสีเทาพร้อมข้อความ "(ไม่ใช้งาน)" เมื่อแท็กนั้น `status = 'N'` — ยังเลือก/ผูกกับบทความได้ตามปกติ แค่ทำให้รู้ว่า
แท็กนี้ไม่ใช้งานอยู่ (`attachedTagChips()` ใน `ArticleItemController` ก็ส่ง `status` ไปด้วยเพื่อให้หน้าแก้ไข
บทความแสดงกล่องแท็กเดิมที่ไม่ใช้งานเป็นสีเทาเช่นกัน)

## 3. ตั้งค่าโมดูลบทความ

**วัตถุประสงค์** — ตั้งค่าที่มีผลกับโมดูลบทความ เก็บใน `sys_setting` กลุ่ม `article` (เทียบเคียงกลุ่มตั้งค่าอื่น
ของตั้งค่าระบบ) แยกกลุ่มย่อยตามหัวข้อได้เหมือนตั้งค่าระบบ แต่ **รวมทุกกลุ่มไว้ในฟอร์มเดียว ปุ่มบันทึกอยู่นอกกล่อง
กลุ่มทั้งหมด** (เหมือนฟอร์มเพิ่ม/แก้ไขบทความทั่วไป) — ไม่แยกฟอร์ม/ปุ่มบันทึกต่อกลุ่มแบบตั้งค่าระบบ

**Permission code** — `article.setting.manage` ตัวเดียว ใช้ทั้งตรวจสอบสิทธิ์เข้าหน้าและบันทึก (ไม่มี `.view` แยก)

**ทะเบียนคีย์ที่เดียว** — `App\Support\ArticleSetting` (`defaults()` / `rules()` / `all()` = ค่าที่บันทึกไว้ทับค่าเริ่มต้น,
ค่าที่ไม่ผ่าน rules ใช้ค่าเริ่มต้น / `listSetting()` + `detailSetting()` = รูปแบบที่หน้าบ้านใช้) — `UpdateArticleSettingRequest`,
`ArticleSettingController@index` และ `ArticleSeeder` อ่านจากที่นี่ทั้งหมด; ตัวเลือกฝั่งหน้าจออยู่ `resources/js/utils/articleSetting.ts`
(ค่าต้องตรงกัน). เพิ่มคีย์ใหม่ = `defaults()` + `rules()` + ฟอร์ม `Setting/Index.vue`
(controller ลบทั้งกลุ่มแล้ว insert ใหม่ คีย์ที่ไม่อยู่ใน rules จะหาย)

**กลุ่ม "รายการบทความ"** (ใช้ทั้งหน้ารายการของหมวดหมู่และของแท็ก):

| ชื่อ (sys_setting.name) | ชนิด | ค่าเริ่มต้น | หมายเหตุ |
|--------------------------|------|-------------|----------|
| `list_show_category_intro` | `Y` \| `N` | `N` | แสดงข้อความเกริ่นนำของหมวดหมู่ (ปิด = server ไม่ส่งค่าไปหน้าบ้าน) |
| `list_show_category_detail` | `Y` \| `N` | `N` | แสดงรายละเอียด (rich text) ของหมวดหมู่ |
| `list_per_page` | integer (1–100) | `10` | จำนวนรายการบทความที่แสดงต่อหน้า (หน้าบ้าน) |
| `list_display_mode` | `card` \| `row` | `card` | รูปแบบการแสดงผลตั้งต้น — การ์ด/แถว (ผู้ชมสลับได้) |
| `list_default_sort` | `newest` \| `oldest` \| `title_asc` \| `title_desc` \| `views_desc` \| `views_asc` | `newest` | การเรียงลำดับตั้งต้น (ผู้ชมเปลี่ยนได้) |
| `list_show_date` / `list_show_views` | `Y` \| `N` | `Y` | แสดงวันที่เผยแพร่ / จำนวนเข้าชม ในแต่ละรายการ |
| `card_aspect_ratio` / `row_aspect_ratio` | `16:9` \| `21:9` \| `4:3` \| `1:1` (แถวเพิ่ม `natural` = ตามขนาดของรูป) | `16:9` | อัตราส่วนรูปภาพ — `natural` ไม่ครอป ไม่มีประเภทการแสดงรูป/สีพื้นหลัง |
| `card_image_fit` / `row_image_fit` | `cover` \| `contain` | `cover` | ประเภทการแสดงรูปภาพ |
| `card_image_background` / `row_image_background` | สี hex / `transparent` | `#F3F4F6` | สีพื้นหลังของรูป (แสดงในฟอร์มเฉพาะเมื่อ contain) |
| `card_title_lines` / `row_title_lines` | 1–3 | `1` | จำนวนบรรทัดที่แสดงหัวเรื่อง |
| `card_intro_lines` / `row_intro_lines` | 1–3 | `2` | จำนวนบรรทัดที่แสดงข้อความเกริ่นนำ |

**กลุ่ม "รายละเอียดบทความ"** (อยู่ใน group `article` เดียวกัน):

| ชื่อ (sys_setting.name) | ชนิด | ค่าเริ่มต้น | หมายเหตุ |
|--------------------------|------|-------------|----------|
| `detail_show_cover` | `Y` \| `N` | `N` | แสดงรูปภาพหน้าปกก่อนเนื้อหา |
| `detail_show_print` | `Y` \| `N` | `N` | แสดงปุ่มพิมพ์ (ต่อจากวันที่เผยแพร่ + จำนวนเข้าชม) |
| `detail_share_position` | `top` \| `bottom` \| `both` \| `none` | `bottom` | ตำแหน่งปุ่มแชร์ Facebook / X / LINE / คัดลอกลิงก์ |

หน้าฟอร์ม: แสดง/ซ่อน = checkbox (`YesNoCheckbox`), การเรียงลำดับ = `SearchableSelect`, รูปแบบตั้งต้น/ตำแหน่งแชร์ = `SegmentedChoice`,
กลุ่มย่อย "ส่วนหัวของหมวดหมู่" / "รายการ" / "การแสดงแบบการ์ด" / "การแสดงแบบแถว" เป็นหัวข้อ h3 ในกล่องเดียวกัน

**Controller** — `Admin\Article\ArticleSettingController` (`index`/`update`/`clearcache`/`clearCacheSetting`/
`clearCacheAll`) log action module_code = `article.setting` (บันทึก) / `article.setting.cache` (ล้างแคช)

**Route** — `admin.article.setting.{index,update,clearcache,clearcache.setting,clearcache.all}`

**หน้าจอ** — `Pages/Admin/Article/Setting/{Index,ClearCache}.vue` มี `TabNav` สลับ "ตั้งค่า"/"ล้างแคช" เหมือนหน้า
ตั้งค่าระบบ หน้าล้างแคชแสดงรายการแคชของโมดูลนี้ทีละอัน (ตอนนี้มีแค่ "ตั้งค่าบทความ" — อนาคตถ้าเพิ่มแคชอื่นของ
โมดูลนี้ เช่น รายละเอียดบทความ/รายการบทความที่ใช้ในหน้าบ้าน ให้เพิ่มแถวในนี้ + endpoint ล้างแคชของตัวเองด้วย)
พร้อมปุ่ม "ล้างแคชทั้งหมดของบทความ"

**Seeder** — ค่าเริ่มต้นเพิ่มใน `ArticleSeeder.php` (กลุ่ม `article` ใน `sys_setting`)

**การลงทะเบียนแคชร่วมกับตั้งค่าระบบ** — เพิ่ม `'article'` เข้า `App\Support\Setting::GROUPS` (ทะเบียนกลุ่ม
`sys_setting` ทั้งหมดที่มีแคช ไม่ใช่แค่ของตั้งค่าระบบ) ทำให้:
- หน้า "ล้างแคช" ของตั้งค่าระบบ (`admin.system.setting.clearcache`) มีปุ่มล้างแคชกลุ่ม `article` เพิ่มมาด้วย
- ปุ่ม "ล้างแคชทั้งหมด" ของตั้งค่าระบบ (`Setting::forgetAll()` วนทุกกลุ่มใน `GROUPS`) ครอบคลุมกลุ่ม `article` ไปด้วยอัตโนมัติ
- แต่หน้าฟอร์มตั้งค่าระบบเอง (`Admin\System\SettingController::index()`) ยังคง**ไม่**แสดงฟอร์มของกลุ่ม `article`
  (แยก const `OWN_GROUPS` ออกจาก `Setting::GROUPS` โดยเฉพาะ เพื่อไม่ให้กลุ่มของโมดูลอื่นที่มาลงทะเบียนร่วม
  ทะเบียนเดียวกันหลุดเข้าไปในหน้าฟอร์มของตั้งค่าระบบ)

> **บั๊กที่แก้ไปพร้อมกัน (ประวัติ อย่าทำซ้ำ)** — seeder เดิม (`DatabaseSeeder.php`/`ArticleSeeder.php`) ใช้
> `SysSetting::updateOrCreate(...)` (Eloquent) เพื่อ seed ค่าตั้งค่า แต่ `sys_setting` มี primary key แบบ
> composite (`group`,`name`) ไม่มีคอลัมน์ `id` ของตัวเอง — Eloquent ที่ไม่รู้จัก key นี้ (ไม่ได้ override
> `$primaryKey`) จะพังด้วย query `WHERE id = ...` ทันทีที่ต้อง **update** แถวที่มีอยู่แล้วจริง ๆ (ค่าต่างจากเดิม)
> ตอน insert ใหม่ไม่มีปัญหาเพราะไม่ต้องมี WHERE จึงไม่เคยเจอบั๊กนี้ตอนเทส (sqlite `:memory:` สร้างใหม่ทุกครั้ง)
> แก้โดยเปลี่ยนเป็น `DB::table('sys_setting')->upsert($rows, ['group','name'], ['value','updated_at'])`
> (query builder ตรง ๆ ไม่ผ่าน Eloquent) ทั้งสองไฟล์ — มีเทส regression ทั้งฝั่ง `site`
> (`SiteSettingTest.php`) และ `article` (`ArticleSettingManagementTest.php`)

## 4. รายงานการเข้าชม (branch `article-edit-report`)

**ข้อมูลต้นทาง** — `article_item_view` (1 แถว = 1 การเข้าชมที่ `ViewCounter` นับ — ข้ามบอท + dedupe ต่อ session แล้ว).
migration `2026_10_07_000001_*` เพิ่ม `browser`/`platform`/`device_type`/`referrer` ให้ `article_item_view`/`page_item_view`/`banner_item_click`
(ทั้งสามตารางเขียนผ่าน `ViewCounter::write()` ตัวเดียวกัน) — แถวก่อนหน้านี้เป็น null ("ไม่ทราบ" / "เข้าตรง")

**ตัวคำนวณ** — `App\Support\Report\ViewReport` (กลาง รับชื่อตาราง/FK + scope → page/banner ใช้ต่อได้) และ `App\Support\Report\ArticleReport`
(ชื่อบทความ/หมวดหมู่ภาษาหลัก, top, ตามหมวดหมู่):
- ตัวกรอง `date_from`/`date_to` (ค่าเริ่มต้น 30 วันล่าสุด, สลับให้ถ้ากลับด้าน, ไม่เกิน 5 ปี) กรองที่ `action_date`;
  `period` = `day`/`week`/`month`/`year` (ไม่ส่ง = เลือกตามความยาวช่วง: ≤62 วัน รายวัน, ≤190 รายสัปดาห์, ≤1100 รายเดือน, เกินนั้นรายปี)
- จัดกลุ่มตามช่วงใน SQL (นิพจน์แยก MySQL / SQLite สำหรับเทส) สัปดาห์เริ่มวันจันทร์ — เติมช่วงที่ไม่มีข้อมูลเป็น 0, ป้ายชื่อไทย ปี พ.ศ.
- ตัวเลขต่อช่วง: ยอดเข้าชม, ผู้เข้าชมไม่ซ้ำ (distinct `session_id`), IP ไม่ซ้ำ; สรุป: ยอดรวม/ไม่ซ้ำ/เฉลี่ยต่อวัน/วันที่มีผู้เข้าชม/ช่วงสูงสุด
  + % เปลี่ยนแปลงเทียบช่วงก่อนหน้าที่ยาวเท่ากัน
- แยกตามภาษา/อุปกรณ์/เบราว์เซอร์/ระบบปฏิบัติการ, แหล่งที่มาจาก host ของ referrer (เข้าตรง / ภายในเว็บ / เครื่องมือค้นหา / โซเชียล / เว็บอื่น),
  heatmap วันในสัปดาห์ × ชั่วโมง (จาก `created_at`)
- ส่งออก CSV (UTF-8 + BOM ให้ Excel อ่านไทยได้) ตามตัวกรองเดียวกับหน้าจอ

**รายงานรายบทความ** — `admin.article.item.report` (`/admin/article/item/{item}/report`) + `.report.export`, สิทธิ์ `article.item.view`,
log module `article.item.report` (`view` เฉพาะเข้าหน้าครั้งแรกที่ไม่มี query, `export` ทุกครั้ง). เข้าได้จากคอลัมน์สุดท้ายของหน้ารายการบทความ
และแท็บ "รายงาน" ของหน้าแก้ไข. หน้า `Pages/Admin/Article/Item/Report.vue`

**เมนูรายงาน** (sidebar กลุ่มบทความ, `sys_menu` `article-report` ไอคอน `ChartColumn`) — สิทธิ์ `article.report.view` ทุกหน้า
(`ArticleReportController`, หน้า `Pages/Admin/Article/Report/*`, โครงหน้า `Components/Admin/ArticleReport/ReportShell.vue`):

| route | แท็บ | เนื้อหา | log module_code |
|---|---|---|---|
| `admin.article.report.index` | รายการเข้าชม | ชื่อบทความ / IP / วันเวลาที่เข้าชม — ค้นหา (ชื่อ/IP) + ช่วงวันที่ + paging + เรียง, ไม่มี dialog; ส่งออกข้อมูลดิบ (สูงสุด 100,000 แถว) | access เท่านั้น (`article.report.index` เมื่อส่งออก) |
| `.overview` | ภาพรวม | เหมือนรายงานรายบทความแต่รวมทุกบทความ + กรองหมวดหมู่ + 5 อันดับแรก | `article.report.overview` |
| `.top` | บทความยอดนิยม | 20 อันดับในช่วงวันที่ (กราฟแท่งแนวนอน + ตาราง %) | `article.report.top` |
| `.category` | ตามหมวดหมู่ | ยอดต่อหมวดหมู่ + แนวโน้ม 5 หมวดหมู่ยอดนิยม | `article.report.category` |
| `.audience` | ผู้เข้าชมและแหล่งที่มา | ภาษา/อุปกรณ์/เบราว์เซอร์/OS/แหล่งที่มา + เว็บไซต์ภายนอกที่อ้างอิงมา | `article.report.audience` |
| `.time` | ช่วงเวลา | heatmap วัน × ชั่วโมง + ยอดตามวันในสัปดาห์/ชั่วโมง | `article.report.time` |
| `.export` | — | CSV ของแท็บ `?tab=` | `article.report.<tab>` action `export` |

ตัวกรองช่วงวันที่ส่งต่อกันระหว่างแท็บรายงาน; ชนิดกราฟ (แท่ง/เส้น) จำใน localStorage (`admin.report.chartType`) ไม่ส่งไป server.
component กลาง `Components/Admin/Report/*` (`ReportFilterBar`, `ReportDashboard`, `ViewTrendChart` = Chart.js ผ่าน `vue-chartjs`,
`ReportStatCards`, `ReportSeriesTable`, `BreakdownList`, `AudienceBreakdowns`, `WeekHourHeatmap`) + `resources/js/utils/report.ts`

---

## Roadmap

| รอบ | ขอบเขต | สถานะ |
|-----|--------|-------|
| 0 — schema หมวดหมู่ | `article_category_info`/`article_category_detail` + `ArticleSeeder` ตัวอย่าง | ✅ เสร็จ |
| 1 — CRUD หมวดหมู่ | controller/route/หน้า Vue list+form ตามต้นแบบ `system.user` (ดู `docs/PRD-system.md` §1) | ✅ เสร็จ |
| 2 — schema บทความ + content part + แท็ก | `article_item_*` + ตาราง part (ข้อความ/รูปภาพ/วิดีโอ/เอกสาร) + `article_tag_*` + `ArticleSeeder` ตัวอย่าง | ✅ เสร็จ |
| 3 — CRUD บทความ | controller/route/หน้า Vue list+add+edit พร้อม part editor (ลากสลับลำดับรูป/เอกสารในกลุ่มตรง ๆ, สลับลำดับ part ผ่าน dialog), `TagPicker.vue` เลือก/สร้างแท็กแบบ autocomplete, แสดง/ซ่อน part + ตัวเลือกแสดงหัวเรื่อง | ✅ เสร็จ |
| 4 — ตั้งค่าโมดูลบทความ | หน้า `admin.article.setting.index` + ล้างแคช (`sys_setting` group `article`) + ลงทะเบียนร่วมกับล้างแคชของตั้งค่าระบบ | ✅ เสร็จ |
| 5 — ปรับปรุงบทความ (branch `article-edit`) | จัดฟอร์มหมวดหมู่/บทความใหม่เป็นการ์ดตามลำดับ (component ร่วม `Components/Admin/ArticleForm/*`), ตั้งค่ารายการ/รายละเอียดเพิ่ม, หน้าบ้าน: ค้นหา + เรียงลำดับ, รายละเอียดเรียงใหม่ + พิมพ์ + แชร์ + แท็กเป็นลิงก์, หน้ารายการตามแท็ก | ✅ เสร็จ |
| **6 — รายงานการเข้าชม (branch `article-edit-report`)** *(รอบนี้)* | รายงานรายบทความ + เมนูรายงานภาพรวม 6 แท็บ, คอลัมน์ client ในตารางประวัติการเข้าชม, ส่งออก CSV (ดู §4) | ✅ เสร็จ |

## หน้าบ้าน (สรุป — รายละเอียดเต็มดู [PRD-front.md](PRD-front.md) §5, §8)

- หมวดหมู่: `/{lang}/article/category/{id}/{slug?}` (`front.article.category`) — h1 ชื่อหมวดหมู่, การ์ด/แถวตาม `list_display_mode`
  (ผู้ชมสลับได้ `?view=`), จำนวนต่อหน้าตาม `list_per_page`, ค้นหาจากชื่อ `?q=`, เรียงลำดับ `?sort=` (ตั้งต้นตาม `list_default_sort`)
- แท็ก: `/{lang}/article/tag/{tag}` (`front.article.tag`, `{tag}` = ชื่อแท็กภาษาใดก็ได้) — ตั้งค่าชุดเดียวกับรายการของหมวดหมู่
  ไม่มีเกริ่นนำ/รายละเอียด/ช่องค้นหา, ไม่พบ = "ไม่พบข้อมูล" (200)
- รายละเอียด: `/{lang}/article/category/{id}/{article_id}/{slug?}` (`front.article.category.item` — บทความต้องอยู่ในหมวดหมู่นั้น) และ
  `/{lang}/article/item/{id}/{slug?}` (`front.article.item`) — h1 ชื่อบทความ, หัวข้อ part h2, canonical ของทั้งสองทางชี้ URL เดียวกัน
- slug หมวดหมู่ห้ามเป็นตัวเลขล้วน/มี `/` (validation `not_regex` ใน Store/UpdateArticleCategoryRequest) — กันชนกับ `{article_id}`
- ยอดเข้าชม: ตาราง `article_item_view` (แทนตารางระบบเดิมที่ insert → count → update ทุกครั้ง) + บวก `article_item_info.view_amount` แบบ batch
  ผ่านคิว Redis + `php artisan front:flush-views` (ดู PRD-front.md §8)
