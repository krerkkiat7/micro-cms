<?php

namespace App\Support\PageWidget;

use App\Support\PageTextStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * ฐานของ widget ที่เก็บการตั้งค่าเป็นคอลัมน์ในตารางของประเภท (`page_item_widget_<ประเภท>`, PK = `page_item_widget.id`) —
 * คลาสลูกประกาศ "ฟิลด์ตั้งค่า" ครั้งเดียวใน `fields()` (ชื่อคอลัมน์ => ป้ายชื่อ, ค่าเริ่มต้น, กฎ validation) แล้วส่วนที่เหลือ
 * (rules/messages/defaults/บันทึก/แปลงเป็นข้อมูลส่งหน้าจอ/soft delete) ทำให้เอง จึงเพิ่มฟิลด์ = เพิ่มบรรทัดเดียว (+ คอลัมน์ใน migration/model)
 *
 * รูปแบบของแต่ละฟิลด์: ['label' => ชื่อเรียกใน error, 'default' => ค่าเริ่มต้น, 'rules' => [...], 'type' => 'string'|'int'|'nullint',
 * 'range' => [min, max, หน่วย] (ไว้สร้างข้อความ between), 'messages' => [rule => ข้อความ] (แทนข้อความอัตโนมัติ)]
 * ตัวช่วยสร้างฟิลด์: flag / choice / number / fontSize / fontFamily / color
 */
abstract class SettingsWidget implements PageWidgetType
{
    /** สีตัวอักษร: รหัส hex เท่านั้น (ไม่มีตัวเลือกโปร่งใส) */
    private const TEXT_COLOR_REGEX = '/^#[0-9a-fA-F]{3,8}$/';

    /** @return class-string<Model> model ของตารางตั้งค่าประเภทนี้ */
    abstract protected function model(): string;

    /**
     * @return array<string, array<string, mixed>>
     */
    abstract protected function fields(): array;

    // ---------------------------------------------------------------- ตัวช่วยสร้างฟิลด์

    /**
     * @return array<string, mixed>
     */
    protected static function flag(string $label, string $default): array
    {
        return ['label' => $label, 'default' => $default, 'rules' => ['required', Rule::in(['Y', 'N'])]];
    }

    /**
     * @param  list<string>  $values
     * @return array<string, mixed>
     */
    protected static function choice(string $label, string $default, array $values): array
    {
        return ['label' => $label, 'default' => $default, 'rules' => ['required', Rule::in($values)]];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function number(string $label, int $default, int $min, int $max, string $unit = ''): array
    {
        return [
            'label' => $label, 'default' => $default, 'type' => 'int', 'range' => [$min, $max, $unit],
            'rules' => ['required', 'integer', "between:{$min},{$max}"],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function fontSize(string $label, int $default): array
    {
        return self::number($label, $default, PageTextStyle::FONT_SIZE_MIN, PageTextStyle::FONT_SIZE_MAX, ' px');
    }

    /**
     * @return array<string, mixed>
     */
    protected static function fontFamily(string $label): array
    {
        return ['label' => $label, 'default' => PageTextStyle::DEFAULT_FONT, 'rules' => ['required', Rule::in(PageTextStyle::fontNames())]];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function color(string $label, string $default): array
    {
        return ['label' => $label, 'default' => $default, 'rules' => ['required', 'string', 'max:20', 'regex:'.self::TEXT_COLOR_REGEX]];
    }

    // ---------------------------------------------------------------- PageWidgetType

    public function rules(): array
    {
        return array_map(fn (array $field) => $field['rules'], $this->fields());
    }

    public function messages(): array
    {
        $messages = [];

        foreach ($this->fields() as $name => $field) {
            $label = $field['label'];
            $messages["{$name}.required"] = "กรุณาระบุ{$label}";
            $messages["{$name}.in"] = "{$label} ไม่ถูกต้อง";
            $messages["{$name}.integer"] = "{$label} ต้องเป็นจำนวนเต็ม";
            $messages["{$name}.regex"] = "รูปแบบ{$label}ไม่ถูกต้อง";

            if (isset($field['range'])) {
                [$min, $max, $unit] = $field['range'];
                $messages["{$name}.between"] = "{$label}ต้องอยู่ระหว่าง {$min} - {$max}{$unit}";
            }

            foreach ($field['messages'] ?? [] as $rule => $text) {
                $messages["{$name}.{$rule}"] = $text;
            }
        }

        return $messages;
    }

    public function defaults(): array
    {
        return array_map(fn (array $field) => $field['default'], $this->fields());
    }

    /**
     * ปรับค่าก่อนบันทึก (คลาสลูก override ได้ เช่น ค่าว่างให้เป็น 0)
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function normalize(array $values): array
    {
        return $values;
    }

    public function save(int $widgetId, array $setting, ?int $actorId): void
    {
        $model = $this->model();
        $values = $this->normalize(array_intersect_key($setting, $this->fields()) + $this->defaults());
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

        $values = [];

        foreach ($this->fields() as $name => $field) {
            $value = $row->{$name};
            $values[$name] = match ($field['type'] ?? 'string') {
                'int' => (int) $value,
                'nullint' => $value !== null ? (int) $value : null,
                default => $value,
            };
        }

        return $values;
    }

    public function softDelete(array $widgetIds, ?int $actorId): void
    {
        if ($widgetIds !== []) {
            $this->model()::whereIn('id', $widgetIds)->update(['deleted_by' => $actorId, 'deleted_at' => now()]);
        }
    }
}
