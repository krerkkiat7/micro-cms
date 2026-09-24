<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * หมวดหมู่บทความ — ข้อมูลร่วมที่ไม่แยกภาษา (ข้อมูลแยกภาษาอยู่ที่ ArticleCategoryDetail)
 * หมวดหมู่มีระดับเดียว (ไม่มีหมวดหมู่ย่อย) และ 1 บทความมีได้เพียง 1 หมวดหมู่
 */
class ArticleCategoryInfo extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'article_category_info';

    protected $fillable = [
        'intro_image_id',
        'sort_order',
        'status',
        'is_temp',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * รูปภาพหน้าปกหมวดหมู่ (เลือกจากโมดูลจัดการไฟล์)
     */
    public function introImage()
    {
        return $this->belongsTo(FileInfo::class, 'intro_image_id');
    }

    /**
     * ข้อมูลแยกตามภาษาของหมวดหมู่นี้
     */
    public function details()
    {
        return $this->hasMany(ArticleCategoryDetail::class, 'id');
    }
}
