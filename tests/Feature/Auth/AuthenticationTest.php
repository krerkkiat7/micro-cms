<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('admin.dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('front users can not authenticate on the admin login', function () {
    $user = User::factory()->front()->create();

    $response = $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('inactive back users are blocked with a message', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertInvalid(['email' => 'ถูกระงับ']);

    // บัญชีถูกระงับ ไม่นับเป็น login ไม่สำเร็จ
    expect($user->fresh()->failed_login_count)->toBe(0);
});

test('failed login is recorded on the user and cleared on success', function () {
    $user = User::factory()->create();

    $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $user->refresh();
    expect($user->failed_login_count)->toBe(1);
    expect($user->last_failed_login_at)->not->toBeNull();

    $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $user->refresh();
    expect($user->failed_login_count)->toBe(0);
    expect($user->last_failed_login_at)->toBeNull();
    expect($user->last_login_at)->not->toBeNull();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/admin/logout');

    $this->assertGuest();
    $response->assertRedirect('/admin/login');
});
