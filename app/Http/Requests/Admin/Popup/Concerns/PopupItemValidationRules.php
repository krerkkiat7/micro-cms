<?php

namespace App\Http\Requests\Admin\Popup\Concerns;

use App\Models\PopupItemInfo;
use App\Models\PopupItemPart;
use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * กฎ validation ที่ใช้ร่วมกันระหว่าง Store/UpdatePopupItemRequest (ดู docs/PRD-popup.md §2)
 */
trait PopupItemValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $yesNo = ['required', Rule::in(['Y', 'N'])];

        $rules = [
            'name' => ['required', 'string', 'max:250'],
            'display_type' => ['required', Rule::in(PopupItemInfo::DISPLAY_TYPES)],
            'show_dismiss_today' => $yesNo,
            'show_arrows' => $yesNo,
            'show_dots' => $yesNo,
            'autoplay' => $yesNo,
            'slide_interval' => ['required', 'integer', 'min:1', 'max:120'],
            'slide_speed' => ['required', 'integer', 'min:100', 'max:10000'],
            'menu_mode' => ['required', Rule::in(PopupItemInfo::MENU_MODES)],
            'menu_ids' => ['nullable', 'array', 'required_if:menu_mode,selected'],
            'menu_ids.*' => [
                'integer', 'distinct',
                Rule::exists('front_menu_info', 'id')->where(fn ($query) => $query
                    ->whereIn('menu_type', PopupItemInfo::MENU_TYPES)
                    ->whereNull('deleted_at')),
            ],
            'publish_date' => ['required', 'date'],
            'publish_down' => ['nullable', 'date', 'after:publish_date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => $yesNo,

            'parts' => ['required', 'array', 'min:1'],
            'parts.*.part_type' => ['required', Rule::in(PopupItemPart::TYPES)],
            'parts.*.image_id' => [
                'nullable', 'integer', 'required_unless:parts.*.part_type,text',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'parts.*.image_size' => ['required', Rule::in(PopupItemPart::IMAGE_SIZES)],
            'parts.*.url' => ['nullable', 'string', 'max:500'],
            'parts.*.link_target' => ['required', Rule::in(['_self', '_blank'])],
            'parts.*.status' => $yesNo,
            'parts.*.detail' => ['nullable', 'array'],
        ];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["parts.*.detail.{$lang}"] = ['nullable', 'string'];
        }

        return $rules;
    }

    /**
     * ตรวจเพิ่มเติมที่เขียนเป็น rule ธรรมดาไม่ได้
     * - ต้องมี part ที่แสดง (status = Y) อย่างน้อย 1 รายการ
     * - part ที่มีข้อความ ต้องกรอกข้อความของภาษาหลัก (rich text ว่าง เช่น "<p></p>" นับเป็นว่าง)
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $parts = $this->input('parts');

                if (! is_array($parts) || $parts === []) {
                    return;
                }

                $hasShown = collect($parts)->contains(fn ($part) => is_array($part) && ($part['status'] ?? null) === 'Y');

                if (! $hasShown) {
                    $validator->errors()->add('parts', 'ต้องมีข้อมูลที่แสดงอย่างน้อย 1 รายการ');
                }

                $defaultLang = Setting::defaultLanguage();

                foreach ($parts as $index => $part) {
                    if (! is_array($part) || ! in_array($part['part_type'] ?? null, ['image_text', 'text'], true)) {
                        continue;
                    }

                    $text = (string) ($part['detail'][$defaultLang] ?? '');

                    if (trim(html_entity_decode(strip_tags($text))) === '') {
                        $validator->errors()->add("parts.{$index}.detail.{$defaultLang}", "กรุณากรอกข้อความ ({$defaultLang})");
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อ',
            'publish_date.required' => 'กรุณากรอกวันที่เผยแพร่',
            'publish_down.after' => 'วันที่ปิดการเผยแพร่ต้องมากกว่าวันที่เผยแพร่',
            'menu_ids.required_if' => 'กรุณาเลือกเมนูที่แสดงอย่างน้อย 1 เมนู',
            'menu_ids.*.exists' => 'เมนูที่เลือกไม่ถูกต้อง',
            'parts.required' => 'ต้องมีข้อมูลอย่างน้อย 1 รายการ',
            'parts.min' => 'ต้องมีข้อมูลอย่างน้อย 1 รายการ',
            'parts.*.image_id.required_unless' => 'กรุณาเลือกรูปภาพ',
            'parts.*.image_id.exists' => 'รูปภาพที่เลือกไม่ถูกต้อง',
            'slide_interval.min' => 'เวลาที่ค้างต้องอย่างน้อย 1 วินาที',
            'slide_speed.min' => 'ความเร็วการสไลด์ต้องอย่างน้อย 100 มิลลิวินาที',
        ];
    }
}
