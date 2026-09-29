<?php

use App\Models\LogBackAction;
use App\Models\SysSetting;
use App\Support\ContactusSetting;
use App\Support\Front\FrontCache;
use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function contactusSettingPayload(array $overrides = []): array
{
    return array_merge(ContactusSetting::defaults(), ['map_image_id' => null, 'latitude' => null, 'longitude' => null], $overrides);
}

test('setting pages redirect to dashboard without contactus.setting.manage', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.contactus.setting.index'))->assertRedirect(route('admin.dashboard'));
    $this->get(route('admin.contactus.setting.clearcache'))->assertRedirect(route('admin.dashboard'));
    $this->put(route('admin.contactus.setting.update'), contactusSettingPayload())->assertRedirect(route('admin.dashboard'));

    foreach (['setting', 'front', 'all'] as $name) {
        $this->post(route("admin.contactus.setting.clearcache.{$name}"))->assertRedirect(route('admin.dashboard'));
    }
});

test('seeder writes the default settings', function () {
    expect(SysSetting::where('group', 'contactus')->count())->toBe(count(ContactusSetting::defaults()))
        ->and(ContactusSetting::all())->toBe(ContactusSetting::defaults());
});

test('index renders defaults and warns when turnstile and google map key are missing', function () {
    actingAsUserWithPermissions(['contactus.setting.manage']);

    $this->get(route('admin.contactus.setting.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Contactus/Setting/Index')
            ->where('settings.display_type', 'split_info')
            ->where('settings.form_email_required', 'Y')
            ->where('turnstileConfigured', false)
            ->where('googleMapKeySet', false)
            ->where('canManageSystemSetting', false)
        );
});

test('index reports turnstile and google map key once configured', function () {
    actingAsUserWithPermissions(['contactus.setting.manage', 'system.setting.manage']);

    SysSetting::create(['group' => 'turnstile', 'name' => 'site_key', 'value' => 'site']);
    SysSetting::create(['group' => 'turnstile', 'name' => 'key_secret', 'value' => 'secret']);
    SysSetting::create(['group' => 'google_map', 'name' => 'api_key', 'value' => 'key']);
    Setting::forgetAll();

    $this->get(route('admin.contactus.setting.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('turnstileConfigured', true)
            ->where('googleMapKeySet', true)
            ->where('canManageSystemSetting', true)
        );
});

test('update saves settings and logs it', function () {
    actingAsUserWithPermissions(['contactus.setting.manage']);

    $this->put(route('admin.contactus.setting.update'), contactusSettingPayload([
        'display_type' => 'half',
        'show_google_map' => 'Y',
        'latitude' => '13.756331',
        'longitude' => '100.501765',
        'owner_font_family' => 'Kanit',
        'form_company_show' => 'Y',
        'form_company_required' => 'Y',
    ]))->assertSessionHasNoErrors()->assertSessionHas('success');

    $settings = ContactusSetting::all();

    expect($settings['display_type'])->toBe('half')
        ->and($settings['latitude'])->toBe('13.756331')
        ->and($settings['owner_font_family'])->toBe('Kanit')
        ->and(ContactusSetting::formFields($settings))->toHaveKey('company', true)
        ->and(LogBackAction::where('module_code', 'contactus.setting')->where('action_type', 'update')->exists())->toBeTrue();
});

test('update validates display type, font, color and required coordinates', function () {
    actingAsUserWithPermissions(['contactus.setting.manage']);

    $this->put(route('admin.contactus.setting.update'), contactusSettingPayload([
        'display_type' => 'grid',
        'address_font_family' => 'Comic Sans',
        'email_color' => 'red',
        'show_google_map' => 'Y',
        'show_map_image' => 'Y',
    ]))->assertSessionHasErrors(['display_type', 'address_font_family', 'email_color', 'latitude', 'longitude', 'map_image_id']);

    $this->put(route('admin.contactus.setting.update'), contactusSettingPayload([
        'show_google_map' => 'Y',
        'latitude' => '123',
        'longitude' => '200',
    ]))->assertSessionHasErrors(['latitude', 'longitude']);
});

test('clear cache page renders and clear actions bump the front cache version', function () {
    actingAsUserWithPermissions(['contactus.setting.manage']);

    $this->get(route('admin.contactus.setting.clearcache'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Contactus/Setting/ClearCache'));

    foreach (['setting', 'front', 'all'] as $name) {
        $before = FrontCache::version();
        $this->post(route("admin.contactus.setting.clearcache.{$name}"))->assertSessionHas('success');
        expect(FrontCache::version())->not->toBe($before);
    }

    expect(LogBackAction::where('module_code', 'contactus.setting')->where('action_type', 'clear')->count())->toBe(3);
});

test('system setting saves the google map api key', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->put(route('admin.system.setting.update.google_map'), ['api_key' => 'AIza-test'])->assertSessionHas('success');

    expect(Setting::get('google_map', 'api_key'))->toBe('AIza-test');

    $this->post(route('admin.system.setting.clearcache.group', 'contactus'))->assertSessionHas('success');
    $this->post(route('admin.system.setting.clearcache.group', 'google_map'))->assertSessionHas('success');
});
