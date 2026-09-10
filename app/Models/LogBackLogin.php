<?php

namespace App\Models;

use App\Support\ClientIp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * log การเข้า/ออกระบบหลังบ้าน — 1 แถวต่อ 1 เหตุการณ์
 *
 * log_type: login | logout
 * result:   success | fail | block
 * user_id เก็บเมื่อ login สำเร็จ หรือ logout (fail/block = null)
 */
class LogBackLogin extends Model
{
    use SoftDeletes;

    protected $table = 'log_back_login';

    protected $fillable = [
        'user_id',
        'log_type',
        'username',
        'result',
        'note',
        'remote_ip',
        'action_date',
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
     * ผู้ใช้งานที่เกี่ยวข้องกับ log แถวนี้ (sys_user) — null ถ้า login ไม่สำเร็จ
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * login สำเร็จ
     */
    public static function loginSuccess(User $user): self
    {
        return self::write('login', 'success', $user->email, $user->id, 'เข้าสู่ระบบสำเร็จ');
    }

    /**
     * login ไม่สำเร็จ (รหัสผ่านผิด / ไม่พบบัญชี)
     */
    public static function loginFailed(string $username, string $note): self
    {
        return self::write('login', 'fail', $username, null, $note);
    }

    /**
     * login ถูกปฏิเสธ (บัญชีถูกระงับ / ถูก throttle)
     */
    public static function loginBlocked(string $username, string $note): self
    {
        return self::write('login', 'block', $username, null, $note);
    }

    /**
     * ออกจากระบบ
     */
    public static function logout(?int $userId, ?string $username): self
    {
        return self::write('logout', 'success', $username, $userId, 'ออกจากระบบ');
    }

    private static function write(string $logType, string $result, ?string $username, ?int $userId, string $note): self
    {
        return self::create([
            'user_id' => $userId,
            'log_type' => $logType,
            'username' => $username !== null && $username !== '' ? Str::limit($username, 150, '') : null,
            'result' => $result,
            'note' => Str::limit($note, 1000, ''),
            'remote_ip' => ClientIp::from(),
            'created_by' => $userId,
        ]);
    }
}
