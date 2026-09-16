# PRD — โมดูลบทความ (Article)

เอกสารนี้ลงรายละเอียดของโมดูล **บทความ** (หมวดหมู่ + บทความ + ตั้งค่า) ในหลังบ้าน ภาพรวมทั้งระบบดูที่
[PRD-overview.md](PRD-overview.md) §3

สถานะ: 🟢 มีแล้ว · 🟡 มีบางส่วน · 🔴 ยังไม่มี

| # | หัวข้อ | ตารางหลัก | สถานะ |
|---|--------|-----------|-------|
| 1 | หมวดหมู่บทความ | `article_category_info`, `article_category_detail` | 🟢 schema + controller/route/UI (list, add, edit) เสร็จครบ |
| 2 | บทความ | `article_item_info`, `article_item_detail`, `article_item_part`, `article_item_part_file`, `article_item_part_detail` | 🟡 schema + model + seeder ตัวอย่างเสร็จ ยังไม่มี controller/route/UI |
| 2.1 | แท็กบทความ | `article_tag_info`, `article_tag_detail`, `article_item_tag` (pivot) | 🟡 schema + model + seeder ตัวอย่างเสร็จ ยังไม่มี UI จัดการ |
| 3 | ตั้งค่าโมดูลบทความ | — *(เสนอ)* | 🔴 |

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

โครงสร้างตาราง (`article_item_part`, `article_item_part_file`, `article_item_part_detail`) อิง `file_info`
(โมดูลจัดการไฟล์ที่มีอยู่แล้ว) เป็นที่เก็บไฟล์จริง ผ่าน `FilePickerField.vue`/`FilePickerDialog.vue` — ดูรายละเอียด
เต็มที่ §2

---

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
| `setting` | `json` null | ตั้งค่าเพิ่มเติมตามประเภท เช่น เปิด/ปิด pdf preview — cast เป็น `array` ใน model |
| `created_by` / `updated_by` / `deleted_by`, `timestamps`, `deleted_at` | | ไม่มีคอลัมน์ `status` แยก (ลบแถวใช้ soft delete แทนการปิด/เปิด) |

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

**หน้าจอ** (ยังไม่ทำ — รอบถัดไป)
- list บทความ (ค้นหา/กรองสถานะ+หมวดหมู่+แท็ก, เรียงลำดับ/paging)
- form เพิ่ม/แก้ไข ตามรูปแบบ "กลุ่มข้อมูลร่วม" + "กลุ่มข้อมูลแยกภาษา" (§0) + part editor ลากสลับลำดับ
  (ต้องเพิ่ม drag-reorder library เช่น `vuedraggable@next` — ยังไม่มี pattern นี้ในโปรเจกต์) ใช้
  `FilePickerField.vue`/`FilePickerDialog.vue` ของโมดูลจัดการไฟล์สำหรับเลือกรูป/เอกสาร/วิดีโอ

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
| `name` | `varchar(100)` null | ชื่อแท็ก |
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

**Seeder** — สร้างแท็กตัวอย่าง 4 แท็ก (ประชาสัมพันธ์/กิจกรรม/ความรู้/อัปเดต) ใน `ArticleSeeder.php`

**หน้าจอ** — ยังไม่มีหน้าจัดการแท็กแยก (รอบถัดไปจะพิจารณาว่าจัดการผ่านหน้าบทความ หรือแยกหน้า list/form ต่างหาก)

## 3. ตั้งค่าโมดูลบทความ (เสนอ — ยังไม่ออกแบบ)

**Permission code** (seed ไว้แล้ว) — `article.setting.manage`

**Route** (ผูกไว้ใน `MenuSeeder.php`) — `admin.article.setting.index`

---

## Roadmap

| รอบ | ขอบเขต | สถานะ |
|-----|--------|-------|
| 0 — schema หมวดหมู่ | `article_category_info`/`article_category_detail` + `ArticleSeeder` ตัวอย่าง | ✅ เสร็จ |
| 1 — CRUD หมวดหมู่ | controller/route/หน้า Vue list+form ตามต้นแบบ `system.user` (ดู `docs/PRD-system.md` §1) | ✅ เสร็จ |
| **2 — schema บทความ + content part + แท็ก** *(รอบนี้)* | `article_item_*` + ตาราง part (ข้อความ/รูปภาพ/วิดีโอ/เอกสาร) + `article_tag_*` + `ArticleSeeder` ตัวอย่าง | ✅ เสร็จ |
| 3 — CRUD บทความ | controller/route/หน้า Vue พร้อม part editor ลากสลับลำดับ (ต้องเพิ่ม drag-reorder library), ใช้ `FilePickerField.vue` เลือกไฟล์ | 🔴 ยังไม่ทำ |
| 4 — ตั้งค่าโมดูลบทความ | หน้า `admin.article.setting.index` | 🔴 ยังไม่ทำ |
