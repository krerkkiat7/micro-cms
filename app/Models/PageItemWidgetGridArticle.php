<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * การตั้งค่าเฉพาะของ widget ประเภท "Grid จาก article" — 1 widget = 1 แถว, PK `id` = `page_item_widget.id`
 * (ไม่ auto-increment; สร้างพร้อม widget เสมอ) ดู App\Support\PageWidget\GridArticleWidget
 */
class PageItemWidgetGridArticle extends Model
{
    use SoftDeletes;

    protected $table = 'page_item_widget_gridarticle';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'article_category_info_id',
        'sort_by',
        'max_items',
        'display_type',
        'per_row_pc',
        'per_row_notebook',
        'per_row_tablet',
        'per_row_mobile',
        'link_target',
        'show_image',
        'image_width_percent',
        'aspect_ratio',
        'image_fit',
        'image_background',
        'image_clickable',
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
        'show_read_all',
        'read_all_position',
        'read_all_icon',
        'read_all_icon_position',
        'read_all_style',
        'read_all_font_size',
        'read_all_font_family',
        'read_all_color',
        'read_all_background',
        'read_all_url',
        'read_all_link_target',
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
        return $this->hasMany(PageItemWidgetGridArticleDetail::class, 'id');
    }

    public function category()
    {
        return $this->belongsTo(ArticleCategoryInfo::class, 'article_category_info_id');
    }
}
