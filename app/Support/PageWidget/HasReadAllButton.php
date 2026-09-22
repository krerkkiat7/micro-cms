<?php

namespace App\Support\PageWidget;

/**
 * ส่วนตั้งค่าปุ่ม "อ่านทั้งหมด" ที่ widget กลุ่ม article ใช้ร่วมกัน (Slideset จาก article, Grid จาก article) — เปิด/ปิด, ตำแหน่ง
 * (บน/ล่าง × ซ้าย/กึ่งกลาง/ขวา — เทียบกับกรอบของ widget เอง), ข้อความแทนแยกภาษา (ตาราง `*_detail`), ไอคอน, รูปแบบ (ปุ่ม/ลิงก์ข้อความ/ปุ่มมนใหญ่),
 * ตัวอักษร (ขนาด/ฟอนต์/สี) + สีพื้นหลัง (เฉพาะแบบปุ่ม/ปุ่มมนใหญ่), ลิงก์ปลายทาง + เป้าหมายการเปิดลิงก์ ต้องใช้กับคลาสที่ extends SettingsWidget
 * (ใช้ตัวช่วยสร้างฟิลด์ flag/choice/color/fontSize/fontFamily) และ CategoryListWidget (ใช้ LINK_TARGETS) ตัวเลือก/ค่าเริ่มต้นต้องตรงกับ utils/readAllButton.ts
 */
trait HasReadAllButton
{
    /** ตำแหน่งปุ่ม "อ่านทั้งหมด" เทียบกับกรอบของ widget (บน/ล่าง × ซ้าย/กึ่งกลาง/ขวา) */
    public const READ_ALL_POSITIONS = ['top_left', 'top_center', 'top_right', 'bottom_left', 'bottom_center', 'bottom_right'];

    /** ไอคอนที่แสดงร่วมกับข้อความปุ่ม (`none` = ไม่แสดง) — ตัวเลือกและชื่อที่หน้าจอต้องตรงกัน */
    public const READ_ALL_ICONS = ['none', 'plus', 'plus_circle', 'arrow_right', 'arrow_right_circle', 'chevron_right', 'chevron_right_circle', 'arrow_up_right'];

    public const READ_ALL_ICON_POSITIONS = ['before', 'after'];

    /** รูปแบบของปุ่ม: ปุ่ม / ลิงก์ข้อความ / ปุ่มมนใหญ่ (คล้ายวงรี) */
    public const READ_ALL_STYLES = ['button', 'link', 'pill'];

    /** สีเริ่มต้นของปุ่ม: ตัวหนังสือขาวบนพื้นเทาเข้ม (gray-800) */
    public const READ_ALL_BUTTON_TEXT = '#FFFFFF';

    public const READ_ALL_BUTTON_BACKGROUND = '#1F2937';

    /** ลิงก์ปลายทางที่รับ: URL เต็ม, path ภายในเว็บ (ขึ้นต้น /), anchor (#), mailto:, tel: */
    private const READ_ALL_URL_REGEX = '/^(https?:\/\/|\/|#|mailto:|tel:)\S*$/i';

    /**
     * ฟิลด์แยกภาษาของปุ่ม "อ่านทั้งหมด" — ใส่ค่านี้เป็น detailFields() ของคลาสที่ใช้ trait นี้
     *
     * @return array<string, array{label: string, max: int}>
     */
    protected function readAllDetailFields(): array
    {
        return ['read_all_text' => ['label' => 'ข้อความของปุ่มอ่านทั้งหมด', 'max' => 100]];
    }

    /**
     * ฟิลด์ตั้งค่าทั้งหมดของปุ่ม "อ่านทั้งหมด" (เปิด/ปิด, ตำแหน่ง, ไอคอน, รูปแบบ, ตัวอักษร, พื้นหลัง, ลิงก์ปลายทาง)
     *
     * @return array<string, array<string, mixed>>
     */
    protected function readAllFields(): array
    {
        return [
            'show_read_all' => self::flag('การแสดงปุ่มอ่านทั้งหมด', 'N'),
            'read_all_position' => self::choice('ตำแหน่งของปุ่มอ่านทั้งหมด', 'bottom_center', self::READ_ALL_POSITIONS),
            'read_all_icon' => self::choice('ไอคอนของปุ่มอ่านทั้งหมด', 'arrow_right', self::READ_ALL_ICONS),
            'read_all_icon_position' => self::choice('ตำแหน่งไอคอนของปุ่มอ่านทั้งหมด', 'after', self::READ_ALL_ICON_POSITIONS),
            'read_all_style' => self::choice('รูปแบบของปุ่มอ่านทั้งหมด', 'button', self::READ_ALL_STYLES),
            // ตัวอักษร (ทุกรูปแบบ) + สีพื้นหลัง (ใช้เฉพาะแบบปุ่ม/ปุ่มมนใหญ่) — default ตรงกับปุ่มสีเทาเข้มตัวหนังสือขาว
            // (แบบลิงก์ข้อความหน้าจอจะสลับสีตัวอักษรเป็นสีน้ำเงินให้เมื่อยังเป็นค่าเริ่มต้นอยู่)
            'read_all_font_size' => self::fontSize('ขนาดตัวอักษรของปุ่มอ่านทั้งหมด', 14),
            'read_all_font_family' => self::fontFamily('ฟอนต์ของปุ่มอ่านทั้งหมด'),
            'read_all_color' => self::color('สีตัวอักษรของปุ่มอ่านทั้งหมด', self::READ_ALL_BUTTON_TEXT),
            'read_all_background' => self::color('สีพื้นหลังของปุ่มอ่านทั้งหมด', self::READ_ALL_BUTTON_BACKGROUND),
            'read_all_url' => [
                'label' => 'ลิงก์ปลายทางของปุ่มอ่านทั้งหมด', 'default' => '', 'type' => 'nullstring',
                // จำเป็นต้องกรอกเมื่อแสดงปุ่ม (ภายหลังอาจเลือกจากเมนูหน้าบ้านแทนการกรอก URL)
                'rules' => ['nullable', 'string', 'max:500', 'regex:'.self::READ_ALL_URL_REGEX, 'required_if:show_read_all,Y'],
                'messages' => [
                    'required_if' => 'กรุณากรอกลิงก์ปลายทางของปุ่มอ่านทั้งหมด (ต้องกรอกเมื่อเปิดแสดงปุ่ม)',
                    'regex' => 'ลิงก์ปลายทางของปุ่มอ่านทั้งหมดต้องขึ้นต้นด้วย http://, https:// หรือ / (หรือ #, mailto:, tel:)',
                    'max' => 'ลิงก์ปลายทางของปุ่มอ่านทั้งหมดต้องไม่เกิน 500 ตัวอักษร',
                ],
            ],
            'read_all_link_target' => self::choice('เป้าหมายการเปิดลิงก์ของปุ่มอ่านทั้งหมด', '_self', self::LINK_TARGETS),
        ];
    }

    /**
     * ตัด whitespace ของ read_all_url แล้วเก็บเป็น null เมื่อว่าง — เรียกจาก normalize() ของคลาสที่ใช้ trait นี้
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function normalizeReadAllUrl(array $values): array
    {
        $url = $values['read_all_url'] ?? null;
        $values['read_all_url'] = is_string($url) && trim($url) !== '' ? trim($url) : null;

        return $values;
    }
}
