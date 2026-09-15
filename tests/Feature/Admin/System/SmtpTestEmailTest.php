<?php

use App\Mail\TestSmtpMail;
use App\Models\SysSetting;
use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function testEmailPayload(array $overrides = []): array
{
    return array_merge([
        'to' => 'someone@example.com',
        'subject' => 'ทดสอบการส่งอีเมล',
        'body' => 'เนื้อหาทดสอบ',
    ], $overrides);
}

test('guests are redirected to login', function () {
    $this->post(route('admin.system.setting.smtp.test'), testEmailPayload())
        ->assertRedirect(route('admin.login'));
});

test('a user without system.setting.manage is redirected to the dashboard', function () {
    actingAsUserWithPermissions([]);

    $this->post(route('admin.system.setting.smtp.test'), testEmailPayload())
        ->assertRedirect(route('admin.dashboard'));
});

test('to, subject and body are required', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->post(route('admin.system.setting.smtp.test'), [])
        ->assertInvalid(['to', 'subject', 'body']);
});

test('to must be a valid email address', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->post(route('admin.system.setting.smtp.test'), testEmailPayload(['to' => 'not-an-email']))
        ->assertInvalid(['to']);
});

test('sending is refused with a friendly error when no SMTP host has been saved yet', function () {
    Mail::fake();
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->post(route('admin.system.setting.smtp.test'), testEmailPayload())
        ->assertInvalid(['send']);

    Mail::assertNothingSent();
});

test('sends the test email using the saved SMTP settings and shows a success message', function () {
    Mail::fake();
    actingAsUserWithPermissions(['system.setting.manage']);

    SysSetting::create(['group' => 'smtp', 'name' => 'host', 'value' => 'smtp.example.com']);
    Setting::forget('smtp');

    $payload = testEmailPayload(['to' => 'test-recipient@example.com', 'subject' => 'หัวข้อทดสอบ', 'body' => 'เนื้อหาทดสอบส่ง']);

    $this->post(route('admin.system.setting.smtp.test'), $payload)
        ->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertSent(TestSmtpMail::class, function (TestSmtpMail $mail) {
        return $mail->hasTo('test-recipient@example.com')
            && $mail->subjectLine === 'หัวข้อทดสอบ'
            && $mail->bodyText === 'เนื้อหาทดสอบส่ง';
    });

    $this->assertDatabaseHas('log_back_action', [
        'module_code' => 'system.setting.smtp',
        'action_type' => 'test',
        'value_string' => 'test-recipient@example.com',
    ]);
});
