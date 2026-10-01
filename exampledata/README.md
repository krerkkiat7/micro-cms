# ข้อมูลตัวอย่าง (exampledata)

รูปภาพและไฟล์ต้นฉบับของข้อมูลตัวอย่าง MicroCMS — ตอน `php artisan migrate --seed` (`SampleDataSeeder`) ไฟล์ในโฟลเดอร์นี้
ถูกนำเข้าโมดูลจัดการไฟล์ของผู้ดูแล (`admin@microcms.com`) แยกโฟลเดอร์ "ตัวอย่าง - ..." แล้วผูกกับเนื้อหา

## สิทธิ์การใช้งาน

**ทุกไฟล์สร้างขึ้นเองสำหรับโปรเจกต์นี้** ไม่มีภาพหรือเนื้อหาของบุคคลที่สาม:

- ภาพประกอบ/ป้ายโฆษณา/แผนที่/โลโก้ — วาดด้วย HTML/SVG (`_build/templates.mjs`) แล้ว render ด้วย Microsoft Edge แบบ headless
  ไอคอนจาก [Lucide](https://lucide.dev) (ISC License) ฟอนต์ Prompt/Sarabun (SIL Open Font License)
- `images/guide/*` — ภาพหน้าจอของ MicroCMS เอง (หลังบ้าน/หน้าบ้าน ที่ seed ข้อมูลตัวอย่างแล้ว)
- ภาพขั้นตอนของบริการภายนอก (Cloudflare, Google, Gmail) เป็นแผนภาพที่วาดเอง **ไม่ใช่ภาพหน้าจอของบริการนั้น**
- เนื้อหาบทความ (`database/seeders/data/articles.php`) เขียนขึ้นใหม่ — ข่าวในหมวด "ข่าวสาร" เป็นข่าวสมมติของ MicroCMS
- ข้อมูลติดต่อ (ที่อยู่ เบอร์โทร) เป็นค่าสมมติ

## โครงสร้าง

| โฟลเดอร์ | ใช้ที่ |
|---------|-------|
| `images/site/` | โลโก้ (`logo.png` พื้นใส), `favicon.ico`, รูปโปรไฟล์ผู้ดูแล |
| `images/article/` | รูปปกหมวดหมู่/บทความ (1200×675), ภาพประกอบโมดูล (1200×800), แผนภาพขั้นตอนบริการภายนอก |
| `images/guide/` | ภาพหน้าจอระบบจริง (1440×900) สำหรับบทความหมวด "การใช้งานระบบ"/"การตั้งค่าบริการภายนอก" |
| `images/banner/` | ป้ายโฆษณา Highlight (1920×823 = 21:9) |
| `images/intropage/`, `images/popup/` | ภาพยินดีต้อนรับ (ข้อความสองภาษาในภาพ) |
| `images/menu/` | รูปส่วนหัวของเมนูหน้าบ้าน (1920×360) |
| `images/page/` | ภาพประกอบ/พื้นหลัง/ภาพแชร์โซเชียลของหน้าแรก |
| `images/contactus/` | รูปแผนที่ (ใช้แทน Google Map เมื่อยังไม่มี API key) |
| `files/` | PDF คู่มือเริ่มต้นใช้งาน (ไทย/อังกฤษ) — part เอกสารของบทความ "แนะนำระบบ" |
| `_build/` | สคริปต์สร้างไฟล์ทั้งหมดใหม่ (ไม่ถูกใช้ตอน seed) |

## ข้อมูลตัวอย่างที่ seed

| ส่วน | รายละเอียด | seeder |
|-----|-----------|--------|
| ตั้งค่าระบบ | โลโก้, favicon, ข้อมูลติดต่อ/social สมมติ, **คีย์ทดสอบของ Cloudflare Turnstile** (ผ่านเสมอ — ต้องเปลี่ยนก่อนใช้งานจริง); GA/Google Map/SMTP ปล่อยว่าง | `SiteSettingSeeder` |
| บทความ | 4 หมวด (ข่าวสาร/การใช้งานระบบ/การตั้งค่าบริการภายนอก/ทั่วไป), 15 บทความสองภาษา, 20 แท็ก, ตั้งค่าการแสดงผล | `ArticleSeeder` |
| หน้าเพจ | "หน้าแรก" 5 แถว: Slideshow Highlight / แนะนำระบบ / Slideset ข่าวสาร / Grid การใช้งานระบบ / Grid บริการภายนอก | `PageSeeder`, `PageLayoutSeeder` |
| เมนูหน้าบ้าน | หน้าแรก · แนะนำระบบ · บทความ (ข่าวสาร / การใช้งานระบบ / การตั้งค่าบริการภายนอก) · ติดต่อเรา | `FrontMenuSeeder` |
| ป้ายโฆษณา | หมวด Highlight 4 รายการ ลิงก์ไปเมนู | `BannerSeeder` |
| Intropage | รูปยินดีต้อนรับ + ปุ่มเข้าสู่เว็บไซต์/แนะนำระบบ | `IntropageSeeder` |
| Template | "MicroCMS Blue" 1 รายการ (ใช้งาน) | `TemplateSeeder` |
| ติดต่อเรา | split_info, รูปแผนที่, แบบฟอร์มครบทุกฟิลด์ | `ContactusSeeder` |
| Popup | modal ยินดีต้อนรับ เฉพาะเมนูหน้าแรก | `PopupSeeder` |

ทุกแถวเนื้อหา `is_temp='Y'` (แสดงที่หน้าบ้านตามปกติ — แก้ไขต่อหรือลบออกได้)

- **seed ครั้งเดียวต่อฐานข้อมูล** — ถ้ามีบทความ `introducing-microcms` อยู่แล้ว `SampleDataSeeder` จะข้ามทั้งชุด
  (ส่วนสิทธิ์/ผู้ดูแล/เมนูหลังบ้านใน `DatabaseSeeder` ยังรันซ้ำได้) ต้องการชุดใหม่: ลบไฟล์ใน `storage/app/private/filemanager/`
  แล้ว `php artisan migrate:fresh --seed`
- seed เสร็จแล้วสร้าง thumbnail ล่วงหน้าให้ทุกรูป (ประมาณ 1 นาที) และล้าง cache ที่เกี่ยวข้อง
- **Docker:** รันในนาม `www-data` — `docker exec -u www-data cms_app php artisan migrate:fresh --seed`
  (ถ้ารันเป็น root โฟลเดอร์ไฟล์ที่สร้างจะเป็น `0700` ของ root และ PHP-FPM อ่านไม่ได้ → รูปขึ้น 404;
  แก้ด้วย `docker exec cms_app sh -c 'chown -R www-data:www-data /var/www/html/storage/app/private'`)
- ตอนรันเทส ไม่คัดลอกไฟล์จริง (สร้างเฉพาะแถว `file_info`) — ดู `database/seeders/Support/SampleFiles.php`

## สร้างไฟล์ใหม่ (`_build/`)

ต้องมี Microsoft Edge (หรือ Chrome — ตั้ง `EDGE_PATH`) และ `npm install` ของโปรเจกต์แล้ว (ใช้ไอคอนจาก `lucide-vue-next`)

```bash
node exampledata/_build/generate.mjs          # ภาพประกอบทั้งหมด + PDF → _build/out/  (ใส่ตัวกรองชื่อได้ เช่น banner)
php exampledata/_build/convert.php            # PNG → JPG (q82), logo คง PNG, สร้าง favicon.ico → images/, files/

# ภาพหน้าจอ (หลัง seed + เปิดระบบที่ http://localhost:8001 แล้ว) — ใช้ playwright-core กับ Edge ที่ติดตั้งในเครื่อง
npm i --prefix /tmp/pw playwright-core
PW_HOME=/tmp/pw BASE_URL=http://localhost:8001 node exampledata/_build/screenshots.mjs
php exampledata/_build/convert.php
```

ภาพหน้าจอถ่ายจากข้อมูลตัวอย่าง จึงต้อง seed ก่อนถ่าย แล้ว seed ใหม่อีกรอบเพื่อให้ภาพหน้าจอชุดใหม่ถูกนำเข้า
