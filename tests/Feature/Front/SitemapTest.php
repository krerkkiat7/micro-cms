<?php

use App\Models\ArticleItemDetail;
use App\Models\ArticleItemInfo;
use App\Models\FrontMenuInfo;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use App\Support\FrontMenuType;
use Database\Seeders\DatabaseSeeder;

// sitemap.xml หน้าบ้าน — อิงตามเมนูที่เผยแพร่ (App\Support\Front\Sitemap)

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->categoryMenu = FrontMenuInfo::query()->where('menu_type', FrontMenuType::ARTICLE_CATEGORY)->orderBy('sort_order')->orderBy('id')->firstOrFail();
    $this->categoryId = (int) $this->categoryMenu->target_article_category_id;
});

/** โหลดไฟล์ sitemap แล้วคืน SimpleXMLElement (ลงทะเบียน namespace ให้ xpath แล้ว) */
function sitemapXml(string $url): SimpleXMLElement
{
    $response = test()->get($url)->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    $xml = simplexml_load_string($response->getContent());

    expect($xml)->not->toBeFalse();
    $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $xml->registerXPathNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

    return $xml;
}

/**
 * <loc> ทุกรายการจากทุกไฟล์ที่ index ชี้
 *
 * @return list<string>
 */
function sitemapLocs(): array
{
    $locs = [];

    foreach (sitemapXml('/sitemap.xml')->xpath('//s:sitemap/s:loc') as $file) {
        foreach (sitemapXml((string) $file)->xpath('//s:url/s:loc') as $loc) {
            $locs[] = (string) $loc;
        }
    }

    return $locs;
}

function sitemapArticle(int $categoryId, array $attributes = []): ArticleItemInfo
{
    $article = ArticleItemInfo::forceCreate($attributes + ['article_category_info_id' => $categoryId, 'status' => 'Y']);
    ArticleItemDetail::forceCreate(['id' => $article->id, 'lang' => 'th', 'title' => "บทความ {$article->id}", 'slug' => "sitemap-article-{$article->id}", 'status' => 'Y']);

    return $article;
}

test('sitemap index links the main and article files', function () {
    sitemapArticle($this->categoryId);

    $files = array_map('strval', sitemapXml('/sitemap.xml')->xpath('//s:sitemap/s:loc'));

    expect($files)->toContain(route('front.sitemap.main'))
        ->toContain(route('front.sitemap.article', ['n' => 1]));
});

test('published menu targets are listed in every language with hreflang alternates', function () {
    $homeMenu = FrontMenuInfo::query()->where('is_home', 'Y')->firstOrFail();
    $page = PageItemInfo::with('details')->findOrFail($homeMenu->target_page_item_id);
    $slug = $page->details->firstWhere('lang', 'th')?->slug;

    $xml = sitemapXml('/sitemap-main.xml');
    $thUrl = route('front.page.item', array_filter(['lang' => 'th', 'id' => $page->id, 'slug' => $slug]));
    $url = $xml->xpath("//s:url[s:loc='{$thUrl}']");

    expect($url)->toHaveCount(1);

    $alternates = collect($url[0]->xpath('xhtml:link'))->mapWithKeys(fn ($link) => [(string) $link['hreflang'] => (string) $link['href']]);
    expect($alternates->keys()->all())->toEqualCanonicalizing(['th', 'en', 'x-default'])
        ->and($alternates['th'])->toBe($thUrl)
        ->and($alternates['x-default'])->toBe($thUrl);

    // หมวดหมู่ที่มีเมนู + ติดต่อเรา (ข้อมูลตัวอย่างมีเมนูติดต่อเรา) — ซ่อนเมนูติดต่อเรา = ไม่อยู่
    $locs = sitemapLocs();
    $contactUrl = route('front.contactus.item', ['lang' => 'th']);
    expect(collect($locs)->contains(fn ($loc) => str_starts_with($loc, route('front.article.category', ['lang' => 'th', 'id' => $this->categoryId]))))->toBeTrue()
        ->and($locs)->toContain($contactUrl);

    FrontMenuInfo::query()->where('menu_type', FrontMenuType::CONTACTUS)->get()->each->update(['status' => 'N']);
    expect(sitemapLocs())->not->toContain($contactUrl);
});

test('content without a published menu is not listed', function () {
    $page = PageItemInfo::forceCreate(['status' => 'Y']);
    PageItemDetail::forceCreate(['id' => $page->id, 'lang' => 'th', 'title' => 'ไม่มีเมนู', 'slug' => 'no-menu', 'status' => 'Y']);
    $pageUrl = route('front.page.item', ['lang' => 'th', 'id' => $page->id, 'slug' => 'no-menu']);

    expect(sitemapLocs())->not->toContain($pageUrl);

    // เพิ่มเมนูที่แสดง → อยู่ใน sitemap (cache ถูกล้างเมื่อบันทึกเมนู)
    $menu = FrontMenuInfo::forceCreate(['menu_type' => FrontMenuType::PAGE, 'target_page_item_id' => $page->id, 'status' => 'Y', 'sort_order' => 99]);
    expect(sitemapLocs())->toContain($pageUrl);

    // ซ่อนเมนู → หายไป
    $menu->update(['status' => 'N']);
    expect(sitemapLocs())->not->toContain($pageUrl);
});

test('hidden parent menu hides the whole branch', function () {
    $article = sitemapArticle($this->categoryId);
    $articleUrl = route('front.article.category.item', ['lang' => 'th', 'id' => $this->categoryId, 'article_id' => $article->id, 'slug' => "sitemap-article-{$article->id}"]);

    expect(sitemapLocs())->toContain($articleUrl);

    FrontMenuInfo::findOrFail($this->categoryMenu->parent_id)->update(['status' => 'N']);

    $locs = sitemapLocs();
    expect($locs)->not->toContain($articleUrl)
        ->and(collect($locs)->contains(fn ($loc) => str_contains($loc, "/article/category/{$this->categoryId}")))->toBeFalse();
});

test('only published articles of menu categories are listed and tag pages never are', function () {
    $published = sitemapArticle($this->categoryId, ['publish_date' => now()->subDay()]);
    $disabled = sitemapArticle($this->categoryId, ['status' => 'N']);
    $future = sitemapArticle($this->categoryId, ['publish_date' => now()->addDay()]);
    $expired = sitemapArticle($this->categoryId, ['publish_down' => now()->subMinute()]);
    $deleted = sitemapArticle($this->categoryId);
    $deleted->delete();

    $ids = collect(sitemapLocs())
        ->map(fn ($loc) => preg_match("~/article/category/{$this->categoryId}/(\d+)/~", $loc, $m) ? (int) $m[1] : null)
        ->filter()
        ->all();

    expect($ids)->toContain($published->id)
        ->not->toContain($disabled->id)
        ->not->toContain($future->id)
        ->not->toContain($expired->id)
        ->not->toContain($deleted->id)
        ->and(collect(sitemapLocs())->contains(fn ($loc) => str_contains($loc, '/article/tag/')))->toBeFalse();
});

test('article files are split by the configured size', function () {
    config(['front.sitemap.article_per_file' => 1]);
    sitemapArticle($this->categoryId);
    sitemapArticle($this->categoryId);

    $files = sitemapXml('/sitemap.xml')->xpath('//s:sitemap/s:loc');

    expect(count($files))->toBeGreaterThanOrEqual(3); // main + บทความอย่างน้อย 2 ไฟล์
    $this->get('/sitemap-article-999.xml')->assertNotFound();
});

test('every sitemap url opens', function () {
    sitemapArticle($this->categoryId);

    foreach (sitemapLocs() as $loc) {
        $this->get(parse_url($loc, PHP_URL_PATH))->assertOk();
    }
});

test('sitemap responses are cacheable without cookies and honour etags', function () {
    $response = $this->get('/sitemap.xml')->assertOk();

    expect($response->headers->getCookies())->toBe([])
        ->and($response->headers->get('Cache-Control'))->toContain('public')
        ->and($response->headers->get('X-Robots-Tag'))->toBe('noindex');

    $this->get('/sitemap.xml', ['If-None-Match' => $response->headers->get('ETag')])->assertStatus(304);
});

test('robots.txt points to the sitemap', function () {
    $response = $this->get('/robots.txt')->assertOk();

    expect($response->getContent())->toContain('Disallow: /admin')
        ->toContain('Sitemap: '.route('front.sitemap'))
        ->and($response->headers->getCookies())->toBe([]);
});
