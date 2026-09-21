<?php

namespace App\Support\PageWidget;

use App\Models\BannerCategoryInfo;
use App\Models\BannerItemInfo;
use App\Models\PageItemWidgetSlideshowBanner;
use App\Support\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * widget "Slideshow จาก banner" — ภาพเต็มภาพเดียวที่สไลด์ได้ ข้อมูลมาจากป้ายโฆษณา (banner_item_*) ของหมวดหมู่ที่เลือก
 * ตาราง `page_item_widget_slideshowbanner` (PK = `page_item_widget.id`); ตัวเลือก/ค่าเริ่มต้นต้องตรงกับ utils/pageWidget.ts
 */
class SlideshowBannerWidget implements PageWidgetType
{
    public const TYPE = 'slideshowbanner';

    /** จำนวน banner สูงสุดที่ดึงมาแสดงเป็นตัวอย่างในหน้าโครงสร้าง */
    public const PREVIEW_LIMIT = 10;

    public const SORTS = ['publish_desc', 'publish_asc', 'order_asc', 'order_desc'];

    public const EFFECTS = ['slide', 'fade', 'zoom'];

    public const ASPECT_RATIOS = ['16:9', '21:9', '4:3', '1:1'];

    public const LINK_TARGETS = ['_self', '_blank'];

    public const TEXT_ALIGNS = ['left', 'center', 'right'];

    public const TEXT_WIDTHS = ['full', 'container'];

    public const INTERVAL_MIN = 1;

    public const INTERVAL_MAX = 60;

    public const SPEED_MIN = 100;

    public const SPEED_MAX = 3000;

    /** คอลัมน์ตั้งค่า (ไม่รวม PK/audit) ที่ประเภทนี้รับ/ส่งกลับ */
    private const FIELDS = [
        'banner_category_info_id', 'sort_by', 'show_arrows', 'show_dots', 'autoplay', 'autoplay_interval',
        'transition_speed', 'transition_effect', 'aspect_ratio', 'is_clickable', 'link_target',
        'show_title', 'show_intro_text', 'text_align', 'text_width',
    ];

    public function type(): string
    {
        return self::TYPE;
    }

    public function relation(): string
    {
        return 'slideshowBanner';
    }

    public function rules(): array
    {
        $yesNo = ['required', Rule::in(['Y', 'N'])];

        return $this->previewRules() + [
            'show_arrows' => $yesNo,
            'show_dots' => $yesNo,
            'autoplay' => $yesNo,
            'autoplay_interval' => ['required', 'integer', 'between:'.self::INTERVAL_MIN.','.self::INTERVAL_MAX],
            'transition_speed' => ['required', 'integer', 'between:'.self::SPEED_MIN.','.self::SPEED_MAX],
            'transition_effect' => ['required', Rule::in(self::EFFECTS)],
            'aspect_ratio' => ['required', Rule::in(self::ASPECT_RATIOS)],
            'is_clickable' => $yesNo,
            'link_target' => ['required', Rule::in(self::LINK_TARGETS)],
            'show_title' => $yesNo,
            'show_intro_text' => $yesNo,
            'text_align' => ['required', Rule::in(self::TEXT_ALIGNS)],
            'text_width' => ['required', Rule::in(self::TEXT_WIDTHS)],
        ];
    }

    public function messages(): array
    {
        return [
            'banner_category_info_id.required' => 'กรุณาเลือกหมวดหมู่ banner',
            'banner_category_info_id.exists' => 'ไม่พบหมวดหมู่ banner ที่เลือก (อาจถูกปิดใช้งานหรือลบไปแล้ว)',
            'banner_category_info_id.integer' => 'หมวดหมู่ banner ไม่ถูกต้อง',
            'sort_by.in' => 'ลำดับการเรียงลำดับไม่ถูกต้อง',
            'autoplay_interval.between' => 'ระยะเวลาค้างต่อภาพต้องอยู่ระหว่าง '.self::INTERVAL_MIN.' - '.self::INTERVAL_MAX.' วินาที',
            'autoplay_interval.integer' => 'ระยะเวลาค้างต่อภาพต้องเป็นจำนวนเต็ม',
            'autoplay_interval.required' => 'กรุณากรอกระยะเวลาค้างต่อภาพ',
            'transition_speed.between' => 'ความเร็วในการเปลี่ยนภาพต้องอยู่ระหว่าง '.self::SPEED_MIN.' - '.self::SPEED_MAX.' มิลลิวินาที',
            'transition_speed.integer' => 'ความเร็วในการเปลี่ยนภาพต้องเป็นจำนวนเต็ม',
            'transition_speed.required' => 'กรุณากรอกความเร็วในการเปลี่ยนภาพ',
            'transition_effect.in' => 'ประเภทการเลื่อนไม่ถูกต้อง',
            'aspect_ratio.in' => 'สัดส่วนภาพไม่ถูกต้อง',
            'link_target.in' => 'เป้าหมายการเปิดลิงก์ไม่ถูกต้อง',
            'text_align.in' => 'ตำแหน่งที่แสดงข้อความไม่ถูกต้อง',
            'text_width.in' => 'ขอบเขตของข้อความไม่ถูกต้อง',
            '*.in' => 'ค่าที่เลือกไม่ถูกต้อง',
            '*.required' => 'กรุณาระบุค่าที่จำเป็นให้ครบ',
        ];
    }

    public function previewRules(): array
    {
        return [
            // หมวดหมู่ต้องมีอยู่จริง เปิดใช้งาน และไม่ถูกลบ
            'banner_category_info_id' => [
                'required', 'integer',
                Rule::exists('banner_category_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'sort_by' => ['required', Rule::in(self::SORTS)],
        ];
    }

    public function defaults(): array
    {
        return [
            'banner_category_info_id' => null,
            'sort_by' => 'publish_desc',
            'show_arrows' => 'Y',
            'show_dots' => 'Y',
            'autoplay' => 'Y',
            'autoplay_interval' => 5,
            'transition_speed' => 500,
            'transition_effect' => 'slide',
            'aspect_ratio' => '16:9',
            'is_clickable' => 'Y',
            'link_target' => '_self',
            'show_title' => 'Y',
            'show_intro_text' => 'N',
            'text_align' => 'center',
            'text_width' => 'container',
        ];
    }

    public function save(int $widgetId, array $setting, ?int $actorId): void
    {
        $values = array_intersect_key($setting, array_flip(self::FIELDS)) + $this->defaults();
        $query = PageItemWidgetSlideshowBanner::where('id', $widgetId);

        if ($query->exists()) {
            $query->update($values + ['updated_by' => $actorId]);
        } else {
            PageItemWidgetSlideshowBanner::create(['id' => $widgetId, 'created_by' => $actorId] + $values);
        }
    }

    public function toArray(?Model $row): array
    {
        if (! $row instanceof PageItemWidgetSlideshowBanner) {
            return $this->defaults();
        }

        return [
            'banner_category_info_id' => $row->banner_category_info_id !== null ? (int) $row->banner_category_info_id : null,
            'sort_by' => $row->sort_by,
            'show_arrows' => $row->show_arrows,
            'show_dots' => $row->show_dots,
            'autoplay' => $row->autoplay,
            'autoplay_interval' => (int) $row->autoplay_interval,
            'transition_speed' => (int) $row->transition_speed,
            'transition_effect' => $row->transition_effect,
            'aspect_ratio' => $row->aspect_ratio,
            'is_clickable' => $row->is_clickable,
            'link_target' => $row->link_target,
            'show_title' => $row->show_title,
            'show_intro_text' => $row->show_intro_text,
            'text_align' => $row->text_align,
            'text_width' => $row->text_width,
        ];
    }

    public function softDelete(array $widgetIds, ?int $actorId): void
    {
        if ($widgetIds !== []) {
            PageItemWidgetSlideshowBanner::whereIn('id', $widgetIds)->update(['deleted_by' => $actorId, 'deleted_at' => now()]);
        }
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

    public function preview(array $setting): array
    {
        $defaultLang = Setting::defaultLanguage();
        $now = now();

        $query = BannerItemInfo::query()
            ->join('banner_item_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'banner_item_info.id')->where('d.lang', $defaultLang);
            })
            // ต้องมีรูปที่ใช้งานได้ (ภาพคือตัวเนื้อหาของ slideshow)
            ->join('file_info as img', function ($join) {
                $join->on('img.id', '=', 'banner_item_info.intro_image_id')
                    ->where('img.status', 'Y')
                    ->whereNull('img.deleted_at');
            })
            ->whereNull('d.deleted_at') // join ตรง ไม่ผ่าน scope ของ model ต้องกันเองไม่ให้ดึงแถวที่ถูกลบ
            ->where('banner_item_info.banner_category_info_id', $setting['banner_category_info_id'] ?? 0)
            ->where('banner_item_info.status', 'Y')
            ->where(fn ($q) => $q->whereNull('banner_item_info.publish_date')->orWhere('banner_item_info.publish_date', '<=', $now))
            ->where(fn ($q) => $q->whereNull('banner_item_info.publish_down')->orWhere('banner_item_info.publish_down', '>', $now));

        match ($setting['sort_by'] ?? 'publish_desc') {
            'publish_asc' => $query->orderByRaw('COALESCE(banner_item_info.publish_date, banner_item_info.created_at) asc'),
            'order_asc' => $query->orderBy('banner_item_info.sort_order'),
            'order_desc' => $query->orderByDesc('banner_item_info.sort_order'),
            default => $query->orderByRaw('COALESCE(banner_item_info.publish_date, banner_item_info.created_at) desc'),
        };

        return $query
            ->orderBy('banner_item_info.id') // tie-breaker ให้ลำดับเสถียร
            ->limit(self::PREVIEW_LIMIT)
            ->get(['banner_item_info.id', 'banner_item_info.url', 'img.hash_name as image', 'd.title', 'd.intro_text'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'image' => $row->image,
                'title' => $row->title ?? '',
                'intro_text' => $row->intro_text ?? '',
                // ไม่ส่ง URL — ตัวอย่างแค่บอกว่ามีลิงก์ (กดไม่ได้)
                'has_link' => $row->url !== null && trim($row->url) !== '',
            ])
            ->values()
            ->all();
    }
}
