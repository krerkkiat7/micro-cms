<?php

use App\Models\ArticleCategoryDetail;
use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemDetail;
use App\Models\ArticleItemInfo;
use App\Models\ArticleTagDetail;
use App\Models\FrontMenuInfo;
use App\Models\IntropageItemInfo;
use App\Models\LogFrontAccess;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemRow;
use App\Models\SysSetting;
use App\Support\Front\FrontCache;
use App\Support\Front\FrontUrl;
use App\Support\Front\HtmlSanitizer;
use App\Support\Front\ViewCounter;
use App\Support\Front\Views\ArrayViewBuffer;
use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * หน้าบ้าน (docs/PRD-front.md) — ใช้ข้อมูลตัวอย่างจาก DatabaseSeeder (intropage / หน้าเพจ / บทความ / เมนู / template)
 * ข้อมูลตัวอย่าง is_temp = Y ต้องแสดงได้ตามปกติ (ผู้ใช้อาจแก้ตัวอย่างแล้วใช้ต่อ)
 */
beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function samplePage(): PageItemInfo
{
    return PageItemInfo::query()->orderBy('id')->firstOrFail();
}

function sampleArticle(): ArticleItemInfo
{
    return ArticleItemInfo::query()->whereNotNull('article_category_info_id')->orderBy('id')->firstOrFail();
}

// ---------------------------------------------------------------- Intropage

test('home shows the published intropage on a blank layout', function () {
    $this->get('/th')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Front/Intropage/Index')
            ->where('intro.buttons.0.type', 'home')
            ->where('homeUrl', FrontMenuResolverUrlHelper::home())
            ->missing('front.template'));
});

test('home redirects to the home menu when no intropage is in its publish window', function () {
    IntropageItemInfo::query()->update(['publish_down' => now()->subDay()]);
    FrontCache::forgetAll();

    $this->get('/th')->assertRedirect(FrontMenuResolverUrlHelper::home());
});

test('home returns the front 404 page when there is no intropage and no home menu', function () {
    IntropageItemInfo::query()->update(['status' => 'N']);
    FrontMenuInfo::query()->update(['is_home' => 'N']);
    FrontCache::forgetAll();

    $this->get('/th')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('Front/Error')->where('status', 404));
});

// ---------------------------------------------------------------- หน้าเพจ

test('page renders with or without slug, including sample (is_temp) data', function () {
    $page = samplePage();
    expect($page->is_temp)->toBe('Y');
    $slug = PageItemDetail::where('id', $page->id)->where('lang', 'th')->value('slug');

    $this->get("/th/page/item/{$page->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p
            ->component('Front/Page/Item')
            ->where('page.id', $page->id)
            ->has('page.rows')
            ->where('seo.canonical', FrontUrl::page('th', $page->id, $slug)));

    $this->get("/th/page/item/{$page->id}/".rawurlencode((string) $slug))->assertOk();
    $this->get("/th/page/item/{$page->id}/wrong-slug")->assertOk();
});

test('page sends padding (null when off) and column gaps from the layout settings', function () {
    $page = samplePage();
    $row = PageItemRow::where('page_item_info_id', $page->id)->where('status', 'Y')->orderBy('sort_order')->orderBy('id')->firstOrFail();
    $row->update(['use_padding' => 'Y', 'padding_top' => 10, 'padding_right' => 20, 'padding_bottom' => 30, 'padding_left' => 40, 'gap_x' => 8, 'gap_y' => 12]);

    $this->get("/th/page/item/{$page->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p
            ->where('page.rows.0.padding', ['top' => 10, 'right' => 20, 'bottom' => 30, 'left' => 40])
            ->where('page.rows.0.gap_x', 8)
            ->where('page.rows.0.gap_y', 12)
            ->where('page.rows.0.columns.0.padding', null)
            ->where('page.rows.0.title_style.bold', true)
            ->where('page.rows.0.subtitle_style.bold', false));
});

test('unpublished, deleted or missing pages return 404', function () {
    $page = samplePage();

    $this->get('/th/page/item/999999')->assertNotFound();

    $page->update(['status' => 'N']);
    $this->get("/th/page/item/{$page->id}")->assertNotFound();

    $page->update(['status' => 'Y']);
    $page->delete();
    $this->get("/th/page/item/{$page->id}")->assertNotFound();
});

test('page view is counted once per session and bots are ignored', function () {
    $page = samplePage();

    $this->get("/th/page/item/{$page->id}")->assertOk();
    $this->get("/th/page/item/{$page->id}")->assertOk();

    expect(DB::table('page_item_view')->where('page_item_info_id', $page->id)->count())->toBe(1)
        ->and($page->fresh()->view_amount)->toBe(1);

    $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
        ->flushSession()
        ->get("/th/page/item/{$page->id}")
        ->assertOk();

    expect($page->fresh()->view_amount)->toBe(1);
});

// ---------------------------------------------------------------- บทความ

test('article category lists published articles with the configured display mode', function () {
    $article = sampleArticle();
    $categoryId = $article->article_category_info_id;

    // sys_setting ใช้ composite PK — แก้ด้วย query ตรง
    SysSetting::where('group', 'article')->where('name', 'list_display_mode')->update(['value' => 'row']);
    Setting::forget('article');

    $this->get("/th/article/category/{$categoryId}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Front/Article/Category')
            ->where('category.id', $categoryId)
            ->where('view', 'row')
            ->has('articles.data'));

    $this->get("/th/article/category/{$categoryId}?view=card")
        ->assertInertia(fn (Assert $page) => $page->where('view', 'card'));
});

test('unpublished or deleted categories return 404', function () {
    $category = ArticleCategoryInfo::query()->orderBy('id')->firstOrFail();

    $category->update(['status' => 'N']);
    $this->get("/th/article/category/{$category->id}")->assertNotFound();

    $category->update(['status' => 'Y']);
    $category->delete();
    $this->get("/th/article/category/{$category->id}")->assertNotFound();
});

test('article detail works through its category and directly', function () {
    $article = sampleArticle();
    $categoryId = $article->article_category_info_id;

    $this->get("/th/article/category/{$categoryId}/{$article->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Front/Article/Item')
            ->where('article.id', $article->id)
            ->where('shareUrl', fn (string $url) => str_contains($url, "/th/article/category/{$categoryId}/{$article->id}"))
            ->has('detailSetting')
            ->missing('category'));

    $this->get("/th/article/item/{$article->id}/any-slug")->assertOk();

    // บทความต้องอยู่ในหมวดหมู่ที่ถูกต้อง
    $otherCategory = ArticleCategoryInfo::query()->where('id', '!=', $categoryId)->value('id');
    $this->get("/th/article/category/{$otherCategory}/{$article->id}")->assertNotFound();
});

test('article category list searches by title and sorts', function () {
    $article = sampleArticle();
    $categoryId = $article->article_category_info_id;
    $title = ArticleItemDetail::where('id', $article->id)->where('lang', 'th')->value('title');

    $this->get("/th/article/category/{$categoryId}?q=".urlencode($title))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('q', $title)
            ->where('articles.data', fn ($items) => collect($items)->pluck('id')->contains($article->id)));

    $this->get("/th/article/category/{$categoryId}?q=".urlencode('ไม่มีบทความชื่อนี้แน่นอน'))
        ->assertInertia(fn (Assert $page) => $page->where('articles.total', 0));

    // ค่าเริ่มต้นตามตั้งค่า / ค่าที่ไม่รู้จักใช้ค่าเริ่มต้น
    $this->get("/th/article/category/{$categoryId}")
        ->assertInertia(fn (Assert $page) => $page->where('sort', 'newest')->has('listSetting.card'));

    $this->get("/th/article/category/{$categoryId}?sort=bogus")
        ->assertInertia(fn (Assert $page) => $page->where('sort', 'newest'));

    // ทุกรายการอยู่หน้าเดียว — ก ถึง ฮ กลับด้านต้องได้ ฮ ถึง ก (ลำดับตาม collation ของฐานข้อมูล ไม่เทียบกับ PHP)
    SysSetting::where('group', 'article')->where('name', 'list_per_page')->update(['value' => '100']);
    Setting::forget('article');

    $titles = fn (string $sort) => collect($this->get("/th/article/category/{$categoryId}?sort={$sort}")
        ->viewData('page')['props']['articles']['data'])->pluck('title')->all();

    $asc = $titles('title_asc');
    expect($asc)->not->toBeEmpty()
        ->and($titles('title_desc'))->toBe(array_reverse($asc));
});

test('category intro and detail are only sent when enabled in the article settings', function () {
    $categoryId = sampleArticle()->article_category_info_id;
    $toggle = function (string $value) {
        DB::table('sys_setting')->where('group', 'article')->whereIn('name', ['list_show_category_intro', 'list_show_category_detail'])->update(['value' => $value]);
        Setting::forget('article');
        FrontCache::forgetAll();
    };

    // ข้อมูลตัวอย่างเปิดไว้ทั้งคู่
    $this->get("/th/article/category/{$categoryId}")
        ->assertInertia(fn (Assert $page) => $page->where('category.intro_text', fn ($text) => $text !== '')->where('category.detail_html', fn ($html) => $html !== ''));

    $toggle('N');
    $this->get("/th/article/category/{$categoryId}")
        ->assertInertia(fn (Assert $page) => $page->where('category.intro_text', '')->where('category.detail_html', ''));
});

test('article tag page lists articles with that tag and shows no data for unknown tags', function () {
    $article = ArticleItemInfo::query()->whereHas('tags')->whereNotNull('article_category_info_id')->orderBy('id')->firstOrFail();
    $tag = $article->tags()->firstOrFail();
    $name = ArticleTagDetail::where('id', $tag->id)->where('lang', 'th')->value('name');

    $this->get(route('front.article.tag', ['lang' => 'th', 'tag' => $name]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Front/Article/Tag')
            ->where('tag', $name)
            ->where('articles.data', fn ($items) => collect($items)->pluck('id')->contains($article->id)));

    $this->get('/th/article/tag/'.rawurlencode('แท็กที่ไม่มีอยู่จริง'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('articles.total', 0));

    // หน้ารายละเอียดส่งแท็กเป็นลิงก์ไปหน้านี้
    $this->get("/th/article/item/{$article->id}")
        ->assertInertia(fn (Assert $page) => $page->where('article.tags', fn ($tags) => collect($tags)->contains(fn ($t) => $t['name'] === $name
            && $t['url'] === route('front.article.tag', ['lang' => 'th', 'tag' => $name]))));
});

test('unpublished articles return 404', function () {
    $article = sampleArticle();

    $article->update(['publish_down' => now()->subMinute()]);
    $this->get("/th/article/item/{$article->id}")->assertNotFound();

    $article->update(['publish_down' => null, 'status' => 'N']);
    $this->get("/th/article/item/{$article->id}")->assertNotFound();
});

test('article views are recorded in article_item_view', function () {
    $article = sampleArticle();
    $before = (int) $article->view_amount;

    $this->get("/th/article/item/{$article->id}")->assertOk();

    expect(DB::table('article_item_view')->where('article_item_info_id', $article->id)->count())->toBe(1)
        ->and((int) $article->fresh()->view_amount)->toBe($before + 1);
});

test('article views store device, browser, platform and referrer for reports', function () {
    $article = sampleArticle();

    $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        'Referer' => 'https://www.google.com/search?q=test',
    ])->get("/th/article/item/{$article->id}")->assertOk();

    $row = DB::table('article_item_view')->where('article_item_info_id', $article->id)->sole();

    expect($row->device_type)->toBe('mobile')
        ->and($row->browser)->not->toBeNull()
        ->and($row->platform)->not->toBeNull()
        ->and($row->referrer)->toBe('https://www.google.com/search?q=test');
});

test('category slugs cannot be all digits (would collide with the article route)', function () {
    actingAsUserWithPermissions(['article.category.manage', 'article.category.view']);
    $category = ArticleCategoryInfo::query()->orderBy('id')->firstOrFail();
    $detail = ArticleCategoryDetail::where('id', $category->id)->where('lang', 'th')->firstOrFail();

    $this->put(route('admin.article.category.update', $category->id), [
        'status' => 'Y',
        'detail' => ['th' => ['title' => $detail->title, 'slug' => '12345']],
    ])->assertSessionHasErrors('detail.th.slug');
});

// ---------------------------------------------------------------- ประวัติการเข้าชม + keep-alive

test('front pages record log_front_access and share the keep-alive token', function () {
    $page = samplePage();

    $response = $this->get("/th/page/item/{$page->id}");
    $log = LogFrontAccess::sole();

    $response->assertInertia(fn (Assert $p) => $p->where('accessLog.token', $log->token));
    expect($log->user_id)->toBeNull()
        ->and($log->uri_string)->toBe("/th/page/item/{$page->id}");
});

test('ping updates last_visited only for the same session', function () {
    $page = samplePage();
    $this->get("/th/page/item/{$page->id}");
    $log = LogFrontAccess::sole();
    $first = $log->last_visited;

    // เบราว์เซอร์ส่งคุกกี้ session เดิมกลับมา (test client ไม่เก็บคุกกี้ข้าม request ให้เอง)
    $this->travel(5)->minutes();
    $this->withCookie(config('session.cookie'), $log->session_id)
        ->post(route('front.access.ping'), ['token' => $log->token])
        ->assertNoContent();
    expect($log->fresh()->last_visited->gt($first))->toBeTrue();

    // session อื่นอัปเดตไม่ได้
    $this->travel(5)->minutes();
    $before = $log->fresh()->last_visited;
    $this->withCookie(config('session.cookie'), str_repeat('x', 40))->post(route('front.access.ping'), ['token' => $log->token])->assertNoContent();
    expect($log->fresh()->last_visited->equalTo($before))->toBeTrue();
});

test('admin front access log requires permission', function () {
    actingAsUserWithPermissions([]);
    $this->get(route('admin.system.frontlog.access.index'))->assertRedirect(route('admin.dashboard'));

    actingAsUserWithPermissions(['system.frontlog.access']);
    $this->get(route('admin.system.frontlog.access.index', ['q' => 'x']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/System/FrontLogAccess/Index'));
});

// ---------------------------------------------------------------- ไฟล์ / ความปลอดภัย / cache

test('public file route serves files by hash and rejects unknown sizes', function () {
    Storage::fake('local');
    fakeFileInfo('doc.pdf', '01JFRONTTESTFILE000000000.pdf', 'pdf', 'application/pdf');

    $this->get('/file/get/01JFRONTTESTFILE000000000.pdf')->assertOk();
    $this->get('/file/get/does-not-exist.pdf')->assertNotFound();
    $this->get('/file/type/thumbnail/size/123/get/01JFRONTTESTFILE000000000.pdf')->assertNotFound();
});

test('front pages do not expose admin user data or the admin route list', function () {
    actingAsUserWithPermissions(['system.frontlog.access']);
    $page = samplePage();

    $response = $this->get("/th/page/item/{$page->id}");

    $response->assertInertia(fn (Assert $p) => $p->where('auth.user', null)->where('menu', []));
    expect($response->getContent())->not->toContain('admin.dashboard');
});

test('saving front content bumps the front cache version', function () {
    $version = FrontCache::version();
    $menu = FrontMenuInfo::query()->firstOrFail();

    // save() ที่ไม่มีอะไรเปลี่ยนไม่ต้องล้าง cache
    $menu->save();
    expect(FrontCache::version())->toBe($version);

    $menu->update(['sort_order' => $menu->sort_order + 1]);

    expect(FrontCache::version())->toBeGreaterThan($version);
});

test('the view buffer flushes queued views in one batch', function () {
    $buffer = new ArrayViewBuffer;
    $counter = new ViewCounter($buffer);
    $page = samplePage();

    foreach (range(1, 3) as $i) {
        $buffer->push('page', ['id' => $page->id, 'lang' => 'th', 'at' => now()->format('Y-m-d H:i:s')]);
    }

    expect($counter->flush('page'))->toBe(3)
        ->and($buffer->size('page'))->toBe(0)
        ->and(DB::table('page_item_view')->count())->toBe(3)
        ->and($page->fresh()->view_amount)->toBe(3);
});

test('rich text is sanitized before it reaches the front', function () {
    $html = HtmlSanitizer::clean('<h1>หัว</h1><p onclick="x()">ok <a href="javascript:alert(1)">bad</a> <a href="https://example.com" target="_blank">good</a></p><script>alert(1)</script>');

    expect($html)->toContain('<h2>หัว</h2>')
        ->not->toContain('onclick')
        ->not->toContain('javascript:')
        ->not->toContain('<script')
        ->toContain('rel="noopener noreferrer"');
});

/**
 * URL หน้าแรกตามเมนู (is_home) ของข้อมูลตัวอย่าง — หน้าเพจตัวอย่าง
 */
final class FrontMenuResolverUrlHelper
{
    public static function home(): string
    {
        $menu = FrontMenuInfo::where('is_home', 'Y')->firstOrFail();
        $slug = PageItemDetail::where('id', $menu->target_page_item_id)->where('lang', 'th')->value('slug');

        return FrontUrl::page('th', (int) $menu->target_page_item_id, $slug);
    }
}
