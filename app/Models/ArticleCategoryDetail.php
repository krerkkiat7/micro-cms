<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * หมวดหมู่บทความ — ข้อมูลแยกตามภาษา (title/slug/SEO ฯลฯ)
 *
 * primary key เป็นแบบ composite (id, lang) — Eloquent ไม่รองรับ composite key เต็มรูปแบบ
 * ให้ค้นด้วย ArticleCategoryDetail::where('id', ...)->where('lang', ...) แทนการใช้ find()
 * ฟิลด์ทุกตัวเป็น nullable ระดับ DB — การ required เฉพาะภาษาหลัก (sys_setting: site.lang_default)
 * บังคับที่ FormRequest ตอนบันทึก ไม่ใช่ที่ระดับ schema
 */
class ArticleCategoryDetail extends Model
{
    use SoftDeletes;

    protected $table = 'article_category_detail';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'lang',
        'title',
        'intro_text',
        'detail',
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
     * หมวดหมู่ (ข้อมูลร่วม) ที่ข้อมูลภาษานี้สังกัดอยู่
     */
    public function category()
    {
        return $this->belongsTo(ArticleCategoryInfo::class, 'id');
    }
}
