# PRD — โมดูลบทความ (Article)

เอกสารนี้ลงรายละเอียดของโมดูล **บทความ** (หมวดหมู่ + บทความ + ตั้งค่า) ในหลังบ้าน ภาพรวมทั้งระบบดูที่
[PRD-overview.md](PRD-overview.md) §3

สถานะ: 🟢 มีแล้ว · 🟡 มีบางส่วน · 🔴 ยังไม่มี

| # | หัวข้อ | ตารางหลัก | สถานะ |
|---|--------|-----------|-------|
| 1 | หมวดหมู่บทความ | `article_category_info`, `article_category_detail` | 🟡 schema + seeder ตัวอย่างมี, ยังไม่มี controller/route/UI |
| 2 | บทความ | `article_item_*` *(เสนอ — ยังไม่ออกแบบ)* | 🔴 |
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

### เนื้อหาบทความแบบแบ่ง part (เสนอ — ยังไม่ทำรอบนี้)

เนื้อหาบทความ (ต่างจากหมวดหมู่ ซึ่งมีแค่ฟิลด์ `detail` เดียว) ประกอบด้วย **หลาย part เรียงลำดับได้**
(ลากสลับลำดับ, เริ่มต้นด้วย part ข้อความ 1 อันเสมอ) part แต่ละแบบ:

| ประเภท part | รายละเอียด |
|-------------|-----------|
| ข้อความ | text editor (rich text) — **ไม่รองรับแทรกรูปภาพ/วิดีโอ/media** ในตัวข้อความ (ให้ใช้ part ประเภทอื่นแทน) |
| รูปภาพ | เดี่ยว หรือ กลุ่ม (เลือกรูปแบบจัดวาง: สไลด์ หรือ art layout) |
| วิดีโอ | เดี่ยว + ตั้งค่ารูปภาพหน้าปก (thumbnail) |
| เอกสาร | เดี่ยว หรือ กลุ่ม — ถ้าเป็น PDF ให้มี preview แสดงในหน้า |

โครงสร้างตารางสำหรับ part เหล่านี้ (เช่น `article_item_part`, `article_item_part_file` หรือเทียบเท่า) **ยังไม่ออกแบบ
ในรอบนี้** — จะออกแบบพร้อมกับ schema ของ `article_item_*` ในรอบถัดไป โดยจะอิง `file_info`/`folder_info`
(โมดูลจัดการไฟล์ที่มีอยู่แล้ว) เป็นที่เก็บไฟล์จริง

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

**หน้าจอ** (ยังไม่ทำ — รอบถัดไป)
- list หมวดหมู่ (ค้นหา/กรองสถานะ, เรียงลำดับ/ลากสลับ `sort_order`)
- form เพิ่ม/แก้ไข ตามรูปแบบ "กลุ่มข้อมูลร่วม" + "กลุ่มข้อมูลแยกภาษา" (§0)

**Permission code** (seed ไว้แล้วใน `DatabaseSeeder.php`) — `article.category.view`, `article.category.manage`,
`article.category.delete`

**Route** (ผูกไว้ใน `MenuSeeder.php` แล้ว แต่ **ยังไม่มี route จริงรองรับ**) — `admin.article.category.index`
(ตาม convention URL เอกพจน์ `/admin/article/category` — ดู `docs/PRD-overview.md` §5.1)

---

## 2. บทความ (เสนอ — ยังไม่ออกแบบ schema)

**วัตถุประสงค์** — เนื้อหาข่าว/บทความ ผูกกับหมวดหมู่เดียว รองรับ SEO/AEO/GEO และเนื้อหาแบบแบ่ง part (§0)

**Permission code** (seed ไว้แล้ว) — `article.item.view`, `article.item.manage`, `article.item.delete`

**Route** (ผูกไว้ใน `MenuSeeder.php`) — `admin.article.item.index`

**ยังไม่ทำ**: schema `article_item_info`/`article_item_detail` (เทียบเคียงหมวดหมู่ + เพิ่ม `category_id` FK →
`article_category_info`, สถานะเผยแพร่, วันที่เผยแพร่) และ schema ของ content part (§0)

## 3. ตั้งค่าโมดูลบทความ (เสนอ — ยังไม่ออกแบบ)

**Permission code** (seed ไว้แล้ว) — `article.setting.manage`

**Route** (ผูกไว้ใน `MenuSeeder.php`) — `admin.article.setting.index`

---

## Roadmap

| รอบ | ขอบเขต |
|-----|--------|
| **0 — schema หมวดหมู่** *(รอบนี้)* | `article_category_info`/`article_category_detail` + `ArticleSeeder` ตัวอย่าง |
| 1 — CRUD หมวดหมู่ | controller/route/หน้า Vue list+form ตามต้นแบบ `system.user` (ดู `docs/PRD-system.md` §1) |
| 2 — schema บทความ + content part | ออกแบบ `article_item_*` + ตาราง part (ข้อความ/รูปภาพ/วิดีโอ/เอกสาร) |
| 3 — CRUD บทความ | controller/route/หน้า Vue พร้อม part editor ลากสลับลำดับ |
| 4 — ตั้งค่าโมดูลบทความ | หน้า `admin.article.setting.index` |
