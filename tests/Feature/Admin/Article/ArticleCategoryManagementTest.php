<?php

use App\Models\ArticleCategoryDetail;
use App\Models\ArticleCategoryInfo;
use App\Models\LogBackAction;
use App\Models\SysSetting;
use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // seed สร้างข้อมูลตัวอย่างหมวดหมู่ (ArticleSeeder) ด้วย — เทสด้านล่างจึงค้นหา/กรองด้วยคำเฉพาะของเทสเอง
    // ไม่พึ่งจำนวนแถวทั้งหมด และภาษาระบบที่ seed คือ th (หลัก) + en
    $this->seed(DatabaseSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function validCategoryPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'sort_order' => '1',
        'status' => 'Y',
        'detail' => [
            'th' => [
                'title' => 'หมวดหมู่ทดสอบ',
                'intro_text' => 'ข้อความเกริ่นนำภาษาไทย',
                'detail' => '<p>รายละเอียดภาษาไทย</p>',
                'slug' => 'test-category-th',
            ],
            'en' => [
                'title' => 'Test Category',
                'intro_text' => 'English intro',
                'detail' => '<p>English detail</p>',
                'slug' => 'test-category-en',
            ],
        ],
    ], $overrides);
}

// ---------------------------------------------------------------- index

test('index redirects to dashboard without article.category.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.article.category.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('index renders for a user with article.category.view', function () {
    actingAsUserWithPermissions(['article.category.view']);

    $this->get(route('admin.article.category.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Article/Category/Index')
            ->has('categories.data')
        );
});

test('index shows the default-language title and filters by search term', function () {
    actingAsUserWithPermissions(['article.category.view', 'article.category.manage']);

    $this->post(route('admin.article.category.store'), validCategoryPayload());

    $this->get(route('admin.article.category.index', ['q' => 'หมวดหมู่ทดสอบ']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories.data', 1)
            ->where('categories.data.0.title', 'หมวดหมู่ทดสอบ')
        );

    $this->get(route('admin.article.category.index', ['q' => 'ไม่มีอยู่จริงแน่นอน']))
        ->assertInertia(fn (Assert $page) => $page->has('categories.data', 0));
});

test('index defaults to sorting by title ascending and accepts a sort override', function () {
    actingAsUserWithPermissions(['article.category.view', 'article.category.manage']);

    $this->post(route('admin.article.category.store'), validCategoryPayload([
        'sort_order' => '9',
        'detail' => ['th' => ['title' => 'ก ทดสอบเรียงลำดับ A', 'slug' => 'sort-a-th'], 'en' => ['title' => 'Sort A', 'slug' => 'sort-a-en']],
    ]));
    $this->post(route('admin.article.category.store'), validCategoryPayload([
        'sort_order' => '1',
        'detail' => ['th' => ['title' => 'ข ทดสอบเรียงลำดับ B', 'slug' => 'sort-b-th'], 'en' => ['title' => 'Sort B', 'slug' => 'sort-b-en']],
    ]));

    // ค่าเริ่มต้น: ชื่อ (ภาษาหลัก) น้อยไปมาก — "ก ทดสอบ..." ต้องมาก่อน "ข ทดสอบ..."
    $this->get(route('admin.article.category.index', ['q' => 'ทดสอบเรียงลำดับ']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sort', 'title')
            ->where('direction', 'asc')
            ->where('categories.data.0.title', fn ($title) => str_starts_with($title, 'ก'))
        );

    // เรียงตามลำดับ (sort_order) น้อยไปมาก — รายการ sort_order=1 ต้องมาก่อน sort_order=9
    $this->get(route('admin.article.category.index', ['q' => 'ทดสอบเรียงลำดับ', 'sort' => 'sort_order', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sort', 'sort_order')
            ->where('categories.data.0.sort_order', 1)
        );
});

test('index filters by status', function () {
    actingAsUserWithPermissions(['article.category.view', 'article.category.manage']);

    $this->post(route('admin.article.category.store'), validCategoryPayload([
        'status' => 'N',
        'detail' => ['th' => ['title' => 'หมวดหมู่ปิดใช้งาน'], 'en' => ['title' => 'Disabled Category']],
    ]));

    $this->get(route('admin.article.category.index', ['q' => 'หมวดหมู่ปิดใช้งาน', 'status' => 'N']))
        ->assertInertia(fn (Assert $page) => $page->has('categories.data', 1));

    $this->get(route('admin.article.category.index', ['q' => 'หมวดหมู่ปิดใช้งาน', 'status' => 'Y']))
        ->assertInertia(fn (Assert $page) => $page->has('categories.data', 0));
});

// ---------------------------------------------------------------- add / store

test('add page redirects without article.category.manage', function () {
    actingAsUserWithPermissions(['article.category.view']);

    $this->get(route('admin.article.category.add'))
        ->assertRedirect(route('admin.article.category.index'));
});

test('add page renders with the system languages', function () {
    actingAsUserWithPermissions(['article.category.manage']);

    $this->get(route('admin.article.category.add'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Article/Category/Add')
            ->where('languages', [
                ['code' => 'th', 'is_default' => true],
                ['code' => 'en', 'is_default' => false],
            ])
        );
});

test('languages are ordered with the default language first, then alphabetically', function () {
    actingAsUserWithPermissions(['article.category.manage']);

    // ตั้งค่าให้ลำดับที่เก็บใน sys_setting ไม่ใช่ default-first และไม่เรียงตามตัวอักษร
    // เพื่อยืนยันว่า languageOptions() เป็นผู้จัดลำดับเอง ไม่ใช่แค่คืนค่าตามที่เก็บไว้
    SysSetting::where('group', 'site')->where('name', 'lang_selected')->update(['value' => 'en,zh,th']);
    SysSetting::where('group', 'site')->where('name', 'lang_default')->update(['value' => 'th']);
    Setting::forget('site');

    $this->get(route('admin.article.category.add'))
        ->assertInertia(fn (Assert $page) => $page->where('languages', [
            ['code' => 'th', 'is_default' => true],
            ['code' => 'en', 'is_default' => false],
            ['code' => 'zh', 'is_default' => false],
        ]));
});

test('store requires the title only for the default language', function () {
    actingAsUserWithPermissions(['article.category.manage']);

    $this->from(route('admin.article.category.add'))
        ->post(route('admin.article.category.store'), validCategoryPayload([
            'detail' => ['th' => ['title' => ''], 'en' => ['title' => '']],
        ]))
        ->assertInvalid(['detail.th.title'])
        ->assertValid(['detail.en.title']);
});

test('store creates the category with per-language details and logs the action', function () {
    $me = actingAsUserWithPermissions(['article.category.manage']);

    $response = $this->post(route('admin.article.category.store'), validCategoryPayload());

    $category = ArticleCategoryInfo::query()->latest('id')->first();

    expect($category)->not->toBeNull()
        ->and($category->status)->toBe('Y')
        ->and($category->created_by)->toBe($me->id);

    $th = ArticleCategoryDetail::where('id', $category->id)->where('lang', 'th')->first();
    $en = ArticleCategoryDetail::where('id', $category->id)->where('lang', 'en')->first();

    expect($th->title)->toBe('หมวดหมู่ทดสอบ')
        ->and($th->slug)->toBe('test-category-th')
        ->and($en->title)->toBe('Test Category')
        ->and($en->created_by)->toBe($me->id);

    $response->assertRedirect(route('admin.article.category.edit', $category->id))
        ->assertSessionHas('success');

    expect(LogBackAction::where('module_code', 'article.category')
        ->where('action_type', 'create')
        ->where('ref_id', $category->id)
        ->exists())->toBeTrue();
});

test('store rejects a slug that already exists in the same language', function () {
    actingAsUserWithPermissions(['article.category.manage']);

    $this->post(route('admin.article.category.store'), validCategoryPayload());

    // slug ซ้ำเฉพาะภาษา th — ต้อง invalid แค่ detail.th.slug (en ยังไม่ชนเพราะ slug คนละอัน)
    $this->from(route('admin.article.category.add'))
        ->post(route('admin.article.category.store'), validCategoryPayload([
            'detail' => ['th' => ['slug' => 'test-category-th'], 'en' => ['slug' => 'another-en-slug']],
        ]))
        ->assertInvalid(['detail.th.slug']);
});

// ---------------------------------------------------------------- edit / update

test('edit redirects for a missing or soft-deleted category', function () {
    actingAsUserWithPermissions(['article.category.view']);

    $this->get(route('admin.article.category.edit', 999999))
        ->assertRedirect(route('admin.article.category.index'));

    $me = actingAsUserWithPermissions(['article.category.manage', 'article.category.view', 'article.category.delete']);
    $this->post(route('admin.article.category.store'), validCategoryPayload());
    $category = ArticleCategoryInfo::query()->latest('id')->first();
    $this->delete(route('admin.article.category.destroy', $category->id));

    $this->get(route('admin.article.category.edit', $category->id))
        ->assertRedirect(route('admin.article.category.index'));
});

test('edit renders category details keyed by language and logs access + view action', function () {
    actingAsUserWithPermissions(['article.category.manage', 'article.category.view']);
    $this->post(route('admin.article.category.store'), validCategoryPayload());
    $category = ArticleCategoryInfo::query()->latest('id')->first();

    $this->get(route('admin.article.category.edit', $category->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Article/Category/Edit')
            ->where('details.th.title', 'หมวดหมู่ทดสอบ')
            ->where('details.en.title', 'Test Category')
            ->where('can.manage', true)
            ->where('can.delete', false)
        );

    expect(LogBackAction::where('module_code', 'article.category')
        ->where('action_type', 'view')
        ->where('ref_id', $category->id)
        ->exists())->toBeTrue();
});

test('update saves changes to the info row and every language detail row', function () {
    $me = actingAsUserWithPermissions(['article.category.manage']);
    $this->post(route('admin.article.category.store'), validCategoryPayload());
    $category = ArticleCategoryInfo::query()->latest('id')->first();

    $payload = validCategoryPayload([
        'sort_order' => '5',
        'detail' => ['th' => ['title' => 'ชื่อใหม่'], 'en' => ['title' => 'Updated Name']],
    ]);

    $this->put(route('admin.article.category.update', $category->id), $payload)
        ->assertRedirect(route('admin.article.category.edit', $category->id))
        ->assertSessionHas('success');

    expect($category->fresh()->sort_order)->toBe(5)
        ->and($category->fresh()->updated_by)->toBe($me->id);

    $th = ArticleCategoryDetail::where('id', $category->id)->where('lang', 'th')->first();
    expect($th->title)->toBe('ชื่อใหม่')
        ->and($th->updated_by)->toBe($me->id);

    expect(LogBackAction::where('module_code', 'article.category')
        ->where('action_type', 'update')
        ->where('ref_id', $category->id)
        ->exists())->toBeTrue();
});

test('update redirects without article.category.manage', function () {
    actingAsUserWithPermissions(['article.category.manage']);
    $this->post(route('admin.article.category.store'), validCategoryPayload());
    $category = ArticleCategoryInfo::query()->latest('id')->first();

    actingAsUserWithPermissions(['article.category.view']);

    $this->put(route('admin.article.category.update', $category->id), validCategoryPayload())
        ->assertRedirect(route('admin.article.category.index'));
});

// ---------------------------------------------------------------- destroy

test('destroy redirects without article.category.delete', function () {
    actingAsUserWithPermissions(['article.category.manage']);
    $this->post(route('admin.article.category.store'), validCategoryPayload());
    $category = ArticleCategoryInfo::query()->latest('id')->first();

    actingAsUserWithPermissions(['article.category.view']);

    $this->delete(route('admin.article.category.destroy', $category->id))
        ->assertRedirect(route('admin.article.category.index'));

    expect($category->fresh()->trashed())->toBeFalse();
});

test('destroy soft deletes the category and logs the action', function () {
    actingAsUserWithPermissions(['article.category.manage']);
    $this->post(route('admin.article.category.store'), validCategoryPayload());
    $category = ArticleCategoryInfo::query()->latest('id')->first();

    $me = actingAsUserWithPermissions(['article.category.delete']);

    $this->delete(route('admin.article.category.destroy', $category->id))
        ->assertRedirect(route('admin.article.category.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($category);
    expect($category->fresh()->deleted_by)->toBe($me->id);

    expect(LogBackAction::where('module_code', 'article.category')
        ->where('action_type', 'delete')
        ->where('ref_id', $category->id)
        ->exists())->toBeTrue();
});
