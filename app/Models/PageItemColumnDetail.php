<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ข้อมูลคอลัมน์ของหน้าเพจแยกตามภาษา (PK = id+lang) — Eloquent ไม่รองรับ composite key เต็มรูปแบบ
 * ค้นด้วย ::where('id', ...)->where('lang', ...) เสมอ ห้ามใช้ find()
 */
class PageItemColumnDetail extends Model
{
    use SoftDeletes;

    protected $table = 'page_item_column_detail';

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

    public function column()
    {
        return $this->belongsTo(PageItemColumn::class, 'id');
    }
}
