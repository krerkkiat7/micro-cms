/**
 * ตัวเลือกแบบเห็นภาพประกอบ (ไดอะแกรม SVG) ของหน้า Intropage — เทียบเคียง `utils/articleParts.ts`
 * (`IMAGES_DISPLAY_TYPES`) แยกไฟล์ต่างหากจาก `utils/options.ts` เพราะมีคำอธิบายประกอบยาวกว่า
 * dropdown ทั่วไป และใช้เฉพาะกับ picker ของโมดูลนี้เท่านั้น
 */

/** ประเภทการแสดงผลสื่อหลักของหน้า Intropage (intropage_item_info.display_type) */
export const INTROPAGE_DISPLAY_TYPES: { value: string; label: string; description: string }[] = [
    { value: 'image', label: 'รูปภาพ', description: 'แสดงรูปภาพเดี่ยวเป็นสื่อหลัก' },
    { value: 'vdo', label: 'ไฟล์วิดีโอ', description: 'อัพโหลดไฟล์วิดีโอจากโมดูลจัดการไฟล์' },
    { value: 'vdourl', label: 'URL วิดีโอ', description: 'ลิงก์ไฟล์วิดีโอจากที่อื่น (เช่น .mp4)' },
    { value: 'youtubeurl', label: 'YouTube URL', description: 'ฝังวิดีโอจาก YouTube ด้วยลิงก์' },
];

/**
 * ขนาดการแสดงผลสื่อหลักเทียบกับความกว้างจอ/container (intropage_item_info.display_size)
 * screen_* = กว้างเทียบกับทั้งหน้าจอ, container_* = กว้างเทียบกับ container เนื้อหา (แคบกว่าจอ)
 */
export const INTROPAGE_DISPLAY_SIZES: { value: string; label: string; description: string }[] = [
    { value: 'screen_100', label: 'เต็มความกว้างหน้าจอ', description: 'กว้างเท่าหน้าจอทั้งหมด' },
    { value: 'screen_75', label: '75% ของหน้าจอ', description: 'กว้าง 3 ใน 4 ของหน้าจอ' },
    { value: 'screen_50', label: '50% ของหน้าจอ', description: 'กว้างครึ่งหนึ่งของหน้าจอ' },
    { value: 'screen_25', label: '25% ของหน้าจอ', description: 'กว้าง 1 ใน 4 ของหน้าจอ' },
    { value: 'container_100', label: 'เต็มความกว้าง container', description: 'กว้างเท่า container เนื้อหา' },
    { value: 'container_75', label: '75% ของ container', description: 'กว้าง 3 ใน 4 ของ container' },
    { value: 'container_50', label: '50% ของ container', description: 'กว้างครึ่งหนึ่งของ container' },
    { value: 'container_25', label: '25% ของ container', description: 'กว้าง 1 ใน 4 ของ container' },
];
