<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * เมนูหน้าบ้าน — ข้อมูลร่วมที่ไม่แยกภาษา (ชื่อ/หัวเรื่อง/หัวเรื่องรองแยกภาษาอยู่ที่ FrontMenuDetail)
 * โครงสร้างเป็น tree ผ่าน parent_id — เฉพาะเมนูประเภท heading เท่านั้นที่เป็น parent ได้ (เช็กในโค้ด)
 */
class FrontMenuInfo extends Model
{
    use SoftDeletes;

    protected $table = 'front_menu_info';

    protected $fillable = [
        'parent_id',
        'menu_type',
        'target_article_category_id',
        'target_article_item_id',
        'target_page_item_id',
        'url',
        'link_target',
        'is_home',
        'show_header_image',
        'header_image_id',
        'header_image_aspect_ratio',
        'header_image_fit',
        'header_image_background',
        'show_title',
        'title_font_size',
        'title_font_family',
        'title_color',
        'title_bold',
        'show_subtitle',
        'subtitle_font_size',
        'subtitle_font_family',
        'subtitle_color',
        'subtitle_bold',
        'header_content_align',
        'use_container',
        'show_breadcrumb',
        'sort_order',
        'status',
        'is_temp',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * เมนูแม่ (มีได้เฉพาะกรณี parent เป็นประเภท heading)
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * เมนูลูกโดยตรง เรียงตามลำดับ
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * ข้อมูลแยกตามภาษา (ชื่อเมนู/หัวเรื่อง/หัวเรื่องรอง)
     */
    public function details()
    {
        return $this->hasMany(FrontMenuDetail::class, 'id');
    }

    /**
     * รูปพื้นหลังส่วนหัว (เลือกจากโมดูลจัดการไฟล์)
     */
    public function headerImage()
    {
        return $this->belongsTo(FileInfo::class, 'header_image_id');
    }

    /**
     * หมวดหมู่บทความเป้าหมาย — เฉพาะ menu_type = article_category
     */
    public function targetArticleCategory()
    {
        return $this->belongsTo(ArticleCategoryInfo::class, 'target_article_category_id');
    }

    /**
     * บทความเป้าหมาย — เฉพาะ menu_type = article_item
     */
    public function targetArticleItem()
    {
        return $this->belongsTo(ArticleItemInfo::class, 'target_article_item_id');
    }

    /**
     * หน้าเพจเป้าหมาย — เฉพาะ menu_type = page
     */
    public function targetPageItem()
    {
        return $this->belongsTo(PageItemInfo::class, 'target_page_item_id');
    }
}
