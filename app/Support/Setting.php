<?php

namespace App\Support;

use App\Models\SysSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * อ่านค่าตั้งค่าระบบ (sys_setting) แบบ cache-first — โหลดทั้งกลุ่มแล้วแคชไว้ (invalidate ตอนบันทึก)
 * ตามที่ CLAUDE.md ระบุไว้ว่าจะทำ (ดู docs/PRD-system.md)
 *
 * GROUPS คือทะเบียนกลุ่มตั้งค่าทั้งหมดที่มี — ใช้ทั้งวนสร้างฟอร์ม/ปุ่มล้างแคช และ "ล้างแคชทั้งหมด"
 * เพิ่มกลุ่มตั้งค่าใหม่ในอนาคต ให้เพิ่มชื่อกลุ่มในนี้ด้วย
 */
class Setting
{
    public const GROUPS = ['site', 'smtp', 'recaptcha', 'login_back'];

    /**
     * ค่าตั้งค่าทั้งกลุ่ม เป็น array แบบ name => value — แคชไว้ 1 วัน
     * คืน [] เงียบ ๆ ถ้าตาราง sys_setting ยังไม่มี (เช่น ก่อนรัน migrate ครั้งแรก)
     */
    public static function group(string $group): array
    {
        return Cache::remember("sys_setting.{$group}", now()->addDay(), function () use ($group) {
            if (! Schema::hasTable('sys_setting')) {
                return [];
            }

            return SysSetting::query()
                ->where('group', $group)
                ->pluck('value', 'name')
                ->all();
        });
    }

    /**
     * ค่าตั้งค่า 1 ตัวในกลุ่ม — คืน $default ถ้าไม่มีค่า (ไม่เคยตั้ง หรือค่าว่าง)
     */
    public static function get(string $group, string $name, mixed $default = null): mixed
    {
        $value = self::group($group)[$name] ?? null;

        return $value !== null && $value !== '' ? $value : $default;
    }

    /**
     * ล้างแคชของกลุ่มเดียว — เรียกทันทีหลังบันทึกค่ากลุ่มนั้น
     */
    public static function forget(string $group): void
    {
        Cache::forget("sys_setting.{$group}");
    }

    /**
     * ล้างแคชของทุกกลุ่มที่ลงทะเบียนไว้ (GROUPS)
     */
    public static function forgetAll(): void
    {
        foreach (self::GROUPS as $group) {
            self::forget($group);
        }
    }

    /**
     * ชื่อไซต์สำหรับแสดงผล (เช่น <title>) — ใช้ค่าจากการตั้งค่าก่อน ไม่มีค่อย fallback ไปที่ .env (APP_NAME)
     */
    public static function siteName(): string
    {
        return self::get('site', 'site_name') ?? config('app.name', 'Laravel');
    }
}
