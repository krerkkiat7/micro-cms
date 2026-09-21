<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * การตั้งค่าเฉพาะของ widget ประเภท "Slideshow จาก article" — 1 widget = 1 แถว, PK `id` = `page_item_widget.id`
 * (ไม่ auto-increment; สร้างพร้อม widget เสมอ) ดู App\Support\PageWidget\SlideshowArticleWidget
 */
class PageItemWidgetSlideshowArticle extends Model
{
    use SoftDeletes;

    protected $table = 'page_item_widget_slideshowarticle';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'article_category_info_id',
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
        return $this->belongsTo(ArticleCategoryInfo::class, 'article_category_info_id');
    }
}
