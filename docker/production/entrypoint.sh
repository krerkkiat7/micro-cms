#!/bin/bash
# entrypoint ของ production image — บทบาทตามอาร์กิวเมนต์แรก:
#   web          (ค่าเริ่มต้น) nginx :8080 + PHP-FPM — cache config/route/view แล้วเปิดบริการ
#   scheduler    php artisan schedule:work — งานตามเวลา (front:flush-views ทุกนาที) รันแค่ 1 ตัวต่อระบบ
#   migrate      php artisan migrate --force (เพิ่ม --seed ต่อท้ายได้: `migrate --seed` = ข้อมูลตัวอย่างครั้งแรก)
#   artisan ...  รันคำสั่ง artisan อื่น ๆ ในนาม www-data
#   healthcheck  เรียก /up ผ่าน nginx (ใช้กับ HEALTHCHECK ของ Docker)
#
# ทุกคำสั่ง artisan รันในนาม www-data — ไฟล์ใน storage/ ที่ root สร้าง PHP-FPM จะอ่าน/เขียนไม่ได้
set -euo pipefail

APP_DIR=/var/www/html
cd "$APP_DIR"

as_www() {
    runuser -u www-data -- "$@"
}

prepare_storage() {
    # volume ว่าง (ติดตั้งครั้งแรก) — สร้างโครงโฟลเดอร์ของ storage/ จากสำเนาใน image
    if [ ! -d storage/framework ]; then
        cp -a /opt/storage-skeleton/. storage/
    fi
    mkdir -p storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
    # เปลี่ยนเจ้าของเฉพาะเมื่อจำเป็น (chown -R ทั้ง storage ที่มีไฟล์เป็นหมื่นทุกครั้งที่เปิด container ช้า)
    if [ "$(stat -c %U storage)" != "www-data" ] || [ -n "$(find storage -maxdepth 3 ! -user www-data -print -quit)" ]; then
        chown -R www-data:www-data storage
    fi
    chown www-data:www-data bootstrap/cache
}

require_key() {
    if [ -z "${APP_KEY:-}" ]; then
        echo "[microcms] ต้องตั้ง APP_KEY (สร้างด้วย: docker run --rm <image> artisan key:generate --show)" >&2
        exit 1
    fi
}

warm_caches() {
    # ค่าใน env ของ container ถูกฝังลง bootstrap/cache ตรงนี้ — เปลี่ยน env/ภาษาที่เปิดใช้ ต้อง restart container
    as_www php artisan optimize --no-interaction
}

role="${1:-web}"
[ $# -gt 0 ] && shift

case "$role" in
    web)
        require_key
        prepare_storage
        warm_caches
        php-fpm -F &
        fpm=$!
        nginx -g 'daemon off;' &
        web=$!
        # หยุดแบบ graceful (QUIT = ทั้ง php-fpm และ nginx ทำ request ที่ค้างให้เสร็จก่อน)
        trap 'kill -QUIT "$fpm" "$web" 2>/dev/null; wait' TERM INT QUIT
        # ตัวใดตัวหนึ่งหยุด = หยุดทั้ง container ให้ Docker/k8s เริ่มใหม่
        wait -n "$fpm" "$web"
        status=$?
        kill -TERM "$fpm" "$web" 2>/dev/null || true
        wait || true
        exit "$status"
        ;;
    scheduler)
        require_key
        prepare_storage
        warm_caches
        # schedule:work ไม่ดักสัญญาณเอง — รันเป็นลูกของ shell แล้วส่ง SIGTERM ต่อให้ (หยุดได้ทันทีตอน deploy/scale)
        setpriv --reuid=www-data --regid=www-data --init-groups php artisan schedule:work --no-interaction &
        child=$!
        trap 'kill -TERM "$child" 2>/dev/null' TERM INT QUIT
        wait "$child" || true
        ;;
    migrate)
        require_key
        prepare_storage
        as_www php artisan migrate --force --no-interaction "$@"
        # --isolated: pod อื่นกำลัง migrate อยู่ คำสั่งข้างบนจบทันที — รอจนไม่มี migration ค้าง ค่อยให้ web เริ่ม
        waited=0
        until as_www php artisan migrate:status --pending=1 --no-interaction >/dev/null 2>&1; do
            if [ "$waited" -ge 600 ]; then
                echo "[microcms] ยังมี migration ค้างเกิน 10 นาที" >&2
                exit 1
            fi
            sleep 5
            waited=$((waited + 5))
        done
        ;;
    artisan)
        prepare_storage
        exec runuser -u www-data -- php artisan "$@"
        ;;
    healthcheck)
        host=$(php -r 'echo parse_url((string) getenv("APP_URL"), PHP_URL_HOST) ?: "localhost";')
        exec php -r '
            $ctx = stream_context_create(["http" => ["header" => "Host: ".$argv[1], "timeout" => 4, "ignore_errors" => true]]);
            $body = @file_get_contents("http://127.0.0.1:8080/up", false, $ctx);
            exit($body !== false && str_contains($http_response_header[0] ?? "", " 200") ? 0 : 1);
        ' "$host"
        ;;
    *)
        exec "$role" "$@"
        ;;
esac
