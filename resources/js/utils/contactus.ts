/**
 * ค่าคงที่/ชนิดข้อมูลของโมดูลติดต่อเรา — ต้องตรงกับ App\Support\ContactusSetting และ App\Models\ContactusItem ฝั่ง backend
 */

export type ContactusDisplayType = 'stacked' | 'split_info' | 'half';

export const CONTACTUS_DISPLAY_TYPE_OPTIONS: { value: ContactusDisplayType; label: string; description: string }[] = [
    { value: 'stacked', label: 'แบบเรียงลงมา', description: 'ข้อมูลติดต่อ (กึ่งกลาง) → รูปแผนที่ → Google Map → แบบฟอร์ม' },
    { value: 'split_info', label: 'แบบแบ่งข้อมูลติดต่อ', description: 'ซ้ายข้อมูลติดต่อ ขวาแผนที่ + Google Map แล้วแบบฟอร์มด้านล่าง' },
    { value: 'half', label: 'แบบครึ่ง', description: 'ซ้ายข้อมูลติดต่อ + แผนที่ + Google Map ขวาแบบฟอร์ม' },
];

/** ข้อความข้อมูลติดต่อที่จัดรูปแบบได้ (owner = ชื่อเจ้าของ บังคับแสดง) */
export const CONTACTUS_TEXT_PARTS: { part: string; label: string; toggleable: boolean }[] = [
    { part: 'owner', label: 'ชื่อเจ้าของ', toggleable: false },
    { part: 'address', label: 'ที่อยู่', toggleable: true },
    { part: 'phone', label: 'เบอร์ติดต่อ', toggleable: true },
    { part: 'fax', label: 'เบอร์แฟกซ์', toggleable: true },
    { part: 'mobile', label: 'เบอร์มือถือ', toggleable: true },
    { part: 'email', label: 'อีเมล', toggleable: true },
];

/** ฟิลด์ของแบบฟอร์มติดต่อที่ตั้งค่าแสดง/บังคับกรอกได้ (ชื่อ - นามสกุล แสดงและบังคับกรอกเสมอ) */
export const CONTACTUS_FORM_FIELDS: { field: string; label: string }[] = [
    { field: 'position', label: 'ตำแหน่ง' },
    { field: 'company', label: 'บริษัท' },
    { field: 'phone', label: 'เบอร์ติดต่อ' },
    { field: 'email', label: 'อีเมล' },
    { field: 'subject', label: 'หัวข้อ' },
    { field: 'detail', label: 'รายละเอียด' },
];

export type ContactusProcessStatus = 'unread' | 'read' | 'considering' | 'done';

export const CONTACTUS_PROCESS_STATUS_OPTIONS: { value: ContactusProcessStatus; label: string }[] = [
    { value: 'unread', label: 'ยังไม่ได้อ่าน' },
    { value: 'read', label: 'อ่านแล้ว' },
    { value: 'considering', label: 'พิจารณา' },
    { value: 'done', label: 'เสร็จสิ้น' },
];

/** สีของ pill สถานะในหน้ารายการ/แก้ไข */
export const CONTACTUS_PROCESS_STATUS_CLASS: Record<ContactusProcessStatus, string> = {
    unread: 'bg-red-50 text-red-700 ring-red-600/20',
    read: 'bg-blue-50 text-blue-700 ring-blue-600/20',
    considering: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    done: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
};

export function contactusProcessStatusLabel(value: string): string {
    return CONTACTUS_PROCESS_STATUS_OPTIONS.find((o) => o.value === value)?.label ?? value;
}
