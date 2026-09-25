<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * แท็กบทความ — ชุดแท็กกลาง ไม่แยกภาษา (ชื่อแท็กต่อภาษาอยู่ที่ ArticleTagDetail)
 * บทความหนึ่งเลือกได้หลายแท็ก ผ่าน pivot article_item_tag
 */
class ArticleTagInfo extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'article_tag_info';

    protected $fillable = [
        'status',
        'is_temp',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * ชื่อแท็กแยกตามภาษา
     */
    public function details()
    {
        return $this->hasMany(ArticleTagDetail::class, 'id');
    }

    /**
     * บทความที่ผูกแท็กนี้ไว้
     */
    public function items()
    {
        return $this->belongsToMany(ArticleItemInfo::class, 'article_item_tag', 'article_tag_info_id', 'article_item_info_id')
            ->withPivot('created_by', 'updated_by')
            ->withTimestamps();
    }
}
