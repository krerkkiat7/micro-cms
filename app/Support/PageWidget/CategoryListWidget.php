<?php

namespace App\Support\PageWidget;

use App\Support\Front\FrontFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * ฐานของ widget ที่ "ดึงรายการจากหมวดหมู่ของโมดูลอื่น" (ตอนนี้: banner / article) มาแสดง — ฟิลด์ตั้งค่าร่วม: หมวดหมู่ (จำเป็นต้องเลือก),
 * ลำดับการเรียงลำดับ, จำนวนที่แสดงสูงสุด (ว่าง/0 = ทั้งหมด) + ชุดฟิลด์ carousel (ลูกศร จุด เลื่อนอัตโนมัติ ความเร็ว) ที่ Slideshow/Slideset ใช้ร่วมกัน
 * และการดึงข้อมูลตัวอย่างสำหรับหน้าโครงสร้าง (`preview()`) คลาสลูกกำหนดแหล่งข้อมูล: ตารางหมวดหมู่, query รายการที่เผยแพร่อยู่, การเรียงลำดับ
 * และหน้าตาของแต่ละแถวที่ส่งกลับ ตัวเลือก/ค่าเริ่มต้นต้องตรงกับ utils/pageWidget.ts
 */
abstract class CategoryListWidget extends SettingsWidget
{
    /** จำนวนรายการสูงสุดที่ดึงมาแสดงเป็นตัวอย่างในหน้าโครงสร้าง */
    public const PREVIEW_LIMIT = 10;

    /** จำนวนที่แสดงสูงสุดที่กรอกได้ (0 = แสดงทั้งหมด) */
    public const MAX_ITEMS_LIMIT = 1000;

    public const ASPECT_RATIOS = ['16:9', '21:9', '4:3', '1:1'];

    public const IMAGE_FITS = ['cover', 'contain'];

    public const LINK_TARGETS = ['_self', '_blank'];

    public const TEXT_ALIGNS = ['left', 'center', 'right'];

    public const INTERVAL_MIN = 1;

    public const INTERVAL_MAX = 60;

    public const SPEED_MIN = 100;

    public const SPEED_MAX = 3000;

    /** จำนวนบรรทัดที่แสดงของหัวเรื่อง/ข้อความเกริ่นนำ (เกินตัดด้วย ...) */
    public const LINES_MIN = 1;

    public const LINES_MAX = 3;

    /** สีพื้นหลังเริ่มต้นของกรอบรูปเมื่อแสดงแบบ contain (เทาอ่อน = bg-gray-100 เหมือนกรอบรูปในหน้าจัดการไฟล์) */
    public const DEFAULT_IMAGE_BACKGROUND = '#F3F4F6';

    /** สีเส้นขอบเริ่มต้นของกล่อง/แถวที่ครอบแต่ละรายการ (เทาอ่อน = border-gray-200 เหมือนที่แสดงอยู่เดิมก่อนเลือกสีเองได้) */
    public const DEFAULT_BORDER_COLOR = '#E5E7EB';

    /** ชื่อคอลัมน์ FK หมวดหมู่ในตารางตั้งค่า (ชื่อเต็มของตารางที่อ้างถึง เช่น banner_category_info_id) */
    abstract protected function categoryField(): string;

    /** ตารางหมวดหมู่ที่ FK ชี้ไป */
    abstract protected function categoryTable(): string;

    /** ชื่อเรียกหมวดหมู่ในข้อความ error (เช่น "หมวดหมู่ banner") */
    abstract protected function categoryLabel(): string;

    /** @return list<string> ตัวเลือกการเรียงลำดับที่ใช้ได้กับแหล่งข้อมูลนี้ (ตัวแรก = ค่าเริ่มต้น) */
    abstract protected function sorts(): array;

    /**
     * query ข้อมูลที่ widget จะแสดง (ยังไม่ order/limit) — เฉพาะรายการที่เผยแพร่อยู่; select ให้ครบตามที่ previewRow() และ frontLink() ใช้
     * `$lang` = ภาษาของข้อความ (null = ภาษาหลัก — หน้าโครงสร้างหลังบ้าน)
     */
    abstract protected function previewQuery(int $categoryId, ?string $lang = null): Builder;

    /**
     * ลิงก์ปลายทางของรายการ 1 แถวสำหรับหน้าบ้าน (url = null คือไม่มีลิงก์)
     *
     * @return array{url: string|null, link_target: string}
     */
    abstract protected function frontLink(object $row, string $lang): array;

    /** เรียงลำดับ query ของ previewQuery ตามตัวเลือก `sort_by` */
    abstract protected function orderPreview(Builder $query, string $sortBy): void;

    /**
     * แปลง 1 แถวผลลัพธ์เป็นข้อมูลส่งหน้าจอ — ห้ามส่ง URL ปลายทาง (ตัวอย่างกดลิงก์ไม่ได้)
     *
     * @return array<string, mixed>
     */
    abstract protected function previewRow(object $row): array;

    /**
     * ฟิลด์ตั้งค่าร่วม: หมวดหมู่ + การเรียงลำดับ + จำนวนที่แสดงสูงสุด
     *
     * @return array<string, array<string, mixed>>
     */
    protected function listFields(): array
    {
        $category = $this->categoryLabel();

        return [
            $this->categoryField() => [
                'label' => $category, 'default' => null, 'type' => 'nullint',
                'rules' => [
                    'required', 'integer',
                    // หมวดหมู่ต้องมีอยู่จริง เปิดใช้งาน และไม่ถูกลบ
                    Rule::exists($this->categoryTable(), 'id')->where(fn ($query) => $query
                        ->where('status', 'Y')
                        ->whereNull('deleted_at')),
                ],
                'messages' => [
                    'required' => "กรุณาเลือก{$category}",
                    'exists' => "ไม่พบ{$category} ที่เลือก (อาจถูกปิดใช้งานหรือลบไปแล้ว)",
                ],
            ],
            'sort_by' => self::choice('ลำดับการเรียงลำดับ', $this->sorts()[0], $this->sorts()),
            // ว่างหรือ 0 = แสดงทั้งหมด
            'max_items' => [
                'label' => 'จำนวนที่แสดงสูงสุด', 'default' => 0, 'type' => 'int', 'range' => [0, self::MAX_ITEMS_LIMIT, ' (0 = แสดงทั้งหมด)'],
                'rules' => ['nullable', 'integer', 'between:0,'.self::MAX_ITEMS_LIMIT],
            ],
        ];
    }

    /**
     * ฟิลด์ carousel ที่ Slideshow/Slideset ใช้ร่วมกัน: ลูกศร จุด เลื่อนอัตโนมัติ ระยะค้างต่อภาพ (วินาที) และความเร็วเปลี่ยนภาพ (มิลลิวินาที)
     *
     * @return array<string, array<string, mixed>>
     */
    protected function carouselFields(bool $autoplayDefault = true): array
    {
        return [
            'show_arrows' => self::flag('การแสดงลูกศร', 'Y'),
            'show_dots' => self::flag('การแสดงจุด', 'Y'),
            'autoplay' => self::flag('การเลื่อนอัตโนมัติ', $autoplayDefault ? 'Y' : 'N'),
            'autoplay_interval' => self::number('ระยะเวลาค้างต่อภาพ', 5, self::INTERVAL_MIN, self::INTERVAL_MAX, ' วินาที'),
            'transition_speed' => self::number('ความเร็วในการเปลี่ยนภาพ', 500, self::SPEED_MIN, self::SPEED_MAX, ' มิลลิวินาที'),
        ];
    }

    /**
     * ฟิลด์ของข้อความที่กดลิงก์ได้และกำหนดจำนวนบรรทัดได้ (หัวเรื่อง / ข้อความเกริ่นนำ): แสดง, ขนาด, ตัวหนา, ฟอนต์, สี, จัดตำแหน่ง, กดลิงก์ได้, จำนวนบรรทัด
     * ชื่อคอลัมน์ตาม PageTextStyle (`<part>_font_size` ฯลฯ) — ใช้ร่วมกันระหว่าง Slideset และ Grid
     *
     * @return array<string, array<string, mixed>>
     */
    protected function textFields(string $part, string $label, int $size, string $bold, string $color, int $lines, string $showDefault, string $clickable): array
    {
        return [
            $part === 'title' ? 'show_title' : 'show_intro_text' => self::flag("การแสดง{$label}", $showDefault),
            "{$part}_font_size" => self::fontSize("ขนาดตัวอักษรของ{$label}", $size),
            "{$part}_bold" => self::flag("ตัวหนาของ{$label}", $bold),
            "{$part}_font_family" => self::fontFamily("ฟอนต์ของ{$label}"),
            "{$part}_color" => self::color("สีตัวอักษรของ{$label}", $color),
            "{$part}_align" => self::choice("การจัดตำแหน่งของ{$label}", 'left', self::TEXT_ALIGNS),
            "{$part}_clickable" => self::flag("การกดลิงก์ที่{$label}", $clickable),
            "{$part}_lines" => self::number("จำนวนบรรทัดที่แสดงของ{$label}", $lines, self::LINES_MIN, self::LINES_MAX, ' บรรทัด'),
        ];
    }

    /**
     * ฟิลด์ของข้อมูลเสริมของการ์ด (เช่น วันที่เผยแพร่ / จำนวนเข้าชม): แสดง, ขนาด, ตัวหนา, ฟอนต์, สี (default เทา)
     *
     * @return array<string, array<string, mixed>>
     */
    protected function metaFields(string $part, string $label, string $showDefault): array
    {
        return [
            "show_{$part}" => self::flag("การแสดง{$label}", $showDefault),
            "{$part}_font_size" => self::fontSize("ขนาดตัวอักษรของ{$label}", 12),
            "{$part}_bold" => self::flag("ตัวหนาของ{$label}", 'N'),
            "{$part}_font_family" => self::fontFamily("ฟอนต์ของ{$label}"),
            "{$part}_color" => self::color("สีตัวอักษรของ{$label}", '#667085'),
        ];
    }

    /**
     * ฟิลด์ของกล่อง/แถวที่ครอบแต่ละรายการ: เส้นขอบ (แสดง/สี) + มุมมน + สีพื้นหลังของแต่ละรายการ — ใช้ร่วมกันระหว่าง Slideset และ Grid
     *
     * @return array<string, array<string, mixed>>
     */
    protected function cardBoxFields(): array
    {
        return [
            'show_border' => self::flag('การแสดงเส้นขอบของกล่อง', 'Y'),
            'border_color' => self::color('สีเส้นขอบของกล่อง', self::DEFAULT_BORDER_COLOR),
            'rounded_corners' => self::flag('การทำมุมมนของกล่อง', 'Y'),
            'item_background' => self::backgroundColor('สีพื้นหลังของแต่ละรายการ', '#FFFFFF'),
        ];
    }

    protected function normalize(array $values): array
    {
        $values['max_items'] = (int) ($values['max_items'] ?? 0); // ว่าง = 0 = แสดงทั้งหมด

        return $values;
    }

    public function previewRules(): array
    {
        return array_intersect_key($this->rules(), array_flip([$this->categoryField(), 'sort_by', 'max_items']));
    }

    public function preview(array $setting): array
    {
        $query = $this->previewQuery((int) ($setting[$this->categoryField()] ?? 0));
        $this->orderPreview($query, $setting['sort_by'] ?? $this->sorts()[0]);

        // จำนวนที่แสดงสูงสุดของ widget (0/ว่าง = ทั้งหมด) แต่ตัวอย่างในหน้าโครงสร้างแสดงไม่เกิน PREVIEW_LIMIT
        $maxItems = (int) ($setting['max_items'] ?? 0);
        $limit = $maxItems > 0 ? min($maxItems, self::PREVIEW_LIMIT) : self::PREVIEW_LIMIT;

        return $query
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->previewRow($row))
            ->values()
            ->all();
    }

    /**
     * ความกว้างของรูปที่หน้าบ้านขอ (thumbnail) — Slideshow แสดงเต็มความกว้างจึงใช้ใหญ่กว่า (override ใน SlideshowWidget)
     */
    protected function frontImageWidth(): int
    {
        return 640;
    }

    /**
     * รายการที่ widget แสดงจริงที่หน้าบ้าน — เหมือน preview() แต่ตามภาษาที่ขอ, จำนวนตามที่ตั้งไว้ (0 = ทั้งหมด ไม่เกิน MAX_ITEMS_LIMIT),
     * มีลิงก์ปลายทางจริง และ URL รูปสำหรับหน้าบ้าน (ไม่ส่ง hash_name)
     *
     * @param  array<string, mixed>  $setting  ค่าจาก toArray()
     * @return list<array<string, mixed>>
     */
    public function frontItems(array $setting, string $lang): array
    {
        $categoryId = (int) ($setting[$this->categoryField()] ?? 0);

        if ($categoryId <= 0) {
            return [];
        }

        $query = $this->previewQuery($categoryId, $lang);
        $this->orderPreview($query, $setting['sort_by'] ?? $this->sorts()[0]);

        $maxItems = (int) ($setting['max_items'] ?? 0);

        return $query
            ->limit($maxItems > 0 ? min($maxItems, self::MAX_ITEMS_LIMIT) : self::MAX_ITEMS_LIMIT)
            ->get()
            ->map(function ($row) use ($lang) {
                $item = $this->previewRow($row);
                $item['image_url'] = FrontFile::thumbnail($item['image'] ?? null, $this->frontImageWidth());
                unset($item['image'], $item['has_link']);

                return [...$item, ...$this->frontLink($row, $lang)];
            })
            ->values()
            ->all();
    }
}
