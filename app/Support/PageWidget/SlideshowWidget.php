<?php

namespace App\Support\PageWidget;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * ส่วนที่ใช้ร่วมกันของ widget กลุ่ม Slideshow (ภาพเต็มภาพเดียวที่สไลด์ได้) — การตั้งค่าเหมือนกันทุกแหล่งข้อมูล
 * (ลูกศร จุด เลื่อนอัตโนมัติ effect สัดส่วนภาพ ลิงก์ ข้อความบนภาพ ฯลฯ) ต่างกันที่แหล่งข้อมูล: คลาสลูกกำหนดตารางตั้งค่า/คอลัมน์หมวดหมู่/ตัวเลือกการเรียงลำดับ
 * และวิธีดึงข้อมูลตัวอย่าง (ตอนนี้มี banner และ article) ตัวเลือก/ค่าเริ่มต้นต้องตรงกับ utils/pageWidget.ts
 */
abstract class SlideshowWidget implements PageWidgetType
{
    /** จำนวนรายการสูงสุดที่ดึงมาแสดงเป็นตัวอย่างในหน้าโครงสร้าง */
    public const PREVIEW_LIMIT = 10;

    public const EFFECTS = ['slide', 'fade', 'zoom'];

    public const ASPECT_RATIOS = ['16:9', '21:9', '4:3', '1:1'];

    public const LINK_TARGETS = ['_self', '_blank'];

    public const TEXT_ALIGNS = ['left', 'center', 'right'];

    public const TEXT_WIDTHS = ['full', 'container'];

    public const INTERVAL_MIN = 1;

    public const INTERVAL_MAX = 60;

    public const SPEED_MIN = 100;

    public const SPEED_MAX = 3000;

    /** คอลัมน์ตั้งค่าที่ไม่ใช่หมวดหมู่ (ไม่รวม PK/audit) */
    private const COMMON_FIELDS = [
        'sort_by', 'show_arrows', 'show_dots', 'autoplay', 'autoplay_interval',
        'transition_speed', 'transition_effect', 'aspect_ratio', 'is_clickable', 'link_target',
        'show_title', 'show_intro_text', 'text_align', 'text_width',
    ];

    /** @return class-string<Model> model ของตารางตั้งค่าประเภทนี้ */
    abstract protected function model(): string;

    /** ชื่อคอลัมน์ FK หมวดหมู่ในตารางตั้งค่า (ชื่อเต็มของตารางที่อ้างถึง เช่น banner_category_info_id) */
    abstract protected function categoryField(): string;

    /** ตารางหมวดหมู่ที่ FK ชี้ไป */
    abstract protected function categoryTable(): string;

    /** ชื่อเรียกหมวดหมู่ในข้อความ error (เช่น "หมวดหมู่ banner") */
    abstract protected function categoryLabel(): string;

    /** @return list<string> ตัวเลือกการเรียงลำดับที่ใช้ได้กับแหล่งข้อมูลนี้ (ตัวแรก = ค่าเริ่มต้น) */
    abstract protected function sorts(): array;

    /**
     * query ข้อมูลที่ widget จะแสดง (ยังไม่ order/limit) — เฉพาะรายการที่เผยแพร่อยู่และมีรูป ภาษาหลัก
     * ต้อง select คอลัมน์ `id`, `image` (hash_name ของรูป), `title`, `intro_text`, `has_link` (0/1)
     */
    abstract protected function previewQuery(int $categoryId): Builder;

    /** เรียงลำดับ query ของ previewQuery ตามตัวเลือก `sort_by` */
    abstract protected function orderPreview(Builder $query, string $sortBy): void;

    public function rules(): array
    {
        $yesNo = ['required', Rule::in(['Y', 'N'])];

        return $this->previewRules() + [
            'show_arrows' => $yesNo,
            'show_dots' => $yesNo,
            'autoplay' => $yesNo,
            'autoplay_interval' => ['required', 'integer', 'between:'.self::INTERVAL_MIN.','.self::INTERVAL_MAX],
            'transition_speed' => ['required', 'integer', 'between:'.self::SPEED_MIN.','.self::SPEED_MAX],
            'transition_effect' => ['required', Rule::in(self::EFFECTS)],
            'aspect_ratio' => ['required', Rule::in(self::ASPECT_RATIOS)],
            'is_clickable' => $yesNo,
            'link_target' => ['required', Rule::in(self::LINK_TARGETS)],
            'show_title' => $yesNo,
            'show_intro_text' => $yesNo,
            'text_align' => ['required', Rule::in(self::TEXT_ALIGNS)],
            'text_width' => ['required', Rule::in(self::TEXT_WIDTHS)],
        ];
    }

    public function messages(): array
    {
        $category = $this->categoryField();

        return [
            "{$category}.required" => "กรุณาเลือก{$this->categoryLabel()}",
            "{$category}.exists" => "ไม่พบ{$this->categoryLabel()}ที่เลือก (อาจถูกปิดใช้งานหรือลบไปแล้ว)",
            "{$category}.integer" => "{$this->categoryLabel()}ไม่ถูกต้อง",
            'sort_by.in' => 'ลำดับการเรียงลำดับไม่ถูกต้อง',
            'autoplay_interval.between' => 'ระยะเวลาค้างต่อภาพต้องอยู่ระหว่าง '.self::INTERVAL_MIN.' - '.self::INTERVAL_MAX.' วินาที',
            'autoplay_interval.integer' => 'ระยะเวลาค้างต่อภาพต้องเป็นจำนวนเต็ม',
            'autoplay_interval.required' => 'กรุณากรอกระยะเวลาค้างต่อภาพ',
            'transition_speed.between' => 'ความเร็วในการเปลี่ยนภาพต้องอยู่ระหว่าง '.self::SPEED_MIN.' - '.self::SPEED_MAX.' มิลลิวินาที',
            'transition_speed.integer' => 'ความเร็วในการเปลี่ยนภาพต้องเป็นจำนวนเต็ม',
            'transition_speed.required' => 'กรุณากรอกความเร็วในการเปลี่ยนภาพ',
            'transition_effect.in' => 'ประเภทการเลื่อนไม่ถูกต้อง',
            'aspect_ratio.in' => 'สัดส่วนภาพไม่ถูกต้อง',
            'link_target.in' => 'เป้าหมายการเปิดลิงก์ไม่ถูกต้อง',
            'text_align.in' => 'ตำแหน่งที่แสดงข้อความไม่ถูกต้อง',
            'text_width.in' => 'ขอบเขตของข้อความไม่ถูกต้อง',
            '*.in' => 'ค่าที่เลือกไม่ถูกต้อง',
            '*.required' => 'กรุณาระบุค่าที่จำเป็นให้ครบ',
        ];
    }

    public function previewRules(): array
    {
        return [
            // หมวดหมู่ต้องมีอยู่จริง เปิดใช้งาน และไม่ถูกลบ
            $this->categoryField() => [
                'required', 'integer',
                Rule::exists($this->categoryTable(), 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'sort_by' => ['required', Rule::in($this->sorts())],
        ];
    }

    public function defaults(): array
    {
        return [
            $this->categoryField() => null,
            'sort_by' => $this->sorts()[0],
            'show_arrows' => 'Y',
            'show_dots' => 'Y',
            'autoplay' => 'Y',
            'autoplay_interval' => 5,
            'transition_speed' => 500,
            'transition_effect' => 'slide',
            'aspect_ratio' => '16:9',
            'is_clickable' => 'Y',
            'link_target' => '_self',
            'show_title' => 'Y',
            'show_intro_text' => 'N',
            'text_align' => 'center',
            'text_width' => 'container',
        ];
    }

    public function save(int $widgetId, array $setting, ?int $actorId): void
    {
        $model = $this->model();
        $fields = array_flip([$this->categoryField(), ...self::COMMON_FIELDS]);
        $values = array_intersect_key($setting, $fields) + $this->defaults();
        $query = $model::where('id', $widgetId);

        if ($query->exists()) {
            $query->update($values + ['updated_by' => $actorId]);
        } else {
            $model::create(['id' => $widgetId, 'created_by' => $actorId] + $values);
        }
    }

    public function toArray(?Model $row): array
    {
        if ($row === null || ! is_a($row, $this->model())) {
            return $this->defaults();
        }

        $category = $this->categoryField();
        $values = [$category => $row->{$category} !== null ? (int) $row->{$category} : null];

        foreach (self::COMMON_FIELDS as $field) {
            $values[$field] = in_array($field, ['autoplay_interval', 'transition_speed'], true) ? (int) $row->{$field} : $row->{$field};
        }

        return $values;
    }

    public function softDelete(array $widgetIds, ?int $actorId): void
    {
        if ($widgetIds !== []) {
            $this->model()::whereIn('id', $widgetIds)->update(['deleted_by' => $actorId, 'deleted_at' => now()]);
        }
    }

    public function preview(array $setting): array
    {
        $query = $this->previewQuery((int) ($setting[$this->categoryField()] ?? 0));
        $this->orderPreview($query, $setting['sort_by'] ?? $this->sorts()[0]);

        return $query
            ->limit(self::PREVIEW_LIMIT)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'image' => $row->image,
                'title' => $row->title ?? '',
                'intro_text' => $row->intro_text ?? '',
                // ไม่ส่ง URL — ตัวอย่างแค่บอกว่ามีลิงก์ (กดไม่ได้)
                'has_link' => (bool) $row->has_link,
            ])
            ->values()
            ->all();
    }
}
