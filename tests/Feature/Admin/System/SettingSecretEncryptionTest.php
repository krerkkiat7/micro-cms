<?php

use App\Models\SysSetting;
use App\Models\User;
use App\Support\Setting;
use App\Support\Turnstile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

// ค่าลับใน sys_setting (Setting::SECRETS) เก็บแบบเข้ารหัส — อ่านผ่าน Setting ได้ค่าจริง

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function rawSetting(string $group, string $name): ?string
{
    return DB::table('sys_setting')->where('group', $group)->where('name', $name)->value('value');
}

test('secrets saved from the settings page are encrypted in the database but readable through Setting', function () {
    $this->actingAs(User::where('email', 'admin@admin.com')->firstOrFail());

    $this->put(route('admin.system.setting.update.smtp'), [
        'host' => 'smtp.example.com', 'port' => 587, 'use_auth' => 'Y', 'username' => 'mailer',
        'password' => 'p@ss-secret', 'ssl_type' => 'tls',
    ])->assertSessionHasNoErrors();
    $this->put(route('admin.system.setting.update.turnstile'), ['site_key' => 'site-key', 'key_secret' => 'ts-secret'])
        ->assertSessionHasNoErrors();

    expect(rawSetting('smtp', 'password'))->not->toBe('p@ss-secret')
        ->and(Crypt::decryptString(rawSetting('smtp', 'password')))->toBe('p@ss-secret')
        ->and(rawSetting('turnstile', 'key_secret'))->not->toBe('ts-secret')
        // ค่าอื่นที่ไม่ใช่ค่าลับไม่เข้ารหัส
        ->and(rawSetting('smtp', 'username'))->toBe('mailer');

    Setting::forgetAll();
    expect(Setting::get('smtp', 'password'))->toBe('p@ss-secret')
        ->and(Setting::get('turnstile', 'key_secret'))->toBe('ts-secret')
        ->and(Turnstile::configured())->toBeTrue()
        ->and(config('mail.mailers.smtp.host'))->not->toBeNull();

    // บันทึกซ้ำโดยเว้นรหัสว่าง (ใช้ค่าเดิม) — ไม่เข้ารหัสซ้อน
    $this->put(route('admin.system.setting.update.smtp'), [
        'host' => 'smtp.example.com', 'port' => 587, 'use_auth' => 'Y', 'username' => 'mailer',
        'password' => '', 'ssl_type' => 'tls',
    ])->assertSessionHasNoErrors();

    Setting::forgetAll();
    expect(Setting::get('smtp', 'password'))->toBe('p@ss-secret');
});

test('the cache never holds the plain secret', function () {
    SysSetting::create(['group' => 'smtp', 'name' => 'password', 'value' => 'plain-in-cache?']);
    Setting::forgetAll();

    expect(Setting::get('smtp', 'password'))->toBe('plain-in-cache?')
        ->and(serialize(cache()->get('sys_setting.smtp')))->not->toContain('plain-in-cache?');
});

test('the migration encrypts existing plain-text secrets and can be rolled back', function () {
    DB::table('sys_setting')->insert(['group' => 'turnstile', 'name' => 'key_secret', 'value' => 'old-plain']);

    $migration = require database_path('migrations/2026_10_09_000001_encrypt_setting_secrets.php');
    $migration->up();

    expect(rawSetting('turnstile', 'key_secret'))->not->toBe('old-plain')
        ->and(Setting::get('turnstile', 'key_secret'))->toBe('old-plain');

    $migration->up(); // รันซ้ำไม่เข้ารหัสซ้อน
    expect(Setting::get('turnstile', 'key_secret'))->toBe('old-plain');

    $migration->down();
    expect(rawSetting('turnstile', 'key_secret'))->toBe('old-plain');
});

test('a secret encrypted with another APP_KEY reads as not set', function () {
    $other = new Encrypter(random_bytes(32), 'aes-256-cbc');
    DB::table('sys_setting')->insert(['group' => 'turnstile', 'name' => 'key_secret', 'value' => $other->encryptString('x')]);
    Setting::forgetAll();

    expect(Setting::get('turnstile', 'key_secret'))->toBeNull()
        ->and(Turnstile::configured())->toBeFalse();
});
