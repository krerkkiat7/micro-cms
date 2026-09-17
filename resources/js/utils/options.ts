/**
 * ตัวเลือกที่ใช้ร่วมกันหลายหน้าสำหรับ SearchableSelect.vue
 */

/** สถานะ Y/N มาตรฐานของระบบ (char(1) — ดู CLAUDE.md) ใช้กับฟิลด์สถานะทั่วทั้งระบบ */
export const STATUS_OPTIONS = [
    { value: 'Y', label: 'ใช้งาน' },
    { value: 'N', label: 'ไม่ใช้งาน' },
];

/** เหมือน STATUS_OPTIONS แต่เติมตัวเลือก "ทุกสถานะ" (value ว่าง) ไว้ก่อน — ใช้กับ dropdown กรองในหน้ารายการ */
export const STATUS_FILTER_OPTIONS = [{ value: '', label: 'ทุกสถานะ' }, ...STATUS_OPTIONS];
