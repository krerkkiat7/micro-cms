<?php

namespace App\Http\Requests\Admin\Intropage\Concerns;

use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * กฎ validation ที่ใช้ร่วมกันระหว่าง Store/UpdateIntropageItemRequest — เทียบเคียง BannerItemValidationRules
 * (info/detail) + ArticleItemValidationRules::partRules() (โครงปุ่มแบบแบ่งอาเรย์ เข้มงวดแค่ button_type)
 */
trait IntropageItemValidationRules
{
    private const DISPLAY_TYPES = ['image', 'vdo', 'vdourl', 'youtubeurl'];

    private const DISPLAY_SIZES = [
        'screen_100', 'screen_75', 'screen_50', 'screen_25',
        'container_100', 'container_75', 'container_50', 'container_25',
    ];

    private const YOUTUBE_URL_REGEX = '#^https?://(www\.)?(youtube\.com/watch\?v=|youtube\.com/embed/|youtu\.be/)[\w-]+#i';

    /**
     * กฎของฟิลด์ข้อมูลร่วม (ไม่แยกภาษา ไม่รวมปุ่ม)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function commonRules(): array
    {
        return [
            'background_color' => ['nullable', 'string', 'max:20'],
            'background_image_id' => [
                'nullable', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'background_repeat' => ['nullable', Rule::in(['repeat', 'no-repeat', 'repeat-x', 'repeat-y'])],
            'background_size' => ['nullable', Rule::in(['auto', 'cover', 'contain'])],
            'background_attachment' => ['nullable', Rule::in(['scroll', 'fixed'])],
            'background_position' => ['nullable', 'string', 'max:50'],

            'display_type' => ['required', Rule::in(self::DISPLAY_TYPES)],
            'display_size' => ['required', Rule::in(self::DISPLAY_SIZES)],
            'image_file_id' => [
                'nullable', 'required_if:display_type,image', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'vdo_file_id' => [
                'nullable', 'required_if:display_type,vdo', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'vdo_url' => [
                'nullable', 'required_if:display_type,vdourl', 'required_if:display_type,youtubeurl',
                'string', 'max:500',
                Rule::when(
                    $this->input('display_type') === 'youtubeurl',
                    ['regex:'.self::YOUTUBE_URL_REGEX],
                ),
            ],

            'show_button' => ['required', Rule::in(['Y', 'N'])],

            'publish_date' => ['required', 'date'],
            'publish_down' => ['required', 'date', 'after:publish_date'],

            'status' => ['required', Rule::in(['Y', 'N'])],
        ];
    }

    /**
     * กฎของฟิลด์แยกภาษา (detail.<lang>.*) — required เฉพาะ "ชื่อ" ของภาษาหลักเท่านั้น (site.lang_default)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function languageDetailRules(): array
    {
        $defaultLang = Setting::defaultLanguage();
        $rules = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["detail.{$lang}.title"] = [$lang === $defaultLang ? 'required' : 'nullable', 'string', 'max:500'];
            $rules["detail.{$lang}.detail"] = ['nullable', 'string'];
        }

        return $rules;
    }

    /**
     * กฎของปุ่ม (buttons.*) — เข้มงวดเท่า button_type เท่านั้น ที่เหลือ nullable (เทียบเคียง
     * ArticleItemValidationRules::partRules()) ส่วนกติกา "ต้องมีปุ่ม home แถวเดียว" เช็กใน withValidator()
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function buttonRules(): array
    {
        return [
            'buttons' => ['required', 'array', 'min:1'],
            'buttons.*.button_type' => ['required', Rule::in(['home', 'other'])],
            'buttons.*.button_display_type' => ['nullable', Rule::in(['text', 'image'])],
            'buttons.*.background_color' => ['nullable', 'string', 'max:10'],
            'buttons.*.text_color' => ['nullable', 'string', 'max:10'],
            'buttons.*.button_image_id' => [
                'nullable', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'buttons.*.url' => ['nullable', 'string', 'max:500'],
            'buttons.*.link_target' => ['nullable', Rule::in(['_self', '_blank'])],
            'buttons.*.texts' => ['nullable', 'array'],
        ];
    }

    /**
     * ต้องมีปุ่ม button_type = home อยู่พอดี 1 ปุ่มเสมอ (ฝั่ง frontend ป้องกันการลบปุ่ม home อยู่แล้ว
     * แต่ backend ต้อง guard เอง — ตาม principle validate ที่ boundary)
     */
    protected function ensureSingleHomeButton(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $buttons = collect($this->input('buttons', []));
            $homeCount = $buttons->where('button_type', 'home')->count();

            if ($homeCount !== 1) {
                $validator->errors()->add('buttons', 'ต้องมีปุ่ม "เข้าหน้าแรก" (home) อยู่พอดี 1 ปุ่มเสมอ');
            }
        });
    }
}
