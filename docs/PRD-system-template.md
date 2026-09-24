# PRD — โมดูลจัดการ Template (system.template)

> สถานะ: 🟡 schema + หลังบ้านครบ (รายการ / เพิ่ม / แท็บข้อมูลทั่วไป / แท็บโครงสร้างพร้อมตัวอย่าง / Custom CSS/JS / หน้า Loading) —
> การ render template จริงที่หน้าบ้านยังไม่ทำ (ดู §Roadmap ท้ายเอกสาร)
> ลิงก์กลับ: [PRD-overview.md](PRD-overview.md), [PRD-system.md](PRD-system.md) §4

## 0. ภาพรวมโมดูล

กำหนดหน้าตาส่วนกลางของเว็บหน้าบ้าน แบ่งเป็น 4 โซน — **header**, **main body**, **footer**, **aside (เมนูข้าง)** —
โดยไม่ต้องเขียนโค้ด ตั้งค่าทั้งหมดผ่านตัวเลือกที่เตรียมไว้ พร้อมตัวอย่าง (preview) ที่ใช้ข้อมูลจริงของระบบ

- สร้าง template ได้หลายรายการ แต่ **ใช้งานได้ครั้งละ 1 รายการ** และ **ต้องมีรายการที่ใช้งานอยู่เสมอ**
- ตอนเพิ่ม เลือก **แม่แบบตั้งต้น** (preset) เป็นจุดเริ่มต้นของค่าทุกโซน แล้วปรับต่อได้อิสระ
- ข้อมูลของไซต์ (โลโก้, ชื่อเว็บ, ข้อมูลติดต่อ, Social Media, ภาษา, ลิขสิทธิ์) **ไม่เก็บใน template** — อ่านจากหน้าตั้งค่าระบบ
  (`sys_setting` กลุ่ม `site` / `contact` / `social`) ส่วนรายการเมนูอ่านจากโมดูลเมนูหน้าบ้าน (`front_menu_*`)
  template เก็บเฉพาะ "จะแสดงอะไร แสดงอย่างไร"

ต่างจากระบบเดิม (`sys_template` ตารางเดียว + `LayoutParams`/`ThemeParams` เป็นข้อความ, จัด position/widget แบบ Joomla รุ่นเก่า)
ระบบนี้ไม่มีการสร้าง position/widget เอง — เนื้อหาของแต่ละหน้าจัดที่โมดูลหน้าเพจ (page) แทน template ดูแลแค่โครงส่วนกลาง

## 1. Data model

Migration: `database/migrations/2026_09_30_000001_create_sys_template_tables.php` — ตาราง 5 ตาราง ตาม convention
(`status` char(1), audit `created_by`/`updated_by`/`deleted_by` ไม่มี FK, คอลัมน์แบนไม่ใช้ JSON, ชื่อ FK ตั้งเองให้สั้นกว่า 64 ตัวอักษร)

### `sys_template` (ข้อมูลทั่วไป + Custom CSS/JS + หน้า Loading)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| `id` | `id()` | |
| `name` | `varchar(250)` | |
| `preset` | `varchar(30)` nullable | แม่แบบที่เลือกตอนสร้าง (แสดงเป็นข้อมูลอ้างอิงเท่านั้น ไม่ผูกค่ากันต่อ) |
| `custom_css_status` / `custom_css` | `char(1)` N / `mediumText` | แท็บ Custom CSS/JS |
| `custom_js_status` / `custom_js` | `char(1)` N / `text` | |
| `loading_status` | `char(1)` N | แท็บหน้า Loading |
| `loading_type` | `varchar(20)` `spinner` | `spinner` = ตัวหมุนของระบบ / `image` = รูปจากไฟล์ |
| `loading_spinner` | `varchar(20)` `ring` | `ring` / `dots` / `bar` |
| `loading_color` / `loading_background_color` | `varchar(20)` | hex (พื้นหลังเลือก `transparent` ได้) |
| `loading_image_id` | FK → `file_info` nullOnDelete | บังคับเมื่อ `loading_type=image` และเปิดใช้งาน |
| `layout_updated_at` / `layout_updated_by` | | วัน/ผู้บันทึกแท็บโครงสร้างล่าสุด |
| `status` | `char(1)` default `N` | `Y` = template ที่ใช้งานอยู่ — มีได้แถวเดียว บังคับในโค้ด |
| audit + timestamps + softDeletes | | |

### ตารางตั้งค่าโซน (1:1 กับ `sys_template`, PK = `sys_template_id` FK `cascadeOnDelete`)

ทุกตารางโซนมีชุดพื้นหลังเดียวกับโมดูล page: `background_color`, `background_image_id` (FK `file_info` nullOnDelete),
`background_repeat` / `_size` / `_attachment` / `_position` (เพิ่ม attachment จากที่ร้องขอ เพื่อใช้ `BackgroundFields.vue` ซ้ำได้ตรง ๆ)
+ `created_by`/`updated_by` + timestamps (ไม่มี softDeletes — อายุเท่ากับ template)

**`sys_template_header`**

| กลุ่ม | คอลัมน์ | ค่า |
|---|---|---|
| การแสดง | `status` | Y/N |
| รูปแบบ | `layout_type` | `topbar_main` (แถบบน + แถบหลัก) / `main_menubar` (แถบหลัก + แถวเมนู) / `main_only` (แถบหลักเท่านั้น) |
| | `sticky` | Y = แสดง header เสมอ (เลื่อนลงยังติดด้านบน) |
| โลโก้ | `logo_status`, `logo_align` (left/center/right), `logo_display` (`image` / `image_name` / `name`), `logo_action` (`none` / `home`) | |
| เมนู | `menu_align`, `menu_style` (ดู §2), `menu_text_color`, `menu_active_color` | สีเมนูเพิ่มจากที่ร้องขอ — จำเป็นเมื่อพื้นแถบเป็นสีเข้ม |
| ภาษา | `lang_status`, `lang_display` (`code` / `flag` / `flag_code`), `lang_select` (`all` / `dropdown`) | ภาษาที่แสดง = `site.lang_selected` |
| Social / ค้นหา | `social_status`, `search_status` | |
| ปรับตัวอักษร | `fontsize_status`, `fontsize_display` (`icon` = ไอคอน + , - / `text` = ตัวอักษร ก ก ก) | |
| การแสดงสี | `contrast_status`, `contrast_display` (`icon` / `text`) | |
| แถบหลัก | `main_width` (`full`/`container`), `main_text_color`, พื้นหลังชุดเต็ม | |
| แถบบน | `topbar_width`, `topbar_background_color`, `topbar_text_color` | ใช้เมื่อ `layout_type = topbar_main` |
| แถวเมนู | `menubar_width`, `menubar_background_color` | ใช้เมื่อ `layout_type = main_menubar` (สีตัวอักษร = `menu_text_color`) |

**`sys_template_body`** — พื้นหลังชุดเต็มเท่านั้น

**`sys_template_footer`**

| กลุ่ม | คอลัมน์ |
|---|---|
| การแสดง | `status` |
| รูปแบบ | `layout_type` (`site_contact_menu` / `site_contact_center` / `site_contact_block`), `width`, พื้นหลังชุดเต็ม |
| หัวข้อ | `heading_color`, `heading_font_size`, `heading_font_family`, `heading_bold` |
| เนื้อหา | `text_color`, `text_font_size`, `text_font_family`, `text_bold` |
| ข้อมูลที่แสดง | `show_address`, `show_phone`, `show_fax`, `show_mobile`, `show_email`, `show_social`, `show_menu` (`show_menu` ใช้กับ `site_contact_menu`) |
| แถบลิขสิทธิ์ | `copyright_status`, `copyright_width`, `copyright_background_color`, `copyright_text_color`, `copyright_font_size`, `copyright_font_family`, `copyright_align`, `copyright_show_owner` |

ฟอนต์ใช้ชุดฟอนต์ไทยเดียวกับโมดูล page (`App\Support\PageTextStyle::fontNames()`)

**`sys_template_aside`** — `status` (ใช้งานเมนูข้าง), `toggle_position` (left/right), `display_type` (`fullscreen` / `drawer`),
พื้นหลังชุดเต็ม, `text_color` (เพิ่มจากที่ร้องขอ), `menu_style` (ดู §2)

### Model / Support class

- `App\Models\SysTemplate` (SoftDeletes; `header()` / `body()` / `footer()` / `aside()` hasOne, `loadingImage()`)
- `App\Models\SysTemplateHeader` / `Body` / `Footer` / `Aside` (`$primaryKey = 'sys_template_id'`, `backgroundImage()`)
- `App\Support\Template\TemplateZone` — **ทะเบียนฟิลด์ของทุกโซนที่เดียว** (คอลัมน์ ⇒ ค่าเริ่มต้น + กฎ validation) ใช้ทั้งสร้างค่าเริ่มต้น,
  validation ของแท็บโครงสร้าง, ส่งค่าไปหน้าจอ และบันทึก — **เพิ่มคอลัมน์ใหม่ต้องแก้ 3 ที่ให้ตรงกัน**: migration, `TemplateZone::fields()`,
  interface ใน `resources/js/utils/template.ts` (+ UI ใน dialog / preview)
- `App\Support\Template\TemplatePreset` — แม่แบบตั้งต้น (ค่าที่ต่างจากค่าเริ่มต้นของแต่ละโซน)
- `App\Support\FrontMenuTree::forLanguage()` — tree เมนูหน้าบ้านที่เปิดใช้ (ชื่อ + `menu_type` ไม่มี URL) สำหรับ preview

## 2. ตัวเลือกที่ออกแบบเพิ่ม

**รูปแบบเมนูใน header (`menu_style`)** — เมนูย่อยแสดงเป็น dropdown ทุกแบบ
- `plain` ตัวอักษรเรียบ — เมนูที่เลือกอยู่เปลี่ยนเป็นสี `menu_active_color`
- `underline` เส้นใต้ — เมนูที่เลือกอยู่/ชี้อยู่มีเส้นใต้
- `pill` พื้นมน — เมนูที่เลือกอยู่มีพื้นหลังมน (สี active โปร่ง 22%)
- `divider` คั่นด้วยเส้น — มีเส้นตั้งคั่นระหว่างเมนู

**รูปแบบเมนูใน aside (`menu_style`)**
- `list` รายการ — ทุกระดับเรียงลงมา มีเส้นคั่น เมนูย่อยเยื้องเข้าไป
- `accordion` ย่อ/ขยาย — กดเมนูแม่เพื่อขยายเมนูย่อย
- `drilldown` เลื่อนเข้าเมนูย่อย — แสดงทีละระดับ กดแล้วเลื่อนเข้า มีปุ่มย้อนกลับ (เหมาะกับเมนูหลายระดับบนมือถือ)
- `large` ตัวอักษรใหญ่ — เฉพาะระดับแรกตัวใหญ่กึ่งกลาง (เหมาะกับ `display_type = fullscreen`)

**แม่แบบตั้งต้น (`preset`)**

| ค่า | ชื่อ | ลักษณะ |
|---|---|---|
| `classic` | องค์กร / หน่วยงาน | แถบบนสีเข้ม (social ซ้าย / ภาษา + ค้นหา + ตัวอักษร + สี ขวา) + แถบหลักขาว โลโก้ซ้าย เมนูขวาแบบเส้นใต้, footer 3 ส่วนพื้นเข้ม, aside แถบข้างแบบย่อ/ขยาย |
| `corporate` | แถวเมนูเด่น | แถบหลักขาว + แถวเมนูสีแบรนด์เต็มจอแบบพื้นมน, footer บล็อกพื้นอ่อน |
| `centered` | จัดกึ่งกลาง | โลโก้และเมนูกึ่งกลาง คั่นเมนูด้วยเส้น, footer กึ่งกลาง, aside ซ้ายแบบเลื่อนเข้าเมนูย่อย |
| `minimal` | เรียบง่าย | แถบหลักแถบเดียว ติดด้านบน เมนูเรียบ ไม่มี social/เครื่องมือช่วยอ่าน, footer ขาวกึ่งกลาง, aside เต็มจอตัวใหญ่ |
| `dark` | โทนมืด | header / footer / aside โทนมืด เนื้อหาพื้นอ่อน |

**ตำแหน่งองค์ประกอบใน header ตามรูปแบบ** — `topbar_main`: social ซ้าย + เครื่องมืออื่นขวาอยู่บนแถบบน, โลโก้ + เมนูอยู่แถบหลัก ·
`main_menubar`: โลโก้ + เครื่องมืออยู่แถบหลัก, เมนูอยู่แถวเมนู · `main_only`: ทุกอย่างอยู่แถบหลัก ·
แถบหลักแบ่ง 3 ช่อง ซ้าย/กลาง/ขวา: โลโก้อยู่ช่องตาม `logo_align`, เมนูอยู่ช่องตาม `menu_align`, เครื่องมือต่อท้ายช่องขวา,
ไอคอนเปิด aside อยู่ขอบฝั่งตาม `aside.toggle_position` (แสดงเมื่อ `aside.status = Y`)

## 3. เงื่อนไข/การทำงาน

- **ใช้งานได้ครั้งละ 1 รายการ** — เปิดใช้งานรายการใดก็ตาม (ปุ่ม "เปิดใช้งาน" ในหน้ารายการ, checkbox ในแท็บข้อมูลทั่วไป, ติ๊กตอนเพิ่ม)
  ปิดรายการอื่นทั้งหมดในทรานแซกชันเดียวกัน
- **ต้องมีรายการที่ใช้งานอยู่เสมอ** — ปิดรายการที่ใช้งานอยู่ตรง ๆ ไม่ได้ (`UpdateTemplateRequest` error ที่ `status`) และลบไม่ได้
  (`destroy` ตอบ error `delete`); ยังไม่มีรายการที่ใช้งานอยู่เลย = รายการที่สร้างใหม่ถูกเปิดใช้งานอัตโนมัติ
- สร้าง template = สร้างแถวของทั้ง 4 โซนทันทีจากแม่แบบ (`TemplatePreset::zones()`); บันทึกแท็บโครงสร้าง = บันทึกทั้ง 4 โซนพร้อมกัน
  (โซนที่ไม่มีแถวด้วยเหตุผลใดก็ตาม ถูกสร้างใหม่จากค่าเริ่มต้น)
- ลบ = soft delete เฉพาะ `sys_template` (แถวโซนคงอยู่ ไม่มีผลเพราะอ่านผ่าน template เสมอ)

## 4. หน้าจอจัดการ (admin)

| หน้า | route | ไฟล์ |
|---|---|---|
| รายการ | `admin.system.template.index` | `Pages/Admin/System/Template/Index.vue` — ค้นหาชื่อ / สถานะ / เรียง / แบ่งหน้า, ปุ่ม "เปิดใช้งาน" (ถามยืนยัน) + ลิงก์ไปแท็บโครงสร้าง |
| เพิ่ม | `admin.system.template.add` / `.store` | `Add.vue` — ชื่อ + เปิดใช้งานทันที + เลือกแม่แบบ (`PresetPicker.vue` การ์ด SVG) → บันทึกแล้วไปแท็บโครงสร้าง |
| ข้อมูลทั่วไป | `.edit` / `.update` / `.destroy` | `Edit.vue` — ชื่อ, แม่แบบตั้งต้น (อ่านอย่างเดียว), เปิดใช้งาน, ลบ |
| โครงสร้าง | `.layout` / `.layout.update` | `Layout.vue` |
| Custom CSS/JS | `.code` / `.code.update` | `Code.vue` — checkbox เปิด/ปิด + กล่องโค้ดแยก CSS / JS |
| หน้า Loading | `.loading` / `.loading.update` | `Loading.vue` — เปิด/ปิด, ประเภท, รูปแบบตัวหมุน/สี หรือรูปภาพ, สีพื้นหลัง + ตัวอย่างสด |
| เปิดใช้งาน | `.activate` (PUT) | จากหน้ารายการ |

แท็บ (TabNav) ของ 4 หน้าหลังสร้างจาก `templateTabs()` ใน `utils/template.ts`

**แท็บโครงสร้าง** (pattern เดียวกับหน้าโครงสร้างของ page)
1. ปุ่ม "บันทึกโครงสร้าง" + "กลับไปหน้ารายการ" ทั้งด้านบนและล่าง, แจ้ง "มีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก" + กันออกจากหน้า
2. ตรงกลางเป็นตัวอย่างหน้าเว็บ 4 โซน: Header / (Main Body + Aside ฝั่งตาม `toggle_position`) / Footer
3. แต่ละโซนมีแถบจัดการ (`ZoneToolbar.vue`) — **เฟือง** เปิด dialog ตั้งค่าของโซน, **ลูกตา** สลับแสดง/ซ่อนโซน (ค่า `status` เดียวกับใน dialog;
   Main Body ไม่มีลูกตาเพราะซ่อนไม่ได้)
4. dialog แก้บนสำเนา กด "ตกลง" แล้วตัวอย่างเปลี่ยนตามทันที — มีผลจริงเมื่อกด "บันทึกโครงสร้าง"
5. ตัวอย่างใช้ข้อมูลจริงของระบบ ณ ปัจจุบัน (`TemplateController::previewData()`) และกดไม่ได้ (`pointer-events-none`, ไม่มีลิงก์)
   ข้อมูลที่ยังไม่ได้ตั้งค่า (เช่น เบอร์แฟกซ์) แสดงเป็นข้อความจาง ๆ, ไม่มีเมนูหน้าบ้าน = แสดงเมนูสมมติ

**กติกาการแสดงผล (ใช้ทั้ง preview และตอน render หน้าบ้านในอนาคต)**
- **ขอบเขตความกว้าง** (`*_width`: เต็มหน้าจอ / ตาม container) มีผลกับ **ข้อมูลในแถบเท่านั้น** — พื้นหลังของแถบ/footer/แถบลิขสิทธิ์กว้างเต็มหน้าจอเสมอ
- **Social Media** แสดงเป็นไอคอนของแต่ละแบรนด์ (`Preview/SocialIcon.vue` — facebook / youtube / x / instagram / tiktok / line) เฉพาะช่องทางที่ตั้งค่าไว้
- **footer — ข้อมูลไซต์** แสดงโลโก้ (`site.logo_id`) คู่ชื่อเว็บ
- **footer — ติดต่อเรา** ขึ้นต้นด้วยชื่อเจ้าของไซต์ (`site.copyright_owner`) เฉพาะเมื่อตั้งค่าไว้ แล้วตามด้วยที่อยู่/เบอร์/อีเมลที่เลือกแสดง
- **footer — เมนู** แสดงเมนูระดับแรก + เมนูย่อยระดับที่สองเฉพาะที่เป็นลิงก์ (ของในระบบ `page` / `article_category` / `article_item`
  และลิงก์ภายนอก `external` — `LINK_MENU_TYPES` ใน `utils/template.ts`) แสดงเยื้องเป็นเมนูย่อย; เมนูหัวข้อ/ไม่มีลิงก์ และระดับที่ 3 ขึ้นไปไม่แสดง
- **aside** มีพื้นที่ fix ด้านล่าง (ไม่เลื่อนตามเมนู) แสดง social / ภาษา / ปรับขนาดตัวอักษร / การแสดงสี **ตามที่เปิดไว้ในตั้งค่า header**
  (ไม่มีค้นหา) — ไม่มีรายการใดเปิดเลย = ไม่มีพื้นที่นี้; ใน preview แผง aside สูงเท่าพื้นที่เนื้อหาพอดี เมนูที่ยาวเกินถูกตัดในส่วนบน

ส่วนประกอบใน `resources/js/Components/Admin/Template/`: dialog 4 ตัว (`HeaderSettingsDialog` ฯลฯ บนเปลือก `PageLayout/LayoutDialog.vue`),
ตัวเลือกแบบการ์ด SVG (`VisualPicker.vue` + `PresetPicker` / `HeaderLayoutPicker` / `HeaderMenuStylePicker` / `FooterLayoutPicker` /
`AsideDisplayPicker` / `AsideMenuStylePicker`), `SegmentedChoice.vue` (ตัวเลือกสั้น 2-3 ค่า), `YesNoCheckbox.vue` (แสดง/ซ่อนทุกจุดเป็น checkbox),
`FontStyleFields.vue`, และตัวอย่างใน `Preview/` (`HeaderPreview` / `HeaderMenu` / `HeaderTools` / `BodyPreview` / `FooterPreview` /
`AsidePreview` / `LanguageFlag`) — ใช้ซ้ำ `BackgroundFields.vue`, `ColorPickerInput`, `FilePickerField`, `SearchableSelect`

## 5. Permission / Route / Log

- Permission (seed ไว้แล้วใน `DatabaseSeeder`): `system.template.view` (ดูรายการ/ทุกแท็บ), `system.template.manage` (เพิ่ม/แก้ไข/เปิดใช้งาน/บันทึกทุกแท็บ),
  `system.template.delete` (ลบ) — เช็กในแต่ละ method ของ `App\Http\Controllers\Admin\System\TemplateController`
- เมนู sidebar `system-template` (`MenuSeeder`) ชี้ `admin.system.template.index`
- Route prefix `admin/system/template` ใน `routes/web.php`
- Log: `LogBackAccess::record()` ทุกหน้าจอ (หน้ารายการเฉพาะไม่มี query string); `LogBackAction::record('system.template', …)`
  — `create` (store), `view` (เปิดแท็บใด ๆ ของรายการ), `update` (บันทึกแท็บใด ๆ / เปิดใช้งาน), `delete`

## 6. ข้อมูลตัวอย่าง (`TemplateSeeder`)

เรียกจาก `DatabaseSeeder` ต่อจาก `FrontMenuSeeder` — 3 รายการจากแม่แบบ: "Template องค์กร / หน่วยงาน" (`classic`, ใช้งาน),
"Template แถวเมนูเด่น" (`corporate`), "Template เรียบง่าย" (`minimal`) — `updateOrCreate` ตามชื่อ รันซ้ำได้ (ค่าในโซนถูกรีเซ็ตเป็นค่าของแม่แบบ)
และไม่แย่งสถานะใช้งานถ้ามี template อื่นที่ผู้ใช้เปิดใช้งานไว้แล้ว

รันเดี่ยว: `php artisan db:seed --class=TemplateSeeder`

## Roadmap — การแสดงผลหน้าบ้าน (ยังไม่ทำในรอบนี้)

- `Layouts/Front/FrontLayout.vue` อ่าน template ที่ `status = Y` (share ผ่าน middleware / cache แบบ `Setting::group()` แล้วล้างแคชตอนบันทึก)
  แล้ว render header / body / footer / aside ตามค่าตั้งค่าเดียวกับ preview (แยก component หน้าบ้านไว้ `Components/Front/Template/*`)
- header: sticky, เมนูหลายระดับ (dropdown / flyout ตาม PRD-system-frontmenu.md), ตัวเลือกภาษาสลับ `{lang}`, ค้นหา,
  ปรับขนาดตัวอักษร (เก็บใน localStorage), โหมดสี (ปกติ / ขาวดำ / ตัดกันสูง)
- บนจอเล็ก เมนูใน header ยุบเข้า aside เสมอ (แม้ `aside.status = N` จะใช้ไอคอนเปิดเมนูแทน); ส่วนล่างของ aside ที่ fix ไว้ต้องเลื่อนเมนูด้านบนได้ (overflow-y auto)
- Custom CSS แทรกท้าย `<head>`, Custom JS แทรกท้าย `<body>` (เฉพาะเมื่อ `*_status = Y`), หน้า Loading แสดงจนกว่า `window.load`
- favicon / Google Analytics มีอยู่แล้วในตั้งค่าระบบ (`site.favicon_id`, `google_analytics.tracking_id`) ไม่ต้องย้ายมา template
