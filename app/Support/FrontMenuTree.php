<?php

namespace App\Support;

use App\Models\FrontMenuDetail;
use App\Models\FrontMenuInfo;

/**
 * tree ของเมนูหน้าบ้านที่เปิดใช้งาน (front_menu_info.status = Y) เฉพาะชื่อเมนูของภาษาที่ระบุ — ใช้แสดงตัวอย่าง
 * (เช่น preview ในหน้าโครงสร้าง template) จึงไม่ส่ง URL ปลายทาง; เมนูที่พาเรนต์ถูกปิด/ลบจะไม่แสดงทั้งกิ่ง
 */
final class FrontMenuTree
{
    /**
     * @return list<array{id: int, name: string, children: list<array<string, mixed>>}>
     */
    public static function forLanguage(?string $lang = null): array
    {
        $lang ??= Setting::defaultLanguage();

        $menus = FrontMenuInfo::query()
            ->where('status', 'Y')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'parent_id']);

        $names = FrontMenuDetail::query()
            ->whereIn('id', $menus->pluck('id'))
            ->where('lang', $lang)
            ->pluck('name', 'id');

        $byParent = $menus->groupBy(fn (FrontMenuInfo $menu) => $menu->parent_id ?? 0);

        $build = function (int $parentId) use (&$build, $byParent, $names): array {
            return ($byParent->get($parentId) ?? collect())
                ->map(fn (FrontMenuInfo $menu) => [
                    'id' => $menu->id,
                    'name' => (string) ($names->get($menu->id) ?? ''),
                    'children' => $build($menu->id),
                ])
                ->values()
                ->all();
        };

        return $build(0);
    }
}
