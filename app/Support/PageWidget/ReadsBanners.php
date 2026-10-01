<?php

namespace App\Support\PageWidget;

use App\Models\BannerCategoryInfo;
use App\Models\BannerItemInfo;
use App\Support\Front\FrontLang;
use App\Support\Front\FrontMenuResolver;
use App\Support\Front\FrontUrl;
use App\Support\Setting;
use Illuminate\Database\Eloquent\Builder;

/**
 * แหล่งข้อมูล "banner" ของ widget ที่ดึงรายการจากหมวดหมู่ (CategoryListWidget) — หมวดหมู่ banner, ตัวเลือกการเรียงลำดับ
 * และ query ป้ายโฆษณาที่เผยแพร่อยู่และมีรูป ใช้ร่วมกันระหว่าง Slideshow/Slideset จาก banner (คลาสที่ใช้ trait ต้อง extends CategoryListWidget)
 * banner เรียงได้ทั้งวันที่เผยแพร่ และลำดับ (`sort_order`) ของ banner เอง
 */
trait ReadsBanners
{
    /** วันที่เผยแพร่ล่าสุด/เก่าสุด และลำดับ (sort_order ของ banner) น้อยไปมาก/มากไปน้อย */
    public const SORTS = ['publish_desc', 'publish_asc', 'order_asc', 'order_desc'];

    protected function categoryField(): string
    {
        return 'banner_category_info_id';
    }

    protected function categoryTable(): string
    {
        return 'banner_category_info';
    }

    protected function categoryLabel(): string
    {
        return 'หมวดหมู่ banner';
    }

    protected function sorts(): array
    {
        return self::SORTS;
    }

    public function options(): array
    {
        $defaultLang = Setting::defaultLanguage();

        // ชื่อหมวดหมู่ = ภาษาหลัก เรียงตามชื่อ (หมวดหมู่ banner ไม่มี sort_order)
        $categories = BannerCategoryInfo::query()
            ->join('banner_category_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'banner_category_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('d.deleted_at')
            ->where('banner_category_info.status', 'Y')
            ->orderBy('d.title')
            ->get(['banner_category_info.id', 'd.title as title'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'title' => $row->title])
            ->values()
            ->all();

        return ['banner_categories' => $categories];
    }

    /**
     * ป้ายโฆษณาของหมวดหมู่ที่เผยแพร่อยู่ (`status = Y`, ไม่ถูกลบ, อยู่ในช่วงเผยแพร่) และมีรูปที่ใช้งานได้ (ภาพคือตัวเนื้อหาของ banner)
     * พร้อมข้อมูลภาษาหลักเป็น `d` และรูปเป็น `img` — select ให้ครบตามที่ previewRow() ใช้ (`has_link` = มีลิงก์ตามประเภท BannerItemInfo::HAS_LINK_SQL)
     */
    protected function bannerQuery(int $categoryId, ?string $lang = null): Builder
    {
        $now = now();

        return BannerItemInfo::query()
            ->join('banner_item_detail as d', fn ($join) => FrontLang::joinDetail($join, 'd', 'banner_item_detail', 'banner_item_info.id', $lang))
            ->join('file_info as img', function ($join) {
                $join->on('img.id', '=', 'banner_item_info.intro_image_id')
                    ->where('img.status', 'Y')
                    ->whereNull('img.deleted_at');
            })
            ->whereNull('d.deleted_at') // join ตรง ไม่ผ่าน scope ของ model ต้องกันเองไม่ให้ดึงแถวที่ถูกลบ
            ->where('banner_item_info.banner_category_info_id', $categoryId)
            ->where('banner_item_info.status', 'Y')
            ->where(fn ($q) => $q->whereNull('banner_item_info.publish_date')->orWhere('banner_item_info.publish_date', '<=', $now))
            ->where(fn ($q) => $q->whereNull('banner_item_info.publish_down')->orWhere('banner_item_info.publish_down', '>', $now))
            ->selectRaw('banner_item_info.id, img.hash_name as image, d.title, d.intro_text, '.BannerItemInfo::HAS_LINK_SQL.' as has_link, '
                .'banner_item_info.link_type as front_link_type, banner_item_info.front_menu_info_id as front_menu_id, '
                .'banner_item_info.url as front_url, banner_item_info.link_target as front_link_target');
    }

    protected function previewQuery(int $categoryId, ?string $lang = null): Builder
    {
        return $this->bannerQuery($categoryId, $lang);
    }

    /**
     * ลิงก์ของ banner 1 แถวตามประเภทลิงก์ — เมนู: ลิงก์ + เป้าหมายตามที่ตั้งไว้ในเมนู (เมนูถูกซ่อน/ลบ = ไม่มีลิงก์),
     * กำหนดเอง: URL ที่ตั้งไว้ (เฉพาะรูปแบบที่ปลอดภัย) + เป้าหมายการเปิด, ไม่มีลิงก์: null
     *
     * @return array{url: string|null, link_target: string}
     */
    protected function frontLink(object $row, string $lang): array
    {
        if ($row->front_link_type === BannerItemInfo::LINK_MENU) {
            $link = $row->front_menu_id ? FrontMenuResolver::linkOf($lang, (int) $row->front_menu_id) : null;

            return ['url' => $link['url'] ?? null, 'link_target' => $link['target'] ?? '_self'];
        }

        if ($row->front_link_type !== BannerItemInfo::LINK_CUSTOM) {
            return ['url' => null, 'link_target' => '_self'];
        }

        return [
            // path ภายในที่ไม่มีภาษา (เช่น /news) เติม /{lang} ให้ — เหมือนเมนูลิงก์ภายนอก/popup/ปุ่มอ่านทั้งหมด
            'url' => FrontUrl::withLang(FrontUrl::safeExternal($row->front_url), $lang),
            'link_target' => $row->front_link_target === '_blank' ? '_blank' : '_self',
        ];
    }

    protected function orderPreview(Builder $query, string $sortBy): void
    {
        match ($sortBy) {
            'publish_asc' => $query->orderByRaw('COALESCE(banner_item_info.publish_date, banner_item_info.created_at) asc'),
            'order_asc' => $query->orderBy('banner_item_info.sort_order'),
            'order_desc' => $query->orderByDesc('banner_item_info.sort_order'),
            default => $query->orderByRaw('COALESCE(banner_item_info.publish_date, banner_item_info.created_at) desc'),
        };

        $query->orderBy('banner_item_info.id'); // tie-breaker ให้ลำดับเสถียร
    }
}
