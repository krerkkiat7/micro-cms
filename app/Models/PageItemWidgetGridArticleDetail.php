<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ข้อมูลแยกภาษาของ widget "Grid จาก article" (ข้อความของปุ่ม "อ่านทั้งหมด") — PK = id + lang (composite key: ห้ามใช้ find()/save()
 * ต้อง where(id, lang) แล้ว update()/create() เหมือนตาราง *_detail อื่น)
 */
class PageItemWidgetGridArticleDetail extends Model
{
    use SoftDeletes;

    protected $table = 'page_item_widget_gridarticle_detail';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'lang',
        'read_all_text',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function setting()
    {
        return $this->belongsTo(PageItemWidgetGridArticle::class, 'id');
    }
}
