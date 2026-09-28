<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * ข้อมูลส่วน "ข้อมูลระบบ" ของหน้าแก้ไขหลังบ้าน — วันเวลาสร้าง/ปรับปรุงล่าสุด + ชื่อผู้กระทำจากคอลัมน์ audit (`*_by` = sys_user.id ไม่มี FK)
 * ชื่อผู้ใช้ที่ถูกลบ (soft delete) ยังแสดงได้ พร้อมต่อท้าย "(ถูกลบแล้ว)"; ไม่มี id / ไม่พบแถว = null (หน้าจอแสดง "-")
 * หน้าจอแสดงผลด้วย `Components/Admin/SystemInfoCard.vue`
 */
class SystemInfo
{
    /**
     * วันเวลาสร้าง/ปรับปรุงล่าสุด + ผู้กระทำ ของแถวข้อมูล — `$extra` = คู่คอลัมน์อื่นเพิ่มเติม เช่น ['layout_updated' => ['layout_updated_at', 'layout_updated_by']]
     *
     * `$names` = ผล names() ที่ดึงไว้ล่วงหน้า (หน้าที่แสดงหลายแถวพร้อมกัน เช่น tree เมนู — กัน query ต่อแถว)
     *
     * @param  array<string, array{0: string, 1: string}>  $extra
     * @param  array<int, string>|null  $names
     * @return array<string, string|null>
     */
    public static function audit(Model $model, array $extra = [], ?array $names = null): array
    {
        $pairs = ['created' => ['created_at', 'created_by'], 'updated' => ['updated_at', 'updated_by'], ...$extra];
        $names ??= self::names(array_map(fn (array $pair) => $model->getAttribute($pair[1]), $pairs));
        $info = [];

        foreach ($pairs as $key => [$atColumn, $byColumn]) {
            $at = $model->getAttribute($atColumn);
            $info["{$key}_at"] = $at ? Carbon::parse($at)->format('Y-m-d H:i:s') : null;
            $info["{$key}_by"] = $names[(int) $model->getAttribute($byColumn)] ?? null;
        }

        return $info;
    }

    /**
     * ชื่อเต็มของผู้ใช้ตาม id (รวมผู้ใช้ที่ถูกลบ ต่อท้าย "(ถูกลบแล้ว)")
     *
     * @param  array<mixed>  $ids
     * @return array<int, string>
     */
    public static function names(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return [];
        }

        return User::withTrashed()
            ->whereIn('id', $ids)
            ->get(['id', 'titlename', 'firstname', 'lastname', 'deleted_at'])
            ->mapWithKeys(fn (User $user) => [
                (int) $user->id => trim($user->name).($user->trashed() ? ' (ถูกลบแล้ว)' : ''),
            ])
            ->all();
    }
}
