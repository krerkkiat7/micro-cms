<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * การตั้งค่าเฉพาะของ widget ประเภท "Slideset จาก banner" — 1 widget = 1 แถว, PK `id` = `page_item_widget.id`
 * (ไม่ auto-increment; สร้างพร้อม widget เสมอ) ดู App\Support\PageWidget\SlidesetBannerWidget
 */
class PageItemWidgetSlidesetBanner extends Model
{
    use SoftDeletes;

    protected $table = 'page_item_widget_slidesetbanner';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'banner_category_info_id',
        'sort_by',
        'max_items',
        'show_arrows',
        'show_dots',
        'autoplay',
        'autoplay_interval',
        'transition_speed',
        'per_row_pc',
        'per_row_notebook',
        'per_row_tablet',
        'per_row_mobile',
        'show_image',
        'aspect_ratio',
        'image_fit',
        'image_background',
        'image_clickable',
        'link_target',
        'show_border',
        'rounded_corners',
        'show_title',
        'title_font_size',
        'title_bold',
        'title_font_family',
        'title_color',
        'title_align',
        'title_clickable',
        'title_lines',
        'show_intro_text',
        'intro_text_font_size',
        'intro_text_bold',
        'intro_text_font_family',
        'intro_text_color',
        'intro_text_align',
        'intro_text_clickable',
        'intro_text_lines',
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
