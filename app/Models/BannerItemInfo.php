<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ป้ายโฆษณา — ข้อมูลร่วมที่ไม่แยกภาษา (ข้อมูลแยกภาษาอยู่ที่ BannerItemDetail) ผูกกับหมวดหมู่เดียว
 * แสดงผลเป็นรูปภาพเท่านั้น (รูปภาพใช้ร่วมทุกภาษา) พร้อมลิงก์ที่กดไปได้
 */
class BannerItemInfo extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'banner_item_info';

    protected $fillable = [
        'banner_category_info_id',
        'intro_image_id',
        'url',
        'link_target',
        'publish_date',
        'publish_down',
        'click_amount',
        'sort_order',
        'status',
        'is_temp',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // ต้อง cast เป็น datetime ชัดเจน — ไม่งั้น $model->publish_date จะเป็น string ธรรมดา ทำให้
    // optional($model->publish_date)->format(...) ในคอนโทรลเลอร์คืนค่า null เงียบ ๆ (Optional::__call
    // เรียก method ต่อเมื่อ is_object() เท่านั้น)
    protected $casts = [
        'publish_date' => 'datetime',
        'publish_down' => 'datetime',
    ];

    /**
     * หมวดหมู่ (ตำแหน่งที่ใช้แสดงผล) ของป้ายโฆษณานี้
     */
    public function category()
    {
        return $this->belongsTo(BannerCategoryInfo::class, 'banner_category_info_id');
    }

    /**
     * รูปภาพป้ายโฆษณา (เลือกจากโมดูลจัดการไฟล์)
     */
    public function introImage()
    {
        return $this->belongsTo(FileInfo::class, 'intro_image_id');
    }

    /**
     * ข้อมูลแยกตามภาษาของป้ายโฆษณานี้
     */
    public function details()
    {
        return $this->hasMany(BannerItemDetail::class, 'id');
    }
}
