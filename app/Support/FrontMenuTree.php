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
     * @return list<array{id: int, name: string, menu_type: string, children: list<array<string, mixed>>}>
     */
    public static function forLanguage(?string $lang = null): array
    {
        $lang ??= Setting::defaultLanguage();

        $menus = FrontMenuInfo::query()
            ->where('status', 'Y')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'menu_type']);

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
                    'menu_type' => $menu->menu_type,
                    'children' => $build($menu->id),
                ])
                ->values()
                ->all();
        };

        return $build(0);
    }

    /**
     * tree ของเมนูทั้งหมดที่ยังไม่ถูกลบ (รวมเมนูที่ปิดใช้งาน — ส่ง status ไปให้หน้าจอแสดงป้ายกำกับ) ชื่อภาษาหลัก
     * สำหรับ checkbox tree ในหลังบ้าน (เช่น เลือกเมนูที่แสดง popup) — `selectable` = ประเภทที่อยู่ใน $selectableTypes
     * เมนูที่พาเรนต์ถูกลบไปแล้วขึ้นเป็นระดับบนสุดแทน (ไม่หายทั้งกิ่งเหมือน forLanguage())
     *
     * @param  list<string>  $selectableTypes
     * @return list<array{id: int, name: string, menu_type: string, status: string, selectable: bool, children: list<array<string, mixed>>}>
     */
    public static function adminCheckTree(array $selectableTypes): array
    {
        $menus = FrontMenuInfo::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'menu_type', 'status']);

        $names = FrontMenuDetail::query()
            ->whereIn('id', $menus->pluck('id'))
            ->where('lang', Setting::defaultLanguage())
            ->pluck('name', 'id');

        $ids = $menus->pluck('id')->flip();
        $byParent = $menus->groupBy(fn (FrontMenuInfo $menu) => $menu->parent_id !== null && $ids->has($menu->parent_id) ? $menu->parent_id : 0);

        $build = function (int $parentId) use (&$build, $byParent, $names, $selectableTypes): array {
            return ($byParent->get($parentId) ?? collect())
                ->map(fn (FrontMenuInfo $menu) => [
                    'id' => $menu->id,
                    'name' => (string) ($names->get($menu->id) ?? ''),
                    'menu_type' => $menu->menu_type,
                    'status' => $menu->status,
                    'selectable' => in_array($menu->menu_type, $selectableTypes, true),
                    'children' => $build($menu->id),
                ])
                ->values()
                ->all();
        };

        return $build(0);
    }

    /**
     * เมนูที่เปิดใช้งานทั้งหมดเรียงตาม tree แบบแบน (ชื่อภาษาหลัก + ระดับความลึก) สำหรับ dropdown เลือกเมนูเป็นลิงก์ปลายทาง —
     * `selectable` = ประเภทที่มีลิงก์ของตัวเอง (FrontMenuType::LINKABLE) เมนูหัวข้อ/ไม่กำหนดแสดงไว้ให้เห็นโครงแต่เลือกไม่ได้
     *
     * @return list<array{id: int, name: string, depth: int, menu_type: string, selectable: bool}>
     */
    public static function pickerOptions(): array
    {
        $flat = [];
        $walk = function (array $nodes, int $depth) use (&$walk, &$flat): void {
            foreach ($nodes as $node) {
                $flat[] = [
                    'id' => (int) $node['id'],
                    'name' => $node['name'],
                    'depth' => $depth,
                    'menu_type' => $node['menu_type'],
                    'selectable' => in_array($node['menu_type'], FrontMenuType::LINKABLE, true),
                ];
                $walk($node['children'], $depth + 1);
            }
        };
        $walk(self::forLanguage(), 0);

        return $flat;
    }
}
