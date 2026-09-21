<?php

namespace App\Support\PageWidget;

use App\Models\PageItemWidgetSlideshowBanner;

/**
 * widget "Slideshow จาก banner" — ภาพเต็มภาพเดียวที่สไลด์ได้ ข้อมูลมาจากป้ายโฆษณา (banner_item_*) ของหมวดหมู่ที่เลือก
 * ตาราง `page_item_widget_slideshowbanner` (PK = `page_item_widget.id`) การตั้งค่าส่วนใหญ่อยู่ใน SlideshowWidget แหล่งข้อมูลอยู่ใน ReadsBanners
 */
class SlideshowBannerWidget extends SlideshowWidget
{
    use ReadsBanners;

    public const TYPE = 'slideshowbanner';

    public function type(): string
    {
        return self::TYPE;
    }

    public function relation(): string
    {
        return 'slideshowBanner';
    }

    protected function model(): string
    {
        return PageItemWidgetSlideshowBanner::class;
    }
}
