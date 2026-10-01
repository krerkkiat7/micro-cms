# PRD — โมดูลติดต่อเรา (Contact Us)

เอกสารนี้ลงรายละเอียดของโมดูล **ติดต่อเรา** (ตั้งค่าการแสดงผล + ล้างแคช, ข้อมูลที่ติดต่อมา) ทั้งฝั่งหลังบ้านและหน้าบ้าน
ภาพรวมทั้งระบบดูที่ [PRD-overview.md](PRD-overview.md)

สถานะ: 🟢 มีแล้ว · 🟡 มีบางส่วน · 🔴 ยังไม่มี

| # | หัวข้อ | ตารางหลัก | สถานะ |
|---|--------|-----------|-------|
| 1 | ตั้งค่าโมดูล + ล้างแคช | `sys_setting` (group = `contactus`) | 🟢 รูปแบบการแสดงผล, ตัวอักษร, แผนที่, แบบฟอร์ม + หน้าล้างแคช |
| 2 | ข้อมูลติดต่อเรา (หลังบ้าน) | `contactus_item` | 🟢 รายการ + รายละเอียด/บันทึกสถานะ-หมายเหตุ (ไม่มีเพิ่ม/ลบ) |
| 3 | หน้าติดต่อเรา (หน้าบ้าน) | — | 🟢 `/{lang}/contactus` 3 รูปแบบ + แบบฟอร์ม (Turnstile) |
| 4 | เมนูประเภท "ติดต่อเรา" | `front_menu_info.menu_type = contactus` | 🟢 เมนูหน้าบ้าน / popup / ปุ่มอ่านทั้งหมดของ page เลือกได้ |
| 5 | ตั้งค่าระบบ — Google Map | `sys_setting` (group = `google_map`) | 🟢 API Key |

---

## 0. ภาพรวมโมดูล

- หน้าติดต่อเรามี **หน้าเดียวทั้งระบบ** (`/{lang}/contactus`, route `front.contactus.item`) แสดงตามตั้งค่าของโมดูล
- **ข้อมูลติดต่อจริงไม่เก็บซ้ำ** — ชื่อเจ้าของ (`site.copyright_owner`, ว่าง = ชื่อเว็บไซต์), ที่อยู่ (`contact.address_{lang}`),
  เบอร์ติดต่อ/แฟกซ์/มือถือ/อีเมล (`contact.*`) และ Social Media (`social.*`) มาจาก **ตั้งค่าระบบ** ผ่าน prop `front` (`FrontLayoutData`)
  โมดูลนี้ตั้งค่าเฉพาะการแสดง/ซ่อน + รูปแบบตัวอักษร + แผนที่ + แบบฟอร์ม
- ผู้ชมกรอกแบบฟอร์มส่งข้อความ → บันทึกลง `contactus_item` → ผู้ดูแลเห็นในหลังบ้าน เปลี่ยนสถานะและบันทึกหมายเหตุได้
- **แบบฟอร์มแสดง/รับข้อมูลเฉพาะเมื่อ "แสดงแบบฟอร์ม" และตั้งค่า Turnstile CAPTCHA ครบ** (site key + secret key ในตั้งค่าระบบ)
  — ไม่ครบ = หน้าบ้านไม่แสดงแบบฟอร์ม และ `POST` ตอบ 404 (เพื่อความปลอดภัยจากสแปม/บอท) หน้าตั้งค่าแจ้งเตือนไว้

---

## 1. ตั้งค่าโมดูล + ล้างแคช

**Permission code:** `contactus.setting.manage` · **Route:** `admin.contactus.setting.index` / `.update` / `.clearcache` /
`.clearcache.setting` / `.clearcache.front` / `.clearcache.all` (`/admin/contactus/setting/...`) — `Admin\Contactus\ContactusSettingController`

**Log action:** module_code `contactus.setting` — `update` (บันทึกตั้งค่า), `clear` (ล้างแคช)

ทะเบียนคีย์ทั้งหมดอยู่ที่ **`App\Support\ContactusSetting`** (`defaults()` / `rules()` / `all()` / `formFields()` / `formEnabled()`)
— เพิ่มคีย์ = แก้ `defaults()` + `rules()` + หน้า `Pages/Admin/Contactus/Setting/Index.vue` (ตัวเลือกฝั่งจอ `utils/contactus.ts`)
บันทึกแบบลบทั้งกลุ่มแล้ว insert ใหม่ (เหมือนตั้งค่า popup/บทความ); `ContactusSeeder` (ส่วนหนึ่งของข้อมูลตัวอย่าง `SampleDataSeeder`) บันทึกทุกคีย์จาก `ContactusSetting::defaults()` แล้วปรับเป็นชุดสาธิต
(รูปแผนที่, แบบฟอร์มครบทุกฟิลด์ — แสดงได้เพราะ `SiteSettingSeeder` ใส่ **คีย์ทดสอบของ Turnstile** ไว้ ต้องเปลี่ยนก่อนใช้งานจริง); ไม่มีข้อความติดต่อตัวอย่าง

| คีย์ | ค่า / ค่าเริ่มต้น | หมายเหตุ |
|------|-------------------|----------|
| `display_type` | `stacked` / `split_info` (default) / `half` | เลือกด้วยการ์ดภาพ SVG (`ContactusDisplayTypePicker.vue`) |
| `{part}_font_size` / `_font_family` / `_bold` / `_italic` / `_underline` / `_color` | part = `owner`, `address`, `phone`, `fax`, `mobile`, `email` | owner 24px ตัวหนา ดำ, อื่น ๆ 16px `#374151` ไม่เอียง/ไม่ขีดเส้นใต้; ฟอนต์จาก `PageTextStyle::fontNames()` — UI `Components/Admin/Contactus/ContactusTextFields.vue`: แถวเดียว ขนาด + ฟอนต์ + "จัดรูปแบบ" (ปุ่มไอคอน ตัวหนา/ตัวเอียง/ขีดเส้นใต้ `Components/Admin/FontStyleToggles.vue`) แล้วสีด้านล่าง |
| `show_{part}` | `Y`/`N` (default `Y`) | ไม่มี `show_owner` — ชื่อเจ้าของแสดงเสมอ |
| `show_social` | `Y` | ไอคอน Social Media จากตั้งค่าระบบ |
| `show_map_image` + `map_image_id` | `N` + ว่าง | รูปแผนที่จากโมดูลจัดการไฟล์ (บังคับเลือกเมื่อแสดง) |
| `show_google_map` + `latitude` / `longitude` | `N` + ว่าง | บังคับกรอกเมื่อแสดง (−90..90 / −180..180) — กรอกเอง หรือปุ่ม **"เลือกจากแผนที่"** เปิด `MapPickerDialog.vue` (Leaflet + OpenStreetMap ไม่ต้องใช้ key: คลิกวางหมุด/ลากหมุด/ตำแหน่งปัจจุบัน แล้ว "ใช้พิกัดนี้") |
| `show_form` | `Y` | |
| `form_{field}_show` / `form_{field}_required` | position N/N, company N/N, phone Y/N, email Y/Y, subject Y/Y, detail Y/Y | ช่อง "จำเป็นต้องกรอก" แสดงเมื่อติ๊กแสดง; ชื่อ - นามสกุลแสดง+บังคับเสมอ (ไม่มีคีย์) |

**คำเตือนบนหน้าตั้งค่า** (ลิงก์ไปตั้งค่าระบบถ้ามีสิทธิ์ `system.setting.manage`, ไม่งั้นให้ติดต่อผู้ดูแลระบบ):
- ติ๊กแสดง Google Map แต่ยังไม่มี API Key (`google_map.api_key`) → การแสดงแผนที่ที่หน้าบ้านจะดูไม่เรียบร้อย
- ติ๊กแสดงแบบฟอร์มแต่ยังไม่ได้ตั้งค่า Turnstile → หน้าบ้านจะไม่แสดงแบบฟอร์มเพื่อความปลอดภัย

**ล้างแคช:** ตั้งค่า (`Setting::forget('contactus')`) / หน้าบ้าน (`FrontCache::forgetAll()`) / ทั้งหมด — และมีปุ่ม
"ล้างแคช - ตั้งค่าติดต่อเรา" ในหน้าล้างแคชของตั้งค่าระบบด้วย (`contactus` อยู่ใน `Setting::GROUPS`)

---

## 2. ข้อมูลติดต่อเรา (หลังบ้าน)

**Permission code:** `contactus.item.view` (รายการ/รายละเอียด), `contactus.item.manage` (บันทึกสถานะ/หมายเหตุ) — ไม่มีเพิ่ม/ลบ
**Route:** `admin.contactus.item.index` / `.edit` / `.update` — `Admin\Contactus\ContactusItemController`
**Log action:** module_code `contactus.item` — `view` (เปิดรายละเอียด), `update` (`value_string` = "ชื่อ - หัวข้อ")

### Data model — `contactus_item` (migration `2026_10_06_000001_create_contactus_tables.php`)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| `fullname` | `varchar(255)` | บังคับเสมอ |
| `position` / `company` / `email` / `subject` | `varchar(255)` nullable | ฟิลด์ที่ตั้งค่าซ่อนไว้ตอนส่ง = null |
| `phone` | `varchar(50)` nullable | |
| `detail` | `text` nullable | plain text (สูงสุด 5,000 ตัวอักษร) |
| `lang` | `varchar(5)` | ภาษาของหน้าที่กรอก (จาก route) |
| `remote_ip` / `agent` | `varchar(45)` / `varchar(500)` | `ClientIp::from()` / User-Agent |
| `process_status` | `varchar(20)` default `unread` | `unread` ยังไม่ได้อ่าน / `read` อ่านแล้ว / `considering` พิจารณา / `done` เสร็จสิ้น (`ContactusItem::PROCESS_STATUSES`) |
| `note` | `text` nullable | บันทึกความเห็น/หมายเหตุ |
| `read_at` / `read_by` | nullable | เปิดอ่านครั้งแรก |
| `status`, `created_by` (null เสมอ), `updated_by`, `deleted_by`, timestamps, softDeletes | | ตาม convention |

Model `App\Models\ContactusItem` — **`$guarded = ['*']`** (ไม่มี mass assignment) กำหนด attribute ทีละตัวใน controller

### หน้าจอ
- **รายการ** (`Pages/Admin/Contactus/Item/Index.vue`) — คอลัมน์ ชื่อ - นามสกุล / อีเมล / หัวข้อ / วันเวลาที่ส่ง / สถานะ (pill สี, ยังไม่ได้อ่าน = ตัวหนา)
  ค้นหา 1 ช่อง (ชื่อ, อีเมล, หัวข้อ, ตำแหน่ง, บริษัท, เบอร์ติดต่อ) + ช่วงวันที่ส่ง + สถานะ, เรียงตามวันที่ส่งล่าสุดเป็นค่าเริ่มต้น, คลิกแถวไปหน้ารายละเอียด
- **รายละเอียด/แก้ไข** (`Edit.vue`) — การ์ด "ข้อมูลการติดต่อ" (ทุกฟิลด์เสมอ แม้ตั้งค่าซ่อนไว้ — ข้อความแสดงแบบ text ห้าม `v-html`),
  "บันทึกความเห็น / หมายเหตุ" (สถานะ + หมายเหตุ, ปิดแก้ไขถ้าไม่มี `contactus.item.manage`), "ข้อมูลระบบ" (`SystemInfoCard` + prop
  `hide-created`: วันเวลาที่ส่ง / ปรับปรุงล่าสุด / ปรับปรุงโดย / IP Address / กรอกจากภาษา)
- เปิดรายการที่ `unread` ครั้งแรก → เปลี่ยนเป็น `read` + `read_at/read_by` อัตโนมัติ (ไม่นับเป็นการปรับปรุง ไม่แตะ `updated_*`)

---

## 3. หน้าติดต่อเรา (หน้าบ้าน)

`Front\Contactus\ContactusController` — `GET /{lang}/contactus` (`front.contactus.item`), `POST /{lang}/contactus`
(`front.contactus.item.store`, `throttle:10,1`) → `Pages/Front/Contactus/Item.vue` + `Components/Front/Contactus/{ContactInfo,ContactMap,ContactForm}.vue`

- **รูปแบบการแสดงผล:** `stacked` ข้อมูลติดต่อ (กึ่งกลาง) → รูปแผนที่ → Google Map → แบบฟอร์ม · `split_info` [ข้อมูลติดต่อ | แผนที่ + Google Map]
  แล้วแบบฟอร์มด้านล่าง · `half` [ข้อมูลติดต่อ + แผนที่ + Google Map | แบบฟอร์ม] — ส่วนที่ไม่มีข้อมูลยุบเป็นคอลัมน์เดียว
- ข้อมูลการแสดงผลอยู่ใน `FrontCache` (`contactus.{lang}`); form token สร้างใหม่ทุก request (ไม่ cache)
- **รูปแผนที่:** คลิกแล้ว **ดาวน์โหลดไฟล์** (`front.file.download`) มีแถบพื้นทึบคาดด้านล่างบนรูป "คลิกเพื่อดาวน์โหลดแผนที่"
- **Google Map:** `App\Support\GoogleMap::embedUrl()` — มี API Key = Maps Embed API (`/maps/embed/v1/place`), ไม่มี = ลิงก์ embed แบบไม่ใช้ key
  (แสดงได้แต่ไม่เรียบร้อย) + **กล่องรายละเอียดสถานที่มุมซ้ายบน** (ชื่อเจ้าของ + ที่อยู่ จากตั้งค่าระบบ) พร้อมปุ่ม "เส้นทาง"
  (`GoogleMap::directionsUrl()` = `/maps/dir/?api=1&destination=lat,lng` เปิดหน้าใหม่)
- ส่วนหัว/breadcrumb: เมนูประเภท `contactus` (§4) ผ่าน `FrontMenuResolver::findFor($lang, 'contactus', 0)`; ไม่มีเมนู = หัวเรื่อง "ติดต่อเรา"
- ข้อความ UI `lang/{th,en}/front.php` กลุ่ม `contactus_form`

### ความปลอดภัยของแบบฟอร์ม (`App\Http\Requests\Front\StoreContactusRequest`)
1. แบบฟอร์มปิด / Turnstile ไม่ครบ → 404 ก่อนตรวจอะไรทั้งนั้น
2. ตัดช่องว่างหัวท้าย + ตัวอักษรควบคุม (รายละเอียดเก็บขึ้นบรรทัดใหม่/แท็บ); ทุกฟิลด์มีความยาวสูงสุด; อีเมล `email:rfc`; เบอร์ติดต่อรับเฉพาะตัวเลขและ `+ - ( ) # . , /` ช่องว่าง
3. rules สร้างจากตั้งค่า — ฟิลด์ที่ซ่อนไม่ถูกบันทึกแม้ส่งมา (`contactData()`), `lang` มาจาก route ไม่รับจาก input, สถานะ/หมายเหตุ/IP ไม่รับจาก input
4. จำกัด 5 ครั้ง / 10 นาที ต่อ IP (`RateLimiter` ด้วย `$request->ip()` ที่เคารพ TrustProxies — ไม่ใช้ header ที่ปลอมได้) + `throttle:10,1` ที่ route
5. `form_token` = เวลาที่เปิดฟอร์ม (เข้ารหัสด้วย `Crypt`) ต้องผ่านไป ≥ 3 วินาที และ ≤ 2 ชั่วโมง (กันบอทที่ยิงทันที/ใช้ token เก่า/ปลอม)
6. Cloudflare Turnstile — `Turnstile::verify()` (fail-closed) ใช้ `Turnstile::configured()` (key ครบ ไม่ผูกตัวเลือก CAPTCHA ของหน้า login)
7. honeypot `website` (ซ่อนจากผู้ใช้/screen reader) มีค่า = ตอบเหมือนสำเร็จแต่ไม่บันทึก
8. CSRF ปกติ (ไม่ยกเว้น); ข้อมูลเก็บเป็น plain text และแสดงผลด้วยการ escape ของ Vue เสมอ

WCAG: label ผูกทุกช่อง, `aria-required`/`aria-invalid`/`aria-describedby` ของ error, โฟกัสช่องแรกที่ผิด, ข้อความสำเร็จ `role="status"`,
iframe มี `title`

---

## 4. เมนูประเภท "ติดต่อเรา" และเมนูที่ไม่แสดง

- `FrontMenuType::CONTACTUS` (`contactus`, "ติดต่อเรา") — ไม่มี id ปลายทาง ลิงก์ = `FrontUrl::contactus($lang)`; อยู่ใน `LINKABLE`
  (ปุ่ม "อ่านทั้งหมด" ของ widget ใน page เลือกได้) และ `PopupItemInfo::MENU_TYPES` (popup แบบเลือกเมนูเลือกได้) ฝั่งจอมีชุดตั้งค่าส่วนหัวเหมือนเมนูเนื้อหา
  (`CONTENT_MENU_TYPES` ใน `utils/frontMenu.ts`)
- **เมนูที่ไม่แสดง** (`status = N` หรือพาเรนต์ถูกซ่อน) = ไม่อยู่ในแถบเมนูเท่านั้น — ถ้าหน้าปัจจุบันตรงกับเมนูนี้ ยังใช้ตั้งค่าเมนู (รูปภาพ/หัวเรื่อง/breadcrumb)
  ตามปกติ แต่ **breadcrumb = หน้าแรก > เมนูตัวเอง** (ไม่ไล่พาเรนต์); เมนูที่แสดงอยู่ชี้ปลายทางเดียวกันได้ก่อนเสมอ;
  popup แบบเลือกเมนูทำงานกับเมนูที่ไม่แสดงได้ (activeMenuIds = เมนูตัวเอง); ปุ่ม "อ่านทั้งหมด" ที่ชี้เมนูที่ไม่แสดงยังไม่แสดงปุ่มเหมือนเดิม
  — ดู `FrontMenuResolver::build()` / `findFor()`

---

## 5. ตั้งค่าระบบ — Google Map

กลุ่ม `google_map` ในหน้าตั้งค่าระบบ: `api_key` (Maps Embed API — แนะนำจำกัด HTTP referrer เฉพาะโดเมนเว็บ)
route `admin.system.setting.update.google_map`, request `UpdateGoogleMapSettingRequest`, ปุ่มล้างแคชในหน้าล้างแคชของตั้งค่าระบบ

---

## Roadmap
- แจ้งเตือนผู้ดูแลทางอีเมลเมื่อมีข้อความใหม่ (ใช้ SMTP ของตั้งค่าระบบ) + ตั้งค่าอีเมลผู้รับ
- ตัวนับ "ยังไม่ได้อ่าน" บนเมนู sidebar
- ส่งออกรายการ (CSV/Excel)
