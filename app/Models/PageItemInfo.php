<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ข้อมูลร่วมของหน้าเพจ (รูปแทนหน้า + พื้นหลัง) — ไม่แยกภาษา; โครงสร้างการแสดงผลอยู่ใน rows() → columns() → widgets()
 */
class PageItemInfo extends Model
{
    use SoftDeletes;

    protected $table = 'page_item_info';

    protected $fillable = [
        'intro_image_id',
        'background_color',
        'background_image_id',
        'background_repeat',
        'background_size',
        'background_attachment',
        'background_position',
        'layout_updated_at',
        'layout_updated_by',
        'status',
        'is_temp',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'layout_updated_at' => 'datetime',
    ];

    public function introImage()
    {
        return $this->belongsTo(FileInfo::class, 'intro_image_id');
    }

    public function backgroundImage()
    {
        return $this->belongsTo(FileInfo::class, 'background_image_id');
    }

    /**
     * ข้อมูลแยกตามภาษา (ชื่อ/ข้อความเกริ่นนำ/SEO)
     */
    public function details()
    {
        return $this->hasMany(PageItemDetail::class, 'id');
    }

    /**
     * แถวของหน้านี้ เรียงตามลำดับ
     */
    public function rows()
    {
        return $this->hasMany(PageItemRow::class)->orderBy('sort_order')->orderBy('id');
    }
}
