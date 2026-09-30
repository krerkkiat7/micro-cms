#!/bin/sh
# vendor/ ของ container อยู่ใน Docker volume (ไม่ใช่โฟลเดอร์บน Windows — bind mount ข้าม OS อ่านไฟล์หลายหมื่นไฟล์ช้ามาก)
# - service app (php-fpm) เป็นตัวเดียวที่ composer install — ครั้งแรก (volume ว่าง) หรือเมื่อ composer.lock เปลี่ยน
# - service อื่น (scheduler) รอจน app ติดตั้งเสร็จ (ห้ามติดตั้งพร้อมกันในแชร์ volume เดียว — ไฟล์เสียหาย)
set -e

cd /var/www/html

MARKER=vendor/.composer.lock.installed

installed() {
    [ -f vendor/autoload.php ] && cmp -s composer.lock "$MARKER"
}

if [ "$1" = "php-fpm" ]; then
    if ! installed; then
        echo "[entrypoint] composer install ..."
        rm -f "$MARKER"
        composer install --no-interaction --prefer-dist --no-progress
        cp composer.lock "$MARKER"
        echo "[entrypoint] composer install เสร็จแล้ว"
    fi
else
    waited=0
    until installed; do
        if [ "$waited" -ge 900 ]; then
            echo "[entrypoint] รอ composer install จาก service app เกิน 15 นาที — ดู docker logs cms_app" >&2
            exit 1
        fi
        [ "$waited" -eq 0 ] && echo "[entrypoint] รอ service app ติดตั้ง vendor ..."
        sleep 5
        waited=$((waited + 5))
    done
fi

exec "$@"
