# PRD — โมดูล Popup

เอกสารนี้ลงรายละเอียดของโมดูล **Popup** (รายการ Popup + ตั้งค่า + ล้างแคช) ทั้งฝั่งหลังบ้านและการแสดงผลที่หน้าบ้าน
ภาพรวมทั้งระบบดูที่ [PRD-overview.md](PRD-overview.md) §3

สถานะ: 🟢 มีแล้ว · 🟡 มีบางส่วน · 🔴 ยังไม่มี

| # | หัวข้อ | ตารางหลัก | สถานะ |
|---|--------|-----------|-------|
| 1 | Popup (หลังบ้าน) | `popup_item_info`, `popup_item_menu`, `popup_item_part`, `popup_item_part_detail` | 🟢 schema + controller/route/UI (list, add, edit) |
| 2 | ตั้งค่าโมดูล + ล้างแคช | `sys_setting` (group = `popup`) | 🟢 ลำดับการแสดงผล + หน้าล้างแคช |
| 3 | แสดงผลที่หน้าบ้าน | — | 🟢 modal / floating, สไลด์, กรองตามเมนูของหน้า, ไม่แสดงวันนี้อีก |

---

## 0. ภาพรวมโมดูล

- Popup ที่สร้างไว้จะไปแสดงที่หน้าบ้าน **ทุกหน้า** หรือ **เฉพาะหน้าของเมนูที่ระบุ** (หรือ "ไม่กำหนด" = ยังไม่แสดงที่ไหน)
- **1 Popup มีข้อมูลได้หลายรายการ (part)** แต่ละ part แสดงเป็น **1 สไลด์** ที่หน้าบ้าน — part มี 3 รูปแบบ:
  **รูปภาพ + ข้อความ** / **รูปภาพ** / **ข้อความ**
- **1 หน้ามีได้หลาย Popup** แสดงซ้อนทับกัน (เผื่อกรณีข้อมูลซ้อนกัน) — ลำดับการซ้อนตามหน้าตั้งค่า (§2)
- รูปแบบการแสดงของ Popup:
  - **Modal** — กลางจอ มีพื้นหลังทึบ ต้องปิดก่อนใช้งานหน้าเว็บต่อ, ปุ่มด้านล่าง "ปิด และไม่แสดงวันนี้อีก" (ถ้าเปิดใช้) ก่อนปุ่ม "ปิด"
  - **Floating (ลอย)** — **รูปภาพอย่างเดียว** ลอยกลางจอ ไม่มีกล่อง/พื้นหลัง รูปสูงไม่เกินหน้าจอ หน้าเว็บด้านหลังยังคลิก/เลื่อนได้,
    ใต้รูป/สไลด์มี "ไม่แสดงวันนี้อีก" (ถ้าเปิดใช้) + ปุ่มปิด
- ไม่แสดงที่หน้า Intropage (หน้าคั่นก่อนเข้าเว็บ ไม่มีเมนู)
- ชื่อ Popup ใช้ในหลังบ้านเท่านั้น (ไม่แยกภาษา); ข้อความของ part แยกตามภาษาที่ระบบเปิดใช้ (required เฉพาะภาษาหลัก)
  รูปภาพใช้ร่วมทุกภาษา

---

## 1. Popup

**Permission code:** `popup.item.view` (ดูรายการ/รายละเอียด), `popup.item.manage` (เพิ่ม/แก้ไข), `popup.item.delete` (ลบ) — seed ไว้แล้วใน `DatabaseSeeder`

**Route:** `admin.popup.item.index` / `.add` / `.store` / `.edit` / `.update` / `.destroy` (`/admin/popup/item/...`)
— `Admin\Popup\PopupItemController` part และเมนูที่แสดงส่งมาพร้อม store/update (ไม่มี route แยก)

**Log action:** module_code `popup.item` — `create` (store) / `view` (เปิดหน้าแก้ไข) / `update` / `delete`
(`value_string` = ชื่อ Popup) หน้ารายการ/หน้าเพิ่มบันทึกแค่ `log_back_access`

### Data model — `popup_item_info`

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK | |
| `name` | `varchar(250)` | ชื่อ (จำเป็น) — ใช้ในหลังบ้าน |
| `display_type` | `varchar(10)` | `modal` (default) / `floating` |
| `show_dismiss_today` | `char(1)` | `Y` = แสดงปุ่ม/ลิงก์ "ไม่แสดงวันนี้อีก" (default `Y`) |
| `show_arrows` | `char(1)` | แสดงลูกศร (default `Y`) |
| `show_dots` | `char(1)` | แสดงจุดด้านล่าง (default `Y`) |
| `autoplay` | `char(1)` | ให้สไลด์อัตโนมัติ (default `Y`) |
| `slide_interval` | `int unsigned` | เวลาที่ค้าง **วินาที** (default 5, 1–120) |
| `slide_speed` | `int unsigned` | ความเร็วการสไลด์ **มิลลิวินาที** (default 3000, 100–10000) |
| `menu_mode` | `varchar(10)` | `all` = ทุกหน้า / `selected` = เมนูที่ระบุ / `none` = ไม่กำหนด (ไม่แสดง) |
| `publish_date` | `datetime` | วันที่เผยแพร่ (จำเป็น) |
| `publish_down` | `datetime` null | วันที่ปิดเผยแพร่ (เคลียร์ได้) ต้องมากกว่าวันที่เผยแพร่ |
| `sort_order` | `int unsigned` | ลำดับ — ใช้เมื่อตั้งค่าเรียงตามลำดับ |
| `status` | `char(1)` | `Y`/`N` |
| `is_temp` | `char(1)` | ข้อมูลตัวอย่าง (default `N`) |
| audit | | `created_by`/`updated_by`/`deleted_by` + timestamps + softDeletes |

### Data model — `popup_item_menu` (pivot)

`popup_item_info_id` + `front_menu_info_id` (FK cascade, PK คู่) + `created_by`/`updated_by` + timestamps —
เก็บเฉพาะเมื่อ `menu_mode = selected` (เปลี่ยนเป็นโหมดอื่นแล้วบันทึก = ล้างเมนูที่เคยเลือก)

### Data model — `popup_item_part`

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `id` | bigint PK | สร้างใหม่ทุกครั้งที่บันทึก (ลบของเดิมแบบ soft delete แล้ว insert ใหม่ตามลำดับ) |
| `popup_item_info_id` | FK cascade | |
| `part_type` | `varchar(20)` | `image_text` (default) / `image` / `text` |
| `image_id` | FK → `file_info` null (nullOnDelete) | จำเป็นเมื่อรูปแบบมีรูปภาพ |
| `image_size` | `varchar(10)` | `full` (default, เต็มความกว้าง) / `large` / `medium` / `small` |
| `url` | `varchar(500)` null | ลิงค์ปลายทาง |
| `link_target` | `varchar(10)` | `_blank` (default) / `_self` |
| `sort_order` | `int unsigned` | ลำดับตามที่เรียงในฟอร์ม |
| `status` | `char(1)` | `Y` = แสดง / `N` = ซ่อน (ไอคอนลูกตา — ต่างจากการลบ) |
| audit | | + timestamps + softDeletes |

### Data model — `popup_item_part_detail`

`id` (= `popup_item_part.id`) + `lang` (PK คู่), `detail` `text` (rich text จาก `RichTextEditor`) + audit/timestamps/softDeletes —
ภาษาที่ข้อความว่าง (รวม `<p></p>`) ไม่สร้างแถว, part แบบ `image` ไม่เก็บข้อความ

> เทียบกับตารางเดิมของระบบเก่า: ตัด `CustomCss`/`Width`/`Height`/`BackgroundColor`/`Padding`/`TemplateType`/`TemplateSetting`
> และคอลัมน์วิดีโอของ part (ไม่ได้ใช้แล้ว), `DisplayType` แบบ `U` (หน้าที่ไม่ถูกเลือก) ไม่มีแล้ว, ข้อความ part แยกเป็นตาราง `_detail` แทน JSON

**Model:** `PopupItemInfo` (`parts()`, `menus()` belongsToMany + pivot audit), `PopupItemPart` (`image()`, `details()`),
`PopupItemPartDetail` — ทุกตัวใช้ `FlushesFrontCache` + `SoftDeletes`

**Migration:** `2026_10_05_000001_create_popup_tables.php` (ชื่อ FK ตั้งเองให้สั้นกว่า 64 ตัวอักษร)

### หน้าจอ

**หน้ารายการ** (`Pages/Admin/Popup/Item/Index.vue`) — ค้นหาจากชื่อ + กรองสถานะ, คอลัมน์ ชื่อ (พร้อมป้าย Modal/Floating) /
วันที่เผยแพร่ / วันที่ปิดเผยแพร่ / สถานะ, เรียงได้ทุกคอลัมน์ (default วันที่เผยแพร่ใหม่สุด), paging + จำนวนต่อหน้า

**หน้าฟอร์ม** (`Add.vue` / `Edit.vue` + `Components/Admin/PopupForm/PopupItemFormFields.vue`) เรียงเป็นการ์ด:

1. **ข้อมูล Popup** — ชื่อ, รูปแบบการแสดง (การ์ดภาพ SVG `PopupDisplayTypePicker`), แสดงปุ่ม "ไม่แสดงวันนี้อีก" (checkbox),
   การสไลด์: แสดงลูกศร / แสดงจุดด้านล่าง / ให้สไลด์อัตโนมัติ (checkbox) + เวลาที่ค้าง (วินาที) + ความเร็วการสไลด์ (มิลลิวินาที)
2. **ข้อมูลที่แสดง** (`PopupPartList` / `PopupPartCard`) — หน้าเพิ่มมีไว้ให้ 1 รายการ, ปุ่ม "เพิ่มข้อมูล";
   แถบจัดการต่อรายการ: เรียงลำดับ (เปิด dialog `PopupPartReorderDialog`) / ลูกตา (แสดง/ซ่อน) / ลบ (dialog ยืนยัน);
   ฟิลด์: รูปแบบ (การ์ดภาพ `PopupPartTypePicker`, default รูปภาพ + ข้อความ), รูปภาพ (`FilePickerField` เฉพาะนามสกุลรูป) +
   ขนาด (segmented control) — ซ่อนเมื่อรูปแบบข้อความ, ข้อความแยกภาษา (`RichTextEditor`) — ซ่อนเมื่อรูปแบบรูปภาพ,
   ลิงค์ปลายทาง + ลิงค์เป้าหมาย
3. **การแสดงผล** — ลำดับ, เมนูที่แสดง (segmented control ทุกหน้า / เมนูที่ระบุ / ไม่กำหนด) — เลือก "เมนูที่ระบุ" แสดง tree เมนูหน้าบ้าน
   (`PopupMenuTreeNode`, ข้อมูลจาก `FrontMenuTree::adminCheckTree()` รวมเมนูที่ปิดใช้งานพร้อมป้ายกำกับ) **checkbox เฉพาะเมนูที่ผูกกับ
   โมดูลเนื้อหา** (`article_category` / `article_item` / `page` — `PopupItemInfo::MENU_TYPES`) เมนูประเภทอื่นแสดงเป็นชื่อให้เห็นโครง
4. **การเผยแพร่** — วันที่เผยแพร่ (จำเป็น) / วันที่ปิดเผยแพร่ (เคลียร์ได้)
5. **สถานะ**
6. **ข้อมูลระบบ** (เฉพาะหน้าแก้ไข) — `SystemInfoCard` (วันเวลาที่สร้าง/สร้างโดย/ปรับปรุงล่าสุด/ปรับปรุงล่าสุดโดย, ผู้ใช้ถูกลบแสดง "(ถูกลบแล้ว)")

**กฎการบันทึก** (`PopupItemValidationRules`):
- ต้องมี part อย่างน้อย 1 รายการ และ **ต้องมี part ที่แสดง (ไม่ได้ซ่อน) อย่างน้อย 1 รายการ**
- **แบบ Floating ใช้ได้เฉพาะ part รูปภาพ** — ฟอร์มซ่อนตัวเลือกรูปแบบและปรับทุก part เป็นรูปภาพเมื่อเลือก Floating, backend ตรวจ `parts.*.part_type` = `image`
- part ที่มีรูปภาพต้องเลือกรูป (`file_info` ที่ `status = Y`), part ที่มีข้อความต้องกรอกข้อความภาษาหลัก (ตัดแท็กแล้วต้องไม่ว่าง)
- เลือก "เมนูที่ระบุ" ต้องเลือกอย่างน้อย 1 เมนู และต้องเป็นเมนูประเภทโมดูลเนื้อหาที่ยังไม่ถูกลบ

---

## 2. ตั้งค่าโมดูล + ล้างแคช

**Permission code:** `popup.setting.manage` · **Log action:** module_code `popup.setting` (`update` = บันทึกตั้งค่า, `clear` = ล้างแคช)

**Route:** `admin.popup.setting.index` (GET) / `.update` (PUT) / `.clearcache` (GET หน้าล้างแคช) /
`.clearcache.setting` / `.clearcache.front` / `.clearcache.all` (POST) — `Admin\Popup\PopupSettingController`
หน้าตั้งค่า/ล้างแคชเป็น 2 แท็บ (`TabNav`) — `Pages/Admin/Popup/Setting/{Index,ClearCache}.vue`

ทะเบียนตั้งค่า `App\Support\PopupSetting` (`sys_setting` group `popup`, ลงทะเบียนใน `Setting::GROUPS` แล้ว):

| คีย์ | ค่า | หมายเหตุ |
|------|-----|----------|
| `display_order` | `publish_desc` (default) / `publish_asc` / `sort_desc` / `sort_asc` | ลำดับการแสดงผล: วันที่เผยแพร่ใหม่สุด / เก่าสุด / ลำดับมากสุด / น้อยสุด — **Popup รายการแรกตามลำดับนี้อยู่บนสุด** เมื่อซ้อนกัน |

ล้างแคช: ตั้งค่า Popup (`Setting::forget('popup')`), Popup ที่แสดงหน้าบ้าน (`FrontCache::forgetAll()`), ทั้งหมด

---

## 3. แสดงผลที่หน้าบ้าน

**ข้อมูล:** `App\Support\Front\PopupResolver`
- `forLanguage($lang)` — popup ที่ `status = Y`, `menu_mode != none`, `publish_date <= now` และ `publish_down` ว่างหรือ `> now`
  เรียงตาม `PopupSetting::orderBy()` (+ id ใหม่ก่อน) พร้อม part ที่ **แสดงเท่านั้น** — cache ต่อภาษาด้วย `FrontCache` (TTL สั้น
  `front.cache.popup_ttl` = 60 วินาที เพราะขึ้นกับเวลา; บันทึก popup/part/ตั้งค่า ล้าง cache ทันทีผ่าน `FlushesFrontCache`/`Setting::forget()`)
- part: รูปผ่าน `FrontFile::fromFileInfo()` (รูปถูกลบ/ปิด = ไม่มีรูป), ข้อความผ่าน `HtmlSanitizer::clean()` (ภาษาที่ขอยังไม่กรอก → ภาษาหลัก),
  ลิงก์ผ่าน `FrontUrl::safeExternal()` + `withLang()` (path ภายในที่ไม่มีภาษาเติม `/{lang}`) — part ที่ไม่เหลืออะไรให้แสดงถูกตัด, popup ที่ไม่เหลือ part ถูกตัด
- `forPage($lang, $menuId)` — popup แบบ `all` ทุกหน้า + แบบ `selected` ที่มี `$menuId` อยู่ในเมนูที่เลือก
- `FrontController::render()` ส่ง prop **`popups`** ให้ทุกหน้าที่ใช้ FrontLayout (หน้าเพจ/หมวดหมู่/รายละเอียด/แท็กบทความ) — เมนูของหน้า =
  ตัวสุดท้ายของ `header.activeMenuIds` (บทความที่ไม่มีเมนูของตัวเองใช้เมนูของหมวดหมู่ → เลือกเมนูหมวดหมู่ = แสดงในบทความของหมวดนั้นด้วย,
  หน้าแท็กไม่มีเมนู = เฉพาะ popup แบบทุกหน้า) — **Intropage และหน้า error ไม่มี popup**

**หน้าจอ:** `Components/Front/Popup/` — `PopupStack.vue` (วางใน `Layouts/Front/FrontLayout.vue` ผ่าน Teleport) → `PopupDialog.vue` → `PopupPart.vue`
- ซ้อนกันตามลำดับ: รายการแรก z-index สูงสุด (อยู่บนสุด) เริ่มที่ 51 (เหนือ aside z-50)
- **modal**: พื้นหลังทึบชั้นเดียวเมื่อมี modal อย่างน้อย 1 รายการ + ล็อกการเลื่อนหน้า, `role="dialog" aria-modal="true"`, กักโฟกัส/Esc เฉพาะรายการบนสุด,
  คืนโฟกัสเดิมเมื่อปิดหมด; ปุ่มล่าง "ปิด และไม่แสดงวันนี้อีก" (ถ้าเปิดใช้) ก่อน "ปิด"
- **floating**: **รูปภาพลอยกลางจออย่างเดียว** ไม่มีกล่อง/พื้นหลังใด ๆ (ชั้นนอก `pointer-events-none` — หน้าเว็บด้านหลังคลิก/เลื่อนได้),
  dialog แบบไม่ modal ไม่ดึงโฟกัส; กรอบกว้างสูงสุด `min(92vw, 40rem)` รูปกว้างตามขนาด (เต็ม/75%/50%/33% ของกรอบ) ย่อคงสัดส่วน
  **สูงไม่เกินหน้าจอ** (`max-height: 100dvh - 7rem`) และไม่ขยายเกินขนาดจริง; ลูกศร/จุด/ปุ่มหยุดซ้อนบนรูป;
  **ใต้รูป/สไลด์**: "ไม่แสดงวันนี้อีก" (ถ้าเปิดใช้) + ปุ่มปิด (X) เป็นปุ่มทรงแคปซูล/วงกลมสีดำโปร่งแสง ให้อ่านออกบนพื้นหลังหน้าเว็บทุกสี
  — part ที่ไม่มีรูป (ข้อมูลเก่า) ถูกตัด และข้อความของ part ไม่ถูกส่งไปหน้าบ้าน
- สไลด์: `useCarousel` (ค้าง `slide_interval` วินาที + เวลาเลื่อน) + `CarouselControls` (ลูกศรซ้อนบนเนื้อหา, จุด + ปุ่มหยุด/เล่นด้านล่าง),
  เลื่อนแนวนอนด้วยความเร็ว `slide_speed` ms, สไลด์ซ้อนใน grid ช่องเดียว (ความสูงกล่อง = สไลด์ที่สูงที่สุด), สไลด์ที่ไม่แสดง `inert`;
  prefers-reduced-motion = ไม่เลื่อนเองและไม่มี animation; มีรายการเดียวไม่แสดงตัวควบคุม
- part: รูปกว้างตามขนาด (เต็ม / 75% / 50% / 33% กึ่งกลาง) — มีลิงก์ = รูปเป็นลิงก์, part ข้อความอย่างเดียวมีลิงก์ "อ่านต่อ"
  (ไม่ครอบ rich text ด้วยลิงก์); คลิกลิงก์ = ปิด popup นั้น
- **การปิด** (เก็บในเบราว์เซอร์ ห่อ try/catch): "ไม่แสดงวันนี้อีก" → `localStorage` `front.popup.dismiss.{id}` = วันที่วันนี้ (เวลาเครื่องผู้ชม, วันถัดไปแสดงใหม่);
  "ปิด" → **ปิดเฉพาะในหน้านี้ ไม่จำ** — เปลี่ยน/โหลดหน้าที่เข้าเงื่อนไขเมนูและช่วงเผยแพร่ก็แสดงอีก (ผู้ชมที่ไม่อยากเห็นอีกให้กด "ไม่แสดงวันนี้อีก" แทน)
- ข้อความ UI: `lang/{th,en}/front.php` คีย์ `popup` / `dont_show_today` / `close_and_dont_show_today` (+ `close`, `read_more` เดิม)

**เทส:** `tests/Feature/Front/PopupTest.php`

---

## Roadmap

- สถิติการแสดง/คลิก Popup (ยังไม่มีแผน)
