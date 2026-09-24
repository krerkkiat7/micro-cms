<?php

namespace App\Http\Controllers\Front\Article;

use App\Http\Controllers\Front\FrontController;
use App\Models\LogFrontAccess;
use App\Support\AppAsset;
use App\Support\Front\ArticleReader;
use App\Support\Front\FrontCache;
use App\Support\Front\FrontMenuResolver;
use App\Support\Front\FrontUrl;
use App\Support\Front\SeoMeta;
use App\Support\Front\ViewCounter;
use App\Support\FrontMenuType;
use App\Support\Setting;
use Inertia\Response;

/**
 * หน้ารายละเอียดบทความ — เข้าได้ 2 ทาง:
 * - ผ่านหมวดหมู่ /{lang}/article/category/{id}/{article_id}/{slug?} (front.article.category.item) — บทความต้องอยู่ในหมวดหมู่นั้น
 *   และหมวดหมู่ต้องเผยแพร่
 * - ตรง /{lang}/article/item/{id}/{slug?} (front.article.item)
 * บทความต้องเผยแพร่ (status = Y, อยู่ในช่วงเผยแพร่) และไม่ถูกลบ ไม่งั้น 404 — ชื่อบทความเป็น h1, หัวข้อ part เป็น h2
 * canonical ของทั้ง 2 ทางชี้ URL เดียวกัน (ผ่านหมวดหมู่ของบทความ ถ้าหมวดหมู่เผยแพร่อยู่) กันเนื้อหาซ้ำ
 */
class ArticleItemController extends FrontController
{
    public function show(ViewCounter $views, string $lang, int $id, ?string $slug = null): Response
    {
        return $this->display($views, $lang, $id, null);
    }

    public function showInCategory(ViewCounter $views, string $lang, int $id, int $article_id, ?string $slug = null): Response
    {
        return $this->display($views, $lang, $article_id, $id);
    }

    private function display(ViewCounter $views, string $lang, int $articleId, ?int $viaCategoryId): Response
    {
        $model = ArticleReader::publishedArticle($articleId);

        if (! $model) {
            abort(404);
        }

        $categoryModel = $model->article_category_info_id ? ArticleReader::publishedCategory((int) $model->article_category_info_id) : null;

        // เข้าผ่านหมวดหมู่: บทความต้องอยู่ในหมวดหมู่นั้นจริง และหมวดหมู่ต้องเผยแพร่
        if ($viaCategoryId !== null && ((int) $model->article_category_info_id !== $viaCategoryId || $categoryModel === null)) {
            abort(404);
        }

        $article = FrontCache::remember(
            "article.item.{$articleId}.{$lang}",
            (int) config('front.cache.content_ttl', 300),
            fn () => ArticleReader::article($model, $lang),
        );

        $category = $categoryModel ? ArticleReader::category($categoryModel, $lang) : null;

        $views->hit('article', $articleId, $lang);
        LogFrontAccess::record($article['title']);

        $menu = FrontMenuResolver::findFor($lang, FrontMenuType::ARTICLE_ITEM, $articleId)
            ?? ($category ? FrontMenuResolver::findFor($lang, FrontMenuType::ARTICLE_CATEGORY, $category['id']) : null);

        $extra = [];

        if ($category && ! collect($menu['trail'] ?? [])->contains('name', $category['title'])) {
            $extra[] = ['name' => $category['title'], 'url' => FrontUrl::articleCategory($lang, $category['id'], $category['slug'])];
        }

        $extra[] = ['name' => $article['title'], 'url' => null];
        $header = $this->pageHeader($lang, $menu, $extra);

        $urlFor = fn (string $code, ?string $articleSlug) => $category
            ? FrontUrl::articleCategoryItem($code, $category['id'], $articleId, $articleSlug)
            : FrontUrl::articleItem($code, $articleId, $articleSlug);
        $canonical = $urlFor($lang, $article['slug']);

        $seo = SeoMeta::make($lang, [
            'title' => $article['meta']['title'] ?: $article['title'],
            'description' => $article['meta']['description'] ?: $article['intro_text'],
            'keywords' => $article['meta']['keywords'] ?: (implode(', ', $article['tags']) ?: null),
            'og_title' => $article['meta']['og_title'],
            'og_description' => $article['meta']['og_description'],
            'image' => $article['image']['url'] ?? null,
            'type' => 'article',
            'canonical' => $canonical,
            'alternates' => $this->alternates(fn (string $code) => $urlFor($code, $article['slugs'][$code] ?? null)),
            'breadcrumb' => $this->crumbsWithCurrent($header['breadcrumb'], $canonical),
            'schema' => array_filter([
                '@type' => 'Article',
                'headline' => $article['title'],
                'description' => SeoMeta::plain($article['meta']['description'] ?: $article['intro_text']) ?: null,
                'image' => $article['image']['url'] ?? null,
                'datePublished' => $article['published_at'],
                'dateModified' => $article['updated_at'] ?? $article['published_at'],
                'inLanguage' => $lang,
                'mainEntityOfPage' => $canonical,
                'articleSection' => $category['title'] ?? null,
                'keywords' => $article['tags'] ?: null,
                'author' => ['@type' => 'Organization', 'name' => Setting::get('site', 'copyright_owner') ?? Setting::siteName()],
                'publisher' => array_filter([
                    '@type' => 'Organization',
                    'name' => Setting::get('site', 'copyright_owner') ?? Setting::siteName(),
                    'logo' => AppAsset::logo() ? ['@type' => 'ImageObject', 'url' => route('app.logo')] : null,
                ]),
                'interactionStatistic' => [
                    '@type' => 'InteractionCounter',
                    'interactionType' => 'https://schema.org/ReadAction',
                    'userInteractionCount' => $article['views'],
                ],
            ], fn ($value) => $value !== null),
        ]);

        return $this->render('Front/Article/Item', $lang, [
            'article' => collect($article)->except(['meta', 'slugs', 'publish_down'])->all(),
            'category' => $category ? [
                'id' => $category['id'],
                'title' => $category['title'],
                'url' => FrontUrl::articleCategory($lang, $category['id'], $category['slug']),
            ] : null,
            'header' => $header,
        ], $seo);
    }
}
