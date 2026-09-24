<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ข้อมูลเมนูหน้าบ้านแยกตามภาษา (PK = id+lang) — Eloquent ไม่รองรับ composite key เต็มรูปแบบ
 * ค้นด้วย ::where('id', ...)->where('lang', ...) เสมอ ห้ามใช้ find()
 */
class FrontMenuDetail extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'front_menu_detail';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'lang',
        'name',
        'title',
        'subtitle',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function menu()
    {
        return $this->belongsTo(FrontMenuInfo::class, 'id');
    }
}
