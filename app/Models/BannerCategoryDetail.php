<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * หมวดหมู่ป้ายโฆษณา — ข้อมูลแยกตามภาษา (title/intro_text)
 *
 * primary key เป็นแบบ composite (id, lang) — Eloquent ไม่รองรับ composite key เต็มรูปแบบ
 * ให้ค้นด้วย BannerCategoryDetail::where('id', ...)->where('lang', ...) แทนการใช้ find()
 * ฟิลด์ทุกตัวเป็น nullable ระดับ DB — การ required เฉพาะภาษาหลัก (sys_setting: site.lang_default)
 * บังคับที่ FormRequest ตอนบันทึก ไม่ใช่ที่ระดับ schema
 */
class BannerCategoryDetail extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'banner_category_detail';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'lang',
        'title',
        'intro_text',
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
        return $this->belongsTo(BannerCategoryInfo::class, 'id');
    }
}
