<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ข้อมูลร่วมของหน้า Intropage (พื้นหลัง + สื่อหลัก + ช่วงเวลาเผยแพร่) — ไม่แยกภาษา
 */
class IntropageItemInfo extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'intropage_item_info';

    protected $fillable = [
        'background_color',
        'background_image_id',
        'background_repeat',
        'background_size',
        'background_attachment',
        'background_position',
        'display_type',
        'display_size',
        'image_file_id',
        'vdo_file_id',
        'vdo_url',
        'show_button',
        'publish_date',
        'publish_down',
        'status',
        'is_temp',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'publish_date' => 'datetime',
        'publish_down' => 'datetime',
    ];

    public function backgroundImage()
    {
        return $this->belongsTo(FileInfo::class, 'background_image_id');
    }

    public function imageFile()
    {
        return $this->belongsTo(FileInfo::class, 'image_file_id');
    }

    public function vdoFile()
    {
        return $this->belongsTo(FileInfo::class, 'vdo_file_id');
    }

    /**
     * ข้อมูลแยกตามภาษา (ชื่อ/ข้อความต้อนรับ)
     */
    public function details()
    {
        return $this->hasMany(IntropageItemDetail::class, 'id');
    }

    /**
     * ปุ่มด้านล่างของหน้า Intropage นี้ เรียงตามลำดับ
     */
    public function buttons()
    {
        return $this->hasMany(IntropageItemButton::class)->orderBy('sort_order');
    }
}
