<?php

use App\Models\LogBackLogin;
use App\Models\User;
use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;

// บันทึกตั้งค่าระบบแต่ละกลุ่ม (นอกจาก site/smtp ที่มีเทสแยก) + การล็อกบัญชีอัตโนมัติจากตั้งค่า login_back

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('contact, social and analytics groups are saved', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->put(route('admin.system.setting.update.contact'), [
        'address_th' => 'กรุงเทพฯ', 'address_en' => 'Bangkok', 'phone' => '02-000-0000', 'fax' => '', 'mobile' => '', 'email' => 'info@example.com',
    ])->assertSessionHasNoErrors();

    $this->put(route('admin.system.setting.update.social'), [
        'facebook' => 'https://facebook.com/example', 'youtube' => '', 'x' => '', 'instagram' => '', 'tiktok' => '', 'line' => '',
    ])->assertSessionHasNoErrors();

    $this->put(route('admin.system.setting.update.google_analytics'), ['tracking_id' => 'G-TEST123'])->assertSessionHasNoErrors();

    expect(Setting::get('contact', 'email'))->toBe('info@example.com')
        ->and(Setting::get('social', 'facebook'))->toBe('https://facebook.com/example')
        ->and(Setting::googleAnalyticsTrackingId())->toBe('G-TEST123');
});

test('settings groups require system.setting.manage', function () {
    actingAsUserWithPermissions([]);

    $this->put(route('admin.system.setting.update.google_analytics'), ['tracking_id' => 'G-NOPE'])
        ->assertRedirect(route('admin.dashboard'));

    expect(Setting::googleAnalyticsTrackingId())->not->toBe('G-NOPE');
});

test('login_back requires a count when lockout is enabled and clears it when disabled', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->put(route('admin.system.setting.update.login_back'), ['captcha_enabled' => 'N', 'lockout_enabled' => 'Y', 'lockout_count' => null])
        ->assertSessionHasErrors('lockout_count');

    $this->put(route('admin.system.setting.update.login_back'), ['captcha_enabled' => 'N', 'lockout_enabled' => 'N', 'lockout_count' => 5])
        ->assertSessionHasNoErrors();

    expect(Setting::get('login_back', 'lockout_count'))->toBeNull();
});

test('an account is locked after the configured number of failed logins', function () {
    $this->actingAs(User::where('email', 'admin@microcms.com')->firstOrFail());
    $this->put(route('admin.system.setting.update.login_back'), ['captcha_enabled' => 'N', 'lockout_enabled' => 'Y', 'lockout_count' => 3])
        ->assertSessionHasNoErrors();
    auth()->logout();

    $user = User::factory()->create(['email' => 'lock@example.com', 'user_type' => 'back', 'status' => 'Y']);

    foreach (range(1, 3) as $attempt) {
        $this->post(route('admin.login'), ['email' => 'lock@example.com', 'password' => 'wrong-password'])->assertSessionHasErrors('email');
    }

    $user->refresh();
    expect($user->status)->toBe('N')
        ->and($user->failed_login_count)->toBe(3)
        ->and(LogBackLogin::where('username', 'lock@example.com')->where('result', 'block')->exists())->toBeTrue();

    // รหัสผ่านถูกแต่บัญชีถูกระงับ → เข้าไม่ได้
    $this->post(route('admin.login'), ['email' => 'lock@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});
