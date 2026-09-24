<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * หมวดหมู่ป้ายโฆษณา (ตำแหน่งที่ใช้แสดงผล เช่น ไฮไลท์, หน่วยงานที่เกี่ยวข้อง) — ข้อมูลร่วมที่ไม่แยกภาษา
 * (ข้อมูลแยกภาษาอยู่ที่ BannerCategoryDetail) หมวดหมู่มีระดับเดียว (ไม่มีหมวดหมู่ย่อย) และ 1 ป้ายโฆษณามีได้เพียง 1 หมวดหมู่
 */
class BannerCategoryInfo extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'banner_category_info';

    protected $fillable = [
        'status',
        'is_temp',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * ข้อมูลแยกตามภาษาของหมวดหมู่นี้
     */
    public function details()
    {
        return $this->hasMany(BannerCategoryDetail::class, 'id');
    }
}
