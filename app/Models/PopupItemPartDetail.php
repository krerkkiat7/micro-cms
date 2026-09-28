<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ข้อความของ part ของ popup แยกตามภาษา
 *
 * primary key เป็นแบบ composite (id, lang) — ค้นด้วย where('id')->where('lang') แทน find()
 */
class PopupItemPartDetail extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'popup_item_part_detail';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'lang',
        'detail',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function part()
    {
        return $this->belongsTo(PopupItemPart::class, 'id');
    }
}
