<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * การตั้งค่าเฉพาะของ widget ประเภท "Slideshow จาก banner" — 1 widget = 1 แถว, PK `id` = `page_item_widget.id`
 * (ไม่ auto-increment; สร้างพร้อม widget เสมอ) ดู App\Support\PageWidget\SlideshowBannerWidget
 */
class PageItemWidgetSlideshowBanner extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'page_item_widget_slideshowbanner';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'banner_category_info_id',
        'sort_by',
        'show_arrows',
        'show_dots',
        'autoplay',
        'autoplay_interval',
        'transition_speed',
        'transition_effect',
        'aspect_ratio',
        'is_clickable',
        'link_target',
        'show_title',
        'show_intro_text',
        'text_align',
        'text_width',
        'max_items',
        'title_font_size',
        'title_font_family',
        'title_color',
        'intro_text_font_size',
        'intro_text_font_family',
        'intro_text_color',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function widget()
    {
        return $this->belongsTo(PageItemWidget::class, 'id');
    }

    public function category()
    {
        return $this->belongsTo(BannerCategoryInfo::class, 'banner_category_info_id');
    }
}
