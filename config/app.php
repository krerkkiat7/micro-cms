<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    | Proxy ที่เชื่อถือได้ (load balancer / reverse proxy / ingress ของ k8s) — คั่นด้วย , หรือ * = ทุกตัว
    | ตั้งเมื่อมี proxy อยู่หน้าระบบ (โดยเฉพาะที่ทำ HTTPS ให้) ไม่งั้นระบบเห็น request เป็น http + IP ของ proxy
    | (ลิงก์/รูปที่สร้างเป็น http://, rate limit นับรวมทุกคนเป็น IP เดียว) — ว่าง = ไม่เชื่อ header X-Forwarded-* ใด ๆ
    */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | ลำดับความสำคัญของ timezone ในโปรเจกต์นี้ (สูงไปต่ำ): (1) sys_setting กลุ่ม 'site' คอลัมน์ 'timezone' — ตั้งได้ที่
    | หน้าตั้งค่าระบบ, ใช้ override ทับค่านี้อีกทีตอน boot (ดู App\Providers\AppServiceProvider::applyTimezoneSetting());
    | (2) .env APP_TIMEZONE (ดู .env.example — ค่าแนะนำของโปรเจกต์คือ Asia/Bangkok); (3) ไม่มีทั้งคู่ = ใช้ค่า default ของ
    | php.ini ตอน boot (date_default_timezone_get() ตรงนี้ยังไม่มีอะไรเรียก date_default_timezone_set() มาก่อน จึงอ่านค่า
    | php.ini ล้วน ๆ) — เดิม Laravel ฮาร์ดโค้ดเป็น "UTC" ทำให้ now()/Carbon::now() ทั้งระบบผิดเพี้ยนจากเวลาไทยจริง (เคยทำให้
    | บทความที่ตั้งวันที่เผยแพร่เป็นเวลาไทยไม่ขึ้นใน widget จนกว่าจะตั้งเวลาย้อนไปหลายชั่วโมง)
    |
    */

    'timezone' => env('APP_TIMEZONE', date_default_timezone_get()),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
