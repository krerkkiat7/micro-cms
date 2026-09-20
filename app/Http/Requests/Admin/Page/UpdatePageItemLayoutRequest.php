<?php

namespace App\Http\Requests\Admin\Page;

use App\Http\Requests\Admin\Page\Concerns\PageItemValidationRules;
use App\Models\PageItemColumn;
use App\Models\PageItemRow;
use App\Models\PageItemWidget;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
     * (validate ที่ boundary; แถวที่ไม่มี id = สร้างใหม่)
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
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rows.*.background_color.regex' => 'รูปแบบสีพื้นหลังของแถวไม่ถูกต้อง',
            'rows.*.columns.*.background_color.regex' => 'รูปแบบสีพื้นหลังของคอลัมน์ไม่ถูกต้อง',
            'rows.*.columns.*.column_size.between' => 'ความกว้างคอลัมน์ต้องอยู่ระหว่าง 1 - 12',
            'rows.*.columns.*.widgets.*.widget_type.in' => 'ประเภท Widget ไม่ถูกต้อง',
        ];
    }
}
