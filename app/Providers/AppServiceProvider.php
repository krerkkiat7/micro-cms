<?php

namespace App\Providers;

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
        //
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
}
