<?php

use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemDetail;
use App\Models\ArticleItemInfo;
use App\Models\BannerItemInfo;
use App\Models\FileInfo;
use App\Models\FrontMenuDetail;
use App\Models\FrontMenuInfo;
use App\Models\IntropageItemInfo;
use App\Models\PageItemRow;
use App\Models\PopupItemInfo;
use App\Models\SysTemplate;
use App\Support\FrontMenuType;
use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * ข้อมูลตัวอย่าง (SampleDataSeeder) — โครงตามที่กำหนด, ครบสองภาษา, ไฟล์ต้นฉบับใน exampledata/ มีจริง, รันซ้ำไม่สร้างซ้ำ
 */
beforeEach(function () {
    Storage::fake('local');
    $this->seed(DatabaseSeeder::class);
});

test('articles: 4 categories and 15 published articles, each with a cover, tags and both languages', function () {
    expect(ArticleCategoryInfo::count())->toBe(4)
        ->and(ArticleItemInfo::count())->toBe(15)
        ->and(ArticleItemInfo::whereNull('intro_image_id')->count())->toBe(0)
        ->and(ArticleItemInfo::where('is_temp', '!=', 'Y')->count())->toBe(0)
        ->and(ArticleItemInfo::doesntHave('tags')->count())->toBe(0)
        ->and(ArticleItemDetail::where('lang', 'en')->count())->toBe(15);

    $perCategory = ArticleItemInfo::query()
        ->join('article_category_detail', fn ($join) => $join->on('article_category_detail.id', '=', 'article_item_info.article_category_info_id')->where('article_category_detail.lang', 'th'))
        ->selectRaw('article_category_detail.slug, count(*) as total')
        ->groupBy('article_category_detail.slug')
        ->pluck('total', 'slug')
        ->map(fn ($total) => (int) $total)
        ->all();

    expect($perCategory)->toEqual(['external-services' => 4, 'general' => 1, 'news' => 5, 'user-guide' => 5]);
});

test('front menu follows the requested structure with a single home page', function () {
    $tree = FrontMenuInfo::query()->whereNull('parent_id')->orderBy('sort_order')->get();
    $name = fn (FrontMenuInfo $menu) => FrontMenuDetail::where('id', $menu->id)->where('lang', 'th')->value('name');

    expect($tree->map($name)->all())->toBe(['หน้าแรก', 'แนะนำระบบ', 'บทความ', 'ติดต่อเรา'])
        ->and($tree->pluck('menu_type')->all())->toBe([FrontMenuType::PAGE, FrontMenuType::ARTICLE_ITEM, FrontMenuType::HEADING, FrontMenuType::CONTACTUS])
        ->and(FrontMenuInfo::where('is_home', 'Y')->count())->toBe(1)
        ->and($tree[0]->is_home)->toBe('Y');

    $children = FrontMenuInfo::where('parent_id', $tree[2]->id)->orderBy('sort_order')->get();
    expect($children->map($name)->all())->toBe(['ข่าวสาร', 'การใช้งานระบบ', 'การตั้งค่าบริการภายนอก'])
        ->and($children->pluck('menu_type')->unique()->all())->toBe([FrontMenuType::ARTICLE_CATEGORY]);
});

test('banners, home page layout, intropage, template and popup are seeded', function () {
    expect(BannerItemInfo::count())->toBe(4)
        ->and(BannerItemInfo::where('link_type', 'menu')->whereNotNull('front_menu_info_id')->count())->toBe(4)
        ->and(PageItemRow::count())->toBe(5)
        ->and(IntropageItemInfo::where('display_type', 'image')->whereNotNull('image_file_id')->count())->toBe(1)
        ->and(SysTemplate::where('status', 'Y')->count())->toBe(1)
        ->and(SysTemplate::count())->toBe(1)
        ->and(PopupItemInfo::where('menu_mode', 'selected')->count())->toBe(1);
});

test('site settings include logo, favicon, contact details and Turnstile test keys (secret encrypted)', function () {
    expect(Setting::get('site', 'logo_id'))->not->toBeEmpty()
        ->and(Setting::get('site', 'favicon_id'))->not->toBeEmpty()
        ->and(Setting::get('contact', 'email'))->toBe('info@microcms.com')
        ->and(Setting::get('turnstile', 'site_key'))->toBe('1x00000000000000000000AA')
        ->and(Setting::get('turnstile', 'key_secret'))->toBe('1x0000000000000000000000000000000AA')
        ->and(DB::table('sys_setting')->where('group', 'turnstile')->where('name', 'key_secret')->value('value'))->not->toBe('1x0000000000000000000000000000000AA');
});

test('every imported file exists in exampledata and is not copied to storage while testing', function () {
    $names = FileInfo::pluck('path', 'name');

    expect($names)->not->toBeEmpty()
        ->and(Storage::disk('local')->allFiles())->toBe([]);

    $sources = collect(glob(base_path('exampledata/{images/*,files}/*.*'), GLOB_BRACE))->map(fn ($path) => basename($path));
    $renamed = ['microcms-logo.png' => 'logo.png', 'microcms-favicon.ico' => 'favicon.ico', 'admin-avatar.jpg' => 'avatar-admin.jpg'];

    foreach ($names->keys() as $name) {
        expect($sources)->toContain($renamed[$name] ?? $name);
    }
});

test('running the seeder again skips the sample data', function () {
    $articles = ArticleItemInfo::count();
    $files = FileInfo::count();

    $this->seed(SampleDataSeeder::class);

    expect(ArticleItemInfo::count())->toBe($articles)
        ->and(FileInfo::count())->toBe($files);
});

test('the sample home page renders with its widgets', function () {
    $home = FrontMenuInfo::where('is_home', 'Y')->firstOrFail();

    $this->get("/th/page/item/{$home->target_page_item_id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Front/Page/Item')
            ->has('page.rows', 5)
            ->has('popups', 1));

    $this->get("/en/page/item/{$home->target_page_item_id}")->assertOk();
});
