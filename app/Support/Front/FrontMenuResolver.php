<?php

namespace App\Support\Front;

use App\Models\FileInfo;
use App\Models\FrontMenuDetail;
use App\Models\FrontMenuInfo;
use App\Support\FrontMenuType;
use App\Support\Setting;
use Illuminate\Support\Facades\DB;

/**
 * เมนูหน้าบ้าน (front_menu_info/front_menu_detail) ของภาษาหนึ่ง พร้อม URL ปลายทางจริง — ใช้กับ header/aside/footer ของ layout
 * หน้าบ้าน, หาหน้าแรก (is_home) และหา "เมนูของหน้าปัจจุบัน" เพื่อแสดงส่วนหัว (รูป/หัวเรื่อง) + breadcrumb
 *
 * กติกา: แสดงเฉพาะ status = Y ไม่ถูกลบ และพาเรนต์ต้องแสดงอยู่ทั้งสาย (พาเรนต์ถูกซ่อน = ซ่อนทั้งกิ่ง)
 * ชื่อเมนูใช้ภาษาที่ขอ ถ้าว่างใช้ภาษาหลัก; ปลายทางที่ไม่พร้อมใช้ (ถูกปิด/ลบ) = เมนูไม่มีลิงก์ (url = null)
 * ผลลัพธ์ cache ต่อภาษา (FrontCache) — ล้างอัตโนมัติเมื่อบันทึกเมนู/เนื้อหาปลายทาง
 */
final class FrontMenuResolver
{
    /**
     * tree สำหรับแสดงเมนู (ไม่มีตั้งค่าส่วนหัว เพื่อให้ prop ที่ส่งทุกหน้ามีขนาดเล็ก)
     *
     * @return list<array<string, mixed>>
     */
    public static function tree(string $lang): array
    {
        return self::data($lang)['tree'];
    }

    /**
     * URL หน้าแรก (เมนู is_home) — null = ยังไม่ได้กำหนด/ปลายทางใช้ไม่ได้
     */
    public static function homeUrl(string $lang): ?string
    {
        return self::data($lang)['home'];
    }

    /**
     * เมนูที่ชี้ไปยังเนื้อหานี้ (เมนูแรกตามลำดับการแสดง) พร้อมตั้งค่าส่วนหัว + เส้นทาง (breadcrumb) จากระดับบนสุด
     *
     * @param  string  $type  FrontMenuType::PAGE | ARTICLE_CATEGORY | ARTICLE_ITEM
     * @return array<string, mixed>|null
     */
    public static function findFor(string $lang, string $type, int $targetId): ?array
    {
        $data = self::data($lang);
        $menuId = $data['targets']["{$type}:{$targetId}"] ?? null;

        if ($menuId === null) {
            return null;
        }

        $node = $data['nodes'][$menuId];
        $trail = [];
        $current = $node;

        while ($current !== null) {
            array_unshift($trail, ['id' => $current['id'], 'name' => $current['name'], 'url' => $current['url']]);
            $current = $current['parent_id'] !== null ? ($data['nodes'][$current['parent_id']] ?? null) : null;
        }

        return [
            'id' => $node['id'],
            'name' => $node['name'],
            'url' => $node['url'],
            'is_home' => $node['is_home'],
            'header' => $node['header'],
            'trail' => $trail,
        ];
    }

    /**
     * @return array{tree: list<array<string, mixed>>, nodes: array<int, array<string, mixed>>, targets: array<string, int>, home: string|null}
     */
    private static function data(string $lang): array
    {
        // จำไว้ใน request ปัจจุบัน (layout + หัวเรื่องของหน้าเรียกหลายครั้ง) — ไม่ใช้ static เพราะจะค้างข้าม request ใน worker/เทส
        $attributes = request()->attributes;
        $memoKey = "front.menu.{$lang}";

        if (! $attributes->has($memoKey)) {
            $attributes->set($memoKey, FrontCache::remember(
                "menu.{$lang}",
                (int) config('front.cache.shared_ttl', 3600),
                fn () => self::build($lang),
            ));
        }

        return $attributes->get($memoKey);
    }

    /**
     * @return array{tree: list<array<string, mixed>>, nodes: array<int, array<string, mixed>>, targets: array<string, int>, home: string|null}
     */
    private static function build(string $lang): array
    {
        $defaultLang = Setting::defaultLanguage();

        $menus = FrontMenuInfo::query()
            ->where('status', 'Y')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $details = FrontMenuDetail::query()
            ->whereIn('id', $menus->pluck('id'))
            ->whereIn('lang', array_unique([$lang, $defaultLang]))
            ->get()
            ->groupBy('id');

        $slugs = self::targetSlugs($menus, $lang);
        $headerImages = FileInfo::query()
            ->whereIn('id', $menus->where('show_header_image', 'Y')->pluck('header_image_id')->filter())
            ->where('status', 'Y')
            ->pluck('hash_name', 'id');

        $nodes = [];

        foreach ($menus as $menu) {
            $byLang = ($details->get($menu->id) ?? collect())->keyBy('lang');
            $detail = $byLang->get($lang);
            $fallback = $byLang->get($defaultLang);
            $text = fn (string $field) => trim((string) ($detail?->{$field} ?? '')) !== ''
                ? (string) $detail->{$field}
                : (string) ($fallback?->{$field} ?? '');

            [$url, $target] = self::link($menu, $lang, $slugs);
            $imageHash = $menu->show_header_image === 'Y' ? $headerImages->get($menu->header_image_id) : null;

            $nodes[$menu->id] = [
                'id' => (int) $menu->id,
                'parent_id' => $menu->parent_id !== null ? (int) $menu->parent_id : null,
                'name' => $text('name'),
                'menu_type' => $menu->menu_type,
                'url' => $url,
                'target' => $target,
                'is_home' => $menu->is_home === 'Y',
                'header' => [
                    'image_url' => $imageHash ? FrontFile::url($imageHash) : null,
                    'image_aspect_ratio' => $menu->header_image_aspect_ratio,
                    'image_fit' => $menu->header_image_fit,
                    'image_background' => $menu->header_image_background,
                    'title' => $menu->show_title === 'Y' ? $text('title') : '',
                    'title_style' => self::fontStyle($menu, 'title'),
                    'subtitle' => $menu->show_subtitle === 'Y' ? $text('subtitle') : '',
                    'subtitle_style' => self::fontStyle($menu, 'subtitle'),
                    'content_align' => $menu->header_content_align,
                    'use_container' => $menu->use_container === 'Y',
                    'show_breadcrumb' => $menu->show_breadcrumb === 'Y',
                ],
            ];
        }

        // ตัดกิ่งที่พาเรนต์ไม่แสดง (ถูกซ่อน/ลบ) — เหลือเฉพาะโหนดที่ไล่ขึ้นไปถึงระดับบนสุดได้
        $visible = [];
        $isVisible = function (int $id, array $seen = []) use (&$isVisible, &$visible, $nodes): bool {
            if (isset($visible[$id])) {
                return $visible[$id];
            }

            $parentId = $nodes[$id]['parent_id'];

            if ($parentId === null) {
                return $visible[$id] = true;
            }

            if (! isset($nodes[$parentId]) || in_array($parentId, $seen, true)) {
                return $visible[$id] = false;
            }

            return $visible[$id] = $isVisible($parentId, [...$seen, $id]);
        };

        $nodes = array_filter($nodes, fn (array $node) => $isVisible($node['id']));

        $byParent = [];

        foreach ($nodes as $node) {
            $byParent[$node['parent_id'] ?? 0][] = $node['id'];
        }

        $build = function (int $parentId) use (&$build, $byParent, $nodes): array {
            return array_map(fn (int $id) => [
                'id' => $nodes[$id]['id'],
                'name' => $nodes[$id]['name'],
                'menu_type' => $nodes[$id]['menu_type'],
                'url' => $nodes[$id]['url'],
                'target' => $nodes[$id]['target'],
                'children' => $build($id),
            ], $byParent[$parentId] ?? []);
        };

        // ปลายทาง → เมนูแรกตามลำดับการแสดง (เดิน tree แบบ depth-first เพื่อให้ได้ลำดับเดียวกับที่ผู้ใช้เห็น)
        $targets = [];
        $home = null;
        $walk = function (int $parentId) use (&$walk, $byParent, $menus, $nodes, &$targets, &$home): void {
            foreach ($byParent[$parentId] ?? [] as $id) {
                $menu = $menus->firstWhere('id', $id);
                $key = match ($menu->menu_type) {
                    FrontMenuType::PAGE => $menu->target_page_item_id ? 'page:'.$menu->target_page_item_id : null,
                    FrontMenuType::ARTICLE_CATEGORY => $menu->target_article_category_id ? 'article_category:'.$menu->target_article_category_id : null,
                    FrontMenuType::ARTICLE_ITEM => $menu->target_article_item_id ? 'article_item:'.$menu->target_article_item_id : null,
                    default => null,
                };

                if ($key !== null && ! isset($targets[$key])) {
                    $targets[$key] = $id;
                }

                if ($nodes[$id]['is_home'] && $home === null) {
                    $home = $nodes[$id]['url'];
                }

                $walk($id);
            }
        };
        $walk(0);

        return [
            'tree' => $build(0),
            'nodes' => $nodes,
            'targets' => $targets,
            'home' => $home,
        ];
    }

    /**
     * slug (ภาษาที่ขอ) ของปลายทางแต่ละประเภท — เฉพาะปลายทางที่เปิดใช้งานและไม่ถูกลบ (ไม่มีในผลลัพธ์ = ลิงก์ใช้ไม่ได้)
     *
     * @return array{page: array<int, string|null>, article_category: array<int, string|null>, article_item: array<int, string|null>}
     */
    private static function targetSlugs($menus, string $lang): array
    {
        $load = function (string $infoTable, string $detailTable, array $ids) use ($lang): array {
            if ($ids === []) {
                return [];
            }

            return DB::table($infoTable)
                ->leftJoin($detailTable.' as d', function ($join) use ($infoTable, $lang) {
                    $join->on('d.id', '=', $infoTable.'.id')->where('d.lang', $lang);
                })
                ->whereIn($infoTable.'.id', $ids)
                ->where($infoTable.'.status', 'Y')
                ->whereNull($infoTable.'.deleted_at')
                ->pluck('d.slug', $infoTable.'.id')
                ->all();
        };

        return [
            'page' => $load('page_item_info', 'page_item_detail', $menus->pluck('target_page_item_id')->filter()->unique()->values()->all()),
            'article_category' => $load('article_category_info', 'article_category_detail', $menus->pluck('target_article_category_id')->filter()->unique()->values()->all()),
            'article_item' => $load('article_item_info', 'article_item_detail', $menus->pluck('target_article_item_id')->filter()->unique()->values()->all()),
        ];
    }

    /**
     * @param  array{page: array<int, string|null>, article_category: array<int, string|null>, article_item: array<int, string|null>}  $slugs
     * @return array{0: string|null, 1: string}
     */
    private static function link(FrontMenuInfo $menu, string $lang, array $slugs): array
    {
        $target = $menu->link_target === '_blank' ? '_blank' : '_self';

        $url = match ($menu->menu_type) {
            FrontMenuType::PAGE => $menu->target_page_item_id && array_key_exists($menu->target_page_item_id, $slugs['page'])
                ? FrontUrl::page($lang, (int) $menu->target_page_item_id, $slugs['page'][$menu->target_page_item_id])
                : null,
            FrontMenuType::ARTICLE_CATEGORY => $menu->target_article_category_id && array_key_exists($menu->target_article_category_id, $slugs['article_category'])
                ? FrontUrl::articleCategory($lang, (int) $menu->target_article_category_id, $slugs['article_category'][$menu->target_article_category_id])
                : null,
            FrontMenuType::ARTICLE_ITEM => $menu->target_article_item_id && array_key_exists($menu->target_article_item_id, $slugs['article_item'])
                ? FrontUrl::articleItem($lang, (int) $menu->target_article_item_id, $slugs['article_item'][$menu->target_article_item_id])
                : null,
            FrontMenuType::EXTERNAL => FrontUrl::safeExternal($menu->url),
            default => null,
        };

        return [$url, $target];
    }

    /**
     * @return array{font_size: int, font_family: string, color: string, bold: bool}
     */
    private static function fontStyle(FrontMenuInfo $menu, string $part): array
    {
        return [
            'font_size' => (int) $menu->{"{$part}_font_size"},
            'font_family' => (string) $menu->{"{$part}_font_family"},
            'color' => (string) $menu->{"{$part}_color"},
            'bold' => $menu->{"{$part}_bold"} === 'Y',
        ];
    }
}
