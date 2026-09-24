<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;

/**
 * ตั้งค่าโซน main body — พื้นหลังของพื้นที่เนื้อหา ของ template — 1 template = 1 แถว, PK = sys_template_id
 * คอลัมน์ทั้งหมดประกาศไว้ที่ App\Support\Template\TemplateZone::fields('body') (ค่าที่ fill มาจาก validation ของทะเบียนนั้นเท่านั้น)
 */
class SysTemplateBody extends Model
{
    use FlushesFrontCache;

    protected $table = 'sys_template_body';

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
