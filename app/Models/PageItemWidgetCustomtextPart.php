<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ส่วนเนื้อหาของ widget "Custom Text" (เทียบเคียง ArticleItemPart) เรียงลำดับได้ (sort_order)
 *
 * part_type: text, image, images, video (ตัดเอกสารออกจากชุดของบทความ เพราะ widget นี้เน้นข้อความ/สื่อ)
 * images_display_type ใช้เฉพาะ part_type = images (ค่าเดียวกับ ArticleItemPart::images_display_type)
 * show_title: แสดงหัวเรื่องของ part นี้หรือไม่ (Y/N)
 * status: แสดง/ซ่อน part นี้ทั้งอัน (Y/N) — คนละความหมายกับ soft delete
 * title_font_size/title_bold/title_font_family/title_align/title_color: การจัดรูปแบบหัวเรื่องของ part นี้ (ชุดฟิลด์เดียวกับ
 * App\Support\PageTextStyle บวกตัวหนา (`title_bold`) เหมือนหัวเรื่อง/ข้อความเกริ่นนำของ Slideset/Grid แต่เก็บแยกต่อ part
 * เพราะแต่ละ part มีหัวเรื่องของตัวเอง)
 */
class PageItemWidgetCustomtextPart extends Model
{
    use SoftDeletes;

    protected $table = 'page_item_widget_customtext_part';

    protected $fillable = [
        'page_item_widget_id',
        'sort_order',
        'part_type',
        'images_display_type',
        'show_title',
        'status',
        'setting',
        'title_font_size',
        'title_bold',
        'title_font_family',
        'title_align',
        'title_color',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'setting' => 'array',
    ];

    /**
     * widget ที่ part นี้สังกัดอยู่
     */
    public function widget()
    {
        return $this->belongsTo(PageItemWidget::class, 'page_item_widget_id');
    }

    /**
     * ไฟล์ของ part นี้ (รูปภาพ/วิดีโอ) เรียงตามลำดับ
     */
    public function files()
    {
        return $this->hasMany(PageItemWidgetCustomtextPartFile::class)->orderBy('sort_order');
    }

    /**
     * หัวข้อ/เนื้อหาข้อความของ part นี้ แยกตามภาษา
     */
    public function details()
    {
        return $this->hasMany(PageItemWidgetCustomtextPartDetail::class, 'id');
    }
}
