<?php

namespace App\Support\PageWidget;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * ประเภท widget ของโมดูล Page (เช่น slideshowbanner) — 1 ประเภทมีตารางตั้งค่าของตัวเอง `page_item_widget_<ประเภท>`
 * (PK = `page_item_widget.id`) แทน JSON ก้อนเดียว คลาสของประเภทรวมทุกอย่างของประเภทนั้นไว้ที่เดียว: กฎ validation, ค่าเริ่มต้น,
 * การบันทึก/ลบ, การแปลงเป็นข้อมูลส่งหน้าจอ, ตัวเลือกประกอบฟอร์ม และข้อมูลตัวอย่าง (preview)
 * เพิ่มประเภทใหม่ = สร้างคลาสที่ implements interface นี้ + ลงทะเบียนใน PageWidgetRegistry + ทำ FE ให้ตรง (utils/pageWidget.ts)
 * ดู docs/PRD-page.md
 */
interface PageWidgetType
{
    /** ชื่อประเภทที่เก็บใน `page_item_widget.widget_type` (ไม่เกิน 20 ตัวอักษร) */
    public function type(): string;

    /** ชื่อ relation บน PageItemWidget ที่ชี้ไปยังแถวตั้งค่าของประเภทนี้ (ไว้ eager load) */
    public function relation(): string;

    /**
     * กฎ validation ของ `setting` (คีย์สัมพัทธ์กับ setting เช่น `sort_by` ไม่มี prefix)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array;

    /**
     * ข้อความ error ของ rules() (ภาษาไทย)
     *
     * @return array<string, string>
     */
    public function messages(): array;

    /**
     * กฎ validation เฉพาะค่าที่มีผลต่อ "ข้อมูลตัวอย่าง" (ใช้กับ endpoint preview ที่ไม่ต้องการค่าตั้งค่าครบทุกตัว)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function previewRules(): array;

    /**
     * ค่าตั้งค่าเริ่มต้นของ widget ใหม่ (รูปแบบเดียวกับ toArray) — ต้องตรงกับ defaultSetting() ใน utils/pageWidget.ts
     *
     * @return array<string, mixed>
     */
    public function defaults(): array;

    /**
     * บันทึกค่าตั้งค่าของ widget (สร้างแถวถ้ายังไม่มี ไม่งั้น update) — รับเฉพาะคีย์ที่ประเภทนี้รู้จัก
     *
     * @param  array<string, mixed>  $setting
     */
    public function save(int $widgetId, array $setting, ?int $actorId): void;

    /**
     * แปลงแถวตั้งค่าเป็น array ส่งหน้าจอ (ไม่มีแถว = ค่าเริ่มต้น)
     *
     * @return array<string, mixed>
     */
    public function toArray(?Model $row): array;

    /**
     * soft delete แถวตั้งค่าของ widget ที่ถูกลบ (เก็บ deleted_by)
     *
     * @param  list<int>  $widgetIds
     */
    public function softDelete(array $widgetIds, ?int $actorId): void;

    /**
     * ข้อมูลประกอบฟอร์มตั้งค่าของประเภทนี้ (เช่น รายการหมวดหมู่ให้เลือก) — คีย์ระดับบนต้องไม่ซ้ำกับประเภทอื่น
     *
     * @return array<string, mixed>
     */
    public function options(): array;

    /**
     * ข้อมูลตัวอย่างที่ widget จะแสดง (ภาษาหลัก) ตามค่าตั้งค่า — ไม่มี URL ปลายทาง (ตัวอย่างในหน้าโครงสร้างกดลิงก์ไม่ได้)
     *
     * @param  array<string, mixed>  $setting
     * @return list<array<string, mixed>>
     */
    public function preview(array $setting): array;
}
