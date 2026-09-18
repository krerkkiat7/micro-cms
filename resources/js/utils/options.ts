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

/** เป้าหมายเปิดลิงก์ของป้ายโฆษณา (banner_item_info.link_target) */
export const LINK_TARGET_OPTIONS = [
    { value: '_self', label: 'เปิดในหน้าต่างเดิม' },
    { value: '_blank', label: 'เปิดในแท็บใหม่' },
];

/** background-repeat มาตรฐาน CSS (intropage_item_info.background_repeat) */
export const BACKGROUND_REPEAT_OPTIONS = [
    { value: 'repeat', label: 'ซ้ำเต็มพื้นที่ (repeat)' },
    { value: 'no-repeat', label: 'ไม่ซ้ำ (no-repeat)' },
    { value: 'repeat-x', label: 'ซ้ำแนวนอน (repeat-x)' },
    { value: 'repeat-y', label: 'ซ้ำแนวตั้ง (repeat-y)' },
];

/** background-size มาตรฐาน CSS (intropage_item_info.background_size) */
export const BACKGROUND_SIZE_OPTIONS = [
    { value: 'auto', label: 'ขนาดจริง (auto)' },
    { value: 'cover', label: 'เต็มพื้นที่ (cover)' },
    { value: 'contain', label: 'พอดีพื้นที่ (contain)' },
];

/** background-attachment มาตรฐาน CSS (intropage_item_info.background_attachment) */
export const BACKGROUND_ATTACHMENT_OPTIONS = [
    { value: 'scroll', label: 'เลื่อนตามเนื้อหา (scroll)' },
    { value: 'fixed', label: 'ตรึงกับจอ (fixed)' },
];

/** background-position preset ที่พบบ่อย (intropage_item_info.background_position) */
export const BACKGROUND_POSITION_OPTIONS = [
    { value: 'center', label: 'กึ่งกลาง' },
    { value: 'top', label: 'บน' },
    { value: 'bottom', label: 'ล่าง' },
    { value: 'left', label: 'ซ้าย' },
    { value: 'right', label: 'ขวา' },
    { value: 'top left', label: 'บนซ้าย' },
    { value: 'top right', label: 'บนขวา' },
    { value: 'bottom left', label: 'ล่างซ้าย' },
    { value: 'bottom right', label: 'ล่างขวา' },
];
