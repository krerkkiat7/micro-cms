<?php

use Database\Seeders\IntropageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the intropage on the root path in the default language', function () {
    $this->seed(IntropageSeeder::class);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Front/Intropage/Index')->where('front.lang', 'th'));
});
