<?php

use App\Models\FileInfo;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the shared auth.user prop exposes the current profile image hash_name', function () {
    $user = User::factory()->create();
    $image = FileInfo::create([
        'user_id' => $user->id, 'name' => 'me.jpg', 'hash_name' => 'me-hash.jpg',
        'extension' => 'jpg', 'path' => 'x', 'status' => 'Y',
    ]);
    $user->update(['profile_image_id' => $image->id]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.user.profile_image_hash_name', 'me-hash.jpg'));
});

test('the shared auth.user prop is null when no profile image is set', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.user.profile_image_hash_name', null));
});
