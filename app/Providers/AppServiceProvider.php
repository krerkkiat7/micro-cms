<?php

namespace App\Providers;

use App\Support\Front\ViewCounter;
use App\Support\Front\Views\RedisViewBuffer;
use App\Support\Setting;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ตัวนับยอดเข้าชมหน้าบ้าน — โหมด redis พักคิวใน Redis แล้ว flush เป็นชุด / โหมด database บันทึกตรง (ดู config/front.php)
        $this->app->singleton(ViewCounter::class, function () {
            $driver = config('front.views.driver', 'auto');

            if ($driver === 'auto') {
                $driver = config('cache.default') === 'redis' ? 'redis' : 'database';
            }

            return new ViewCounter($driver === 'redis'
                ? new RedisViewBuffer((string) config('front.views.redis_connection', 'default'))
                : null);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // อีเมลรีเซ็ตรหัสผ่านต้องชี้ไปที่ route ของหลังบ้าน (/admin/reset-password/{token})
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return route('admin.password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });

        $this->applySmtpSetting();
        $this->applyTimezoneSetting();
    }

    /**
     * เมื่อมีการส่งอีเมลให้ใช้การตั้งค่า SMTP จาก sys_setting (กลุ่ม smtp) แทนค่าใน .env
     * เช็ก Schema::hasTable() กันพังตอนยังไม่ได้ migrate (เช่น ระหว่างรัน migrate เอง)
     */
    private function applySmtpSetting(): void
    {
        if (! Schema::hasTable('sys_setting')) {
            return;
        }

        $smtp = Setting::group('smtp');
        $host = $smtp['host'] ?? null;

        if (! $host) {
            return;
        }

        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $smtp['port'] ?? 587);
        Config::set('mail.mailers.smtp.scheme', ($smtp['ssl_type'] ?? 'none') === 'ssl' ? 'smtps' : 'smtp');

        if (($smtp['use_auth'] ?? 'N') === 'Y') {
            Config::set('mail.mailers.smtp.username', $smtp['username'] ?? null);
            Config::set('mail.mailers.smtp.password', $smtp['password'] ?? null);
        } else {
            Config::set('mail.mailers.smtp.username', null);
            Config::set('mail.mailers.smtp.password', null);
        }

        if (! empty($smtp['from_email'])) {
            Config::set('mail.from.address', $smtp['from_email']);
        }

        if (! empty($smtp['from_name'])) {
            Config::set('mail.from.name', $smtp['from_name']);
        }

        Config::set('mail.default', 'smtp');
    }

    /**
     * โซนเวลาของระบบ — ลำดับความสำคัญ: sys_setting (กลุ่ม site คอลัมน์ timezone) มาก่อนเสมอถ้าตั้งไว้ > `.env` (`APP_TIMEZONE`)
     * > ค่า default ของ php.ini (ตั้งเป็น config('app.timezone') ไว้แล้วตามลำดับนี้ตั้งแต่ config/app.php — ดูคอมเมนต์ที่นั่น)
     * เมธอดนี้ override ชั้นบนสุดเมื่อมีค่าตั้งไว้ใน sys_setting เท่านั้น เช็ก Schema::hasTable() กันพังตอนยังไม่ได้ migrate
     */
    private function applyTimezoneSetting(): void
    {
        if (! Schema::hasTable('sys_setting')) {
            return;
        }

        $timezone = Setting::get('site', 'timezone');

        if (! $timezone) {
            return;
        }

        date_default_timezone_set($timezone);
        Config::set('app.timezone', $timezone);
    }
}
