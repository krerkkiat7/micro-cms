<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ไฟล์ของ part เนื้อหาบทความ (รูปภาพ/เอกสาร/วิดีโอ) — part ประเภท images/documents มีได้หลายแถว เรียงด้วย sort_order
 * cover_image_id ใช้เฉพาะ part_type = video (รูปภาพหน้าปกของวิดีโอ)
 */
class ArticleItemPartFile extends Model
{
    use SoftDeletes;

    protected $table = 'article_item_part_file';

    protected $fillable = [
        'article_item_part_id',
        'sort_order',
        'file_id',
        'cover_image_id',
        'video_type',
        'youtube_url',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'description' => 'array',
    ];

    /**
     * part ที่ไฟล์นี้สังกัดอยู่
     */
    public function part()
    {
        return $this->belongsTo(ArticleItemPart::class, 'article_item_part_id');
    }

    /**
     * ไฟล์หลักของแถวนี้ (เลือกจากโมดูลจัดการไฟล์)
     */
    public function file()
    {
        return $this->belongsTo(FileInfo::class, 'file_id');
    }

    /**
     * รูปภาพหน้าปก (เฉพาะ part ประเภทวิดีโอ)
     */
    public function coverImage()
    {
        return $this->belongsTo(FileInfo::class, 'cover_image_id');
    }
}
