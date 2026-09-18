<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ป้ายโฆษณา — ข้อมูลแยกตามภาษา (title/intro_text)
 *
 * primary key เป็นแบบ composite (id, lang) — Eloquent ไม่รองรับ composite key เต็มรูปแบบ
 * ให้ค้นด้วย BannerItemDetail::where('id', ...)->where('lang', ...) แทนการใช้ find()
 */
class BannerItemDetail extends Model
{
    use SoftDeletes;

    protected $table = 'banner_item_detail';

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
     * ป้ายโฆษณา (ข้อมูลร่วม) ที่ข้อมูลภาษานี้สังกัดอยู่
     */
    public function item()
    {
        return $this->belongsTo(BannerItemInfo::class, 'id');
    }
}
