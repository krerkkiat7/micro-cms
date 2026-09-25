<?php

namespace App\Models;

use App\Support\ClientIp;
use App\Support\Front\FrontAuth;
use App\Support\UserAgentParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

use function Illuminate\Support\defer;

/**
 * log การเข้าชมหน้าบ้าน (1 request = 1 แถว) — โครงเดียวกับ LogBackAccess
 *
 * ต่างจากหลังบ้าน: ผู้เข้าชมไม่ต้อง login (user_id = null ถ้าไม่ได้ login) และ insert "หลังส่ง response แล้ว"
 * (defer()) เพื่อไม่ให้ผู้เข้าชมรอการเขียน DB — token สร้างไว้ก่อนเพื่อส่งให้หน้าจอใช้ ping ได้ทันที
 * last_visited ครั้งแรก = created_at แล้วถูก bump ผ่าน keep-alive ping (อ้างอิงด้วย token + session_id)
 */
class LogFrontAccess extends Model
{
    use SoftDeletes;

    protected $table = 'log_front_access';

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

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->token ??= (string) Str::ulid();
            $log->action_date ??= now()->toDateString();
            $log->last_visited ??= now();
        });
    }

    /**
     * ผู้ใช้งานที่เป็นเจ้าของ log แถวนี้ (sys_user) — null = ผู้เยี่ยมชมทั่วไป
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * บันทึกการเข้าชมหน้าบ้าน 1 แถวจาก request ปัจจุบัน — เรียกจาก controller หน้าบ้านตอน render หน้า
     *
     * ข้อมูลทั้งหมดอ่านจาก request ตอนนี้ แต่ insert จริงหลังส่ง response (defer) — คืน token ที่จะใช้
     * และเก็บลง request attribute `access_log_token` ให้ HandleInertiaRequests แชร์เป็น prop `accessLog.token`
     * (ชื่อเดียวกับหลังบ้าน — composable useAccessHeartbeat ใช้ร่วมกันได้)
     *
     * @param  string  $title  ชื่อหน้าที่แสดงในประวัติ เช่น ชื่อบทความ
     */
    public static function record(string $title): ?string
    {
        $request = request();

        if (! $request->isMethod('GET')) {
            return null;
        }

        $ua = (string) $request->userAgent();
        $agent = UserAgentParser::parse($ua);
        $token = (string) Str::ulid();
        $userId = FrontAuth::id(); // ผู้ใช้หน้าบ้านเท่านั้น — login หลังบ้านไม่นับ (guard แยกกัน)
        $now = now();

        $attributes = [
            'user_id' => $userId,
            'token' => $token,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'uri_string' => $request->getRequestUri(),
            'title_name' => self::clip($title, 255),
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
            'action_date' => $now->toDateString(),
            'last_visited' => $now,
            'created_by' => $userId,
        ];

        // เวลาเข้าชมจริง = ตอนนี้ (ไม่ใช่ตอน insert หลังส่ง response) — defer() = ทำหลังส่ง response เฉพาะ request นี้
        // (ไม่ใช้ app()->terminating() เพราะ callback สะสมข้าม request ใน process ที่รันยาว เช่น Octane/เทส)
        defer(function () use ($attributes, $now) {
            $log = new static($attributes);
            $log->created_at = $now;
            $log->updated_at = $now;
            $log->save();
        });

        $request->attributes->set('access_log_token', $token);

        return $token;
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
