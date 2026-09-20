<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * widget ภายในคอลัมน์ — widget_type + setting (json) รอกำหนดประเภท/รายละเอียดในรอบถัดไป
 */
class PageItemWidget extends Model
{
    use SoftDeletes;

    protected $table = 'page_item_widget';

    /** ประเภท widget ที่รองรับตอนนี้ — ยังรอกำหนดรายละเอียด มี placeholder ประเภทเดียว (ให้ตรงกับ utils/pageLayout.ts) */
    public const TYPES = ['placeholder'];

    protected $fillable = [
        'page_item_column_id',
        'sort_order',
        'show_title',
        'widget_type',
        'setting',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'setting' => 'array',
    ];

    public function column()
    {
        return $this->belongsTo(PageItemColumn::class, 'page_item_column_id');
    }

    public function details()
    {
        return $this->hasMany(PageItemWidgetDetail::class, 'id');
    }
}
