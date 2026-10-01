<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use App\Support\Setting;
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

    /**
     * ค่าลับ (Setting::SECRETS เช่น smtp.password, turnstile.key_secret) เข้ารหัสก่อนบันทึกลง DB ทุกครั้ง — ทุกจุดที่บันทึกผ่าน model ได้ผลเหมือนกัน
     */
    protected static function booted(): void
    {
        static::saving(function (SysSetting $setting) {
            if (Setting::isSecret($setting->group, $setting->name) && filled($setting->value)) {
                $setting->value = Setting::encryptSecret((string) $setting->value);
            }
        });
    }
}
