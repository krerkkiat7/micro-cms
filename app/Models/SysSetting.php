<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ตารางเก็บการตั้งค่าระบบ/เว็บไซต์ แยกกลุ่มด้วยคอลัมน์ group
 *
 * primary key เป็นแบบ composite (group, name) — Eloquent ไม่รองรับ composite key เต็มรูปแบบ
 * ให้ค้นด้วย SysSetting::where('group', ...)->where('name', ...) แทนการใช้ find()
 */
class SysSetting extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'sys_setting';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['group', 'name', 'value', 'created_by', 'updated_by'];
}
