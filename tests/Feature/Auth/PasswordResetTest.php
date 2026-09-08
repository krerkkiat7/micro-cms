<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/admin/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/admin/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('front users can not request an admin password reset', function () {
    Notification::fake();

    $user = User::factory()->front()->create();

    $response = $this->from('/admin/forgot-password')
        ->post('/admin/forgot-password', ['email' => $user->email]);

    $response->assertSessionHasErrors('email');
    Notification::assertNothingSent();
});

test('admin and front reset tokens for the same email are independent', function () {
    $back = User::factory()->create(['email' => 'dual@example.com']);
    $front = User::factory()->front()->create(['email' => 'dual@example.com']);

    $backToken = Password::broker('users')->createToken($back);
    $frontToken = Password::broker('front')->createToken($front);

    expect(Password::broker('users')->tokenExists($back, $backToken))->toBeTrue();
    expect(Password::broker('front')->tokenExists($front, $frontToken))->toBeTrue();

    // โทเคนคนละ broker/ตาราง ใช้ข้ามกันไม่ได้
    expect(Password::broker('users')->tokenExists($back, $frontToken))->toBeFalse();

    $this->assertDatabaseHas('password_reset_tokens', ['email' => 'dual@example.com']);
    $this->assertDatabaseHas('front_password_reset_tokens', ['email' => 'dual@example.com']);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/admin/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/admin/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/admin/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/admin/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.login'));

        return true;
    });
});
