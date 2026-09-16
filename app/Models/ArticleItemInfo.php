<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * บทความ — ข้อมูลร่วมที่ไม่แยกภาษา (ข้อมูลแยกภาษาอยู่ที่ ArticleItemDetail)
 * บทความหนึ่งผูกกับหมวดหมู่ได้เพียงหมวดหมู่เดียว เนื้อหาเต็มประกอบจาก part (ArticleItemPart) เรียงลำดับได้
 */
class ArticleItemInfo extends Model
{
    use SoftDeletes;

    protected $table = 'article_item_info';

    protected $fillable = [
        'article_category_info_id',
        'intro_image_id',
        'publish_date',
        'publish_down',
        'view_amount',
        'status',
        'is_temp',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // ต้อง cast เป็น datetime ชัดเจน — ไม่งั้น $model->publish_date จะเป็น string ธรรมดา ทำให้
    // optional($model->publish_date)->format(...) ในคอนโทรลเลอร์คืนค่า null เงียบ ๆ (Optional::__call
    // เรียก method ต่อเมื่อ is_object() เท่านั้น)
    protected $casts = [
        'publish_date' => 'datetime',
        'publish_down' => 'datetime',
    ];

    /**
     * หมวดหมู่ของบทความนี้
     */
    public function category()
    {
        return $this->belongsTo(ArticleCategoryInfo::class, 'article_category_info_id');
    }

    /**
     * รูปภาพหน้าปกบทความ (เลือกจากโมดูลจัดการไฟล์)
     */
    public function introImage()
    {
        return $this->belongsTo(FileInfo::class, 'intro_image_id');
    }

    /**
     * ข้อมูลแยกตามภาษาของบทความนี้
     */
    public function details()
    {
        return $this->hasMany(ArticleItemDetail::class, 'id');
    }

    /**
     * เนื้อหาบทความแบบแบ่ง part เรียงตามลำดับ
     */
    public function parts()
    {
        return $this->hasMany(ArticleItemPart::class)->orderBy('sort_order');
    }

    /**
     * แท็กที่ผูกกับบทความนี้
     */
    public function tags()
    {
        return $this->belongsToMany(ArticleTagInfo::class, 'article_item_tag', 'article_item_info_id', 'article_tag_info_id')
            ->withPivot('created_by', 'updated_by')
            ->withTimestamps();
    }
}
