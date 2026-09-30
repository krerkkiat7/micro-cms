# PHP-FPM สำหรับรันระบบใน Docker (dev/ทดสอบ) — ใช้ร่วมกันทั้ง service app (php-fpm) และ scheduler (schedule:work)
# ดูวิธีใช้ที่ docker/README.md
FROM php:8.2-fpm

# ตัวช่วยติดตั้ง PHP extension (จัดการ lib ที่ต้องใช้ให้เอง เช่น libjpeg/libpng/libwebp ของ gd)
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# gd = ย่อรูป thumbnail (intervention/image), pdo_mysql = MySQL, opcache = ความเร็ว (ไม่มีช้าลงราว 20 เท่า)
# pcntl = schedule:work/queue:work หยุดได้เรียบร้อย, zip/intl/bcmath/exif = ที่ package ของ Laravel ใช้ทั่วไป
RUN install-php-extensions gd pdo_mysql opcache zip intl bcmath exif pcntl

# unzip/git = ให้ composer แตกไฟล์/ดึง package ได้เสถียร (ไม่ต้องพึ่ง ZipArchive อย่างเดียว)
RUN apt-get update && apt-get install -y --no-install-recommends unzip git \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY --chmod=0755 entrypoint.sh /usr/local/bin/app-entrypoint

WORKDIR /var/www/html

# composer install ลง vendor volume ครั้งแรก แล้วต่อด้วยคำสั่งของ service (php-fpm / schedule:work)
ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]
