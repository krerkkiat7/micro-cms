<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ส่วนเนื้อหาของบทความ (content part) เรียงลำดับได้ (sort_order) เริ่มต้นบทความใหม่ด้วย part ข้อความเสมอ
 *
 * part_type: text, image, images, video, document, documents
 * images_display_type ใช้เฉพาะ part_type = images (thumbnail_carousel, multi_carousel, grid_lightbox,
 * full_width_slider, masonry_grid, justified_grid, stacked_cards)
 */
class ArticleItemPart extends Model
{
    use SoftDeletes;

    protected $table = 'article_item_part';

    protected $fillable = [
        'article_item_info_id',
        'sort_order',
        'part_type',
        'images_display_type',
        'setting',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'setting' => 'array',
    ];

    /**
     * บทความที่ part นี้สังกัดอยู่
     */
    public function item()
    {
        return $this->belongsTo(ArticleItemInfo::class, 'article_item_info_id');
    }

    /**
     * ไฟล์ของ part นี้ (รูปภาพ/เอกสาร/วิดีโอ) เรียงตามลำดับ
     */
    public function files()
    {
        return $this->hasMany(ArticleItemPartFile::class)->orderBy('sort_order');
    }

    /**
     * หัวข้อ/เนื้อหาข้อความของ part นี้ แยกตามภาษา
     */
    public function details()
    {
        return $this->hasMany(ArticleItemPartDetail::class, 'id');
    }
}
