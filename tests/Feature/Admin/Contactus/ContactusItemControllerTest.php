<?php

use App\Models\ContactusItem;
use App\Models\LogBackAction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function makeContactusItem(array $attributes = []): ContactusItem
{
    $item = new ContactusItem;
    $item->forceFill(array_merge([
        'fullname' => 'สมชาย ใจดี',
        'email' => 'somchai@example.com',
        'subject' => 'สอบถามข้อมูล',
        'detail' => "บรรทัดแรก\nบรรทัดสอง",
        'lang' => 'th',
        'remote_ip' => '203.0.113.5',
        'process_status' => 'unread',
    ], $attributes))->save();

    return $item;
}

test('pages redirect to dashboard without permission', function () {
    actingAsUserWithPermissions([]);
    $item = makeContactusItem();

    $this->get(route('admin.contactus.item.index'))->assertRedirect(route('admin.dashboard'));
    $this->get(route('admin.contactus.item.edit', $item))->assertRedirect(route('admin.dashboard'));
    $this->put(route('admin.contactus.item.update', $item), ['process_status' => 'done'])->assertRedirect(route('admin.dashboard'));
});

test('index lists newest first and filters by search, status and date', function () {
    actingAsUserWithPermissions(['contactus.item.view']);

    $old = makeContactusItem(['fullname' => 'เก่า', 'company' => 'Acme Co']);
    $old->forceFill(['created_at' => Carbon::parse('2026-01-01 10:00:00')])->save();
    makeContactusItem(['fullname' => 'ใหม่', 'process_status' => 'done']);

    $this->get(route('admin.contactus.item.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Contactus/Item/Index')
            ->has('items.data', 2)
            ->where('items.data.0.fullname', 'ใหม่')
        );

    $this->get(route('admin.contactus.item.index', ['q' => 'Acme']))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.fullname', 'เก่า'));

    $this->get(route('admin.contactus.item.index', ['process_status' => 'done']))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.fullname', 'ใหม่'));

    $this->get(route('admin.contactus.item.index', ['date_to' => '2026-01-31']))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.fullname', 'เก่า'));
});

test('opening an unread item marks it read and logs a view', function () {
    $user = actingAsUserWithPermissions(['contactus.item.view']);
    $item = makeContactusItem();

    $this->get(route('admin.contactus.item.edit', $item))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Contactus/Item/Edit')
            ->where('item.process_status', 'read')
            ->where('meta.remote_ip', '203.0.113.5')
            ->where('meta.lang', 'th')
            ->where('can.manage', false)
            ->where('systemInfo.updated_at', null)
        );

    $item->refresh();

    expect($item->process_status)->toBe('read')
        ->and($item->read_by)->toBe($user->id)
        ->and($item->updated_by)->toBeNull()
        ->and(LogBackAction::where('module_code', 'contactus.item')->where('action_type', 'view')->where('ref_id', $item->id)->exists())->toBeTrue();
});

test('update saves status and note with manage permission only', function () {
    actingAsUserWithPermissions(['contactus.item.view']);
    $item = makeContactusItem();

    $this->put(route('admin.contactus.item.update', $item), ['process_status' => 'done'])->assertRedirect(route('admin.dashboard'));

    $user = actingAsUserWithPermissions(['contactus.item.view', 'contactus.item.manage']);

    $this->put(route('admin.contactus.item.update', $item), ['process_status' => 'unknown'])->assertSessionHasErrors('process_status');

    $this->put(route('admin.contactus.item.update', $item), ['process_status' => 'considering', 'note' => 'โทรกลับแล้ว'])
        ->assertSessionHas('success');

    $item->refresh();

    expect($item->process_status)->toBe('considering')
        ->and($item->note)->toBe('โทรกลับแล้ว')
        ->and($item->updated_by)->toBe($user->id)
        ->and(LogBackAction::where('module_code', 'contactus.item')->where('action_type', 'update')->exists())->toBeTrue();
});
