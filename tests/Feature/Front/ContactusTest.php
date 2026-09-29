<?php

use App\Http\Requests\Front\StoreContactusRequest;
use App\Models\ContactusItem;
use App\Models\FrontMenuDetail;
use App\Models\FrontMenuInfo;
use App\Models\PopupItemInfo;
use App\Models\PopupItemPart;
use App\Models\PopupItemPartDetail;
use App\Models\SysSetting;
use App\Support\Front\FrontCache;
use App\Support\FrontMenuType;
use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * หน้าติดต่อเรา (/{lang}/contactus) + ส่งแบบฟอร์ม (docs/PRD-contactus.md) และเมนูประเภทติดต่อเรา/เมนูที่ไม่แสดง
 */
beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    RateLimiter::clear(StoreContactusRequest::throttleKey('127.0.0.1'));
});

function configureTurnstile(): void
{
    SysSetting::create(['group' => 'turnstile', 'name' => 'site_key', 'value' => 'site-key']);
    SysSetting::create(['group' => 'turnstile', 'name' => 'key_secret', 'value' => 'secret-key']);
    Setting::forgetAll();
}

function setContactusSetting(string $name, ?string $value): void
{
    SysSetting::query()->where('group', 'contactus')->where('name', $name)->forceDelete();
    SysSetting::create(['group' => 'contactus', 'name' => $name, 'value' => $value]);
    Setting::forget('contactus');
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function contactusPayload(array $overrides = []): array
{
    return array_merge([
        'fullname' => 'สมชาย ใจดี',
        'phone' => '081-234-5678',
        'email' => 'somchai@example.com',
        'subject' => 'สอบถามบริการ',
        'detail' => "ข้อความบรรทัดแรก\nบรรทัดที่สอง",
        'website' => '',
        'form_token' => Crypt::encryptString((string) (time() - 30)),
        'cf-turnstile-response' => 'token',
    ], $overrides);
}

function makeContactusMenu(array $attributes = [], string $name = 'ติดต่อเรา'): FrontMenuInfo
{
    $menu = FrontMenuInfo::create(array_merge(['menu_type' => FrontMenuType::CONTACTUS, 'status' => 'Y', 'sort_order' => 99], $attributes));
    FrontMenuDetail::create(['id' => $menu->id, 'lang' => 'th', 'name' => $name, 'title' => 'หัวเรื่องติดต่อเรา', 'status' => 'Y']);
    FrontCache::forgetAll();

    return $menu;
}

test('contactus page renders without a form while turnstile is not configured', function () {
    $this->get('/th/contactus')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Front/Contactus/Item')
            ->where('contactus.displayType', 'split_info')
            ->where('contactus.texts.address.show', true)
            ->where('form', null)
            ->where('sent', false)
        );

    $this->post('/th/contactus', contactusPayload())->assertNotFound();
    expect(ContactusItem::count())->toBe(0);
});

test('form is offered with configured fields once turnstile is configured', function () {
    configureTurnstile();
    setContactusSetting('form_company_show', 'Y');

    $this->get('/en/contactus')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('form.siteKey', 'site-key')
            ->where('form.fields', ['fullname' => true, 'company' => false, 'phone' => false, 'email' => true, 'subject' => true, 'detail' => true])
            ->has('form.token')
        );

    setContactusSetting('show_form', 'N');

    $this->get('/th/contactus')->assertInertia(fn (Assert $page) => $page->where('form', null));
    $this->post('/th/contactus', contactusPayload())->assertNotFound();
});

test('a valid submission is stored with lang, ip and only enabled fields', function () {
    configureTurnstile();
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

    $this->post('/en/contactus', contactusPayload(['company' => 'Hidden Co', 'process_status' => 'done']))
        ->assertRedirect('/en/contactus')
        ->assertSessionHas('contactus_sent', true);

    $item = ContactusItem::sole();

    expect($item->fullname)->toBe('สมชาย ใจดี')
        ->and($item->detail)->toBe("ข้อความบรรทัดแรก\nบรรทัดที่สอง")
        ->and($item->company)->toBeNull() // ฟิลด์ที่ตั้งค่าซ่อนไว้ไม่ถูกบันทึก
        ->and($item->process_status)->toBe('unread')
        ->and($item->lang)->toBe('en')
        ->and($item->remote_ip)->toBe('127.0.0.1')
        ->and($item->created_by)->toBeNull();

    Http::assertSent(fn ($request) => $request['secret'] === 'secret-key' && $request['response'] === 'token');
});

test('validation follows the configured required fields', function () {
    configureTurnstile();
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

    $this->post('/th/contactus', contactusPayload(['fullname' => '', 'email' => 'not-an-email', 'subject' => '']))
        ->assertSessionHasErrors(['fullname', 'email', 'subject']);

    setContactusSetting('form_subject_required', 'N');
    setContactusSetting('form_phone_required', 'Y');

    $this->post('/th/contactus', contactusPayload(['subject' => '', 'phone' => '']))
        ->assertSessionHasErrors(['phone'])
        ->assertSessionDoesntHaveErrors(['subject']);

    $this->post('/th/contactus', contactusPayload(['phone' => '<script>']))->assertSessionHasErrors(['phone']);

    expect(ContactusItem::count())->toBe(0);
});

test('a failed captcha is rejected', function () {
    configureTurnstile();
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

    $this->post('/th/contactus', contactusPayload())->assertSessionHasErrors('cf-turnstile-response');
    $this->post('/th/contactus', contactusPayload(['cf-turnstile-response' => '']))->assertSessionHasErrors('cf-turnstile-response');

    expect(ContactusItem::count())->toBe(0);
});

test('honeypot submissions look successful but are not stored', function () {
    configureTurnstile();
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

    $this->post('/th/contactus', contactusPayload(['website' => 'http://spam.example']))
        ->assertRedirect('/th/contactus')
        ->assertSessionHas('contactus_sent', true);

    expect(ContactusItem::count())->toBe(0);
});

test('a form sent too quickly or with a forged token is rejected', function () {
    configureTurnstile();
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

    $this->post('/th/contactus', contactusPayload(['form_token' => Crypt::encryptString((string) time())]))->assertSessionHasErrors('form');
    $this->post('/th/contactus', contactusPayload(['form_token' => 'forged']))->assertSessionHasErrors('form');
    $this->post('/th/contactus', contactusPayload(['form_token' => Crypt::encryptString((string) (time() - 3 * 86400))]))->assertSessionHasErrors('form');

    expect(ContactusItem::count())->toBe(0);
});

test('submissions are rate limited per ip', function () {
    configureTurnstile();
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

    for ($i = 0; $i < StoreContactusRequest::MAX_ATTEMPTS; $i++) {
        $this->post('/th/contactus', contactusPayload())->assertSessionHasNoErrors();
    }

    $this->post('/th/contactus', contactusPayload())->assertSessionHasErrors('form');

    expect(ContactusItem::count())->toBe(StoreContactusRequest::MAX_ATTEMPTS);
});

test('a contactus menu links to the page and supplies its header and breadcrumb', function () {
    $menu = makeContactusMenu();

    $this->get('/th/contactus')
        ->assertInertia(fn (Assert $page) => $page
            ->where('header.hero.title', 'หัวเรื่องติดต่อเรา')
            ->where('header.activeMenuIds', [$menu->id])
            ->where('header.breadcrumb.1.name', 'ติดต่อเรา')
            ->where('contactus.title', 'ติดต่อเรา')
        );

    $this->get('/th/contactus')->assertInertia(fn (Assert $page) => $page
        ->where('front.menu', fn ($tree) => collect($tree)->contains(fn ($item) => $item['id'] === $menu->id && $item['url'] === route('front.contactus.item', ['lang' => 'th'])))
    );
});

test('a hidden menu keeps its header settings but the breadcrumb is only home and the menu itself', function () {
    $parent = FrontMenuInfo::create(['menu_type' => FrontMenuType::HEADING, 'status' => 'Y', 'sort_order' => 98]);
    FrontMenuDetail::create(['id' => $parent->id, 'lang' => 'th', 'name' => 'เกี่ยวกับเรา', 'status' => 'Y']);
    $menu = makeContactusMenu(['parent_id' => $parent->id, 'status' => 'N'], 'ติดต่อเรา (ซ่อน)');

    $this->get('/th/contactus')
        ->assertInertia(fn (Assert $page) => $page
            ->where('header.hero.title', 'หัวเรื่องติดต่อเรา')
            ->where('header.activeMenuIds', [$menu->id])
            ->has('header.breadcrumb', 2)
            ->where('header.breadcrumb.1.name', 'ติดต่อเรา (ซ่อน)')
            ->where('front.menu', fn ($tree) => ! collect($tree)->flatMap(fn ($item) => [$item, ...$item['children']])->contains(fn ($item) => $item['id'] === $menu->id))
        );
});

test('a visible menu wins over a hidden one for the same target', function () {
    $hidden = makeContactusMenu(['status' => 'N', 'sort_order' => 1], 'ซ่อน');
    $visible = makeContactusMenu(['status' => 'Y', 'sort_order' => 50], 'แสดง');

    $this->get('/th/contactus')->assertInertia(fn (Assert $page) => $page->where('header.activeMenuIds', [$visible->id]));

    expect($hidden->id)->not->toBe($visible->id);
});

test('popups can target the contactus menu', function () {
    $menu = makeContactusMenu();

    expect(PopupItemInfo::MENU_TYPES)->toContain(FrontMenuType::CONTACTUS);

    $popup = PopupItemInfo::create([
        'name' => 'Contact popup',
        'display_type' => 'modal',
        'menu_mode' => 'selected',
        'publish_date' => now()->subDay(),
        'status' => 'Y',
    ]);
    $popup->menus()->attach($menu->id);
    $part = PopupItemPart::create(['popup_item_info_id' => $popup->id, 'part_type' => 'text', 'sort_order' => 1, 'status' => 'Y']);
    PopupItemPartDetail::create(['id' => $part->id, 'lang' => 'th', 'detail' => '<p>ติดต่อเรา</p>']);
    FrontCache::forgetAll();

    $this->get('/th/contactus')->assertInertia(fn (Assert $page) => $page->where('popups', fn ($popups) => collect($popups)->contains('id', $popup->id)));
});
