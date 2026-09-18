/**
 * ตัวเลือกแบบเห็นภาพประกอบ (ไดอะแกรม SVG) ของฟิลด์พื้นหลัง CSS หน้า Intropage — เทียบเคียง
 * `utils/intropageDisplay.ts` แยกไฟล์ต่างหากจาก `utils/options.ts` เพราะมีคำอธิบายประกอบและใช้เฉพาะกับ
 * picker ของโมดูลนี้เท่านั้น
 */

/** ตัวเลือก "ไม่ระบุ" ร่วมของทุก picker ในไฟล์นี้ — ฟิลด์เหล่านี้ nullable ที่ DB (ไม่บังคับกรอก) */
const UNSET_STYLE = { value: '', label: 'ไม่ระบุ', description: 'ใช้ค่าเริ่มต้นของเบราว์เซอร์' };

/** CSS background-repeat (intropage_item_info.background_repeat) */
export const BACKGROUND_REPEAT_STYLES: { value: string; label: string; description: string }[] = [
    UNSET_STYLE,
    { value: 'repeat', label: 'ซ้ำเต็มพื้นที่', description: 'เรียงซ้ำทั้งแนวนอนและแนวตั้ง' },
    { value: 'no-repeat', label: 'ไม่ซ้ำ', description: 'แสดงรูปเดียว ไม่เรียงซ้ำ' },
    { value: 'repeat-x', label: 'ซ้ำแนวนอน', description: 'เรียงซ้ำตามแนวนอนเท่านั้น' },
    { value: 'repeat-y', label: 'ซ้ำแนวตั้ง', description: 'เรียงซ้ำตามแนวตั้งเท่านั้น' },
];

/** CSS background-size (intropage_item_info.background_size) */
export const BACKGROUND_SIZE_STYLES: { value: string; label: string; description: string }[] = [
    UNSET_STYLE,
    { value: 'auto', label: 'ขนาดจริง', description: 'แสดงตามขนาดจริงของรูป' },
    { value: 'cover', label: 'เต็มพื้นที่', description: 'ขยายเต็มพื้นที่ ตัดขอบรูปที่เกิน' },
    { value: 'contain', label: 'พอดีพื้นที่', description: 'ย่อ/ขยายให้พอดี ไม่ตัดรูป (มีขอบว่าง)' },
];

/** CSS background-attachment (intropage_item_info.background_attachment) */
export const BACKGROUND_ATTACHMENT_STYLES: { value: string; label: string; description: string }[] = [
    UNSET_STYLE,
    { value: 'scroll', label: 'เลื่อนตามเนื้อหา', description: 'พื้นหลังเลื่อนไปพร้อมเนื้อหาเวลาสกรอลล์' },
    { value: 'fixed', label: 'ตรึงกับจอ', description: 'พื้นหลังอยู่กับที่ เนื้อหาเลื่อนผ่านด้านหน้า' },
];

/**
 * CSS background-position preset ที่พบบ่อย (intropage_item_info.background_position) — เรียง 9 ตำแหน่ง
 * เป็น 3x3 ตามตำแหน่งจริงก่อน (ให้ grid 3 คอลัมน์เรียงตรงกับไดอะแกรม) แล้วค่อยต่อท้ายด้วย "ไม่ระบุ" เป็น
 * การ์ดที่ 10 แยกแถว — ถ้าเอา "ไม่ระบุ" ไว้ตัวแรกเหมือน picker อื่นจะทำให้ตำแหน่งจริงเลื่อนเยื้องจากไดอะแกรม
 */
export const BACKGROUND_POSITION_STYLES: { value: string; label: string; description: string }[] = [
    { value: 'top left', label: 'บนซ้าย', description: 'ชิดขอบบน-ซ้าย' },
    { value: 'top', label: 'บน', description: 'ชิดขอบบน กึ่งกลางแนวนอน' },
    { value: 'top right', label: 'บนขวา', description: 'ชิดขอบบน-ขวา' },
    { value: 'left', label: 'ซ้าย', description: 'ชิดขอบซ้าย กึ่งกลางแนวตั้ง' },
    { value: 'center', label: 'กึ่งกลาง', description: 'กึ่งกลางพอดี' },
    { value: 'right', label: 'ขวา', description: 'ชิดขอบขวา กึ่งกลางแนวตั้ง' },
    { value: 'bottom left', label: 'ล่างซ้าย', description: 'ชิดขอบล่าง-ซ้าย' },
    { value: 'bottom', label: 'ล่าง', description: 'ชิดขอบล่าง กึ่งกลางแนวนอน' },
    { value: 'bottom right', label: 'ล่างขวา', description: 'ชิดขอบล่าง-ขวา' },
    UNSET_STYLE,
];
