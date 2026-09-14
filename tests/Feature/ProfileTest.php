<?php

use App\Models\FileInfo;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/admin/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/admin/profile', [
            'titlename' => 'นาย',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success')
        ->assertRedirect('/admin/profile');

    $user->refresh();

    $this->assertSame('Test', $user->firstname);
    $this->assertSame('User', $user->lastname);
    $this->assertSame('นาย Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/admin/profile', [
            'titlename' => 'นาย',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/admin/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('titlename, firstname, lastname and email are required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/admin/profile', [
            'titlename' => '',
            'firstname' => '',
            'lastname' => '',
            'email' => '',
        ])
        ->assertInvalid(['titlename', 'firstname', 'lastname', 'email']);
});

test('email must be a valid format', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/admin/profile', [
            'titlename' => $user->titlename,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => 'not-an-email',
        ])
        ->assertInvalid('email');
});

test('email must not collide with another back-office user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->patch('/admin/profile', [
            'titlename' => $user->titlename,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $other->email,
        ])
        ->assertInvalid('email');
});

test('profile image must belong to the user themself', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $othersImage = FileInfo::create([
        'user_id' => $other->id, 'name' => 'theirs.jpg', 'hash_name' => 'theirs.jpg',
        'extension' => 'jpg', 'path' => 'x', 'status' => 'Y',
    ]);

    $this->actingAs($user)
        ->patch('/admin/profile', [
            'titlename' => $user->titlename,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'profile_image_id' => $othersImage->id,
        ])
        ->assertInvalid('profile_image_id');
});

test('profile image can be set to one of the user\'s own files', function () {
    $user = User::factory()->create();
    $image = FileInfo::create([
        'user_id' => $user->id, 'name' => 'me.jpg', 'hash_name' => 'me.jpg',
        'extension' => 'jpg', 'path' => 'x', 'status' => 'Y',
    ]);

    $this->actingAs($user)
        ->patch('/admin/profile', [
            'titlename' => $user->titlename,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'profile_image_id' => $image->id,
        ])
        ->assertSessionHasNoErrors();

    $this->assertSame($image->id, $user->fresh()->profile_image_id);
});

test('edit exposes the current profile image and falls back to null when unset', function () {
    $user = User::factory()->create();
    $image = FileInfo::create([
        'user_id' => $user->id, 'name' => 'avatar.png', 'hash_name' => 'avatar.png',
        'extension' => 'png', 'path' => 'x', 'status' => 'Y',
    ]);
    $user->update(['profile_image_id' => $image->id]);

    $this->actingAs($user)
        ->get('/admin/profile')
        ->assertInertia(fn (Assert $page) => $page->where('profileImage.hash_name', 'avatar.png'));

    $withoutImage = User::factory()->create();
    $this->actingAs($withoutImage)
        ->get('/admin/profile')
        ->assertInertia(fn (Assert $page) => $page->where('profileImage', null));
});

test('password can be updated with a flash success message', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/admin/password', [
            'current_password' => 'password',
            'password' => 'Aa1!aaaa',
            'password_confirmation' => 'Aa1!aaaa',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
});

test('a weak new password is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/admin/password', [
            'current_password' => 'password',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])
        ->assertInvalid('password');
});

test('the delete-account route no longer exists', function () {
    $user = User::factory()->create();

    // /admin/profile ยังมี GET/PATCH อยู่ — DELETE จึงได้ 405 (method not allowed) ไม่ใช่ 404
    $this->actingAs($user)
        ->delete('/admin/profile')
        ->assertMethodNotAllowed();
});
