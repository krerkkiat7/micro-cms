<?php

namespace App\Models;

use App\Models\Concerns\HasPageTextStyle;
use App\Support\PageWidget\PageWidgetRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * widget ภายในคอลัมน์ — widget_type บอกประเภท และการตั้งค่าเฉพาะประเภทอยู่ในตาราง `page_item_widget_<ประเภท>` ของตัวเอง (PK = id ของ widget นี้;
 * ดู App\Support\PageWidget) + พื้นหลัง (สี/รูป/CSS 4 ค่า) เหมือนแถว/คอลัมน์
 */
class PageItemWidget extends Model
{
    use HasPageTextStyle;
    use SoftDeletes;

    protected $table = 'page_item_widget';

    /** ประเภทเดิมก่อนมีประเภทจริง — ไม่มีตารางตั้งค่า ยังโหลด/บันทึกได้เพื่อไม่ให้ข้อมูลเก่าพัง แต่เลือกสร้างใหม่ไม่ได้ */
    public const LEGACY_TYPE = 'placeholder';

    /**
     * ประเภท widget ที่บันทึกได้ (ประเภทจริงในทะเบียน + ประเภทเดิม) — ประเภทที่สร้างใหม่ได้ดู utils/pageWidget.ts
     *
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return [...PageWidgetRegistry::types(), self::LEGACY_TYPE];
    }

    protected $fillable = [
        'page_item_column_id',
        'sort_order',
        'show_title',
        'widget_type',
        'background_color',
        'background_image_id',
        'background_repeat',
        'background_size',
        'background_attachment',
        'background_position',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function column()
    {
        return $this->belongsTo(PageItemColumn::class, 'page_item_column_id');
    }

    public function backgroundImage()
    {
        return $this->belongsTo(FileInfo::class, 'background_image_id');
    }

    public function details()
    {
        return $this->hasMany(PageItemWidgetDetail::class, 'id');
    }

    /** การตั้งค่าเฉพาะของประเภท slideshowbanner (relation ของแต่ละประเภทลงทะเบียนใน PageWidgetRegistry) */
    public function slideshowBanner()
    {
        return $this->hasOne(PageItemWidgetSlideshowBanner::class, 'id');
    }

    /** การตั้งค่าเฉพาะของประเภท slideshowarticle */
    public function slideshowArticle()
    {
        return $this->hasOne(PageItemWidgetSlideshowArticle::class, 'id');
    }
}
