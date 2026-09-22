<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * การตั้งค่าเฉพาะของ widget ประเภท "Slideset จาก article" — 1 widget = 1 แถว, PK `id` = `page_item_widget.id`
 * (ไม่ auto-increment; สร้างพร้อม widget เสมอ) ดู App\Support\PageWidget\SlidesetArticleWidget
 */
class PageItemWidgetSlidesetArticle extends Model
{
    use SoftDeletes;

    protected $table = 'page_item_widget_slidesetarticle';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'article_category_info_id',
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
        'image_clickable',
        'link_target',
        'show_border',
        'border_color',
        'rounded_corners',
        'item_background',
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
        'show_date',
        'date_font_size',
        'date_bold',
        'date_font_family',
        'date_color',
        'show_views',
        'views_font_size',
        'views_bold',
        'views_font_family',
        'views_color',
        'image_background',
        'show_read_all',
        'read_all_position',
        'read_all_icon',
        'read_all_icon_position',
        'read_all_style',
        'read_all_url',
        'read_all_link_target',
        'read_all_font_size',
        'read_all_font_family',
        'read_all_color',
        'read_all_background',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function widget()
    {
        return $this->belongsTo(PageItemWidget::class, 'id');
    }

    /** ข้อความของปุ่ม "อ่านทั้งหมด" แยกตามภาษา */
    public function details()
    {
        return $this->hasMany(PageItemWidgetSlidesetArticleDetail::class, 'id');
    }

    public function category()
    {
        return $this->belongsTo(ArticleCategoryInfo::class, 'article_category_info_id');
    }
}
