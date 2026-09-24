<?php

namespace App\Http\Controllers\Front\Article;

use App\Http\Controllers\Front\FrontController;
use App\Models\LogFrontAccess;
use App\Support\Front\ArticleReader;
use App\Support\Front\FrontCache;
use App\Support\Front\FrontMenuResolver;
use App\Support\Front\FrontUrl;
use App\Support\Front\SeoMeta;
use App\Support\FrontMenuType;
use App\Support\Setting;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * หน้ารายการบทความของหมวดหมู่ (/{lang}/article/category/{id}/{slug?}) — หมวดหมู่ต้องเผยแพร่ (status = Y) และไม่ถูกลบ ไม่งั้น 404
 * แสดงเป็นการ์ด/แถว (ผู้ชมสลับได้ ?view=card|row — ค่าเริ่มต้นตามตั้งค่าบทความ list_display_mode) จำนวนต่อหน้าตาม list_per_page
 * ชื่อหมวดหมู่เป็น h1; คลิกรายการไป /{lang}/article/category/{id}/{article_id}/{article_slug?}
 */
class ArticleCategoryController extends FrontController
{
    public function show(Request $request, string $lang, int $id, ?string $slug = null): Response
    {
        $model = ArticleReader::publishedCategory($id);

        if (! $model) {
            abort(404);
        }

        $category = ArticleReader::category($model, $lang);
        $defaultView = Setting::get('article', 'list_display_mode', 'card') === 'row' ? 'row' : 'card';
        $view = in_array($request->query('view'), ['card', 'row'], true) ? $request->query('view') : $defaultView;
        $perPage = max(1, min(100, (int) Setting::get('article', 'list_per_page', 10)));
        $page = max(1, (int) $request->query('page', 1));

        $articles = FrontCache::remember(
            "article.category.{$id}.{$lang}.{$perPage}.{$page}",
            (int) config('front.cache.content_ttl', 300),
            fn () => ArticleReader::listForCategory($id, $lang, $perPage, $page),
        );

        LogFrontAccess::record($category['title']);

        $menu = FrontMenuResolver::findFor($lang, FrontMenuType::ARTICLE_CATEGORY, $id);
        $header = $this->pageHeader($lang, $menu, [['name' => $category['title'], 'url' => null]]);
        $baseUrl = FrontUrl::articleCategory($lang, $id, $category['slug']);
        // canonical: ไม่รวม ?view (หน้าตาต่างกันแต่เนื้อหาเดียวกัน) รวม ?page เฉพาะหน้าที่ 2 ขึ้นไป
        $canonical = $page > 1 ? $baseUrl.'?page='.$page : $baseUrl;

        $seo = SeoMeta::make($lang, [
            'title' => $category['meta']['title'] ?: $category['title'],
            'description' => $category['meta']['description'] ?: $category['intro_text'],
            'keywords' => $category['meta']['keywords'],
            'og_title' => $category['meta']['og_title'],
            'og_description' => $category['meta']['og_description'],
            'image' => $category['image']['url'] ?? null,
            'canonical' => $canonical,
            'alternates' => $this->alternates(fn (string $code) => FrontUrl::articleCategory($code, $id, $category['slugs'][$code] ?? null)),
            'breadcrumb' => $this->crumbsWithCurrent($header['breadcrumb'], $baseUrl),
            'schema' => [
                '@type' => 'CollectionPage',
                'name' => $category['title'],
                'url' => $baseUrl,
                'inLanguage' => $lang,
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'itemListElement' => collect($articles['data'])->values()->map(fn (array $item, int $i) => [
                        '@type' => 'ListItem',
                        'position' => ($articles['current_page'] - 1) * $articles['per_page'] + $i + 1,
                        'url' => $item['url'],
                        'name' => $item['title'],
                    ])->all(),
                ],
            ],
        ]);

        return $this->render('Front/Article/Category', $lang, [
            'category' => collect($category)->except(['meta', 'slugs'])->all(),
            'articles' => $articles,
            'view' => $view,
            'baseUrl' => $baseUrl,
            'header' => $header,
        ], $seo);
    }
}
