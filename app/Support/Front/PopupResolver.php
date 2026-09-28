<?php

namespace App\Support\Front;

use App\Models\PopupItemInfo;
use App\Models\PopupItemPart;
use App\Support\PopupSetting;
use App\Support\Setting;

/**
 * เลือก popup ที่แสดงที่หน้าบ้าน (ดู docs/PRD-popup.md §3) — status = Y, ไม่ถูกลบ, menu_mode ไม่ใช่ none และอยู่ในช่วงเผยแพร่
 * (publish_date <= now และ publish_down ว่างหรือ > now) เรียงตามตั้งค่าโมดูล (PopupSetting) — รายการแรกอยู่บนสุดเมื่อซ้อนกัน
 *
 * cache ต่อภาษา (สั้น เพราะขึ้นกับเวลา — front.cache.popup_ttl) แล้วค่อยกรองตามเมนูของหน้าปัจจุบันใน forPage()
 * ข้อมูลตัวอย่าง (is_temp = Y) แสดงตามปกติ
 */
final class PopupResolver
{
    /**
     * popup ที่แสดงในหน้าที่ผูกกับเมนู $menuId (null = หน้าที่ไม่มีเมนู — แสดงเฉพาะ popup แบบทุกหน้า)
     *
     * @return list<array<string, mixed>>
     */
    public static function forPage(string $lang, ?int $menuId): array
    {
        return array_values(array_map(
            function (array $popup) {
                unset($popup['menu_mode'], $popup['menu_ids']);

                return $popup;
            },
            array_filter(self::forLanguage($lang), fn (array $popup) => $popup['menu_mode'] === 'all'
                || ($menuId !== null && in_array($menuId, $popup['menu_ids'], true))),
        ));
    }

    /**
     * popup ที่เผยแพร่อยู่ทั้งหมดของภาษานี้ (ก่อนกรองตามหน้า)
     *
     * @return list<array<string, mixed>>
     */
    public static function forLanguage(string $lang): array
    {
        return FrontCache::remember("popup.{$lang}", (int) config('front.cache.popup_ttl', 60), function () use ($lang) {
            [$column, $direction] = PopupSetting::orderBy();
            $now = now();

            return PopupItemInfo::query()
                ->where('status', 'Y')
                ->where('menu_mode', '!=', 'none')
                ->where('publish_date', '<=', $now)
                ->where(fn ($query) => $query->whereNull('publish_down')->orWhere('publish_down', '>', $now))
                ->with(['parts' => fn ($query) => $query->where('status', 'Y'), 'parts.image', 'parts.details', 'menus:id'])
                ->orderBy($column, $direction)
                ->orderByDesc('id')
                ->get()
                ->map(fn (PopupItemInfo $popup) => self::toArray($popup, $lang))
                ->filter(fn (array $popup) => $popup['parts'] !== [])
                ->values()
                ->all();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private static function toArray(PopupItemInfo $popup, string $lang): array
    {
        return [
            'id' => $popup->id,
            'display_type' => $popup->display_type === 'floating' ? 'floating' : 'modal',
            'show_dismiss_today' => $popup->show_dismiss_today === 'Y',
            'show_arrows' => $popup->show_arrows === 'Y',
            'show_dots' => $popup->show_dots === 'Y',
            'autoplay' => $popup->autoplay === 'Y',
            'slide_interval' => max(1, (int) $popup->slide_interval),
            'slide_speed' => max(100, (int) $popup->slide_speed),
            'menu_mode' => $popup->menu_mode,
            'menu_ids' => $popup->menus->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'parts' => $popup->parts
                ->map(fn (PopupItemPart $part) => self::partToArray($part, $lang))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    /**
     * part 1 รายการ — คืน null ถ้าไม่มีอะไรให้แสดง (รูปถูกลบ/ปิด และไม่มีข้อความ)
     *
     * @return array<string, mixed>|null
     */
    private static function partToArray(PopupItemPart $part, string $lang): ?array
    {
        $image = $part->hasImage() ? FrontFile::fromFileInfo($part->image, 1280) : null;
        $html = $part->hasText() ? self::pickText($part, $lang) : null;

        // ขาดส่วนที่รูปแบบนั้นบังคับ (เช่น รูปถูกลบไปแล้ว) — แบบรูปภาพ + ข้อความ ยังแสดงส่วนที่เหลือได้
        if ($image === null && $html === null) {
            return null;
        }

        $url = FrontUrl::withLang(FrontUrl::safeExternal($part->url), $lang);

        return [
            'id' => $part->id,
            'part_type' => $part->part_type,
            'image' => $image,
            'image_size' => in_array($part->image_size, PopupItemPart::IMAGE_SIZES, true) ? $part->image_size : 'full',
            'html' => $html,
            'url' => $url,
            'link_target' => $part->link_target === '_blank' ? '_blank' : '_self',
        ];
    }

    /**
     * ข้อความของภาษาที่ขอ ถ้ายังไม่ได้กรอกใช้ภาษาหลัก — ผ่าน HtmlSanitizer ก่อนส่งให้หน้าบ้านเสมอ
     */
    private static function pickText(PopupItemPart $part, string $lang): ?string
    {
        $byLang = $part->details->keyBy('lang');
        $text = (string) ($byLang->get($lang)?->detail ?? '');

        if (trim(strip_tags($text)) === '') {
            $text = (string) ($byLang->get(Setting::defaultLanguage())?->detail ?? '');
        }

        $clean = HtmlSanitizer::clean($text);

        return trim(strip_tags($clean)) !== '' ? $clean : null;
    }
}
