<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Support\Front\FrontLayoutData;
use App\Support\Front\FrontMenuResolver;
use App\Support\Front\FrontUrl;
use App\Support\Setting;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ฐานของ controller หน้าบ้าน — ส่ง prop `front` (ข้อมูล layout จาก template ที่เปิดใช้งาน + เมนู + ตั้งค่าไซต์) และ `seo` ให้ทุกหน้า
 * พร้อมตัวช่วยสร้างส่วนหัวของหน้า (รูป/หัวเรื่องตามเมนูที่ชี้มาที่หน้านี้) + breadcrumb
 */
abstract class FrontController extends Controller
{
    /**
     * @param  array<string, mixed>  $props
     * @param  array<string, mixed>  $seo  ผลจาก SeoMeta::make()
     */
    protected function render(string $component, string $lang, array $props, array $seo): Response
    {
        return Inertia::render($component, [
            ...$props,
            'front' => FrontLayoutData::forLanguage($lang),
            'seo' => $seo,
        ]);
    }

    /**
     * ส่วนหัวของหน้าภายใน (รูป + หัวเรื่อง/หัวเรื่องรองตามเมนูที่ตรวจพบ) + breadcrumb + เมนูที่ active
     *
     * @param  array<string, mixed>|null  $menu  ผลจาก FrontMenuResolver::findFor()
     * @param  list<array{name: string, url: string|null}>  $extra  ลำดับต่อท้ายเส้นทางของเมนู (เช่น หมวดหมู่ → บทความ) ตัวสุดท้าย = หน้าปัจจุบัน
     * @return array{hero: array<string, mixed>|null, breadcrumb: list<array{name: string, url: string|null}>, showBreadcrumb: bool, activeMenuIds: list<int>}
     */
    protected function pageHeader(string $lang, ?array $menu, array $extra = []): array
    {
        $homeUrl = FrontMenuResolver::homeUrl($lang) ?? FrontUrl::home($lang);
        $crumbs = [['name' => (string) trans('front.home', [], $lang), 'url' => $homeUrl]];

        foreach ($menu['trail'] ?? [] as $item) {
            // เมนูหน้าแรกเองไม่ต้องซ้ำกับ "หน้าแรก" ตัวแรก
            if ($item['url'] !== null && $item['url'] === $homeUrl) {
                continue;
            }

            $crumbs[] = ['name' => $item['name'], 'url' => $item['url']];
        }

        foreach ($extra as $item) {
            $last = end($crumbs);

            if ($last && $last['name'] === $item['name']) {
                array_pop($crumbs); // ชื่อซ้ำกับรายการก่อนหน้า (เช่น เมนูชี้บทความนี้เอง) ใช้ตัวที่ส่งมาแทน
            }

            $crumbs[] = $item;
        }

        return [
            'hero' => $menu['header'] ?? null,
            'breadcrumb' => array_values($crumbs),
            'showBreadcrumb' => ($menu['header']['show_breadcrumb'] ?? true) && count($crumbs) > 1,
            'activeMenuIds' => array_map(fn ($item) => $item['id'], $menu['trail'] ?? []),
        ];
    }

    /**
     * URL ของทุกภาษาที่เปิดใช้ (hreflang / ตัวสลับภาษา) — slug ตามภาษานั้น ๆ
     *
     * @param  callable(string $lang): string  $urlFor
     * @return array<string, string>
     */
    protected function alternates(callable $urlFor): array
    {
        $urls = [];

        foreach (Setting::selectedLanguages() as $code) {
            $urls[$code] = $urlFor($code);
        }

        return $urls;
    }

    /**
     * breadcrumb สำหรับ JSON-LD — รายการสุดท้าย (หน้าปัจจุบัน ที่หน้าจอไม่ใส่ลิงก์) ใส่ URL ของหน้านี้
     *
     * @param  list<array{name: string, url: string|null}>  $crumbs
     * @return list<array{name: string, url: string|null}>
     */
    protected function crumbsWithCurrent(array $crumbs, string $url): array
    {
        $last = count($crumbs) - 1;
        $crumbs[$last]['url'] ??= $url;

        return $crumbs;
    }
}
