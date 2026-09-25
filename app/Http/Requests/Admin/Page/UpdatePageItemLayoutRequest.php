<?php

namespace App\Http\Requests\Admin\Page;

use App\Http\Requests\Admin\Page\Concerns\PageItemValidationRules;
use App\Models\PageItemColumn;
use App\Models\PageItemRow;
use App\Models\PageItemWidget;
use App\Support\PageSpacing;
use App\Support\PageWidget\PageWidgetRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

class UpdatePageItemLayoutRequest extends FormRequest
{
    use PageItemValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->layoutRules();
    }

    /**
     * id ของแถว/คอลัมน์/widget ที่ส่งมาต้องเป็นของหน้านี้จริงเท่านั้น — กันการแก้ข้อมูลของหน้าอื่นโดยส่ง id ปลอมมา
     * (validate ที่ boundary; แถวที่ไม่มี id = สร้างใหม่) และตรวจ widget: ประเภทของ widget เดิมเปลี่ยนไม่ได้ +
     * `setting` ต้องผ่านกฎของประเภทนั้น (แต่ละประเภทมีกฎต่างกัน จึงตรวจตรงนี้แทน rules())
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $pageId = (int) $this->route('item');
            $rows = collect($this->input('rows', []));
            $columns = $rows->flatMap(fn ($row) => $row['columns'] ?? []);
            $widgets = $columns->flatMap(fn ($column) => $column['widgets'] ?? []);

            $rowIds = PageItemRow::where('page_item_info_id', $pageId)->pluck('id');
            $columnIds = PageItemColumn::whereIn('page_item_row_id', $rowIds)->pluck('id');
            $widgetIds = PageItemWidget::whereIn('page_item_column_id', $columnIds)->pluck('id');

            $checks = [
                ['แถว', $rows, $rowIds],
                ['คอลัมน์', $columns, $columnIds],
                ['widget', $widgets, $widgetIds],
            ];

            foreach ($checks as [$label, $sent, $owned]) {
                $foreign = $sent->pluck('id')->filter()->diff($owned);

                if ($foreign->isNotEmpty()) {
                    $validator->errors()->add('rows', "พบ{$label}ที่ไม่ใช่ของหน้านี้ กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง");
                }
            }

            $this->validateWidgets($validator);
        });
    }

    private function validateWidgets(Validator $validator): void
    {
        $storedTypes = PageItemWidget::whereIn('id', collect($this->input('rows', []))
            ->flatMap(fn ($row) => $row['columns'] ?? [])
            ->flatMap(fn ($column) => $column['widgets'] ?? [])
            ->pluck('id')->filter())
            ->pluck('widget_type', 'id');

        foreach ($this->input('rows', []) as $rowIndex => $row) {
            foreach ($row['columns'] ?? [] as $columnIndex => $column) {
                foreach ($column['widgets'] ?? [] as $widgetIndex => $widget) {
                    $label = 'Widget (แถวที่ '.($rowIndex + 1).' คอลัมน์ที่ '.($columnIndex + 1).' ลำดับที่ '.($widgetIndex + 1).')';
                    $key = "rows.{$rowIndex}.columns.{$columnIndex}.widgets.{$widgetIndex}";
                    $storedType = isset($widget['id']) ? $storedTypes->get($widget['id']) : null;

                    if ($storedType !== null && $storedType !== $widget['widget_type']) {
                        $validator->errors()->add("{$key}.widget_type", "{$label}: เปลี่ยนประเภท Widget ที่บันทึกแล้วไม่ได้");

                        continue;
                    }

                    $type = PageWidgetRegistry::find($widget['widget_type']);

                    if ($type === null) {
                        continue; // ประเภทเดิม (legacy) ไม่มีค่าตั้งค่าให้ตรวจ
                    }

                    $setting = ValidatorFacade::make($widget['setting'] ?? [], $type->rules(), $type->messages());

                    foreach ($setting->errors()->messages() as $field => $messages) {
                        foreach ($messages as $message) {
                            $validator->errors()->add("{$key}.setting.{$field}", "{$label}: {$message}");
                        }
                    }
                }
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $padding = 'ระยะขอบด้านในต้องเป็นตัวเลข '.PageSpacing::PADDING_MIN.' - '.PageSpacing::PADDING_MAX.' px';
        $gap = 'ระยะห่างระหว่างคอลัมน์ต้องเป็นตัวเลข '.PageSpacing::GAP_MIN.' - '.PageSpacing::GAP_MAX.' px';
        $spacing = [];

        foreach (['rows.*.', 'rows.*.columns.*.', 'rows.*.columns.*.widgets.*.'] as $prefix) {
            foreach (PageSpacing::SIDES as $side) {
                $spacing["{$prefix}padding_{$side}.integer"] = $padding;
                $spacing["{$prefix}padding_{$side}.between"] = $padding;
            }
        }

        foreach (['gap_x', 'gap_y'] as $field) {
            $spacing["rows.*.{$field}.integer"] = $gap;
            $spacing["rows.*.{$field}.between"] = $gap;
        }

        return $spacing + [
            'rows.*.background_color.regex' => 'รูปแบบสีพื้นหลังของแถวไม่ถูกต้อง',
            'rows.*.columns.*.background_color.regex' => 'รูปแบบสีพื้นหลังของคอลัมน์ไม่ถูกต้อง',
            'rows.*.columns.*.widgets.*.background_color.regex' => 'รูปแบบสีพื้นหลังของ widget ไม่ถูกต้อง',
            'rows.*.columns.*.column_size.between' => 'ความกว้างคอลัมน์ต้องอยู่ระหว่าง 1 - 12',
            'rows.*.columns.*.widgets.*.widget_type.in' => 'ประเภท Widget ไม่ถูกต้อง',
        ];
    }
}
