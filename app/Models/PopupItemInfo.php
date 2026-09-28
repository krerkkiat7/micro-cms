<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use App\Support\FrontMenuType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Popup — ข้อมูลร่วม + ตั้งค่าการแสดงผล/สไลด์ + ช่วงเผยแพร่ ข้อมูลที่แสดงอยู่ที่ part (PopupItemPart)
 * และเมนูที่แสดงอยู่ที่ pivot popup_item_menu (ดู docs/PRD-popup.md)
 */
class PopupItemInfo extends Model
{
    use FlushesFrontCache, SoftDeletes;

    public const DISPLAY_TYPES = ['modal', 'floating'];

    /** all = ทุกหน้า, selected = เมนูที่ระบุ, none = ไม่กำหนด (ไม่แสดงที่หน้าบ้าน) */
    public const MENU_MODES = ['all', 'selected', 'none'];

    /** ประเภทเมนูหน้าบ้านที่เลือกให้แสดง popup ได้ (เมนูที่ผูกกับโมดูลเนื้อหา) */
    public const MENU_TYPES = [FrontMenuType::ARTICLE_CATEGORY, FrontMenuType::ARTICLE_ITEM, FrontMenuType::PAGE];

    protected $table = 'popup_item_info';

    protected $fillable = [
        'name',
        'display_type',
        'show_dismiss_today',
        'show_arrows',
        'show_dots',
        'autoplay',
        'slide_interval',
        'slide_speed',
        'menu_mode',
        'publish_date',
        'publish_down',
        'sort_order',
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

    /**
     * ข้อมูลที่แสดง (part) เรียงตามลำดับ
     */
    public function parts()
    {
        return $this->hasMany(PopupItemPart::class, 'popup_item_info_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * เมนูหน้าบ้านที่เลือกให้แสดง popup (ใช้เมื่อ menu_mode = selected)
     */
    public function menus()
    {
        return $this->belongsToMany(FrontMenuInfo::class, 'popup_item_menu', 'popup_item_info_id', 'front_menu_info_id')
            ->withPivot(['created_by', 'updated_by'])
            ->withTimestamps();
    }
}
