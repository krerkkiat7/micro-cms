<?php

namespace App\Support\PageWidget;

use App\Support\PageTextStyle;
use App\Support\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * ฐานของ widget ที่เก็บการตั้งค่าเป็นคอลัมน์ในตารางของประเภท (`page_item_widget_<ประเภท>`, PK = `page_item_widget.id`) —
 * คลาสลูกประกาศ "ฟิลด์ตั้งค่า" ครั้งเดียวใน `fields()` (ชื่อคอลัมน์ => ป้ายชื่อ, ค่าเริ่มต้น, กฎ validation) แล้วส่วนที่เหลือ
 * (rules/messages/defaults/บันทึก/แปลงเป็นข้อมูลส่งหน้าจอ/soft delete) ทำให้เอง จึงเพิ่มฟิลด์ = เพิ่มบรรทัดเดียว (+ คอลัมน์ใน migration/model)
 *
 * รูปแบบของแต่ละฟิลด์: ['label' => ชื่อเรียกใน error, 'default' => ค่าเริ่มต้น, 'rules' => [...], 'type' => 'string'|'int'|'nullint'|'nullstring' (ค่า null ส่งหน้าจอเป็นข้อความว่าง),
 * 'range' => [min, max, หน่วย] (ไว้สร้างข้อความ between), 'messages' => [rule => ข้อความ] (แทนข้อความอัตโนมัติ)]
 * ตัวช่วยสร้างฟิลด์: flag / choice / number / fontSize / fontFamily / color / backgroundColor
 *
 * ฟิลด์ที่ "แยกตามภาษา" (เช่น ข้อความของปุ่ม) ประกาศใน `detailFields()` + `detailModel()` (ตาราง `*_detail` PK = id + lang เหมือนโมดูลอื่น)
 * ในข้อมูลตั้งค่าเป็น map ภาษา → ข้อความ (`{ th: '...', en: '...' }` ครบทุกภาษาที่เปิดใช้) และบันทึกลงตาราง detail ให้เอง
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

    /**
     * ฟิลด์ที่แยกตามภาษา: ชื่อฟิลด์ => ['label' => ป้ายชื่อใน error, 'max' => ความยาวสูงสุด]
     *
     * @return array<string, array{label: string, max: int}>
     */
    protected function detailFields(): array
    {
        return [];
    }

    /** @return class-string<Model>|null model ของตารางข้อมูลแยกภาษา (ต้องมี relation `details()` บน model ของตารางตั้งค่า) */
    protected function detailModel(): ?string
    {
        return null;
    }

    public function eagerRelations(): array
    {
        return $this->detailModel() ? [$this->relation(), $this->relation().'.details'] : [$this->relation()];
    }

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

    /**
     * สีพื้นหลัง: รหัส hex หรือคำว่า transparent (เหมือนตัวเลือกสีพื้นหลังของแถว/คอลัมน์)
     *
     * @return array<string, mixed>
     */
    protected static function backgroundColor(string $label, string $default): array
    {
        return ['label' => $label, 'default' => $default, 'rules' => ['required', 'string', 'max:20', 'regex:/^(transparent|#[0-9a-fA-F]{3,8})$/']];
    }

    // ---------------------------------------------------------------- PageWidgetType

    public function rules(): array
    {
        $rules = array_map(fn (array $field) => $field['rules'], $this->fields());

        foreach ($this->detailFields() as $name => $field) {
            $rules[$name] = ['nullable', 'array'];

            foreach (Setting::selectedLanguages() as $lang) {
                $rules["{$name}.{$lang}"] = ['nullable', 'string', "max:{$field['max']}"];
            }
        }

        return $rules;
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

        foreach ($this->detailFields() as $name => $field) {
            $messages["{$name}.*.max"] = "{$field['label']}ต้องไม่เกิน {$field['max']} ตัวอักษร";
            $messages["{$name}.*.string"] = "{$field['label']} ไม่ถูกต้อง";
        }

        return $messages;
    }

    public function defaults(): array
    {
        $defaults = array_map(fn (array $field) => $field['default'], $this->fields());

        foreach (array_keys($this->detailFields()) as $name) {
            $defaults[$name] = array_fill_keys(Setting::selectedLanguages(), '');
        }

        return $defaults;
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
        $fields = $this->fields();
        // เฉพาะฟิลด์ที่เป็นคอลัมน์ของตารางตั้งค่า (ฟิลด์แยกภาษาไปอยู่ตาราง detail — ดู saveDetails)
        $values = $this->normalize(array_intersect_key($setting, $fields) + array_intersect_key($this->defaults(), $fields));
        $query = $model::where('id', $widgetId);

        if ($query->exists()) {
            $query->update($values + ['updated_by' => $actorId]);
        } else {
            $model::create(['id' => $widgetId, 'created_by' => $actorId] + $values);
        }

        $this->saveDetails($widgetId, $setting, $actorId);
    }

    /**
     * บันทึกฟิลด์แยกภาษาลงตาราง detail (ทีละภาษา — มีแถวอยู่แล้วให้ update ไม่มีให้ create; ค่าว่างเก็บเป็น null)
     *
     * @param  array<string, mixed>  $setting
     */
    private function saveDetails(int $widgetId, array $setting, ?int $actorId): void
    {
        $model = $this->detailModel();

        if ($model === null || $this->detailFields() === []) {
            return;
        }

        foreach (Setting::selectedLanguages() as $lang) {
            $values = [];

            foreach (array_keys($this->detailFields()) as $name) {
                $text = $setting[$name][$lang] ?? null;
                $values[$name] = is_string($text) && trim($text) !== '' ? trim($text) : null;
            }

            // composite key (id + lang) — ห้ามใช้ find()/save() ผ่าน model
            $query = $model::where('id', $widgetId)->where('lang', $lang);

            if ($query->exists()) {
                $query->update($values + ['updated_by' => $actorId]);
            } else {
                $model::create(['id' => $widgetId, 'lang' => $lang, 'status' => 'Y', 'created_by' => $actorId] + $values);
            }
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
                'nullstring' => $value ?? '',
                default => $value,
            };
        }

        if ($this->detailModel() !== null) {
            $details = $row->details->keyBy('lang');

            foreach (array_keys($this->detailFields()) as $name) {
                $map = [];

                foreach (Setting::selectedLanguages() as $lang) {
                    $map[$lang] = $details->get($lang)?->{$name} ?? '';
                }

                $values[$name] = $map;
            }
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
