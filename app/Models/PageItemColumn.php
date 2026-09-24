<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use App\Models\Concerns\HasPageTextStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * คอลัมน์ (column) ภายในแถว — ระบุความกว้างแบบ grid 12 ผ่าน column_size (1 - 12)
 */
class PageItemColumn extends Model
{
    use FlushesFrontCache, HasPageTextStyle;
    use SoftDeletes;

    protected $table = 'page_item_column';

    protected $fillable = [
        'page_item_row_id',
        'sort_order',
        'show_title',
        'column_size',
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

    public function row()
    {
        return $this->belongsTo(PageItemRow::class, 'page_item_row_id');
    }

    public function backgroundImage()
    {
        return $this->belongsTo(FileInfo::class, 'background_image_id');
    }

    public function details()
    {
        return $this->hasMany(PageItemColumnDetail::class, 'id');
    }

    public function widgets()
    {
        return $this->hasMany(PageItemWidget::class)->orderBy('sort_order')->orderBy('id');
    }
}
