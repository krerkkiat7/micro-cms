<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ข้อมูล 1 รายการ (part) ของ popup — แสดงเป็น 1 สไลด์ที่หน้าบ้าน ข้อความแยกภาษาอยู่ที่ PopupItemPartDetail
 */
class PopupItemPart extends Model
{
    use FlushesFrontCache, SoftDeletes;

    /** image_text = รูปภาพ + ข้อความ, image = รูปภาพ, text = ข้อความ */
    public const TYPES = ['image_text', 'image', 'text'];

    public const IMAGE_SIZES = ['full', 'large', 'medium', 'small'];

    protected $table = 'popup_item_part';

    protected $fillable = [
        'popup_item_info_id',
        'part_type',
        'image_id',
        'image_size',
        'url',
        'link_target',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function popup()
    {
        return $this->belongsTo(PopupItemInfo::class, 'popup_item_info_id');
    }

    public function image()
    {
        return $this->belongsTo(FileInfo::class, 'image_id');
    }

    public function details()
    {
        return $this->hasMany(PopupItemPartDetail::class, 'id');
    }

    public function hasImage(): bool
    {
        return in_array($this->part_type, ['image_text', 'image'], true);
    }

    public function hasText(): bool
    {
        return in_array($this->part_type, ['image_text', 'text'], true);
    }
}
