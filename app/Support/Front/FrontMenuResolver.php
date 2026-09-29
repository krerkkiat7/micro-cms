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
 * กติกา: แถบเมนู (tree) แสดงเฉพาะ status = Y ไม่ถูกลบ และพาเรนต์ต้องแสดงอยู่ทั้งสาย (พาเรนต์ถูกซ่อน = ซ่อนทั้งกิ่ง)
 * เมนูที่ไม่แสดง (status = N หรือพาเรนต์ถูกซ่อน) = แค่ไม่อยู่ในแถบเมนู — ถ้าหน้าปัจจุบันตรงกับเมนูนี้ยังใช้ตั้งค่าส่วนหัว
 * (รูป/หัวเรื่อง) ของเมนูตามปกติ แต่ breadcrumb มีแค่ หน้าแรก > เมนูตัวเอง (ไม่ไล่พาเรนต์) และหาเมนูของหน้าจาก
 * เมนูที่แสดงอยู่ก่อนเสมอ ค่อยใช้เมนูที่ไม่แสดง
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
     * ลิงก์ของเมนู 1 รายการที่แสดงอยู่ (เปิดใช้งาน และพาเรนต์ทุกระดับแสดงอยู่) — null = ไม่มีเมนูนี้/ไม่แสดง/ปลายทางใช้ไม่ได้
     * (ปุ่ม "อ่านทั้งหมด" ที่ชี้เมนูที่ถูกซ่อนจะไม่แสดง)
     *
     * @return array{url: string, target: string, menu_type: string}|null
     */
    public static function linkOf(string $lang, int $menuId): ?array
    {
        $node = self::data($lang)['nodes'][$menuId] ?? null;

        if ($node === null || $node['hidden'] || $node['url'] === null) {
            return null;
        }

        return ['url' => $node['url'], 'target' => $node['target'], 'menu_type' => $node['menu_type']];
    }

    /**
     * เมนูที่ชี้ไปยังเนื้อหานี้ (เมนูแรกตามลำดับการแสดง) พร้อมตั้งค่าส่วนหัว + เส้นทาง (breadcrumb) จากระดับบนสุด
     *
     * เมนูที่ไม่แสดงในแถบเมนู: trail มีแค่ตัวเอง (breadcrumb = หน้าแรก > เมนูตัวเอง)
     *
     * @param  string  $type  FrontMenuType::PAGE | ARTICLE_CATEGORY | ARTICLE_ITEM | CONTACTUS (targetId = 0)
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
            $current = ! $node['hidden'] && $current['parent_id'] !== null ? ($data['nodes'][$current['parent_id']] ?? null) : null;
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

        // โหลดทุกเมนูที่ไม่ถูกลบ (รวมที่ไม่แสดง) — เมนูที่ไม่แสดงไม่อยู่ในแถบเมนูแต่ยังใช้หาส่วนหัวของหน้าได้
        $menus = FrontMenuInfo::query()
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
                'status' => $menu->status,
                'hidden' => false,
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

        // เมนูที่แสดงในแถบเมนู = เปิดใช้งาน และไล่พาเรนต์ขึ้นไปถึงระดับบนสุดได้โดยทุกระดับเปิดใช้งาน
        // (พาเรนต์ถูกซ่อน/ลบ = ซ่อนทั้งกิ่ง) ที่เหลือเป็นเมนูที่ไม่แสดง (hidden) — ไม่อยู่ใน tree
        $visible = [];
        $isVisible = function (int $id, array $seen = []) use (&$isVisible, &$visible, $nodes): bool {
            if (isset($visible[$id])) {
                return $visible[$id];
            }

            if ($nodes[$id]['status'] !== 'Y') {
                return $visible[$id] = false;
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

        foreach ($nodes as $id => $node) {
            $nodes[$id]['hidden'] = ! $isVisible($id);
            unset($nodes[$id]['status']);
        }

        $byParent = [];

        foreach ($nodes as $node) {
            if (! $node['hidden']) {
                $byParent[$node['parent_id'] ?? 0][] = $node['id'];
            }
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
        $menusById = $menus->keyBy('id');
        $targets = [];
        $home = null;
        $walk = function (int $parentId) use (&$walk, $byParent, $menusById, $nodes, &$targets, &$home): void {
            foreach ($byParent[$parentId] ?? [] as $id) {
                $key = self::targetKey($menusById[$id]);

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

        // ปลายทางที่ไม่มีเมนูที่แสดงอยู่ชี้ไป → ใช้เมนูที่ไม่แสดงตัวแรก (ตามลำดับ sort_order) แทน — ไม่ใช้เป็นหน้าแรก
        foreach ($menus as $menu) {
            $key = $nodes[$menu->id]['hidden'] ? self::targetKey($menu) : null;

            if ($key !== null && ! isset($targets[$key])) {
                $targets[$key] = (int) $menu->id;
            }
        }

        return [
            'tree' => $build(0),
            'nodes' => $nodes,
            'targets' => $targets,
            'home' => $home,
        ];
    }

    /**
     * คีย์ปลายทางของเมนู (ใช้หาเมนูของหน้าปัจจุบันใน findFor()) — ติดต่อเรามีหน้าเดียว ใช้ id = 0
     */
    private static function targetKey(FrontMenuInfo $menu): ?string
    {
        return match ($menu->menu_type) {
            FrontMenuType::PAGE => $menu->target_page_item_id ? 'page:'.$menu->target_page_item_id : null,
            FrontMenuType::ARTICLE_CATEGORY => $menu->target_article_category_id ? 'article_category:'.$menu->target_article_category_id : null,
            FrontMenuType::ARTICLE_ITEM => $menu->target_article_item_id ? 'article_item:'.$menu->target_article_item_id : null,
            FrontMenuType::CONTACTUS => 'contactus:0',
            default => null,
        };
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
            FrontMenuType::CONTACTUS => FrontUrl::contactus($lang),
            // ลิงค์ภายนอกที่เป็น path ภายในแต่ไม่ได้ขึ้นต้นด้วยภาษา (เช่น /news) เติมภาษาของหน้าที่เปิดอยู่ให้ (header/footer/aside/ปุ่มอ่านทั้งหมด)
            FrontMenuType::EXTERNAL => FrontUrl::withLang(FrontUrl::safeExternal($menu->url), $lang),
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
