<?php

namespace App\Support\Front;

use App\Models\IntropageItemButton;
use App\Models\IntropageItemInfo;
use App\Support\Setting;

/**
 * เลือก Intropage ที่แสดงที่หน้าบ้าน (ดู docs/PRD-intropage.md §3) — จากรายการที่ status = Y, ไม่ถูกลบ และช่วงเผยแพร่ครอบคลุมเวลาปัจจุบัน
 * (publish_date <= now <= publish_down) ถ้ามีหลายรายการ: publish_date ใกล้ปัจจุบันที่สุดก่อน แล้ว publish_down ไกลที่สุด
 *
 * ข้อมูลตัวอย่างจาก seeder (is_temp = Y) แสดงได้ตามปกติ — ผู้ใช้อาจแก้ตัวอย่างแล้วใช้ต่อเลย
 * cache สั้น (front.cache.intropage_ttl) เพราะขึ้นกับเวลา
 */
final class IntropageResolver
{
    /**
     * @return array<string, mixed>|null
     */
    public static function current(string $lang): ?array
    {
        $data = FrontCache::remember("intropage.{$lang}", (int) config('front.cache.intropage_ttl', 60), function () use ($lang) {
            $item = self::query()->first();

            return $item ? self::toArray($item, $lang) : false; // false = ไม่มี (cache ผลว่างได้)
        });

        return $data ?: null;
    }

    private static function query()
    {
        $now = now();

        return IntropageItemInfo::query()
            ->where('status', 'Y')
            ->where('publish_date', '<=', $now)
            ->where('publish_down', '>=', $now)
            ->with(['backgroundImage', 'imageFile', 'vdoFile', 'details', 'buttons.buttonImage'])
            ->orderByDesc('publish_date')
            ->orderByDesc('publish_down')
            ->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    private static function toArray(IntropageItemInfo $item, string $lang): array
    {
        $detail = FrontLang::pick($item->details, $lang);
        $defaultLang = Setting::defaultLanguage();

        $media = match ($item->display_type) {
            'image' => ['image' => FrontFile::fromFileInfo($item->imageFile, 1920)],
            'vdo' => ['video_url' => $item->vdoFile?->status === 'Y' ? FrontFile::url($item->vdoFile->hash_name) : null],
            'vdourl' => ['video_url' => FrontUrl::safeExternal($item->vdo_url)],
            'youtubeurl' => ['youtube_id' => FrontParts::youtubeId($item->vdo_url)],
            default => [],
        };

        $backgroundImage = $item->backgroundImage;
        $backgroundUrl = $backgroundImage?->status === 'Y' ? FrontFile::url($backgroundImage->hash_name) : null;

        return [
            'id' => (int) $item->id,
            'title' => trim((string) ($detail?->title ?? '')),
            'detail' => trim((string) ($detail?->detail ?? '')),
            'display_type' => $item->display_type,
            'display_size' => $item->display_size ?: 'container_100',
            'image' => $media['image'] ?? null,
            'video_url' => $media['video_url'] ?? null,
            'youtube_id' => $media['youtube_id'] ?? null,
            'background' => [
                'color' => $item->background_color ?: null,
                'image_url' => $backgroundUrl,
                'repeat' => $backgroundUrl ? $item->background_repeat : null,
                'size' => $backgroundUrl ? $item->background_size : null,
                'attachment' => $backgroundUrl ? $item->background_attachment : null,
                'position' => $backgroundUrl ? $item->background_position : null,
            ],
            'show_button' => $item->show_button === 'Y',
            'buttons' => $item->buttons
                ->map(function (IntropageItemButton $button) use ($lang, $defaultLang) {
                    $texts = is_array($button->texts) ? $button->texts : [];
                    $text = trim((string) ($texts[$lang] ?? '')) ?: trim((string) ($texts[$defaultLang] ?? ''));
                    $image = $button->button_display_type === 'image' && $button->buttonImage?->status === 'Y'
                        ? FrontFile::url($button->buttonImage->hash_name)
                        : null;

                    return [
                        'id' => (int) $button->id,
                        'type' => $button->button_type === 'home' ? 'home' : 'other',
                        'display' => $image ? 'image' : 'text',
                        'text' => $text,
                        'image_url' => $image,
                        // ปุ่ม home ลิงก์ไปหน้าแรกเสมอ (ใส่ URL ตอน render — ไม่ cache URL หน้าแรกไว้ที่นี่)
                        'url' => $button->button_type === 'home' ? null : FrontUrl::safeExternal($button->url),
                        'target' => $button->link_target === '_blank' ? '_blank' : '_self',
                        'background_color' => $button->background_color ?: null,
                        'text_color' => $button->text_color ?: null,
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}
