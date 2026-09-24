<?php

namespace App\Http\Controllers\Front\Page;

use App\Http\Controllers\Front\FrontController;
use App\Models\LogFrontAccess;
use App\Models\PageItemInfo;
use App\Support\Front\FrontCache;
use App\Support\Front\FrontFile;
use App\Support\Front\FrontLang;
use App\Support\Front\FrontMenuResolver;
use App\Support\Front\FrontUrl;
use App\Support\Front\PageLayoutReader;
use App\Support\Front\SeoMeta;
use App\Support\Front\ViewCounter;
use App\Support\FrontMenuType;
use App\Support\PageTextStyle;
use Inertia\Response;

/**
 * หน้าเพจ (/{lang}/page/item/{id}/{slug?}) — ต้องมี id, slug ไม่บังคับ (มีผิด/ไม่มีก็เปิดได้ — canonical ชี้ URL ที่ถูก)
 * ไม่พบ/ไม่เผยแพร่ (status ≠ Y)/ถูกลบ = 404; แสดงตามโครงสร้าง แถว → คอลัมน์ → widget เต็มความกว้างหน้าจอ
 * (หน้าเพจกำหนดความกว้างของตัวเองต่อแถวอยู่แล้ว) หัวเรื่องเป็น h1 ที่ซ่อนไว้ (อ่านได้ด้วย screen reader/SEO)
 */
class PageItemController extends FrontController
{
    public function show(ViewCounter $views, string $lang, int $id, ?string $slug = null): Response
    {
        $page = PageItemInfo::query()->where('status', 'Y')->with(['details', 'introImage'])->find($id);

        if (! $page) {
            abort(404);
        }

        $detail = FrontLang::pick($page->details, $lang);
        $title = trim((string) ($detail?->title ?? ''));

        $layout = FrontCache::remember("page.{$page->id}.{$lang}", (int) config('front.cache.content_ttl', 300), function () use ($page, $lang) {
            $data = PageLayoutReader::read($page, $lang);
            $data['fontsUrl'] = PageTextStyle::stylesheetUrlFor($data['fonts']);
            unset($data['fonts']);

            return $data;
        });

        $views->hit('page', $page->id, $lang);
        LogFrontAccess::record($title);

        $menu = FrontMenuResolver::findFor($lang, FrontMenuType::PAGE, $page->id);
        $header = $this->pageHeader($lang, $menu, [['name' => $title, 'url' => null]]);
        $slugs = $page->details->mapWithKeys(fn ($d) => [$d->lang => $d->slug]);
        $canonical = FrontUrl::page($lang, $page->id, $detail?->slug);

        $seo = SeoMeta::make($lang, [
            'title' => $detail?->meta_title ?: $title,
            'description' => $detail?->meta_description ?: $detail?->intro_text,
            'keywords' => $detail?->meta_keywords,
            'og_title' => $detail?->og_title,
            'og_description' => $detail?->og_description,
            'image' => FrontFile::fromFileInfo($page->introImage, 1280)['url'] ?? null,
            'canonical' => $canonical,
            'alternates' => $this->alternates(fn (string $code) => FrontUrl::page($code, $page->id, $slugs[$code] ?? null)),
            'breadcrumb' => $this->crumbsWithCurrent($header['breadcrumb'], $canonical),
            'schema' => [
                '@type' => 'WebPage',
                'name' => $title,
                'url' => $canonical,
                'inLanguage' => $lang,
                'description' => SeoMeta::plain($detail?->meta_description ?: $detail?->intro_text) ?: null,
            ],
        ]);

        return $this->render('Front/Page/Item', $lang, [
            'page' => [
                'id' => $page->id,
                'title' => $title,
                ...$layout,
            ],
            'header' => $header,
        ], $seo);
    }
}
