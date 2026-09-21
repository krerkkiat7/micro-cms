<?php

namespace App\Support\PageWidget;

use App\Models\PageItemWidgetSlidesetBanner;

/**
 * widget "Slideset จาก banner" — การ์ดป้ายโฆษณาหลายใบที่เลื่อนดูได้ (รูป, หัวเรื่อง, ข้อความเกริ่นนำ) ตั้งค่าเหมือน Slideset จาก article ยกเว้น
 * ไม่มีวันที่เผยแพร่/จำนวนเข้าชม/ปุ่ม "อ่านทั้งหมด", ข้อความเกริ่นนำซ่อนเป็นค่าเริ่มต้น และเรียงตามลำดับ (sort_order) ของ banner ได้ด้วย (ดู ReadsBanners)
 * ลิงก์ของการ์ด = ลิงก์ของ banner (banner ที่ไม่มีลิงก์กดไม่ได้) ตาราง `page_item_widget_slidesetbanner` (PK = `page_item_widget.id`)
 */
class SlidesetBannerWidget extends SlidesetWidget
{
    use ReadsBanners;

    public const TYPE = 'slidesetbanner';

    public function type(): string
    {
        return self::TYPE;
    }

    public function relation(): string
    {
        return 'slidesetBanner';
    }

    protected function model(): string
    {
        return PageItemWidgetSlidesetBanner::class;
    }

    protected function introShownByDefault(): string
    {
        return 'N';
    }
}
