<?php

namespace App\Models;

use App\Support\ClientIp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * log การกระทำในระบบหลังบ้าน — 1 แถวต่อ 1 การกระทำบนข้อมูล
 *
 * module_code  : รหัสโมดูล เช่น system.user, system.usergroup.rights
 * action_type  : create / view / update / delete (หรือค่าอื่นที่โมดูลกำหนด)
 * value_string : ชื่อข้อมูลที่ถูกกระทำ (อ่านง่าย)
 * ref_id       : id ของข้อมูลที่ถูกกระทำ
 */
class LogBackAction extends Model
{
    use SoftDeletes;

    protected $table = 'log_back_action';

    protected $fillable = [
        'user_id',
        'module_code',
        'action_type',
        'value_string',
        'ref_id',
        'action_date',
        'remote_ip',
        'geo_ip',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'action_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->action_date ??= now()->toDateString();
        });
    }

    /**
     * ผู้ที่กระทำการนี้ (sys_user)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * บันทึกการกระทำ 1 แถว จากผู้ใช้ที่ล็อกอินอยู่ปัจจุบัน
     *
     * @param  string  $moduleCode  รหัสโมดูล เช่น "system.user"
     * @param  string  $actionType  เช่น "create" / "view" / "update" / "delete"
     * @param  string|null  $valueString  ชื่อข้อมูลที่ถูกกระทำ
     * @param  int|null  $refId  id ของข้อมูลที่ถูกกระทำ
     */
    public static function record(string $moduleCode, string $actionType, ?string $valueString = null, ?int $refId = null): self
    {
        return self::create([
            'user_id' => Auth::id(),
            'module_code' => $moduleCode,
            'action_type' => $actionType,
            'value_string' => $valueString !== null && $valueString !== '' ? Str::limit($valueString, 500, '') : null,
            'ref_id' => $refId,
            'remote_ip' => ClientIp::from(),
            'created_by' => Auth::id(),
        ]);
    }
}
