<?php

namespace App\Models;

use App\Support\ClientIp;
use App\Support\UserAgentParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * log การเข้าชม/เข้าถึงหน้าในระบบหลังบ้าน (1 request = 1 แถว)
 *
 * last_visited ครั้งแรก = created_at แล้วถูก bump ผ่าน keep-alive ping (อ้างอิงด้วย token)
 * เพื่อให้รู้ว่าผู้ใช้อยู่ที่หน้านั้นนานเท่าไร
 */
class LogBackAccess extends Model
{
    use SoftDeletes;

    protected $table = 'log_back_access';

    protected $fillable = [
        'user_id',
        'token',
        'session_id',
        'uri_string',
        'title_name',
        'remote_ip',
        'geo_ip',
        'geo_ip_city',
        'browser',
        'browser_version',
        'mobile',
        'device_type',
        'robot',
        'platform',
        'referrer',
        'agent',
        'accept_lang',
        'accept_charset',
        'action_date',
        'last_visited',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'action_date' => 'date',
            'last_visited' => 'datetime',
        ];
    }

    /**
     * เติมค่าเริ่มต้นตอนสร้างแถว — token (ULID สาธารณะ), วันที่เข้าถึง, และ last_visited แรก
     */
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->token ??= (string) Str::ulid();
            $log->action_date ??= now()->toDateString();
            $log->last_visited ??= now();
        });
    }

    /**
     * ผู้ใช้งานที่เป็นเจ้าของ log แถวนี้ (sys_user)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * บันทึกการเข้าถึงหน้าจอหลังบ้าน 1 แถว จาก request ปัจจุบัน
     *
     * เรียกจาก controller ตอน render หน้าจอ (หลังผ่านเช็กสิทธิ์) — ไม่เรียกตอนค้นหา/กรอง
     * เก็บ token ไว้ใน request attribute ให้ HandleInertiaRequests แชร์ต่อไป frontend (keep-alive)
     *
     * @param  string  $title  ชื่อหน้าที่แสดงในประวัติ เช่น "จัดการผู้ใช้งาน"
     */
    public static function record(string $title): ?self
    {
        $request = request();

        if (! $request->isMethod('GET') || ! Auth::check()) {
            return null;
        }

        $ua = (string) $request->userAgent();
        $agent = UserAgentParser::parse($ua);

        $log = static::create([
            'user_id' => Auth::id(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'uri_string' => $request->getRequestUri(),
            'title_name' => $title,
            'remote_ip' => ClientIp::from($request),
            'browser' => $agent['browser'],
            'browser_version' => $agent['browser_version'],
            'platform' => $agent['platform'],
            'device_type' => $agent['device_type'],
            'mobile' => $agent['mobile'],
            'robot' => $agent['robot'],
            'referrer' => self::clip($request->headers->get('referer'), 250),
            'agent' => self::clip($ua, 255),
            'accept_lang' => self::clip($request->headers->get('accept-language'), 50),
            'accept_charset' => self::clip($request->headers->get('accept-charset'), 50),
            'created_by' => Auth::id(),
        ]);

        $request->attributes->set('access_log_token', $log->token);

        return $log;
    }

    /**
     * ตัดสตริงให้พอดีคอลัมน์ — ค่าว่าง/null คืน null
     */
    private static function clip(?string $value, int $limit): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, $limit, '');
    }
}
