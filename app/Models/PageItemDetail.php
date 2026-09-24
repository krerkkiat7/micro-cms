<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ข้อมูลหน้าเพจแยกตามภาษา (ชื่อ/ข้อความเกริ่นนำ/SEO) (PK = id+lang) — Eloquent ไม่รองรับ composite key เต็มรูปแบบ
 * ค้นด้วย ::where('id', ...)->where('lang', ...) เสมอ ห้ามใช้ find()
 */
class PageItemDetail extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'page_item_detail';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'lang',
        'title',
        'intro_text',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function item()
    {
        return $this->belongsTo(PageItemInfo::class, 'id');
    }
}
