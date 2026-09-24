<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;

/**
 * ตั้งค่าโซน header — แถบบน / แถบหลัก / แถวเมนู ของ template — 1 template = 1 แถว, PK = sys_template_id
 * คอลัมน์ทั้งหมดประกาศไว้ที่ App\Support\Template\TemplateZone::fields('header') (ค่าที่ fill มาจาก validation ของทะเบียนนั้นเท่านั้น)
 */
class SysTemplateHeader extends Model
{
    use FlushesFrontCache;

    protected $table = 'sys_template_header';

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
