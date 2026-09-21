<?php

namespace App\Support\PageWidget;

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

    public const LINK_TARGETS = ['_self', '_blank'];

    public const TEXT_ALIGNS = ['left', 'center', 'right'];

    public const INTERVAL_MIN = 1;

    public const INTERVAL_MAX = 60;

    public const SPEED_MIN = 100;

    public const SPEED_MAX = 3000;

    /** ชื่อคอลัมน์ FK หมวดหมู่ในตารางตั้งค่า (ชื่อเต็มของตารางที่อ้างถึง เช่น banner_category_info_id) */
    abstract protected function categoryField(): string;

    /** ตารางหมวดหมู่ที่ FK ชี้ไป */
    abstract protected function categoryTable(): string;

    /** ชื่อเรียกหมวดหมู่ในข้อความ error (เช่น "หมวดหมู่ banner") */
    abstract protected function categoryLabel(): string;

    /** @return list<string> ตัวเลือกการเรียงลำดับที่ใช้ได้กับแหล่งข้อมูลนี้ (ตัวแรก = ค่าเริ่มต้น) */
    abstract protected function sorts(): array;

    /**
     * query ข้อมูลที่ widget จะแสดง (ยังไม่ order/limit) — เฉพาะรายการที่เผยแพร่อยู่ ภาษาหลัก; select ให้ครบตามที่ previewRow() ใช้
     */
    abstract protected function previewQuery(int $categoryId): Builder;

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
}
