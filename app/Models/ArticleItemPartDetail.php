<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * หัวข้อ/เนื้อหาข้อความของ part เนื้อหาบทความ แยกตามภาษา
 * title ใส่ได้ทุกประเภท part (รูปภาพ/เอกสาร/วิดีโอ ตั้งหัวข้อได้), detail ใช้เฉพาะ part_type = text
 *
 * primary key เป็นแบบ composite (id, lang) — Eloquent ไม่รองรับ composite key เต็มรูปแบบ
 * ให้ค้นด้วย ArticleItemPartDetail::where('id', ...)->where('lang', ...) แทนการใช้ find()
 */
class ArticleItemPartDetail extends Model
{
    use SoftDeletes;

    protected $table = 'article_item_part_detail';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'lang',
        'title',
        'detail',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * part ที่ข้อมูลภาษานี้สังกัดอยู่
     */
    public function part()
    {
        return $this->belongsTo(ArticleItemPart::class, 'id');
    }
}
