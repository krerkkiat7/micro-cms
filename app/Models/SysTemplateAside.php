<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ตั้งค่าโซน aside (เมนูข้าง) ของ template — 1 template = 1 แถว, PK = sys_template_id
 * คอลัมน์ทั้งหมดประกาศไว้ที่ App\Support\Template\TemplateZone::fields('aside') (ค่าที่ fill มาจาก validation ของทะเบียนนั้นเท่านั้น)
 */
class SysTemplateAside extends Model
{
    protected $table = 'sys_template_aside';

    protected $primaryKey = 'sys_template_id';

    public $incrementing = false;

    protected $guarded = [];

    public function template()
    {
        return $this->belongsTo(SysTemplate::class, 'sys_template_id');
    }

    public function backgroundImage()
    {
        return $this->belongsTo(FileInfo::class, 'background_image_id');
    }
}
