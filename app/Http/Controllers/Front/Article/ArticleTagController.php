<?php

namespace App\Http\Controllers\Front\Article;

use App\Http\Controllers\Front\FrontController;
use App\Models\LogFrontAccess;
use App\Support\ArticleSetting;
use App\Support\Front\ArticleReader;
use App\Support\Front\FrontCache;
use App\Support\Front\FrontUrl;
use App\Support\Front\SeoMeta;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * หน้ารายการบทความตามแท็ก (/{lang}/article/tag/{tag}) — {tag} = ชื่อแท็ก (ตรงกับชื่อในภาษาใดก็ได้ ของแท็กที่เปิดใช้งาน)
 * ใช้ตั้งค่าการแสดงผลชุดเดียวกับรายการบทความของหมวดหมู่ (การ์ด/แถว, เรียงลำดับ, จำนวนต่อหน้า ฯลฯ) แต่ไม่มีข้อความเกริ่นนำ/รายละเอียด/ช่องค้นหา
 * หัวเรื่อง = แท็ก "ชื่อแท็ก"; ไม่พบแท็กหรือไม่มีบทความ = แสดง "ไม่พบข้อมูล" (ไม่ใช่ 404)
 */
class ArticleTagController extends FrontController
{
    public function show(Request $request, string $lang, string $tag): Response
    {
        $tag = mb_substr(trim($tag), 0, 100);
        $listSetting = ArticleSetting::listSetting();
        $query = $this->listQuery($request, $listSetting);
        $perPage = $listSetting['per_page'];

        // หาแท็กก่อน (query เล็ก มี index lang+name) — cache เฉพาะแท็กที่มีอยู่จริง; ชื่อแท็กมั่ว ๆ ไม่สร้าง key ใหม่ใน cache
        $tagIds = ArticleReader::tagIdsByName($tag);
        $load = fn () => ArticleReader::listForTags($tagIds, $lang, $perPage, $query['page'], $query['sort']);

        $articles = $tagIds === []
            ? $load()
            : FrontCache::remember(
                "article.tag.{$lang}.{$perPage}.{$query['page']}.{$query['sort']}.".implode('-', $tagIds),
                (int) config('front.cache.content_ttl', 300),
                $load,
            );

        $this->abortIfPageOutOfRange($query['page'], $articles);

        $title = (string) trans('front.tag_title', ['tag' => $tag], $lang);

        LogFrontAccess::record($title);

        $header = $this->pageHeader($lang, null, [['name' => $title, 'url' => null]]);
        $baseUrl = FrontUrl::articleTag($lang, $tag);
        $canonical = $query['page'] > 1 ? $baseUrl.'?page='.$query['page'] : $baseUrl;

        $seo = SeoMeta::make($lang, [
            'title' => $title,
            'canonical' => $canonical,
            'alternates' => $this->alternates(fn (string $code) => FrontUrl::articleTag($code, $tag)),
            'breadcrumb' => $this->crumbsWithCurrent($header['breadcrumb'], $baseUrl),
            'schema' => [
                '@type' => 'CollectionPage',
                'name' => $title,
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

        return $this->render('Front/Article/Tag', $lang, [
            'tag' => $tag,
            'title' => $title,
            'articles' => $articles,
            'listSetting' => $listSetting,
            'view' => $query['view'],
            'sort' => $query['sort'],
            'baseUrl' => $baseUrl,
            'header' => $header,
        ], $seo);
    }
}
