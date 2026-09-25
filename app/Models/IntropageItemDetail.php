<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ข้อมูลหน้า Intropage แยกตามภาษา (PK = id+lang) — Eloquent ไม่รองรับ composite key เต็มรูปแบบ
 * ค้นด้วย ::where('id', ...)->where('lang', ...) เสมอ ห้ามใช้ find()
 */
class IntropageItemDetail extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'intropage_item_detail';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'lang',
        'title',
        'detail',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function item()
    {
        return $this->belongsTo(IntropageItemInfo::class, 'id');
    }
}
