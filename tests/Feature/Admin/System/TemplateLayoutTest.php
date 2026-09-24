<?php

use App\Models\SysTemplate;
use App\Support\Template\TemplateZone;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->template = SysTemplate::where('status', 'Y')->firstOrFail();
});

/**
 * payload ของทั้ง 4 โซน = ค่าเริ่มต้นของ TemplateZone ทับด้วย $overrides
 *
 * @return array<string, array<string, mixed>>
 */
function templateLayoutPayload(array $overrides = []): array
{
    $payload = [];

    foreach (TemplateZone::ZONES as $zone) {
        $payload[$zone] = array_replace(TemplateZone::defaults($zone), $overrides[$zone] ?? []);
    }

    return $payload;
}

// ---------------------------------------------------------------- layout

test('layout renders zones, preview data and fonts', function () {
    actingAsUserWithPermissions(['system.template.view']);

    $this->get(route('admin.system.template.layout', $this->template->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/Template/Layout')
            ->where('zones.header.layout_type', 'topbar_main')
            ->has('zones.body.background_color')
            ->has('zones.footer.copyright_status')
            ->has('zones.aside.menu_style')
            ->has('preview.siteName')
            ->has('preview.menu.0.menu_type') // footer กรองเมนูย่อยระดับ 2 ตามประเภท
            ->has('preview.menu.0.children')
            ->has('preview.contact.owner')
            ->has('fonts')
            ->where('can.manage', false)
        );
});

test('layout redirects without system.template.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.system.template.layout', $this->template->id))
        ->assertRedirect(route('admin.system.template.index'));
});

test('layout update saves every zone and stamps layout_updated_at', function () {
    Storage::fake('local');
    $user = actingAsUserWithPermissions(['system.template.manage']);
    $image = fakeFileInfo('bg.jpg', 'bg-hash.jpg', 'jpg', 'image/jpeg');

    $this->put(route('admin.system.template.layout.update', $this->template->id), templateLayoutPayload([
        'header' => ['layout_type' => 'main_menubar', 'menu_style' => 'pill', 'status' => 'N'],
        'body' => ['background_color' => 'transparent', 'background_image_id' => $image->id, 'background_size' => 'cover'],
        'footer' => ['layout_type' => 'site_contact_block', 'heading_font_size' => 24, 'show_fax' => 'N'],
        'aside' => ['menu_style' => 'drilldown', 'toggle_position' => 'left'],
    ]))->assertRedirect(route('admin.system.template.layout', $this->template->id));

    $template = $this->template->fresh(['header', 'body', 'footer', 'aside']);

    expect($template->header->layout_type)->toBe('main_menubar')
        ->and($template->header->status)->toBe('N')
        ->and($template->header->updated_by)->toBe($user->id)
        ->and($template->body->background_image_id)->toBe($image->id)
        ->and($template->body->background_size)->toBe('cover')
        ->and((int) $template->footer->heading_font_size)->toBe(24)
        ->and($template->footer->show_fax)->toBe('N')
        ->and($template->aside->menu_style)->toBe('drilldown')
        ->and($template->layout_updated_at)->not->toBeNull()
        ->and($template->layout_updated_by)->toBe($user->id);
});

test('layout update rejects invalid values', function () {
    actingAsUserWithPermissions(['system.template.manage']);

    $this->put(route('admin.system.template.layout.update', $this->template->id), templateLayoutPayload([
        'header' => ['layout_type' => 'nope', 'menu_text_color' => 'red'],
        'footer' => ['heading_font_family' => 'Comic Sans'],
        'aside' => ['menu_style' => 'mega'],
    ]))->assertSessionHasErrors(['header.layout_type', 'header.menu_text_color', 'footer.heading_font_family', 'aside.menu_style']);

    expect($this->template->fresh()->header->layout_type)->toBe('topbar_main');
});

test('layout update is forbidden without system.template.manage', function () {
    actingAsUserWithPermissions(['system.template.view']);

    $this->put(route('admin.system.template.layout.update', $this->template->id), templateLayoutPayload([
        'header' => ['layout_type' => 'main_only'],
    ]))->assertRedirect(route('admin.system.template.index'));

    expect($this->template->fresh()->header->layout_type)->toBe('topbar_main');
});

// ---------------------------------------------------------------- code

test('code tab renders and saves custom css/js', function () {
    actingAsUserWithPermissions(['system.template.view', 'system.template.manage']);

    $this->get(route('admin.system.template.code', $this->template->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/System/Template/Code'));

    $this->put(route('admin.system.template.code.update', $this->template->id), [
        'custom_css_status' => 'Y',
        'custom_css' => 'body { color: red; }',
        'custom_js_status' => 'N',
        'custom_js' => 'console.log(1);',
    ])->assertRedirect(route('admin.system.template.code', $this->template->id));

    $template = $this->template->fresh();

    expect($template->custom_css_status)->toBe('Y')
        ->and($template->custom_css)->toBe('body { color: red; }')
        ->and($template->custom_js_status)->toBe('N')
        ->and($template->custom_js)->toBe('console.log(1);');
});

// ---------------------------------------------------------------- loading

test('loading tab saves spinner settings', function () {
    actingAsUserWithPermissions(['system.template.view', 'system.template.manage']);

    $this->get(route('admin.system.template.loading', $this->template->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/System/Template/Loading'));

    $this->put(route('admin.system.template.loading.update', $this->template->id), [
        'loading_status' => 'Y',
        'loading_type' => 'spinner',
        'loading_spinner' => 'dots',
        'loading_color' => '#FF0000',
        'loading_background_color' => 'transparent',
        'loading_image_id' => null,
    ])->assertRedirect(route('admin.system.template.loading', $this->template->id));

    $template = $this->template->fresh();

    expect($template->loading_status)->toBe('Y')
        ->and($template->loading_spinner)->toBe('dots')
        ->and($template->loading_background_color)->toBe('transparent');
});

test('loading image type requires an image when enabled', function () {
    actingAsUserWithPermissions(['system.template.manage']);

    $this->put(route('admin.system.template.loading.update', $this->template->id), [
        'loading_status' => 'Y',
        'loading_type' => 'image',
        'loading_spinner' => 'ring',
        'loading_color' => '#2563EB',
        'loading_background_color' => '#FFFFFF',
        'loading_image_id' => null,
    ])->assertSessionHasErrors('loading_image_id');
});
