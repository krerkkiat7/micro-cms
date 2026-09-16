<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * แท็กบทความ — ชื่อแยกตามภาษา
 *
 * primary key เป็นแบบ composite (id, lang) — Eloquent ไม่รองรับ composite key เต็มรูปแบบ
 * ให้ค้นด้วย ArticleTagDetail::where('id', ...)->where('lang', ...) แทนการใช้ find()
 */
class ArticleTagDetail extends Model
{
    use SoftDeletes;

    protected $table = 'article_tag_detail';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'lang',
        'name',
        'slug',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * แท็ก (ข้อมูลร่วม) ที่ชื่อภาษานี้สังกัดอยู่
     */
    public function tag()
    {
        return $this->belongsTo(ArticleTagInfo::class, 'id');
    }
}
