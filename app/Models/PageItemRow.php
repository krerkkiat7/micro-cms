<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use App\Models\Concerns\HasPageTextStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * แถว (row) ในโครงสร้างหน้าเพจ — ข้อมูลร่วม (พื้นหลัง/การแสดงผล) ไม่แยกภาษา
 */
class PageItemRow extends Model
{
    use FlushesFrontCache, HasPageTextStyle;
    use SoftDeletes;

    protected $table = 'page_item_row';

    protected $fillable = [
        'page_item_info_id',
        'sort_order',
        'show_title',
        'use_container',
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

    public function page()
    {
        return $this->belongsTo(PageItemInfo::class, 'page_item_info_id');
    }

    public function backgroundImage()
    {
        return $this->belongsTo(FileInfo::class, 'background_image_id');
    }

    public function details()
    {
        return $this->hasMany(PageItemRowDetail::class, 'id');
    }

    public function columns()
    {
        return $this->hasMany(PageItemColumn::class)->orderBy('sort_order')->orderBy('id');
    }
}
