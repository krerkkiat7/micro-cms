<?php

use App\Models\ArticleCategoryDetail;
use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemDetail;
use App\Models\ArticleItemInfo;
use App\Models\ArticleTagInfo;
use App\Models\BannerCategoryInfo;
use App\Models\BannerItemInfo;
use App\Models\FileInfo;
use App\Models\FrontMenuInfo;
use App\Models\LogBackAction;
use App\Rules\SafeUrl;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Validator;
use Inertia\Testing\AssertableInertia as Assert;

// เทสรอบทวนสอบระบบ (phase 1) — บั๊กการทำงานที่พบระหว่างตรวจ ดู docs/PRD-overview.md

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

/**
 * ป้ายโฆษณาตัวอย่าง 1 รายการ (seed ไม่ได้สร้างป้ายโฆษณามาให้) ในหมวดหมู่แรกที่ seed ไว้
 */
function fixBanner(): BannerItemInfo
{
    $image = FileInfo::create(['name' => 'b.jpg', 'hash_name' => 'fix-banner.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y']);

    return BannerItemInfo::create([
        'banner_category_info_id' => BannerCategoryInfo::query()->value('id'),
        'intro_image_id' => $image->id,
        'link_target' => '_self',
        'publish_date' => now(),
        'status' => 'Y',
    ]);
}

/**
 * @return array<string, mixed>
 */
function fixArticlePayload(int $categoryId, array $overrides = []): array
{
    return array_replace_recursive([
        'article_category_info_id' => $categoryId,
        'intro_image_id' => null,
        'publish_date' => now()->format('Y-m-d H:i:s'),
        'publish_down' => null,
        'status' => 'Y',
        'tags' => [],
        'detail' => [
            'th' => ['title' => 'บทความทดสอบ', 'slug' => 'fix-item-th'],
            'en' => ['title' => 'Test Article', 'slug' => 'fix-item-en'],
        ],
        'parts' => [],
    ], $overrides);
}

// ---------------------------------------------------------------- ข้อมูลอ้างอิงที่ถูกปิดใช้งาน

test('an article can be re-saved when its category and tags were disabled', function () {
    actingAsUserWithPermissions(['article.item.view', 'article.item.manage']);
    $category = ArticleCategoryInfo::query()->firstOrFail();
    $tag = ArticleTagInfo::query()->firstOrFail();

    $this->post(route('admin.article.item.store'), fixArticlePayload($category->id, ['tags' => [$tag->id]]))
        ->assertSessionHasNoErrors();
    $item = ArticleItemInfo::latest('id')->firstOrFail();

    $category->update(['status' => 'N']);
    $tag->update(['status' => 'N']);

    $this->get(route('admin.article.item.edit', $item->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories', fn ($options) => collect($options)->contains(fn ($o) => $o['id'] === $category->id && str_ends_with($o['title'], '(ไม่ใช้งาน)'))));

    $this->put(route('admin.article.item.update', $item->id), fixArticlePayload($category->id, ['tags' => [$tag->id]]))
        ->assertSessionHasNoErrors();

    expect($item->fresh()->tags()->pluck('article_tag_info.id')->all())->toBe([$tag->id]);

    // บทความใหม่ยังเลือกหมวดหมู่ที่ปิดใช้งานไม่ได้
    $this->post(route('admin.article.item.store'), fixArticlePayload($category->id, ['detail' => ['th' => ['slug' => 'other-th'], 'en' => ['slug' => 'other-en']]]))
        ->assertSessionHasErrors('article_category_info_id');
});

test('a banner can be re-saved when its category was disabled', function () {
    actingAsUserWithPermissions(['banner.item.view', 'banner.item.manage']);
    $banner = fixBanner();
    BannerCategoryInfo::whereKey($banner->banner_category_info_id)->update(['status' => 'N']);

    $this->put(route('admin.banner.item.update', $banner->id), [
        'banner_category_info_id' => $banner->banner_category_info_id,
        'intro_image_id' => $banner->intro_image_id,
        'link_type' => 'custom',
        'url' => '/contactus',
        'link_target' => '_self',
        'publish_date' => now()->format('Y-m-d H:i:s'),
        'status' => 'Y',
        'detail' => ['th' => ['title' => 'ป้าย'], 'en' => ['title' => 'Banner']],
    ])->assertSessionHasNoErrors();
});

// ---------------------------------------------------------------- slug

test('slugs of a deleted article and category can be reused', function () {
    actingAsUserWithPermissions(['article.item.manage', 'article.item.delete', 'article.category.manage', 'article.category.delete']);
    $category = ArticleCategoryInfo::query()->firstOrFail();

    $this->post(route('admin.article.item.store'), fixArticlePayload($category->id))->assertSessionHasNoErrors();
    $item = ArticleItemInfo::latest('id')->firstOrFail();
    $this->delete(route('admin.article.item.destroy', $item->id));

    expect(ArticleItemDetail::where('id', $item->id)->whereNotNull('slug')->exists())->toBeFalse();
    $this->post(route('admin.article.item.store'), fixArticlePayload($category->id))->assertSessionHasNoErrors();

    // หมวดหมู่ใหม่ที่ไม่มีบทความ → ลบแล้วใช้ slug เดิมได้
    $this->post(route('admin.article.category.store'), [
        'status' => 'Y',
        'detail' => ['th' => ['title' => 'หมวดชั่วคราว', 'slug' => 'temp-cat'], 'en' => ['title' => 'Temp']],
    ])->assertSessionHasNoErrors();
    $temp = ArticleCategoryInfo::latest('id')->firstOrFail();
    $this->delete(route('admin.article.category.destroy', $temp->id))->assertSessionHasNoErrors();

    expect(ArticleCategoryDetail::where('id', $temp->id)->whereNotNull('slug')->exists())->toBeFalse();
    $this->post(route('admin.article.category.store'), [
        'status' => 'Y',
        'detail' => ['th' => ['title' => 'หมวดใหม่', 'slug' => 'temp-cat'], 'en' => ['title' => 'New']],
    ])->assertSessionHasNoErrors();
});

test('article and page slugs reject slashes and numeric-only values', function () {
    actingAsUserWithPermissions(['article.item.manage']);
    $category = ArticleCategoryInfo::query()->firstOrFail();

    $this->post(route('admin.article.item.store'), fixArticlePayload($category->id, ['detail' => ['th' => ['slug' => 'a/b']]]))
        ->assertSessionHasErrors('detail.th.slug');
    $this->post(route('admin.article.item.store'), fixArticlePayload($category->id, ['detail' => ['th' => ['slug' => '123']]]))
        ->assertSessionHasErrors('detail.th.slug');
});

// ---------------------------------------------------------------- ลบหมวดหมู่ที่ยังมีรายการ

test('categories that still contain items cannot be deleted', function () {
    actingAsUserWithPermissions(['article.category.delete', 'banner.category.delete']);
    $articleCategory = ArticleItemInfo::query()->firstOrFail()->article_category_info_id;
    $bannerCategory = fixBanner()->banner_category_info_id;

    $this->delete(route('admin.article.category.destroy', $articleCategory))->assertSessionHasErrors('category');
    $this->delete(route('admin.banner.category.destroy', $bannerCategory))->assertSessionHasErrors('category');

    expect(ArticleCategoryInfo::find($articleCategory))->not->toBeNull()
        ->and(BannerCategoryInfo::find($bannerCategory))->not->toBeNull();
});

// ---------------------------------------------------------------- เมนูหน้าแรก

test('the home menu cannot be hidden', function () {
    actingAsUserWithPermissions(['system.menu.view', 'system.menu.manage']);
    $home = FrontMenuInfo::where('is_home', 'Y')->firstOrFail();

    $this->put(route('admin.system.menu.status', $home->id))->assertSessionHasErrors('menu');

    expect($home->fresh()->status)->toBe('Y');
});

// ---------------------------------------------------------------- ภาษา / ลิงก์ / log

test('detail keys for languages that are not enabled are dropped', function () {
    actingAsUserWithPermissions(['article.category.manage']);

    $this->post(route('admin.article.category.store'), [
        'status' => 'Y',
        'detail' => [
            'th' => ['title' => 'หมวดภาษา', 'slug' => 'lang-cat'],
            'en' => ['title' => 'Lang'],
            'xx' => ['title' => str_repeat('x', 5000)],
        ],
    ])->assertSessionHasNoErrors();

    $id = ArticleCategoryInfo::latest('id')->value('id');
    expect(ArticleCategoryDetail::where('id', $id)->pluck('lang')->sort()->values()->all())->toBe(['en', 'th']);
});

test('an admin list keeps records that have no default-language detail', function () {
    actingAsUserWithPermissions(['article.item.view']);
    $item = ArticleItemInfo::query()->firstOrFail();
    $english = ArticleItemDetail::where('id', $item->id)->where('lang', 'en')->value('title');
    ArticleItemDetail::where('id', $item->id)->where('lang', 'th')->delete();

    $this->get(route('admin.article.item.index', ['per_page' => 100]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.data', fn ($rows) => collect($rows)->contains(fn ($r) => $r['id'] === $item->id && $r['title'] === $english)));
});

test('link fields only accept links the front site can show', function () {
    $check = fn (string $value) => Validator::make(['url' => $value], ['url' => [new SafeUrl]])->passes();

    expect($check('https://example.com'))->toBeTrue()
        ->and($check('/th/contactus'))->toBeTrue()
        ->and($check('mailto:a@b.co'))->toBeTrue()
        ->and($check('www.example.com'))->toBeFalse()
        ->and($check('javascript:alert(1)'))->toBeFalse()
        ->and($check('//evil.example'))->toBeFalse();
});

test('quick-creating a tag from the article form is logged', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    $this->postJson(route('admin.article.tag.quickStore'), ['name' => ['th' => 'แท็กด่วน', 'en' => 'Quick tag']])
        ->assertCreated();

    expect(LogBackAction::where('module_code', 'article.tag')->where('action_type', 'create')->where('value_string', 'แท็กด่วน')->exists())->toBeTrue();
});
