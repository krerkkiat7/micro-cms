<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/admin/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/admin/register', [
        'titlename' => 'นาย',
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('admin.dashboard', absolute: false));
});

test('email may be reused when it belongs to a front user', function () {
    User::factory()->front()->create(['email' => 'shared@example.com']);

    $response = $this->post('/admin/register', [
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'shared@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertValid('email');
    $this->assertAuthenticated();
    expect(User::where('email', 'shared@example.com')->where('user_type', 'back')->exists())->toBeTrue();
});

test('email may be reused when the previous back user was soft deleted', function () {
    User::factory()->create(['email' => 'gone@example.com'])->delete();

    $response = $this->post('/admin/register', [
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'gone@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertValid('email');
    $this->assertAuthenticated();
});

test('email can not be reused by an active back user', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this
        ->from('/admin/register')
        ->post('/admin/register', [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertInvalid('email');
    $this->assertGuest();
});
