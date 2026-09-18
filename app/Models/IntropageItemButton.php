<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ปุ่มด้านล่างของหน้า Intropage เรียงลำดับได้ (sort_order)
 *
 * button_type: home (มีอยู่เสมอ 1 ปุ่ม ลบไม่ได้ — บังคับที่ FormRequest) / other (เพิ่ม/ลบได้อิสระ)
 * button_display_type: text (ใช้ texts + text_color + background_color) / image (ใช้ button_image_id)
 * texts เก็บข้อความปุ่มแยกตามภาษา เช่น {"th": "เข้าสู่เว็บไซต์", "en": "Enter Site"}
 */
class IntropageItemButton extends Model
{
    use SoftDeletes;

    protected $table = 'intropage_item_button';

    protected $fillable = [
        'intropage_item_info_id',
        'button_type',
        'sort_order',
        'button_display_type',
        'background_color',
        'text_color',
        'button_image_id',
        'url',
        'link_target',
        'texts',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'texts' => 'array',
    ];

    public function item()
    {
        return $this->belongsTo(IntropageItemInfo::class, 'intropage_item_info_id');
    }

    public function buttonImage()
    {
        return $this->belongsTo(FileInfo::class, 'button_image_id');
    }
}
