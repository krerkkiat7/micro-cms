# PRD — โมดูลป้ายโฆษณา (Banner)

เอกสารนี้ลงรายละเอียดของโมดูล **ป้ายโฆษณา** (หมวดหมู่ + ป้ายโฆษณา + ตั้งค่า) ในหลังบ้าน ภาพรวมทั้งระบบดูที่
[PRD-overview.md](PRD-overview.md) §3

สถานะ: 🟢 มีแล้ว · 🟡 มีบางส่วน · 🔴 ยังไม่มี

| # | หัวข้อ | ตารางหลัก | สถานะ |
|---|--------|-----------|-------|
| 1 | หมวดหมู่ป้ายโฆษณา | `banner_category_info`, `banner_category_detail` | 🟢 schema + controller/route/UI (list, add, edit) เสร็จครบ |
| 2 | ป้ายโฆษณา | `banner_item_info`, `banner_item_detail` | 🟢 schema + controller/route/UI (list, add, edit) เสร็จครบ |
| 3 | ตั้งค่าโมดูลป้ายโฆษณา | `sys_setting` (group = `banner`, ยังไม่มีแถวข้อมูล) | 🟢 หน้าล้างแคช (ยังไม่มีฟิลด์ตั้งค่าจริง) |

---

## 0. ภาพรวมโมดูล

- **หมวดหมู่ในโมดูลนี้ทำหน้าที่เป็น "ตำแหน่งที่ใช้แสดงผล"** (เช่น ไฮไลท์, หน่วยงานที่เกี่ยวข้อง, อื่น ๆ) ไม่ใช่
  หมวดหมู่เนื้อหาแบบบทความ — **หมวดหมู่มีระดับเดียว** (ไม่มีหมวดหมู่ย่อย) และ **1 ป้ายโฆษณามีได้เพียง 1 หมวดหมู่**
  ออกแบบเพื่อความง่ายต่อการใช้งาน
- ป้ายโฆษณาแสดงผลเป็น **รูปภาพเท่านั้น** พร้อม **ลิงก์ที่กดไปได้** (เป้าหมายเปิดหน้าต่างเลือกได้ `_self`/`_blank`)
  และ**ช่วงเวลาเผยแพร่** (วันที่เผยแพร่/วันที่ปิดการเผยแพร่)
- **ต่างจากโมดูลบทความอย่างชัดเจน**: ไม่มี SEO/slug/meta/og ใด ๆ (หมวดหมู่และป้ายโฆษณาในที่นี้ไม่ใช่หน้าเนื้อหาที่
  ต้องการ SEO), ไม่มีเนื้อหาแบบ rich text/แบ่ง part, ไม่มีระบบแท็ก, **รูปภาพใช้ร่วมกันทุกภาษา** (ไม่แยกรูปตามภาษา
  เหมือนบทความ), หมวดหมู่ไม่มีรูปภาพหน้าปกหรือลำดับการแสดงผลของตัวเอง (มีเฉพาะที่ตัวป้ายโฆษณา)
- รองรับ**หลายภาษาตามที่ระบบตั้งค่าไว้** เหมือนโมดูลอื่น — อ่านรายการภาษาที่เปิดใช้จาก `sys_setting`
  (`site.lang_selected`/`site.lang_default` ผ่าน `App\Support\Setting::selectedLanguages()`/`defaultLanguage()`)
  ฟิลด์ที่แยกตามภาษา (ชื่อ/ข้อความเกริ่นนำ) **required เฉพาะภาษาหลัก** ภาษาอื่นกรอกหรือไม่ก็ได้ — บังคับที่
  FormRequest ตอนบันทึก ไม่ใช่ที่ระดับ schema (คอลัมน์ทุกตัวเป็น nullable)
- **`click_amount`** บนป้ายโฆษณาเก็บไว้สำหรับนับจำนวนคลิกลิงก์ **ในอนาคต** — มีคอลัมน์/อยู่ใน model แล้ว แต่
  **ยังไม่มี UI แสดงหรือแก้ไข** ทั้งในฟอร์มเพิ่ม/แก้ไขและหน้ารายการ (เทียบเคียง `article_item_info.view_amount`
  ที่ก็ยังไม่แสดงในฟอร์มเช่นกัน)

### รูปแบบการกรอกข้อมูล (ทั้งหมวดหมู่และป้ายโฆษณา)

ฟอร์มแบ่งข้อมูลเป็น 2 กลุ่มเสมอ (เทียบเคียงโมดูลบทความ):

- **กลุ่มข้อมูลร่วม** — ไม่แยกภาษา เช่น หมวดหมู่ (ของป้ายโฆษณา), รูปภาพ, ลิงก์, เป้าหมายลิงก์, ช่วงเวลาเผยแพร่,
  ลำดับ, สถานะ
- **กลุ่มข้อมูลแยกภาษา** — กรอกพร้อมกันทุกภาษาที่ระบบเปิดใช้ในหน้าเดียว ผ่าน `LangFieldGroup.vue` — มีแค่ชื่อ
  กับข้อความเกริ่นนำ (ไม่มี rich text/SEO เหมือนบทความ)

---

## 1. หมวดหมู่ป้ายโฆษณา

**วัตถุประสงค์** — จัดกลุ่มป้ายโฆษณาตามตำแหน่งที่ใช้แสดงผล 1 ระดับ (ไม่มีหมวดหมู่ย่อย)

**Data model — `banner_category_info`** (ข้อมูลร่วม ไม่แยกภาษา)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK autoincrement | |
| `status` | `char(1)` default `Y` | `Y` = เปิดใช้งาน, `N` = ปิด |
| `is_temp` | `char(1)` default `N` | `Y` = ข้อมูลตัวอย่าง/ตั้งต้น (เผื่อทำระบบลบข้อมูลตัวอย่างออกภายหลัง — จะใช้ต่อก็ได้) |
| `created_by` / `updated_by` / `deleted_by` | bigint null (`sys_user.id`, ไม่มี FK) | ผู้สร้าง / ผู้แก้ไขล่าสุด / ผู้ลบ |
| `timestamps`, `deleted_at` | | `SoftDeletes` |

**ไม่มี** `intro_image_id`/`sort_order` — ต่างจาก `article_category_info` เพราะหมวดหมู่ที่นี่เป็นแค่ตำแหน่ง
แสดงผล ไม่ใช่หน้าเนื้อหา (ผู้ใช้ระบุโครงสร้างเดิมมาไม่มีสองฟิลด์นี้)

**Data model — `banner_category_detail`** (ข้อมูลแยกตามภาษา — PK = `id` + `lang`)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `unsigned bigint` | = `banner_category_info.id` (FK, cascadeOnDelete) — ส่วนหนึ่งของ PK |
| `lang` | `char(2)` | เช่น `th`, `en` — ส่วนหนึ่งของ PK |
| `title` | `varchar(250)` null | ชื่อหมวดหมู่ — required เฉพาะ `lang_default` (เช็กที่ FormRequest) |
| `intro_text` | `varchar(1000)` null | คำอธิบายสั้น ๆ ของตำแหน่งที่ใช้แสดงผล |
| `status` | `char(1)` default `Y` | เปิด/ปิดการแสดงผลต่อภาษา |
| `created_by` / `updated_by` / `deleted_by` | bigint null (`sys_user.id`, ไม่มี FK) | |
| `timestamps`, `deleted_at` | | `SoftDeletes` |

**ไม่มี** `slug`/meta/og หรือฟิลด์ `detail` (rich text) — ต่างจาก `article_category_detail` เพราะไม่ใช่หน้า
เนื้อหาที่ต้องการ SEO

> Eloquent ไม่รองรับ composite primary key เต็มรูปแบบ — ค้นด้วย
> `BannerCategoryDetail::where('id', ...)->where('lang', ...)` เสมอ ห้ามใช้ `find()`

**Model**
- `App\Models\BannerCategoryInfo` — `SoftDeletes`; `details()` hasMany `BannerCategoryDetail` (ไม่มี `introImage()`)
- `App\Models\BannerCategoryDetail` — `SoftDeletes`, `$incrementing = false`; `category()` belongsTo `BannerCategoryInfo`

**Migration** — `database/migrations/2026_09_18_000001_create_banner_category_tables.php`

**Seeder** — `database/seeders/BannerSeeder.php` (เรียกจาก `DatabaseSeeder`) สร้างหมวดหมู่ตัวอย่าง 3 รายการ
(ไฮไลท์/Highlight, หน่วยงานที่เกี่ยวข้อง/Related Agencies, อื่น ๆ/Others) ทำเครื่องหมาย `is_temp = 'Y'` ทุกแถว
อ่านรายการภาษาจาก `Setting::selectedLanguages()` (fallback `th,en`) — รันซ้ำได้ (`updateOrCreate`, resolve แถวเดิม
จาก**ชื่อของภาษาหลัก** เพราะไม่มีคอลัมน์ `slug` ให้อ้างเหมือน `ArticleSeeder`)

**หน้าจอ** — เสร็จแล้ว (`Admin/Banner/Category/{Index,Add,Edit}.vue`)
- list หมวดหมู่ (ค้นหาชื่อ+ข้อความเกริ่นนำ, กรองสถานะ, เรียงชื่อ/สถานะ, แสดงจำนวนป้ายโฆษณาในหมวดหมู่, paging)
- form เพิ่ม/แก้ไข ตามรูปแบบ "กลุ่มข้อมูลร่วม" (สถานะอย่างเดียว) + "กลุ่มข้อมูลแยกภาษา" (§0) — ไม่มีรูปภาพ/ลำดับ/
  rich text/SEO

**Permission code** (seed ไว้แล้วใน `DatabaseSeeder.php`) — `banner.category.view`, `banner.category.manage`,
`banner.category.delete`

**Route** — `admin.banner.category.{index,add,store,edit,update,destroy}` (ผูกไว้ใน `MenuSeeder.php` และมี route
จริงรองรับครบแล้วใน `routes/web.php`, controller: `App\Http\Controllers\Admin\Banner\BannerCategoryController`)

---

## 2. ป้ายโฆษณา

**วัตถุประสงค์** — แสดงรูปภาพโปรโมชัน/ไฮไลต์/หน่วยงานที่เกี่ยวข้อง ผูกกับหมวดหมู่เดียว พร้อมลิงก์ที่กดไปได้

**Data model — `banner_item_info`** (ข้อมูลร่วม ไม่แยกภาษา)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK autoincrement | |
| `banner_category_info_id` | bigint FK → `banner_category_info.id` null (nullOnDelete) | หมวดหมู่ (ตำแหน่งที่ใช้แสดงผล) — **ยัง nullable ระดับ DB** (บังคับเลือกที่ FormRequest ตอนทำ CRUD) ชื่อคอลัมน์ตาม convention ของโปรเจกต์ (เต็มชื่อตาราง `_info` ที่อ้างถึง + `_id` — เทียบเคียง `article_item_info.article_category_info_id`) |
| `intro_image_id` | bigint FK → `file_info.id` null (nullOnDelete) | รูปภาพป้ายโฆษณา — **ใช้ร่วมกันทุกภาษา** (ต่างจากบทความที่ไม่มีรูปแยกภาษาอยู่แล้วเช่นกัน แต่ย้ำไว้เพราะ PRD-overview ฉบับร่างเดิมเข้าใจผิดว่าเป็นรูปต่อภาษา) — **ยัง nullable ระดับ DB แต่บังคับกรอกที่ FormRequest** (ป้ายโฆษณาแสดงผลเป็นรูปภาพเท่านั้น ไม่มีรูปก็ไม่มีอะไรให้แสดง) |
| `url` | `varchar(500)` null | ลิงก์ที่กดไปได้ — ไม่ validate ด้วย rule `url` เพราะอนุญาต relative path เช่น `/news/1` |
| `link_target` | `varchar(20)` null | เป้าหมายเปิดลิงก์: `_self` (หน้าต่างเดิม) / `_blank` (แท็บใหม่) |
| `publish_date` | `datetime` null | วันที่เผยแพร่ — **ยัง nullable ระดับ DB แต่บังคับกรอกที่ FormRequest** (เหมือนบทความ) ฟอร์มเพิ่มตั้งค่าเริ่มต้นเป็นวันเวลาปัจจุบัน |
| `publish_down` | `datetime` null | วันที่ปิดการเผยแพร่ |
| `click_amount` | `unsigned int` default 0 | จำนวนคลิกลิงก์ทั้งหมด — **เก็บไว้ใช้ในอนาคต ยังไม่มี UI แสดง/แก้ไข** ทั้งฟอร์มและหน้ารายการ |
| `sort_order` | `unsigned int` default 0 | ลำดับการแสดงผล |
| `status` | `char(1)` default `Y` | `Y` = เปิดใช้งาน, `N` = ปิด |
| `is_temp` | `char(1)` default `N` | `Y` = ข้อมูลตัวอย่าง/ตั้งต้น |
| `created_by` / `updated_by` / `deleted_by` | bigint null (`sys_user.id`, ไม่มี FK) | |
| `timestamps`, `deleted_at` | | `SoftDeletes` |

**Data model — `banner_item_detail`** (ข้อมูลแยกตามภาษา — PK = `id` + `lang`)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `unsigned bigint` | = `banner_item_info.id` (FK, cascadeOnDelete) |
| `lang` | `char(2)` | ส่วนหนึ่งของ PK |
| `title` | `varchar(500)` null | ชื่อ/หัวเรื่อง — required เฉพาะ `lang_default` |
| `intro_text` | `varchar(2000)` null | ข้อความเกริ่นนำ |
| `status` | `char(1)` default `Y` | |
| `created_by` / `updated_by` / `deleted_by`, `timestamps`, `deleted_at` | | เหมือนหมวดหมู่ |

**ไม่มี** `slug`/meta/og — ต่างจาก `article_item_detail` เพราะไม่ใช่หน้าเนื้อหาที่ต้องการ SEO

**Model**
- `App\Models\BannerItemInfo` — `SoftDeletes`; `casts(['publish_date' => 'datetime', 'publish_down' => 'datetime'])`;
  `category()` belongsTo `BannerCategoryInfo` (fk `banner_category_info_id`); `introImage()` belongsTo `FileInfo`;
  `details()` hasMany `BannerItemDetail` (ไม่มี `parts()`/`tags()`)
- `App\Models\BannerItemDetail` — `SoftDeletes`, `$incrementing = false`; `item()` belongsTo `BannerItemInfo`

**Migration** — `database/migrations/2026_09_18_000002_create_banner_item_tables.php`

**Seeder** — ไม่มีป้ายโฆษณาตัวอย่าง (`BannerSeeder.php` seed แค่หมวดหมู่) เพราะยังไม่มีไฟล์รูปภาพตัวอย่างใน
`file_info` ให้ผูก (โมดูลจัดการไฟล์เป็นพื้นที่ส่วนตัวต่อผู้ใช้ ไม่มี seed ไฟล์กลาง)

**หน้าจอ** — เสร็จแล้ว (`Admin/Banner/Item/{Index,Add,Edit}.vue`)
- list ป้ายโฆษณา (ค้นหาชื่อ+ข้อความเกริ่นนำ, กรองหมวดหมู่+สถานะ, เรียงชื่อ/หมวดหมู่/ลำดับ/วันที่เผยแพร่/สถานะ,
  paging — **ไม่แสดงคอลัมน์จำนวนคลิก**) มีคอลัมน์ "รูปภาพ" แสดงตัวอย่างรูป (thumbnail ผ่าน
  `admin.system.file.get.thumbnail.size`) กว้างพอให้รู้ว่าเป็นภาพอะไร แสดงเต็มความกว้างของกรอบและปล่อยความสูง
  ตามสัดส่วนจริงของรูป (ไม่ครอปสี่เหลี่ยมจัตุรัสเหมือน thumbnail ของโมดูลจัดการไฟล์)
- form เพิ่ม/แก้ไข ตามรูปแบบ "กลุ่มข้อมูลร่วม" (หมวดหมู่, รูปภาพผ่าน `FilePickerField.vue` — **บังคับเลือก**,
  ลิงก์, เป้าหมายลิงก์ผ่าน `SearchableSelect`, วันที่เผยแพร่ผ่าน `DateTimeInput.vue` — **บังคับกรอก** ฟอร์มเพิ่ม
  ตั้งค่าเริ่มต้นเป็นวันเวลาปัจจุบันให้อัตโนมัติ, วันที่ปิดการเผยแพร่ (ไม่บังคับ), ลำดับ, สถานะ) +
  "กลุ่มข้อมูลแยกภาษา" (ชื่อ + ข้อความเกริ่นนำ เท่านั้น) — ไม่มี rich text/SEO/แท็ก/เนื้อหาแบบแบ่ง part เหมือนบทความ

**Permission code** (seed ไว้แล้ว) — `banner.item.view`, `banner.item.manage`, `banner.item.delete`

**Route** — `admin.banner.item.{index,add,store,edit,update,destroy}` (ผูกไว้ใน `MenuSeeder.php` และมี route
จริงรองรับครบแล้วใน `routes/web.php`, controller: `App\Http\Controllers\Admin\Banner\BannerItemController`)

---

## 3. ตั้งค่าโมดูลป้ายโฆษณา

**สถานะ**: 🟢 **หน้าล้างแคช** — ยังไม่มีฟิลด์ตั้งค่าจริง หน้าตั้งค่า (`admin.banner.setting.index`) จึงเป็นหน้าล้างแคชของโมดูลโดยตรง
(ไม่มี `TabNav` ตั้งค่า/ล้างแคชแบบบทความ)

**Controller** — `Admin\Banner\BannerSettingController`: `index()` (render หน้าล้างแคช), `clearCacheSetting()` (`Setting::forget('banner')`),
`clearCacheFront()` (`FrontCache::forgetAll()` — ป้ายโฆษณาที่แสดงผ่าน widget Slideshow/Slideset/Grid ของหน้าเพจ), `clearCacheAll()`
(`Setting::forget('banner')` ซึ่งล้างแคชหน้าบ้านให้ด้วย) — ทุก action เช็ก `banner.setting.manage`, log action module_code `banner.setting.cache`

**หน้าจอ** — `Pages/Admin/Banner/Setting/Index.vue` รูปแบบเดียวกับแท็บล้างแคชของตั้งค่าบทความ: กล่อง "ล้างแคชรายรายการ"
(ตั้งค่าป้ายโฆษณา / ป้ายโฆษณาที่แสดงหน้าบ้าน) + กล่อง "ล้างแคชทั้งหมดของป้ายโฆษณา"

**Permission code** (seed ไว้แล้ว) — `banner.setting.manage`

**Route** — `admin.banner.setting.index` + `admin.banner.setting.clearcache.{setting,front,all}` (POST)

**การลงทะเบียนแคชร่วมกับตั้งค่าระบบ** — เพิ่ม `'banner'` เข้า `App\Support\Setting::GROUPS` แล้ว (ไม่ได้เพิ่มเข้า
`Admin\System\SettingController::OWN_GROUPS` เหมือนที่ `'article'` เองก็ไม่ได้อยู่ในนั้น) ทำให้หน้า "ล้างแคช" ของ
ตั้งค่าระบบมีปุ่มล้างแคชกลุ่ม `banner` เพิ่มมาด้วยล่วงหน้า (ตอนนี้เป็น no-op เพราะยังไม่มีแถว `sys_setting` กลุ่มนี้)

**เมื่อจะออกแบบฟิลด์ตั้งค่าจริงในอนาคต** — แยกหน้าตั้งค่า + ย้ายหน้าล้างแคชไปเป็นแท็บ ตาม pattern `Admin\Article\ArticleSettingController` ทุกจุด
(`update()`/`clearcache()`/`clearCacheSetting()`/`clearCacheAll()`, `UpdateBannerSettingRequest`,
`SysSetting::where('group','banner')->forceDelete()` แล้ว insert ใหม่, `Setting::forget('banner')`,
module_code `banner.setting` / `banner.setting.cache`)

---

## 4. รายงานการคลิก

ใช้ระบบรายงานชุดเดียวกับบทความ (รายละเอียดดู [PRD-article.md](PRD-article.md) §4) แต่ตัวเลขเป็น **ยอดคลิก** จาก `banner_item_click`
(นับผ่าน `POST front.banner.click` — ดู PRD-front.md §8) คำบนหน้าจอ/CSV เปลี่ยนเป็น "ยอดคลิก / ผู้คลิกไม่ซ้ำ" อัตโนมัติจาก `metric = click`:
- **รายป้ายโฆษณา** `admin.banner.item.report` (+`.export`) สิทธิ์ `banner.item.view`, log `banner.item.report` — แท็บในหน้าแก้ไข
  และคอลัมน์สุดท้ายของหน้ารายการ
- **เมนูรายงาน** `admin.banner.report.*` (`BannerReportController`) สิทธิ์ `banner.report.view`, log `banner.report.<แท็บ>` —
  แท็บ รายการคลิก / ภาพรวม / ป้ายโฆษณายอดนิยม / ตามหมวดหมู่ / ผู้คลิกและแหล่งที่มา / ช่วงเวลา
- referrer ของการคลิก = หน้าที่มี banner อยู่ จึงแสดงสัดส่วน **"หน้าที่มีการคลิก"** (path ภายในเว็บ) เพิ่มจากโมดูลอื่น

---

## Roadmap

| รอบ | ขอบเขต | สถานะ |
|-----|--------|-------|
| 0 — schema หมวดหมู่ | `banner_category_info`/`banner_category_detail` + `BannerSeeder` ตัวอย่าง | ✅ เสร็จ |
| 1 — CRUD หมวดหมู่ | controller/route/หน้า Vue list+form ตามต้นแบบ `article.category` | ✅ เสร็จ |
| 2 — schema ป้ายโฆษณา | `banner_item_info`/`banner_item_detail` | ✅ เสร็จ |
| 3 — CRUD ป้ายโฆษณา | controller/route/หน้า Vue list+add+edit (รูปภาพ+ลิงก์+ช่วงเวลาเผยแพร่+ลำดับ) | ✅ เสร็จ |
| 4 — ตั้งค่าโมดูลป้ายโฆษณา | หน้า `admin.banner.setting.index` แบบ placeholder + ลงทะเบียนกลุ่มแคช | ✅ |
| **5 — ปรับปรุงป้ายโฆษณา** *(รอบนี้)* | ฟอร์มหมวดหมู่/ป้ายโฆษณาจัดการ์ดใหม่ (component ร่วม `Components/Admin/BannerForm/*`), การ์ด "ข้อมูลระบบ" ในหน้าแก้ไข (จำนวนป้ายโฆษณา / จำนวนคลิก), หน้าตั้งค่า = หน้าล้างแคช | ✅ |
