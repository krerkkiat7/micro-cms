<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Template หน้าบ้าน (sys_template) — ข้อมูลทั่วไป + Custom CSS/JS + หน้า Loading; ตั้งค่าแต่ละโซนอยู่ตาราง 1:1
 * (header / body / footer / aside, ดู App\Support\Template\TemplateZone) เปิดใช้งาน (status = Y) ได้ครั้งละ 1 รายการ
 */
class SysTemplate extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'sys_template';

    protected $fillable = [
        'name',
        'preset',
        'custom_css_status',
        'custom_css',
        'custom_js_status',
        'custom_js',
        'loading_status',
        'loading_show_logo',
        'loading_type',
        'loading_spinner',
        'loading_color',
        'loading_background_color',
        'loading_image_id',
        'layout_updated_at',
        'layout_updated_by',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'layout_updated_at' => 'datetime',
        ];
    }

    public function header()
    {
        return $this->hasOne(SysTemplateHeader::class, 'sys_template_id');
    }

    public function body()
    {
        return $this->hasOne(SysTemplateBody::class, 'sys_template_id');
    }

    public function footer()
    {
        return $this->hasOne(SysTemplateFooter::class, 'sys_template_id');
    }

    public function aside()
    {
        return $this->hasOne(SysTemplateAside::class, 'sys_template_id');
    }

    public function loadingImage()
    {
        return $this->belongsTo(FileInfo::class, 'loading_image_id');
    }
}
