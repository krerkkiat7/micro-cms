# PRD — หน้าบ้าน (Front-office)

การแสดงผลหน้าเว็บสาธารณะ: Intropage, layout จาก template ที่เปิดใช้งาน, หน้าเพจ, หมวดหมู่/รายละเอียดบทความ, ประวัติการเข้าชม,
ยอดเข้าชม, cache — ออกแบบโดยคำนึงถึง WCAG / W3C / SEO-AEO-GEO / security / performance

สถานะ: 🟢 รอบแรกเสร็จ (branch `front-init`) — ดู [Roadmap](#roadmap) สำหรับสิ่งที่ยังไม่ทำ

---

## 1. URL / route

| URL | route name | controller | หมายเหตุ |
|-----|-----------|------------|----------|
| `/` | `front.root` | `Front\Intropage\IntropageController@root` | Intropage ของภาษาหลัก (ไม่ redirect ก่อน — canonical ชี้ `/{lang}`) |
| `/{lang}` | `front.home` | `…IntropageController@index` | Intropage ที่เผยแพร่อยู่ — ไม่มี → redirect ไปหน้าแรกตามเมนู (`front_menu_info.is_home = Y`) ไม่มีเมนูหน้าแรก → 404 |
| `/{lang}/page/item/{id}/{slug?}` | `front.page.item` | `Front\Page\PageItemController@show` | |
| `/{lang}/article/category/{id}/{slug?}` | `front.article.category` | `Front\Article\ArticleCategoryController@show` | `?q=`, `?sort=`, `?view=card\|row`, `?page=n` |
| `/{lang}/article/category/{id}/{article_id}/{slug?}` | `front.article.category.item` | `Front\Article\ArticleItemController@showInCategory` | ลงทะเบียนก่อน route หมวดหมู่ |
| `/{lang}/article/item/{id}/{slug?}` | `front.article.item` | `…ArticleItemController@show` | |
| `/{lang}/article/tag/{tag}` | `front.article.tag` | `Front\Article\ArticleTagController@show` | `{tag}` = ชื่อแท็ก (`where .+`), `?sort=`, `?view=`, `?page=` |
| `/file/get/{hash}` · `/file/type/download/get/{hash}` · `/file/type/thumbnail/size/{size}/get/{hash}` | `front.file.*` | `Front\FileController` | ดู §7 |
| `POST /front/access/ping` | `front.access.ping` | `Front\AccessLogController@ping` | keep-alive ของ `log_front_access` (ยกเว้น CSRF, throttle 60/นาที) |

- ชื่อ route ใช้ prefix `front.` ตาม convention (ผู้ใช้ระบุชื่อ `page.item`, `article.category`, `article.category.item`, `article.item`)
- **slug ไม่บังคับ** — ไม่มี/ผิดก็เปิดได้ (ค้นด้วย id) แต่ `<link rel="canonical">` ชี้ URL ที่มี slug ของภาษานั้นเสมอ
  slug ที่มี `/` ใช้ใน path ไม่ได้ → `FrontUrl` ตัดทิ้งให้เหลือ URL ที่มีแต่ id; slug หมวดหมู่ห้ามเป็นตัวเลขล้วน/มี `/`
  (validation หลังบ้าน `not_regex`) เพราะจะชนกับ `{article_id}`
- เงื่อนไข "เผยแพร่" (ไม่ผ่าน = 404):
  - หน้าเพจ / หมวดหมู่: `status = Y` และไม่ถูกลบ
  - บทความ: `status = Y`, ไม่ถูกลบ, `publish_date <= now` (หรือว่าง), `publish_down > now` (หรือว่าง) — ผ่านหมวดหมู่ต้องอยู่ในหมวดหมู่นั้น
    และหมวดหมู่ต้องเผยแพร่
  - **ข้อมูลตัวอย่าง `is_temp = Y` แสดงได้ตามปกติทุกโมดูล** (ผู้ใช้อาจแก้ตัวอย่างแล้วใช้ต่อ — ไม่มี query หน้าบ้านตัวไหนกรอง `is_temp`)
- ภาษาที่ยังไม่ได้แปล (หัวเรื่องว่าง) ใช้ข้อมูลภาษาหลัก (`App\Support\Front\FrontLang`)
- หน้า error ของหน้าบ้าน (403/404/419/429/500/503) render `Front/Error.vue` ใน layout หน้าบ้าน (`App\Support\Front\FrontErrorPage`
  ผูกใน `bootstrap/app.php`) — 500 ตอน `APP_DEBUG=true` ยังใช้หน้า debug ของ Laravel

## 2. Intropage

- เลือกตามกติกา `docs/PRD-intropage.md` §3 (`App\Support\Front\IntropageResolver`) — แสดงทุกครั้งที่เข้า `/` หรือ `/{lang}`
- layout เปล่า `Layouts/Front/IntroLayout.vue` (ไม่ใช้ sys_template) ใช้เฉพาะข้อมูลระบบร่วม (ชื่อไซต์/ภาษา/ข้อความ UI)
- หน้าจอ `Pages/Front/Intropage/Index.vue` — สื่อหลัก (รูป / วิดีโอไฟล์ / วิดีโอ URL / YouTube แบบ youtube-nocookie) **ชิดขอบบนสุด**
  ตาม `display_size`, ข้อความต้อนรับ (ฟอนต์/ขนาด/สีตามตั้งค่า), ปุ่ม (ฟอนต์/ขนาดตามตั้งค่า — `home` = URL หน้าแรกตามเมนู, `other` = URL ที่ตั้ง),
  พื้นหลังตามตั้งค่า; **ไม่แสดงชื่อ** — ชื่อเป็น h1 ที่ซ่อนไว้ (sr-only) สำหรับ screen reader/SEO

## 3. Layout หน้าภายใน (`Layouts/Front/FrontLayout.vue`)

- ข้อมูลจาก `App\Support\Front\FrontLayoutData::forLanguage()` ส่งเป็น prop `front` ทุกหน้า (ผ่าน `Front\FrontController::render()`):
  template ที่เปิดใช้งาน (4 โซนผ่าน `TemplateZone::toArray()` — ไม่มี template ใช้ค่าเริ่มต้น), ชื่อไซต์/โลโก้, ติดต่อ, social (เฉพาะที่เป็น URL
  หรือ LINE ID), ลิขสิทธิ์, ภาษา, เมนู (`FrontMenuResolver`), URL หน้าแรก, ข้อความ UI (`lang/<code>/front.php` → `front.t`)
- Custom CSS/JS + หน้า Loading ของ template อ่านใน `app.blade.php` ตรง (`FrontLayoutData::assets()`) เฉพาะโหลดหน้าเต็ม ไม่ส่งซ้ำใน prop
- component: `Components/Front/Template/{FrontHeader,HeaderNavItem,A11yTools,LanguageSwitcher,SocialLinks,FrontAside,AsideMenuList,FrontFooter}.vue`
  — โครงเดียวกับตัวอย่างหลังบ้าน (`Components/Admin/Template/Preview/*`); `LanguageFlag`/`SocialIcon` ย้ายไป `Components/Template/` (ใช้ร่วม)
- เมนู header: ระดับแรกแนวนอน เมนูย่อยกางลง ระดับ 2+ เป็น flyout ด้านข้าง (disclosure button + `aria-expanded`, Esc ปิด);
  จอ < lg ซ่อนเมนูแนวนอนแล้วใช้ปุ่มเปิดแผงเมนูข้างเสมอ (แม้ปิด aside ในตั้งค่า)
- แผงเมนูข้าง: drawer/เต็มจอ × รูปแบบ list/accordion/drilldown/large, เป็น modal dialog (focus trap, Esc, คืนโฟกัส)
- ค้นหา: **ซ่อนไว้ก่อน** (ยังไม่มีหน้าค้นหา) · ขนาดตัวอักษร (4 ระดับ → `zoom` ที่ `<html>`) + การแสดงสี (ปกติ/ความคมชัดสูง/ขาวดำ)
  ใช้งานได้จริง จำค่าใน localStorage (`composables/useA11yPreferences.ts`, CSS `html.front-contrast-*` ใน `app.css`)
- โซน body: สี/รูปพื้นหลังเต็มความกว้างเสมอ → ส่วนหัว (`PageHero.vue` — รูป + หัวเรื่อง/หัวเรื่องรองตามเมนูที่ชี้มาหน้านี้, ตำแหน่ง 9 ทิศ,
  container/เต็มจอ) → breadcrumb (`Breadcrumbs.vue`, แสดงตาม `show_breadcrumb` ของเมนู) → เนื้อหาใน container **ยกเว้นหน้าเพจ**
  (`fullWidth` — หน้าเพจกำหนด container ต่อแถวเอง)
- หาเมนูของหน้า (`FrontMenuResolver::findFor()`): เมนูแรกตามลำดับการแสดงที่ชี้ปลายทางนี้ — บทความ: เมนูประเภทบทความก่อน ไม่มีใช้เมนูของหมวดหมู่;
  เมนูในเส้นทาง = active (`aria-current` ที่ตัวสุดท้าย)

## 4. หน้าเพจ (`Pages/Front/Page/Item.vue`)

- `App\Support\Front\PageLayoutReader` อ่าน แถว → คอลัมน์ → widget เฉพาะ `status = Y`, ข้อความตามภาษา, พื้นหลังเป็น URL, ฟอนต์ที่ใช้จริง
  (`PageTextStyle::stylesheetUrlFor()` — โหลดเฉพาะฟอนต์ที่หน้านั้นใช้)
- widget ที่ดึงรายการจากหมวดหมู่ใช้ `CategoryListWidget::frontItems($setting, $lang)` (query เดียวกับ preview หลังบ้าน แต่ตามภาษา, ไม่จำกัด 10,
  มีลิงก์จริง: บทความ → `front.article.category.item`, banner → `url` ที่ปลอดภัย + `link_target`) / Custom Text → `FrontParts`
- component: `Components/Front/PageLayout/{PageRow,PageWidget,BlockTexts,CarouselControls}.vue` + `widgets/{WidgetSlideshow,WidgetSlideset,WidgetGrid,WidgetCard,ReadAllLink}.vue`
  — หน้าตาตรงกับตัวอย่างหลังบ้าน; carousel ใช้ `composables/useCarousel.ts` (ปุ่มหยุด/เล่น, หยุดเมื่อเมาส์ชี้/โฟกัส, ไม่เล่นอัตโนมัติเมื่อ prefers-reduced-motion)
- หัวเรื่อง: h1 ชื่อหน้า (ซ่อน `sr-only`) → แถว h2 → คอลัมน์ h3 → widget h4 → รายการ/หัวข้อ part ใน widget h5
- จอ < md คอลัมน์เต็มแถว (`.front-col`), Grid ใช้จำนวนคอลัมน์ต่อขนาดจอผ่าน CSS variable (`.front-grid`)

## 5. บทความ

- ตั้งค่าที่หน้าบ้านใช้: `App\Support\ArticleSetting::listSetting()` / `detailSetting()` (ดู PRD-article.md §3) — prop `listSetting` / `detailSetting`
- หมวดหมู่ (`Pages/Front/Article/Category.vue`): h1 ชื่อหมวดหมู่ + intro/รายละเอียด (เฉพาะเมื่อเปิดในตั้งค่า — ค่าเริ่มต้นซ่อน, server ส่งค่าว่างถ้าปิด)
  แล้วต่อด้วย `Components/Front/Article/ArticleListView.vue` (ใช้ร่วมกับหน้าแท็ก): **แถวเครื่องมือเดียว** = ช่องค้นหาจากชื่อ (`?q=`, LIKE escape `%`/`_`)
  + เรียงลำดับ (`SearchableSelect`, `?sort=` ใหม่สุด/เก่าสุด/ก–ฮ/ฮ–ก/เข้าชมมาก/น้อย) + สลับการ์ด/แถว; แถวล่าง = จำนวนทั้งหมด + หน้า x จาก y
  + pagination (ลิงก์จริง `?page=n`). ค่าเท่าค่าเริ่มต้นของตั้งค่าไม่ใส่ใน URL; canonical ไม่รวม q/sort/view; cache key รวม sort + md5(q)
  `ArticleListItem.vue` แสดงรูป (อัตราส่วน/cover-contain/สีพื้น/`natural` เฉพาะแถว) + จำนวนบรรทัดหัวเรื่อง/เกริ่นนำ + วันที่/ยอดเข้าชม ตามตั้งค่าของมุมมองนั้น
- แท็ก (`Pages/Front/Article/Tag.vue`): h1 `แท็ก "ชื่อแท็ก"` (`front.tag_title`), ไม่มี intro/รายละเอียด/ค้นหา; `ArticleReader::tagIdsByName()`
  (แท็ก status Y ที่ชื่อตรงในภาษาใดก็ได้) → `listForTags()` (เฉพาะบทความที่หมวดหมู่เผยแพร่อยู่) ไม่พบ = "ไม่พบข้อมูล" สถานะ 200
- รายการทั้งสองแบบ query ผ่าน `ArticleReader::paginateList()` + `applySort()` ตัวเดียวกัน ลิงก์รายการไปหน้ารายละเอียดผ่านหมวดหมู่ (= canonical)
- รายละเอียด (`Pages/Front/Article/Item.vue`) ลำดับ: h1 ชื่อบทความ → วันที่ `<time datetime>` + ยอดเข้าชม + ปุ่มพิมพ์ (`window.print()`, ถ้าเปิด)
  → รูปหน้าปก (ถ้าเปิด) → แชร์ (บน) → part (`Components/Front/ContentPart/PartList.vue` — หัวข้อ part เป็น h2) → แชร์ (ล่าง) → แท็กเป็นลิงก์ไป `front.article.tag`
  (hover เปลี่ยนสี). **ไม่แสดงหมวดหมู่และข้อความเกริ่นนำ** (เกริ่นนำยังใช้เป็น meta description). แชร์ = `ShareButtons.vue` (Facebook / X / LINE /
  คัดลอกลิงก์ ด้วย canonical URL ที่ server ส่งมาเป็น `shareUrl`). ตอนพิมพ์ซ่อน header/hero/breadcrumb/footer ของ layout + ปุ่มแชร์/พิมพ์ (`print:hidden`)
- part: ข้อความ (rich text ผ่าน `HtmlSanitizer`), รูปเดี่ยว (alignment/size/caption), กลุ่มรูป **ครบ 7 รูปแบบ** (`PartImages.vue` — thumbnail carousel,
  multi-item carousel, grid + lightbox, full-width slider, masonry, justified, stacked cards; `Lightbox.vue` = modal dialog + คีย์บอร์ด), วิดีโอ
  (ไฟล์ / YouTube youtube-nocookie), เอกสาร (ดาวน์โหลด + ขนาด + ตัวอย่าง PDF) — ไม่เพิ่ม library
- canonical ของ 2 ทางเข้า (ผ่านหมวดหมู่ / ตรง) ชี้ URL เดียวกัน (ผ่านหมวดหมู่ของบทความ) กันเนื้อหาซ้ำ

## 6. SEO / AEO / GEO

`App\Support\Front\SeoMeta::make()` → prop `seo` — render ฝั่ง server ใน `app.blade.php` (HTML แรกมีครบ — crawler/บอท AI ที่ไม่รัน JS อ่านได้)
และ `Components/Front/SeoHead.vue` อัปเดตตอนเปลี่ยนหน้า (แท็ก `inertia="…"`):

- `<title>`, description (meta_description → intro_text → site_description), keywords (meta_keywords / แท็ก), robots, canonical
- hreflang ทุกภาษา + `x-default`, Open Graph (`og:type` article สำหรับบทความ, image, locale), Twitter card
- JSON-LD: `WebSite` + `Organization` (ชื่อ/โลโก้/ติดต่อ/social `sameAs`) ทุกหน้า + `BreadcrumbList` + ตามชนิดหน้า
  (`WebPage` / `CollectionPage` + `ItemList` / `Article` พร้อม datePublished/dateModified/image/publisher/จำนวนเข้าชม)
- ใช้ HTML เชิงความหมาย (header/nav/main/footer/article/section/address/time) — ช่วยทั้ง SEO และ assistive technology
- หน้า error `noindex`

## 7. ไฟล์/รูปภาพ

- URL ทั้งหมดของหน้าบ้านสร้างจาก `App\Support\Front\FrontFile` ที่เดียว (ฝั่ง Vue ไม่ประกอบ URL จาก hash เอง) → `/file/get/{hash_name}` ฯลฯ
  (สาธารณะ, Cache-Control public, thumbnail เฉพาะขนาดใน `config('front.thumbnail_sizes')`)
- **อนาคต — ไฟล์เฉพาะสมาชิก/บทความลับ**: เปลี่ยนที่ `FrontFile` (เช่น `URL::temporarySignedRoute`) + ตรวจสิทธิ์ต่อบทความใน `Front\FileController`
  โดยไม่ต้องแก้หน้าจอ; ไฟล์ที่ผูกกับบทความลับต้องไม่ถูกเสิร์ฟผ่าน route สาธารณะ (ตอนนี้ทุกไฟล์เข้าถึงได้ด้วย hash — ULID เดายาก)

## 8. ยอดเข้าชม (`article_item_view` / `page_item_view` + `view_amount`)

ปัญหาระบบเดิม: insert → select count → update ทุก request ทำให้ล็อกแถวเดิมซ้ำ ๆ จนเว็บหน่วงทั้งไซต์ — ออกแบบใหม่ (`App\Support\Front\ViewCounter`):

0. `user_id` ของทุกตารางหน้าบ้าน (`*_item_view`, `banner_item_click`, `log_front_access`) มาจาก `FrontAuth::id()` (guard `front`) —
   การ login หลังบ้านไม่ถูกนับเป็นผู้ใช้หน้าบ้าน (ตอนนี้หน้าบ้านยังไม่มี login จึงเป็น null)
1. ระหว่าง request ไม่แตะฐานข้อมูล: ข้ามบอท (`UserAgentParser`) + ข้ามการเปิดซ้ำของ session เดิมภายใน 5 นาที (เช็กใน session, `dedupe_minutes`)
2. หลังส่ง response (`defer()`): โหมด **redis** `RPUSH front:views:{article|page}`; โหมด **database** insert + update ตรง
3. `php artisan front:flush-views` (schedule ทุกนาทีใน `routes/console.php`) ดึงคิวทีละ 1,000 (Lua LRANGE+LTRIM atomic) → batch insert ตารางประวัติ
   1 คำสั่ง → `UPDATE … SET view_amount = view_amount + CASE id …` 1 คำสั่งต่อชุด (ไม่ count ตารางประวัติ) — ล้มเหลวใส่คืนคิว; คิวยาวเกิน
   `flush_threshold` flush เองทันทีโดยไม่รอ cron
4. `view_amount` อัปเดตผ่าน query builder — ไม่ล้าง cache หน้าบ้าน ไม่แตะ `updated_at`

ตั้งค่า `config/front.php` → `views.driver` (`auto` = redis ถ้า `CACHE_STORE=redis`), `dedupe_minutes`, `flush_threshold`
**ต้องตั้ง cron ที่ server:** `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`

ตาราง (migration `2026_10_01_000001_create_item_view_tables.php`): `article_item_view` / `page_item_view` — `*_item_info_id` (ไม่มี FK — insert เร็ว),
`user_id`, `lang`, `session_id`, `remote_ip`, `geo_ip`, `action_date`, `status`, audit, timestamps (`created_at` = เวลาเข้าชมจริง), softDeletes,
index (`*_item_info_id`, `action_date`); `page_item_info.view_amount` (ใหม่)

**การคลิก banner** (Slideshow / Slideset / Grid จาก banner) ใช้กลไกเดียวกัน — `ViewCounter` ประเภท `banner` → ตาราง `banner_item_click`
(migration `2026_10_03_000004_*`, โครงเดียวกับ `article_item_view` แทนตารางเดิมที่ไม่มี PK) + `banner_item_info.click_amount`
(`ViewCounter::TYPES[*]['amount']`). หน้าจอ: `PageWidget.vue` ดักคลิกลิงก์ (event delegation, รวมคลิกกลาง) ในกล่องรายการที่มี `data-item-id`
แล้ว `trackBannerClick()` (`utils/frontPage.ts`) ยิง sendBeacon → `POST front.banner.click` (`Front\Banner\BannerItemController@click`,
ยกเว้น CSRF + `throttle:60,1`, นับเฉพาะ banner `status = Y` ที่มี URL, ตอบ 204) — ลิงก์ในหน้ายังเป็น URL จริง ไม่ redirect ผ่านเซิร์ฟเวอร์;
ข้ามบอท + ไม่นับซ้ำใน session เดียวกันภายใน `dedupe_minutes` เหมือนยอดเข้าชม

## 9. ประวัติการเข้าชมหน้าบ้าน (`log_front_access`)

- โครงเดียวกับ `log_back_access` ทุกคอลัมน์ (ในไฟล์กลาง `2026_09_10_000001_create_log_tables.php`) — model `App\Models\LogFrontAccess`
- `LogFrontAccess::record($title)` เรียกทุก controller หน้าบ้าน; ไม่ต้อง login (`user_id` = null ถ้าไม่ได้ login); insert หลังส่ง response (`defer()`)
  โดยสร้าง token ไว้ก่อน → prop `accessLog.token` (ชื่อเดียวกับหลังบ้าน)
- keep-alive: `composables/useAccessHeartbeat.ts('front.access.ping')` (sendBeacon) → อัปเดต `last_visited` เฉพาะแถวที่ token + session_id ตรง
  และอายุไม่เกิน 1 วัน (แทน scope user_id ของหลังบ้าน)
- หลังบ้าน: "ประวัติการใช้งาน - หน้าบ้าน" `admin.system.frontlog.access.index` (`Admin\System\FrontLogAccessController`,
  `Pages/Admin/System/FrontLogAccess/Index.vue`) — เหมือนของหลังบ้าน + ตัวกรองผู้เข้าชม (บุคคล/บอท) + คอลัมน์อุปกรณ์;
  permission `system.frontlog.access` และเมนู seed ไว้แล้ว

## 9.1 Popup

prop `popups` ทุกหน้าที่ใช้ FrontLayout (ไม่รวม Intropage/หน้า error) กรองตามเมนูของหน้า (ตัวสุดท้ายของ `header.activeMenuIds`) —
`App\Support\Front\PopupResolver` (cache ต่อภาษา `popup.{lang}`, TTL `front.cache.popup_ttl`) + `Components/Front/Popup/*`
(modal/floating ซ้อนกันรายการแรกอยู่บนสุด, สไลด์, ไม่แสดงวันนี้อีก = localStorage) รายละเอียดดู [PRD-popup.md](PRD-popup.md) §3

## 10. Cache (`App\Support\Front\FrontCache`)

- key `front.v{version}.*` — ล้างทั้งหมดด้วยการเพิ่ม version (ไม่ `Cache::flush()`); TTL ใน `config/front.php`
  (shared 1 ชม. / เนื้อหา 5 นาที / intropage และ popup 60 วินาที — TTL เนื้อหาเป็นตัวกำหนดว่ารายการที่ถึง/หมดช่วงเผยแพร่ตามเวลาจะปรากฏ/หายช้าสุดกี่วินาที)
- cache: layout ต่อภาษา, เมนูต่อภาษา, assets ของ template, intropage, โครงหน้าเพจ (+ รายการ widget), รายการหมวดหมู่ต่อหน้า, รายละเอียดบทความ
  (การเช็กสถานะเผยแพร่ของบทความ/หน้า/หมวดหมู่ query สดทุกครั้ง)
- ล้างอัตโนมัติ: model ที่หน้าบ้านใช้ทั้งหมดใช้ trait `App\Models\Concerns\FlushesFrontCache` (saved/deleted/restored) + `Setting::forget()`
- ล้างเอง: หลังบ้าน ตั้งค่าระบบ → ล้างแคช → "ล้าง Cache หน้าบ้าน" (`admin.system.setting.clearcache.front`) และ "ล้างแคชทั้งหมด"

## 11. WCAG / W3C

- ลิงก์ "ข้ามไปยังเนื้อหาหลัก", landmark ครบ, `<html lang>` ตามภาษา (สลับภาษาโหลดหน้าเต็ม + ตั้ง lang ตอน mount), h1 หน้าละ 1 และลำดับหัวเรื่องต่อเนื่อง
- ทุกปุ่มไอคอนมี `aria-label`, ลิงก์เปิดหน้าต่างใหม่มีข้อความแจ้ง, รูปมี alt (ตกแต่ง = `alt=""`), โฟกัสมองเห็นชัด (`:focus-visible`)
- carousel ตาม ARIA carousel pattern + ปุ่มหยุด (2.2.2), dialog (aside/lightbox/popup แบบ modal) มี focus trap/Esc/คืนโฟกัส, ลิงก์ซ้ำในการ์ดไม่อยู่ในลำดับ Tab
- prefers-reduced-motion: ปิดการเล่นอัตโนมัติ/ลด animation; ขนาดตัวอักษร + โหมดสีช่วยผู้มีปัญหาการมองเห็น

## 12. Security

- rich text ผ่าน `App\Support\Front\HtmlSanitizer` (allowlist ด้วย DOMDocument — ตัด script/iframe/on*/style, ลิงก์เฉพาะ http(s)/mailto/tel/#/path,
  `_blank` ใส่ `rel="noopener noreferrer"`, h1 → h2) ก่อนส่งให้ `v-html`
- URL ภายนอก (เมนู/banner/ปุ่ม intropage/อ่านทั้งหมด/social/popup) ผ่าน `FrontUrl::safeExternal()` (กัน `javascript:` ฯลฯ)
- header `FrontSecurityHeaders` (nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy) — ยังไม่ใส่ CSP (ดู roadmap)
- หน้าบ้านไม่ส่งข้อมูลผู้ใช้/สิทธิ์/เมนูหลังบ้าน (`HandleInertiaRequests` ส่งเฉพาะ `/admin/*`) และ Ziggy ส่งเฉพาะกลุ่ม route `front`
  (`config/ziggy.php`) — ไม่เปิดเผยรายชื่อ URL หลังบ้าน
- Custom JS/CSS ของ template = เนื้อหาที่ผู้ดูแล (สิทธิ์ `system.template.manage`) ใส่เอง ถือว่าเชื่อถือได้

## Roadmap

| รอบ | ขอบเขต | สถานะ |
|-----|--------|-------|
| 1 — front-init | Intropage, layout จาก template, หน้าเพจ, บทความ, SEO, log_front_access, ยอดเข้าชม, cache | ✅ |
| popup-init | Popup (modal/floating, สไลด์, ตามเมนู, ไม่แสดงวันนี้อีก) | ✅ |
| ถัดไป | หน้าค้นหา (เปิดปุ่มค้นหาใน header), หน้าแท็ก, sitemap.xml / robots.txt, CSP (nonce), GeoIP, `log_front_action`/`log_front_login` (สมาชิก), ไฟล์เฉพาะสมาชิก (§7), รายงานยอดเข้าชมจาก `*_item_view` / ยอดคลิกจาก `banner_item_click` | 🔴 |
