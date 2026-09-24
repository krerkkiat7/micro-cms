<?php

namespace App\Support\Front;

use App\Models\ArticleItemPart;
use App\Models\PageItemWidgetCustomtextPart;
use App\Support\Setting;
use Illuminate\Database\Eloquent\Model;

/**
 * แปลง "part" ของเนื้อหา (article_item_part และ part ของ widget Custom Text — โครงเดียวกัน) เป็นข้อมูลสำหรับหน้าบ้าน:
 * เฉพาะ part ที่แสดง (status = Y), ข้อความของภาษาที่ขอ (rich text ผ่าน HtmlSanitizer แล้ว), URL ไฟล์ผ่าน FrontFile
 * (ไม่ส่ง id/hash ให้หน้าจอประกอบเอง) และ YouTube เป็น video id สำหรับ embed แบบ youtube-nocookie
 */
final class FrontParts
{
    /**
     * @param  iterable<ArticleItemPart|PageItemWidgetCustomtextPart>  $parts  ต้อง eager load details, files.file, files.coverImage แล้ว
     * @return list<array<string, mixed>>
     */
    public static function map(iterable $parts, string $lang): array
    {
        $defaultLang = Setting::defaultLanguage();
        $result = [];

        foreach ($parts as $part) {
            if ($part->status !== 'Y') {
                continue;
            }

            $mapped = self::part($part, $lang, $defaultLang);

            if ($mapped !== null) {
                $result[] = $mapped;
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function part(Model $part, string $lang, string $defaultLang): ?array
    {
        $details = $part->details->keyBy('lang');
        $detail = $details->get($lang);
        $setting = is_array($part->setting) ? $part->setting : [];
        $type = (string) $part->part_type;

        $files = [];

        foreach ($part->files as $row) {
            $description = is_array($row->description) ? $row->description : [];
            $file = FrontFile::fromFileInfo($row->file, $type === 'images' ? 1280 : 1600);
            $youtubeId = $row->video_type === 'youtube' ? self::youtubeId($row->youtube_url) : null;

            // ไฟล์ที่ถูกลบ/ปิดไปแล้ว และวิดีโอ YouTube ที่ URL ใช้ไม่ได้ — ข้ามทิ้ง
            if ($file === null && $youtubeId === null) {
                continue;
            }

            $files[] = [
                'file' => $file,
                'cover' => FrontFile::fromFileInfo($row->coverImage, 1280),
                'video_type' => $row->video_type === 'youtube' ? 'youtube' : 'file',
                'youtube_id' => $youtubeId,
                // รูป: ข้อความแทนภาพ/คำบรรยาย (แยกภาษา) — ไม่มีของภาษานี้ใช้ภาษาหลัก
                'alt' => in_array($type, ['image', 'images'], true)
                    ? trim((string) ($description[$lang] ?? '')) ?: trim((string) ($description[$defaultLang] ?? ''))
                    : '',
                'autoplay' => (bool) ($description['autoplay'] ?? false),
                'controls' => (bool) ($description['controls'] ?? true),
                'pdf_preview' => (bool) ($description['pdf_preview'] ?? false),
                'show_file_size' => (bool) ($description['show_file_size'] ?? true),
            ];
        }

        $html = $type === 'text' ? HtmlSanitizer::clean($detail?->detail) : '';

        // part ที่ไม่มีเนื้อหาให้แสดงเลย ไม่ต้องส่ง
        if ($type === 'text' ? $html === '' : $files === []) {
            if (! ($part->show_title === 'Y' && trim((string) $detail?->title) !== '')) {
                return null;
            }
        }

        return [
            'id' => (int) $part->id,
            'type' => $type,
            'images_display_type' => $part->images_display_type ?: 'grid_lightbox',
            'title' => $part->show_title === 'Y' ? trim((string) ($detail?->title ?? '')) : '',
            // การจัดรูปแบบหัวเรื่อง — มีเฉพาะ part ของ Custom Text (part ของบทความใช้สไตล์มาตรฐานของหน้า)
            'title_style' => $part->getAttribute('title_font_size') !== null ? [
                'font_size' => (int) $part->title_font_size,
                'bold' => $part->title_bold === 'Y',
                'font_family' => (string) $part->title_font_family,
                'align' => (string) $part->title_align,
                'color' => (string) $part->title_color,
            ] : null,
            'html' => $html,
            'setting' => [
                'alignment' => in_array($setting['alignment'] ?? null, ['left', 'center', 'right'], true) ? $setting['alignment'] : 'center',
                'size' => in_array($setting['size'] ?? null, ['small', 'medium', 'large', 'full'], true) ? $setting['size'] : 'large',
                'player_size' => in_array($setting['player_size'] ?? null, ['small', 'medium', 'large', 'full'], true) ? $setting['player_size'] : 'large',
                'show_caption' => (bool) ($setting['show_caption'] ?? false),
                'columns' => max(1, min(6, (int) ($setting['columns'] ?? 3))),
                'autoplay' => (bool) ($setting['autoplay'] ?? false),
                'interval_ms' => max(1000, min(60000, (int) ($setting['interval_ms'] ?? 4000))),
            ],
            'files' => $files,
        ];
    }

    /**
     * video id จาก URL YouTube (watch?v=, youtu.be/, embed/, shorts/, live/) — ไม่ใช่ URL YouTube = null
     */
    public static function youtubeId(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (preg_match('~^(?:https?://)?(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/|youtube-nocookie\.com/embed/)([A-Za-z0-9_-]{11})~i', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
