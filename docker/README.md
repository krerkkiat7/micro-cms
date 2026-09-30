# รันระบบใน Docker (dev / ทดสอบ) — http://localhost:8001

ชุดนี้รันทั้งระบบใน Docker: **nginx + PHP-FPM 8.2 (มี OPcache) + scheduler** ต่อกับ MySQL/Redis ใน `docker-compose.yml` เดิม
ใช้ทดสอบความเร็ว/การทำงานใกล้เคียงเครื่องจริง แทน `php artisan serve` (บน Windows รับได้ทีละ request) และ `php artisan schedule:work`

| service | container | หน้าที่ |
|---------|-----------|--------|
| `mysql`, `redis` | `cms_mysql`, `cms_redis` | เหมือนเดิม (เปิดด้วย `docker compose up -d` ธรรมดาได้เหมือนเดิม) |
| `app` | `cms_app` | PHP-FPM — โค้ดคือโฟลเดอร์โปรเจกต์บนเครื่อง (แก้ไฟล์ PHP แล้วเห็นผลภายใน ~30 วินาที หรือ `docker restart cms_app` ทันที; ไฟล์ Vue/CSS ตามปกติของ Vite) |
| `web` | `cms_web` | nginx เปิดที่ **http://localhost:8001** |
| `scheduler` | `cms_scheduler` | `php artisan schedule:work` (เช่น `front:flush-views` ทุกนาที) |

`app`/`web`/`scheduler` อยู่ใน profile `app` — `docker compose up -d` เปล่า ๆ ยังเปิดแค่ MySQL + Redis เหมือนเดิม

## ครั้งแรก

```bash
# 1) build asset หน้าเว็บ (ถ้ายังไม่เคย) — หรือเปิด `npm run dev` ค้างไว้ก็ได้ (ดูหัวข้อ Vite ด้านล่าง)
npm install
npm run build

# 2) build image + เปิดทั้งชุด — ครั้งแรก container จะ composer install ลง volume `vendor` เอง (รอสักครู่)
docker compose --profile app up -d --build

# 3) ดู log ว่าพร้อมแล้ว (เห็น "ready to handle connections")
docker logs -f cms_app

# 4) (ถ้าฐานข้อมูลยังว่าง) migrate + seed ผ่าน container
docker exec cms_app php artisan migrate --seed
```

เปิด http://localhost:8001 (หลังบ้าน http://localhost:8001/admin — admin@admin.com / password123)

## ใช้งานประจำวัน

```bash
docker compose --profile app up -d        # เปิด
docker compose --profile app stop         # ปิด (ข้อมูลใน MySQL ยังอยู่)
docker exec cms_app php artisan <คำสั่ง>   # รัน artisan ใน container เช่น migrate, cache:clear, test
docker logs -f cms_scheduler              # ดูว่า scheduler ทำงาน
```

- **อย่ารัน `php artisan schedule:work` บนเครื่องพร้อมกับ container `scheduler`** — งานตามเวลาจะทำงานซ้ำสองที่
- `composer.lock` เปลี่ยน (ติดตั้ง package ใหม่) → `docker compose --profile app restart app scheduler` container จะ composer install ให้เอง
- แก้ `docker/php/*` หรือ `docker/nginx/*` → `docker compose --profile app up -d --build`
- request แรกหลังเปิด/restart container ช้า 1-4 วินาที (OPcache ยังไม่มีโค้ด) หลังจากนั้นราว 0.2-0.4 วินาทีต่อหน้า

## ค่า environment

container ใช้ `.env` ของโปรเจกต์ แต่ทับบางค่าใน `docker-compose.yml` (Laravel ไม่เขียนทับ env ที่ตั้งไว้แล้ว):

| ค่า | ใน container | เหตุผล |
|-----|-------------|--------|
| `APP_URL` | `http://localhost:8001` | URL เต็มที่ระบบสร้าง (ลิงก์/รูป/sitemap) ต้องตรงกับที่เปิดจริง |
| `DB_HOST` / `REDIS_HOST` | `mysql` / `redis` | ใน network ของ Docker ต่อกันด้วยชื่อ service ไม่ใช่ `127.0.0.1` |

- **ห้ามรัน `php artisan config:cache` บนเครื่อง** ขณะใช้ container — ไฟล์ `bootstrap/cache/config.php` ถูกแชร์และจะฝังค่า `127.0.0.1` ของเครื่องไว้
  (ถ้าเผลอรัน: `php artisan config:clear`)
- ทั้งเครื่อง (`php artisan serve` :8000) และ container (:8001) ใช้ฐานข้อมูล/Redis ชุดเดียวกัน — cache ที่มี URL เต็มอาจปนพอร์ตกัน
  ถ้าเห็นลิงก์ชี้ผิดพอร์ต ให้ล้าง cache หน้าบ้าน (หลังบ้าน → ตั้งค่าระบบ → ล้างแคช) หรือใช้ทีละตัว

## Vite (`npm run dev`)

- ใช้ asset ที่ build แล้ว (`npm run build` → `public/build`) ได้เลย
- ถ้าเปิด `npm run dev` บนเครื่องค้างไว้ หน้า :8001 จะโหลด asset จาก Vite dev server (`localhost:5173`) ของเครื่องแทน — ใช้ได้ แต่ช้ากว่า
  (โหลดไฟล์ JS แยกเป็นร้อยไฟล์) ถ้าต้องการวัดความเร็วจริงให้ปิด `npm run dev` และลบไฟล์ `public/hot` แล้ว `npm run build`

## ทำไม vendor อยู่ใน volume / OPcache ตั้งเช็กไฟล์ทุก 30 วินาที

Docker Desktop บน Windows อ่านไฟล์จากไดรฟ์ของ Windows (bind mount) ช้ามาก — `vendor/` มีราว 11,000 ไฟล์ ถ้า mount ตรง
`php artisan --version` ใช้ ~8 วินาที จึงให้ container มี `vendor` ของตัวเองใน Docker volume `vendor` (ติดตั้งด้วย `docker/php/entrypoint.sh`)
ส่วนโค้ดของโปรเจกต์ยัง mount จากเครื่องเพื่อให้แก้แล้วเห็นผลทันที
เฉพาะ service `app` ที่ composer install (service `scheduler` รอจน app ติดตั้งเสร็จ — ติดตั้งพร้อมกันใน volume เดียวไฟล์จะเสีย)
ล้าง vendor ของ container: `docker compose --profile app rm -sf app scheduler web` แล้ว `docker volume rm my-cms_vendor`

OPcache `revalidate_freq=30` ด้วยเหตุผลเดียวกัน — ตั้งถี่ (เช่น 2 วินาที) แล้ว request จะกระตุก 1-2 วินาทีทุกครั้งที่เช็กเวลาไฟล์ผ่าน bind mount

## หมายเหตุสำหรับ nginx จริง (production)

`fastcgi_buffer_size 32k; fastcgi_buffers 16 32k;` จำเป็น — Laravel ส่ง header `Link` (preload asset) ยาวเกินค่าเริ่มต้น 4k
ทำให้บางหน้าได้ 502 "upstream sent too big header" (ดู `docker/nginx/default.conf`)
