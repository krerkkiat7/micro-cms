<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * ทะเบียนตั้งค่าโมดูล popup (sys_setting group = "popup") — ค่าเริ่มต้น + validation rules ของทุกคีย์
 * ใช้ทั้งหลังบ้าน (UpdatePopupSettingRequest / PopupSettingController) และหน้าบ้าน (PopupResolver)
 * เพิ่มคีย์ใหม่ = เพิ่มใน defaults() + rules() + ฟอร์ม `Pages/Admin/Popup/Setting/Index.vue`
 */
class PopupSetting
{
    /**
     * ลำดับการแสดงผลเมื่อมีหลาย popup ในหน้าเดียว — รายการแรกตามลำดับนี้จะอยู่บนสุดของกองที่ซ้อนกัน
     * publish_desc = วันที่เผยแพร่ใหม่สุด, publish_asc = เก่าสุด, sort_desc = ลำดับมากสุด, sort_asc = ลำดับน้อยสุด
     */
    public const DISPLAY_ORDERS = ['publish_desc', 'publish_asc', 'sort_desc', 'sort_asc'];

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'display_order' => 'publish_desc',
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function rules(): array
    {
        return [
            'display_order' => ['required', Rule::in(self::DISPLAY_ORDERS)],
        ];
    }

    /**
     * ค่าที่บันทึกไว้ทับค่าเริ่มต้น — ค่าที่ไม่ผ่าน rules (เช่น ค่าเก่าที่เลิกใช้) ย้อนกลับเป็นค่าเริ่มต้น
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $rules = self::rules();
        $values = [];

        foreach (self::defaults() as $name => $default) {
            $value = (string) Setting::get('popup', $name, $default);
            $values[$name] = Validator::make([$name => $value], [$name => $rules[$name]])->passes() ? $value : $default;
        }

        return $values;
    }

    /**
     * คอลัมน์ + ทิศทางการเรียงของ popup_item_info ตามตั้งค่าลำดับการแสดงผล
     *
     * @return array{0: string, 1: string}
     */
    public static function orderBy(): array
    {
        return match (self::all()['display_order']) {
            'publish_asc' => ['publish_date', 'asc'],
            'sort_desc' => ['sort_order', 'desc'],
            'sort_asc' => ['sort_order', 'asc'],
            default => ['publish_date', 'desc'],
        };
    }
}
