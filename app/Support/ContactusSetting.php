<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * ทะเบียนตั้งค่าโมดูลติดต่อเรา (sys_setting group = "contactus") — ค่าเริ่มต้น + validation rules ของทุกคีย์
 * ใช้ทั้งหลังบ้าน (UpdateContactusSettingRequest / ContactusSettingController) และหน้าบ้าน (Front\Contactus\ContactusController)
 * เพิ่มคีย์ใหม่ = เพิ่มใน defaults() + rules() + ฟอร์ม `Pages/Admin/Contactus/Setting/Index.vue`
 *
 * ข้อมูลติดต่อจริง (ที่อยู่/เบอร์/อีเมล/social) ไม่ได้เก็บที่นี่ — อ่านจากตั้งค่าระบบกลุ่ม contact/social
 * (FrontLayoutData) ที่นี่เก็บแค่การแสดงผล: รูปแบบ, การจัดรูปแบบตัวอักษร, แผนที่ และแบบฟอร์มติดต่อ
 */
class ContactusSetting
{
    /**
     * รูปแบบการแสดงผล
     * stacked = เรียงลงมา (ข้อมูลติดต่อกึ่งกลาง → รูปแผนที่ → google map → แบบฟอร์ม)
     * split_info = แบ่งข้อมูลติดต่อ (ซ้ายข้อมูลติดต่อ | ขวาแผนที่ + google map แล้วแบบฟอร์มด้านล่าง)
     * half = ครึ่ง (ซ้ายข้อมูลติดต่อ + แผนที่ + google map | ขวาแบบฟอร์ม)
     */
    public const DISPLAY_TYPES = ['stacked', 'split_info', 'half'];

    /** ข้อความข้อมูลติดต่อที่จัดรูปแบบได้ — owner (ชื่อเจ้าของ) บังคับแสดงเสมอ จึงไม่มี show_owner */
    public const TEXT_PARTS = ['owner', 'address', 'phone', 'fax', 'mobile', 'email'];

    /**
     * ฟิลด์ของแบบฟอร์มติดต่อที่ตั้งค่าแสดง/บังคับกรอกได้ => [แสดง, บังคับกรอก] เริ่มต้น
     * ชื่อ-นามสกุล (fullname) แสดงและบังคับกรอกเสมอ จึงไม่อยู่ในรายการนี้
     */
    public const FORM_FIELDS = [
        'position' => ['N', 'N'],
        'company' => ['N', 'N'],
        'phone' => ['Y', 'N'],
        'email' => ['Y', 'Y'],
        'subject' => ['Y', 'Y'],
        'detail' => ['Y', 'Y'],
    ];

    private const COLOR_REGEX = '/^#[0-9a-fA-F]{3,8}$/';

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        $values = ['display_type' => 'split_info'];

        foreach (self::TEXT_PARTS as $part) {
            if ($part !== 'owner') {
                $values["show_{$part}"] = 'Y';
            }

            $values["{$part}_font_size"] = $part === 'owner' ? '24' : '16';
            $values["{$part}_font_family"] = PageTextStyle::DEFAULT_FONT;
            $values["{$part}_bold"] = $part === 'owner' ? 'Y' : 'N';
            $values["{$part}_color"] = $part === 'owner' ? '#000000' : '#374151';
        }

        $values += [
            'show_social' => 'Y',
            'show_map_image' => 'N',
            'map_image_id' => '',
            'show_google_map' => 'N',
            'latitude' => '',
            'longitude' => '',
            'show_form' => 'Y',
        ];

        foreach (self::FORM_FIELDS as $field => [$show, $required]) {
            $values["form_{$field}_show"] = $show;
            $values["form_{$field}_required"] = $required;
        }

        return $values;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function rules(): array
    {
        $flag = ['required', Rule::in(['Y', 'N'])];
        $rules = ['display_type' => ['required', Rule::in(self::DISPLAY_TYPES)]];

        foreach (self::TEXT_PARTS as $part) {
            if ($part !== 'owner') {
                $rules["show_{$part}"] = $flag;
            }

            $rules["{$part}_font_size"] = ['required', 'integer', 'between:'.PageTextStyle::FONT_SIZE_MIN.','.PageTextStyle::FONT_SIZE_MAX];
            $rules["{$part}_font_family"] = ['required', Rule::in(PageTextStyle::fontNames())];
            $rules["{$part}_bold"] = $flag;
            $rules["{$part}_color"] = ['required', 'string', 'max:20', 'regex:'.self::COLOR_REGEX];
        }

        $rules += [
            'show_social' => $flag,
            'show_map_image' => $flag,
            'map_image_id' => [
                'nullable', 'integer', 'required_if:show_map_image,Y',
                Rule::exists('file_info', 'id')->where('status', 'Y')->whereNull('deleted_at'),
            ],
            'show_google_map' => $flag,
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_if:show_google_map,Y'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_if:show_google_map,Y'],
            'show_form' => $flag,
        ];

        foreach (array_keys(self::FORM_FIELDS) as $field) {
            $rules["form_{$field}_show"] = $flag;
            $rules["form_{$field}_required"] = $flag;
        }

        return $rules;
    }

    /**
     * ค่าที่บันทึกไว้ทับค่าเริ่มต้น — ค่าที่ไม่ผ่าน rules (เช่น ค่าเก่าที่เลิกใช้) ย้อนกลับเป็นค่าเริ่มต้น
     * (ตรวจทีละคีย์ เฉพาะ rule ของคีย์นั้นเอง — ค่าว่างของคีย์ที่ไม่บังคับ เช่น พิกัด/รูปแผนที่ คงเป็นค่าว่าง)
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $rules = self::rules();
        $values = [];

        foreach (self::defaults() as $name => $default) {
            $value = (string) Setting::get('contactus', $name, $default);
            $values[$name] = Validator::make([$name => $value], [$name => $rules[$name]])->passes() ? $value : $default;
        }

        return $values;
    }

    /**
     * ฟิลด์ของแบบฟอร์มที่แสดงที่หน้าบ้าน เรียงตามลำดับในฟอร์ม => บังคับกรอกหรือไม่ (fullname อยู่ลำดับแรกเสมอ)
     *
     * @param  array<string, string>|null  $settings
     * @return array<string, bool>
     */
    public static function formFields(?array $settings = null): array
    {
        $settings ??= self::all();
        $fields = ['fullname' => true];

        foreach (array_keys(self::FORM_FIELDS) as $field) {
            if (($settings["form_{$field}_show"] ?? 'N') === 'Y') {
                $fields[$field] = ($settings["form_{$field}_required"] ?? 'N') === 'Y';
            }
        }

        return $fields;
    }

    /**
     * แบบฟอร์มติดต่อเปิดรับข้อมูลที่หน้าบ้านหรือไม่ — ต้องเปิดแสดงและตั้งค่า Turnstile CAPTCHA ครบ
     * (ไม่มี CAPTCHA = ไม่แสดงแบบฟอร์ม/ไม่รับข้อมูลเลย เพื่อความปลอดภัยจากสแปม/บอท)
     *
     * @param  array<string, string>|null  $settings
     */
    public static function formEnabled(?array $settings = null): bool
    {
        $settings ??= self::all();

        return ($settings['show_form'] ?? 'N') === 'Y' && Turnstile::configured();
    }
}
