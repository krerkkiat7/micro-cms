# syntax=docker/dockerfile:1
#
# Production image ของ MicroCMS — nginx + PHP-FPM 8.2 (OPcache) ใน image เดียว, โค้ด + vendor (ไม่มี dev) + asset ที่ build แล้วอยู่ใน image
# image เดียวกันใช้ได้หลายบทบาท (ดู entrypoint.sh): web (ค่าเริ่มต้น) / scheduler / migrate / artisan <คำสั่ง>
#
# build (จาก root ของโปรเจกต์):  docker build -f docker/production/Dockerfile -t microcms:latest .
# ดูวิธีใช้ทั้งหมดใน README.md (หัวข้อ Docker / Kubernetes)

# ---------------------------------------------------------------- base: PHP + extension ที่ระบบต้องใช้
FROM php:8.2-fpm-bookworm AS base

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# gd = thumbnail/WebP (intervention/image), pdo_mysql = MySQL, opcache = ความเร็ว, pcntl = schedule:work หยุดได้เรียบร้อย
RUN install-php-extensions gd pdo_mysql opcache zip intl bcmath exif pcntl

WORKDIR /var/www/html

# ---------------------------------------------------------------- vendor: composer install แบบ production
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apt-get update && apt-get install -y --no-install-recommends unzip git && rm -rf /var/lib/apt/lists/*

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader

COPY . .
# package:discover boot ระบบ — ตอน build ไม่มี Redis/MySQL: ใช้ cache แบบ array (Setting::group() ต่อ DB ไม่ได้จะคืนค่าว่างเอง)
RUN CACHE_STORE=array SESSION_DRIVER=array composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && CACHE_STORE=array SESSION_DRIVER=array php artisan package:discover --ansi

# ---------------------------------------------------------------- assets: npm run build (ต้องใช้ vendor/tightenco/ziggy)
FROM node:20-bookworm-slim AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
COPY --from=vendor /var/www/html/vendor/tightenco/ziggy ./vendor/tightenco/ziggy
RUN npm run build

# ---------------------------------------------------------------- final
FROM base AS app

RUN apt-get update && apt-get install -y --no-install-recommends nginx \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

COPY docker/production/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/production/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/production/nginx.conf /etc/nginx/conf.d/microcms.conf
COPY --chmod=0755 docker/production/entrypoint.sh /usr/local/bin/microcms

COPY --from=vendor --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

# storage/ ถูก mount เป็น volume ตอนใช้งานจริง — สำเนาโครงโฟลเดอร์ไว้ให้ entrypoint สร้างใน volume ว่างครั้งแรก
RUN cp -a storage /opt/storage-skeleton \
    && rm -f bootstrap/cache/*.php \
    && chown -R www-data:www-data storage bootstrap/cache

# XDG_* = ที่เก็บ config/history ของ php artisan tinker (home ของ www-data คือ /var/www ซึ่งเขียนไม่ได้)
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_LEVEL=warning \
    XDG_CONFIG_HOME=/tmp/.config \
    XDG_DATA_HOME=/tmp/.local/share

EXPOSE 8080
VOLUME ["/var/www/html/storage"]

# เรียก /up ผ่าน nginx → PHP-FPM โดยส่ง Host ของ APP_URL (production รับเฉพาะ Host ของ APP_URL — trustHosts)
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 CMD ["microcms", "healthcheck"]

# image php:*-fpm ตั้ง STOPSIGNAL SIGQUIT ไว้ (สำหรับ php-fpm ตรง ๆ) — entrypoint เป็น bash จึงใช้ SIGTERM แล้วส่งต่อให้เอง
STOPSIGNAL SIGTERM

ENTRYPOINT ["microcms"]
CMD ["web"]
