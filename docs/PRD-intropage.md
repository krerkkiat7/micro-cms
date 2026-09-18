# PRD — โมดูล Intropage

เอกสารนี้ลงรายละเอียดของโมดูล **Intropage** (หน้าคั่น/splash ก่อนเข้าเว็บ) ในหลังบ้าน ภาพรวมทั้งระบบดูที่
[PRD-overview.md](PRD-overview.md) §5

สถานะ: 🟢 มีแล้ว · 🟡 มีบางส่วน · 🔴 ยังไม่มี

| # | หัวข้อ | ตารางหลัก | สถานะ |
|---|--------|-----------|-------|
| 1 | Intropage | `intropage_item_info`, `intropage_item_detail` | 🟢 schema + controller/route/UI (list, add, edit) เสร็จครบ |
| 2 | ปุ่มของ Intropage | `intropage_item_button` | 🟢 จัดการอยู่ในฟอร์ม Intropage (ไม่มี route/controller แยก) |
| 3 | การเลือกว่าจะแสดง Intropage ใด | (คำนวณที่หน้าบ้าน) | 🔴 ยังไม่ได้ทำ — ดู §3 |

---

## 0. ภาพรวมโมดูล

- Intropage คือหน้าคั่น/splash ที่แสดงก่อนเข้าเว็บจริง (เช่น หน้าประชาสัมพันธ์/โปรโมชัน) ประกอบด้วย **พื้นหลัง**
  (สี และ/หรือรูปภาพพื้นหลังแบบ CSS background) + **สื่อหลัก** (รูปภาพ/ไฟล์วิดีโอ/URL วิดีโอ/YouTube) +
  **ข้อความต้อนรับต่อภาษา** + **ชุดปุ่มด้านล่าง** ที่กดได้ (ปุ่ม "เข้าหน้าแรก" ที่มีอยู่เสมอ 1 ปุ่ม + ปุ่มอื่นที่
  เพิ่มเองได้)
- **บันทึกเก็บได้หลายรายการ แต่ "แสดงจริง" ที่หน้าบ้านได้ทีละ 1 รายการเท่านั้น** — โมดูลนี้ (หลังบ้าน) ไม่มี
  กลไกสลับ/exclusive switch ใด ๆ เก็บแค่ `status` (Y/N ปกติ) + `publish_date`/`publish_down` เหมือนโมดูลอื่น
  ดู §3 สำหรับกติกาที่ตกลงไว้สำหรับตอนทำหน้าบ้าน
- **ไม่มีหมวดหมู่** (ต่างจาก banner/article) — สิทธิ์/เมนูที่ seed ไว้มีแค่ tier เดียว (`intropage.item.*`)
- รองรับ**หลายภาษาตามที่ระบบตั้งค่าไว้** เหมือนโมดูลอื่น (`App\Support\Setting::selectedLanguages()`/
  `defaultLanguage()`) — ชื่อ **required เฉพาะภาษาหลัก** ภาษาอื่นกรอกหรือไม่ก็ได้ บังคับที่ FormRequest
- **`publish_date` และ `publish_down` required ทั้งคู่** — ต่างจาก banner/article ที่ `publish_down` เป็น
  optional (ผู้ใช้ระบุไว้ชัดเจนว่า "วันที่ปิดประกาศ จำเป็นต้องกรอก")

### รูปแบบการกรอกข้อมูล

ฟอร์มแบ่งเป็น 3 การ์ดเสมอ:

- **กลุ่มข้อมูลร่วม** ("ข้อมูลทั่วไป") — ไม่แยกภาษา: พื้นหลัง (สี/รูป/repeat/size/attachment/position),
  ประเภท+ขนาดการแสดงผลของสื่อหลัก, สื่อหลักตามประเภทที่เลือก, ช่วงเวลาประกาศ, สถานะ
- **กลุ่มข้อมูลแยกภาษา** ("ข้อมูลหน้า Intropage") — ชื่อ + ข้อความต้อนรับ ผ่าน `LangFieldGroup.vue`
- **กลุ่มการจัดการปุ่ม** ("การจัดการปุ่ม") — `show_button` (แสดง/ซ่อนโซนปุ่มทั้งหมด) + รายการปุ่ม (ดู §2)

---

## 1. Intropage

**Data model — `intropage_item_info`** (ข้อมูลร่วม ไม่แยกภาษา)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK autoincrement | |
| `background_color` | `varchar(20)` null | สีพื้นหลัง (hex) |
| `background_image_id` | bigint FK → `file_info.id` null (nullOnDelete) | รูปภาพพื้นหลัง |
| `background_repeat` | `varchar(50)` null | CSS `background-repeat`: `repeat`/`no-repeat`/`repeat-x`/`repeat-y` |
| `background_size` | `varchar(50)` null | CSS `background-size`: `auto`/`cover`/`contain` |
| `background_attachment` | `varchar(50)` null | CSS `background-attachment`: `scroll`/`fixed` |
| `background_position` | `varchar(50)` null | preset เช่น `center`/`top left`/... |
| `display_type` | `varchar(10)` null | `image`/`vdo`/`vdourl`/`youtubeurl` — **required ที่ FormRequest** |
| `display_size` | `varchar(20)` null | ขนาดการแสดงผลของสื่อหลักเทียบความกว้างจอ/container: `screen_100/75/50/25`, `container_100/75/50/25` — **required ที่ FormRequest** ใช้ร่วมกันทุก `display_type` |
| `image_file_id` | bigint FK → `file_info.id` null (nullOnDelete) | ใช้เมื่อ `display_type=image` |
| `vdo_file_id` | bigint FK → `file_info.id` null (nullOnDelete) | ใช้เมื่อ `display_type=vdo` |
| `vdo_url` | `varchar(500)` null | ใช้เมื่อ `display_type=vdourl` หรือ `youtubeurl` (คอลัมน์เดียวใช้ร่วมกัน) |
| `show_button` | `char(1)` default `Y` | แสดง/ซ่อนโซนปุ่มทั้งหมด — ฟิลด์นี้อยู่ในฟอร์มการ์ด "การจัดการปุ่ม" ไม่ใช่ "ข้อมูลทั่วไป" |
| `publish_date` | `datetime` null | วันที่ประกาศ — **required ที่ FormRequest** ฟอร์มเพิ่มตั้งค่าเริ่มต้นเป็นวันเวลาปัจจุบัน |
| `publish_down` | `datetime` null | วันที่ปิดประกาศ — **required ที่ FormRequest** (ต่างจาก banner/article ที่ optional) + `after:publish_date` |
| `status` | `char(1)` default `Y` | `Y` = เปิดใช้งาน, `N` = ปิด |
| `is_temp` | `char(1)` default `N` | `Y` = ข้อมูลตัวอย่าง/ตั้งต้น (ลบทิ้งภายหลังได้) |
| `created_by`/`updated_by`/`deleted_by` | bigint null (`sys_user.id`, ไม่มี FK) | |
| `timestamps`, `deleted_at` | | `SoftDeletes` |

**ไม่มี** `button_text_color`/`button_background_color` ระดับ info (ต่างจากสคีมาต้นทางแบบ legacy ที่มีฟิลด์นี้) —
ย้ายไปเป็นสีต่อปุ่มที่ `intropage_item_button.text_color`/`background_color` แทน เพราะแต่ละปุ่มมีสีของตัวเองอยู่
แล้ว การเก็บซ้ำระดับ info จะไม่มีจุดใช้งานจริง

**Data model — `intropage_item_detail`** (ข้อมูลแยกตามภาษา — PK = `id` + `lang`)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | `unsigned bigint` | = `intropage_item_info.id` (FK, cascadeOnDelete) |
| `lang` | `char(2)` | ส่วนหนึ่งของ PK |
| `title` | `varchar(500)` null | ชื่อ — required เฉพาะภาษาหลัก |
| `detail` | `text` null | ข้อความต้อนรับ |
| `status` | `char(1)` default `Y` | |
| `created_by`/`updated_by`/`deleted_by`, `timestamps`, `deleted_at` | | เหมือนโมดูลอื่น |

> Eloquent ไม่รองรับ composite primary key เต็มรูปแบบ — ค้นด้วย
> `IntropageItemDetail::where('id', ...)->where('lang', ...)` เสมอ ห้ามใช้ `find()`

**Model**
- `App\Models\IntropageItemInfo` — `SoftDeletes`; `casts(['publish_date' => 'datetime', 'publish_down' => 'datetime'])`;
  `backgroundImage()`/`imageFile()`/`vdoFile()` belongsTo `FileInfo`; `details()` hasMany `IntropageItemDetail`;
  `buttons()` hasMany `IntropageItemButton` orderBy `sort_order`
- `App\Models\IntropageItemDetail` — `SoftDeletes`, `$incrementing = false`; `item()` belongsTo `IntropageItemInfo`

**Migration** — `database/migrations/2026_09_18_000003_create_intropage_item_tables.php` (สร้างทั้ง 3 ตารางของ
โมดูลนี้ในไฟล์เดียว รวมถึง `intropage_item_button`)

**Seeder** — `database/seeders/IntropageSeeder.php` (เรียกจาก `DatabaseSeeder`) สร้าง Intropage ตัวอย่าง 1
รายการ (`is_temp='Y'`, `display_type='youtubeurl'` เพื่อไม่ต้องพึ่งไฟล์ใน `file_info`) พร้อมปุ่ม `home` เริ่มต้น
1 ปุ่ม — `updateOrCreate` resolve แถวเดิมจากชื่อของภาษาหลัก (เหมือน `BannerSeeder`) รันซ้ำได้

**หน้าจอ** — เสร็จแล้ว (`Admin/Intropage/Item/{Index,Add,Edit}.vue`)
- list Intropage (ค้นหาชื่อ, กรองสถานะ, เรียงชื่อ/วันที่ประกาศ/วันที่ปิดประกาศ/สถานะ, paging) — คอลัมน์ ชื่อ,
  วันที่ประกาศ, วันที่ปิดประกาศ, สถานะ
- form เพิ่ม/แก้ไข ตามรูปแบบ 3 การ์ด (§0) — ลำดับฟิลด์ในการ์ด "ข้อมูลทั่วไป" ตั้งใจให้ไล่ตามการตัดสินใจของ
  ผู้ใช้: **ประเภทการแสดงผล → ฟิลด์สื่อ conditional ตามประเภท → ขนาดการแสดงผล** ก่อน แล้วค่อยเป็นกลุ่ม
  "พื้นหลัง" (สี/รูป/repeat/size/attachment/position) แยกด้วยเส้นคั่น + หัวข้อย่อย แล้วปิดท้ายด้วย
  สถานะ/ช่วงเวลาประกาศ — `display_type` และ `display_size` ใช้ตัวเลือกแบบเห็นภาพประกอบ (ไดอะแกรม SVG)
  แทน dropdown ธรรมดา ผ่าน `Components/Admin/IntropageDisplayTypePicker.vue` /
  `IntropageDisplaySizePicker.vue` (เทียบเคียง `ArticlePart/ImagesDisplayTypePicker.vue` ของบทความ —
  ข้อมูล label/description อยู่ที่ `utils/intropageDisplay.ts`) พื้นหลังใช้ `ColorPickerInput.vue`
  (ใหม่ — preset สี + กำหนดเอง) + `FilePickerField.vue`, ช่วงเวลาประกาศผ่าน `DateTimeInput.vue`
  (ทั้งสองฟิลด์ required ไม่มี `clearable`)

**Permission code** (seed ไว้แล้วใน `DatabaseSeeder.php`) — `intropage.item.view`, `intropage.item.manage`,
`intropage.item.delete`

**Route** — `admin.intropage.item.{index,add,store,edit,update,destroy}` (ผูกไว้ใน `MenuSeeder.php` และมี route
จริงรองรับครบแล้วใน `routes/web.php`, controller: `App\Http\Controllers\Admin\Intropage\IntropageItemController`)

**Log action** — `module_code = "intropage.item"` เดียวสำหรับทุกการกระทำ (create/view/update/delete) — การ
บันทึก/แก้ไขปุ่ม **ไม่แยก module_code** (พับรวมอยู่ใน create/update ของ Intropage เอง เหมือนที่ article ไม่แยก
`.part`)

---

## 2. ปุ่มของ Intropage

**วัตถุประสงค์** — ปุ่มด้านล่างของหน้า Intropage ที่ผู้ใช้กดได้ เรียงลำดับ/เพิ่ม/ลบได้อิสระ ยกเว้นปุ่ม "เข้าหน้าแรก"
ที่มีอยู่เสมอ 1 ปุ่ม ลบไม่ได้

**Data model — `intropage_item_button`**

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK autoincrement | |
| `intropage_item_info_id` | bigint FK → `intropage_item_info.id` (cascadeOnDelete) | |
| `button_type` | `varchar(10)` | `home` (มีอยู่เสมอ 1 ปุ่ม ลบไม่ได้ — guard ที่ FormRequest ว่าต้องมีพอดี 1 แถว) / `other` (เพิ่ม/ลบได้อิสระ) |
| `sort_order` | `unsigned int` default 0 | ลำดับปุ่ม |
| `button_display_type` | `varchar(10)` default `text` | `text` (ใช้ `texts`+`text_color`+`background_color`) / `image` (ใช้ `button_image_id`) |
| `background_color` | `varchar(10)` null | เฉพาะ `button_display_type=text` |
| `text_color` | `varchar(10)` null | เฉพาะ `button_display_type=text` |
| `button_image_id` | bigint FK → `file_info.id` null (nullOnDelete) | เฉพาะ `button_display_type=image` |
| `url` | `varchar(500)` null | เฉพาะ `button_type=other` (ปุ่ม `home` ไม่มีฟิลด์นี้ — ลิงก์ไปหน้าแรกเสมอโดยนัย) |
| `link_target` | `varchar(20)` null | `_self`/`_blank` — เฉพาะ `button_type=other` |
| `texts` | `json` null | ข้อความปุ่มแยกตามภาษา เช่น `{"th": "เข้าสู่เว็บไซต์", "en": "Enter Site"}` |
| `created_by`/`updated_by`/`deleted_by`, `timestamps`, `deleted_at` | | เหมือนโมดูลอื่น |

**Model** — `App\Models\IntropageItemButton` — `SoftDeletes`; cast `texts` → `array`; `item()` belongsTo
`IntropageItemInfo`; `buttonImage()` belongsTo `FileInfo`

**การจัดการในฟอร์ม** — เทียบเคียง `article_item_part` (เนื้อหาแบบแบ่ง part ของบทความ): ไม่มี route/controller
แยก ฟอร์มเพิ่ม/แก้ไข Intropage ส่ง `buttons` เป็นอาเรย์มาพร้อมกันทั้งชุดทุกครั้ง — backend
(`IntropageItemController::syncButtons()`) ลบปุ่มเดิมทั้งหมดแล้วสร้างใหม่ตามอาเรย์ที่ส่งมา (`sort_order` =
ลำดับในอาเรย์) ไม่ diff เอง — ฟอร์ม add ใหม่เริ่มต้นด้วย `buttons: [createHomeButton(languages)]` เสมอ
(`resources/js/utils/intropageButtons.ts`)

**Vue component** — `resources/js/Components/Admin/IntropageButton/{ButtonList,ButtonCard,ButtonReorderDialog}.vue`
เทียบเคียง `Components/Admin/ArticlePart/{PartList,PartCard,PartReorderDialog}.vue`: `ButtonList` มีปุ่ม "เพิ่มปุ่ม"
เดียว (สร้าง `button_type=other`), ปุ่มลบซ่อนเมื่อ `button_type=home`, จัดลำดับผ่าน `ButtonReorderDialog`
(`vuedraggable` แบบเดียวกับ `PartReorderDialog`) — ปุ่ม home ลากสลับตำแหน่งได้ปกติ

**Validation** — เข้มงวดเท่า `button_type` (required, `Rule::in(['home','other'])`) เท่านั้น ที่เหลือ nullable
(เทียบเคียง `ArticleItemValidationRules::partRules()`) + guard เพิ่มที่ `withValidator()`: ต้องมีปุ่ม
`button_type=home` อยู่พอดี 1 แถวเสมอ (ฝั่ง UI ป้องกันการลบอยู่แล้ว แต่ backend ต้อง validate ที่ boundary ด้วย)

---

## 3. การเลือกว่าจะแสดง Intropage ใด (ยังไม่ทำ — หมายเหตุสำหรับตอนทำหน้าบ้าน)

**สถานะ**: 🔴 ยังไม่ได้ทำ — เก็บกติกาไว้ที่นี่สำหรับตอนพัฒนาหน้าบ้าน (front-office)

Intropage บันทึกเก็บได้หลายรายการ แต่ "แสดงจริง" ที่หน้าบ้านได้ทีละ 1 รายการเท่านั้น **หลังบ้านไม่มีกลไกสลับ/
exclusive switch ใด ๆ** (ยืนยันกับผู้ใช้แล้ว) — แค่มี `status`/`publish_date`/`publish_down` ปกติเหมือนโมดูลอื่น

กติกาที่ตกลงไว้สำหรับหน้าบ้าน: ให้ **หน้าบ้านเป็นผู้คำนวณเองว่าจะแสดง Intropage รายการไหน** จากรายการที่
`status='Y'` และช่วงเวลาเผยแพร่ครอบคลุมเวลาปัจจุบัน (`publish_date <= now <= publish_down`) — ถ้ามีมากกว่า 1
รายการที่ช่วงเวลาทับกัน ให้เลือกตามลำดับนี้:

1. รายการที่ `publish_date` **ใกล้เวลาปัจจุบันที่สุด** (ประกาศล่าสุดมาก่อน)
2. ถ้ายังเท่ากัน (tie) ให้เลือกรายการที่ `publish_down` **ไกลจากเวลาปัจจุบันที่สุด** (ปิดประกาศช้าที่สุด)

---

## Roadmap

| รอบ | ขอบเขต | สถานะ |
|-----|--------|-------|
| 0 — schema Intropage + ปุ่ม | `intropage_item_info`/`intropage_item_detail`/`intropage_item_button` + `IntropageSeeder` ตัวอย่าง | ✅ เสร็จ |
| 1 — CRUD Intropage + จัดการปุ่ม | controller/route/หน้า Vue list+add+edit (พื้นหลัง+สื่อหลัก+ช่วงเวลาประกาศ+ปุ่มแบบเรียงลำดับ) | ✅ เสร็จ |
| **2 — การเลือกแสดง Intropage ที่หน้าบ้าน** *(รอบถัดไป)* | คำนวณรายการที่จะแสดงตามกติกา §3 ที่หน้าบ้าน | 🔴 ยังไม่เริ่ม |
