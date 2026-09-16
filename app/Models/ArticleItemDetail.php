<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * บทความ — ข้อมูลแยกตามภาษา (title/slug/SEO ฯลฯ) เนื้อหาเต็มอยู่ที่ระบบ part (ArticleItemPart) ไม่ใช่ที่นี่
 *
 * primary key เป็นแบบ composite (id, lang) — Eloquent ไม่รองรับ composite key เต็มรูปแบบ
 * ให้ค้นด้วย ArticleItemDetail::where('id', ...)->where('lang', ...) แทนการใช้ find()
 * ฟิลด์ทุกตัวเป็น nullable ระดับ DB — การ required เฉพาะภาษาหลัก (sys_setting: site.lang_default)
 * บังคับที่ FormRequest ตอนบันทึก ไม่ใช่ที่ระดับ schema
 */
class ArticleItemDetail extends Model
{
    use SoftDeletes;

    protected $table = 'article_item_detail';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'lang',
        'title',
        'intro_text',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * บทความ (ข้อมูลร่วม) ที่ข้อมูลภาษานี้สังกัดอยู่
     */
    public function item()
    {
        return $this->belongsTo(ArticleItemInfo::class, 'id');
    }
}
