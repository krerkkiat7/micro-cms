import { ArrowRight, ArrowUpRight, ChevronRight, CircleArrowRight, CircleChevronRight, CirclePlus, Plus } from 'lucide-vue-next';
import type { Component } from 'vue';

/**
 * ปุ่ม "อ่านทั้งหมด" ของ widget Slideset จาก article — ตัวเลือกตำแหน่ง/ไอคอน/รูปแบบ (ต้องตรงกับค่าคงที่ READ_ALL_* ใน SlidesetArticleWidget ฝั่ง backend)
 * ใช้ทั้งในฟอร์มตั้งค่า (การ์ดเลือกให้เห็นตัวอย่างและชื่อ) และตัวอย่างในหน้าโครงสร้าง (ReadAllButton.vue)
 */

/** ข้อความมาตรฐานเมื่อไม่ได้กรอกข้อความแทน */
export const READ_ALL_DEFAULT_TEXT = 'อ่านทั้งหมด';

export type ReadAllPosition = 'top_left' | 'top_center' | 'top_right' | 'bottom_left' | 'bottom_center' | 'bottom_right';

export const READ_ALL_POSITIONS: { value: ReadAllPosition; label: string }[] = [
    { value: 'top_left', label: 'บนซ้าย' },
    { value: 'top_center', label: 'บนกึ่งกลาง' },
    { value: 'top_right', label: 'บนขวา' },
    { value: 'bottom_left', label: 'ล่างซ้าย' },
    { value: 'bottom_center', label: 'ล่างกึ่งกลาง' },
    { value: 'bottom_right', label: 'ล่างขวา' },
];

export type ReadAllIcon = 'none' | 'plus' | 'plus_circle' | 'arrow_right' | 'arrow_right_circle' | 'chevron_right' | 'chevron_right_circle' | 'arrow_up_right';

/** ไอคอนที่แสดงร่วมกับข้อความ — `component` = null คือ "ไม่เลือก" (แสดงชื่อคู่กับไอคอนเสมอเพื่อให้ผู้ใช้รู้ว่าเป็นไอคอนแบบไหน) */
export const READ_ALL_ICONS: { value: ReadAllIcon; label: string; component: Component | null }[] = [
    { value: 'none', label: 'ไม่เลือก', component: null },
    { value: 'plus', label: 'บวก', component: Plus },
    { value: 'plus_circle', label: 'บวกในวงกลม', component: CirclePlus },
    { value: 'arrow_right', label: 'ลูกศรขวา', component: ArrowRight },
    { value: 'arrow_right_circle', label: 'ลูกศรขวาในวงกลม', component: CircleArrowRight },
    { value: 'chevron_right', label: 'เครื่องหมายมากกว่า', component: ChevronRight },
    { value: 'chevron_right_circle', label: 'เครื่องหมายมากกว่าในวงกลม', component: CircleChevronRight },
    { value: 'arrow_up_right', label: 'ลูกศรเฉียงขวาบน', component: ArrowUpRight },
];

export function readAllIconComponent(icon: string): Component | null {
    return READ_ALL_ICONS.find((i) => i.value === icon)?.component ?? null;
}

export type ReadAllIconPosition = 'before' | 'after';

export const READ_ALL_ICON_POSITIONS: { value: ReadAllIconPosition; label: string }[] = [
    { value: 'before', label: 'หน้าข้อความ' },
    { value: 'after', label: 'หลังข้อความ' },
];

export type ReadAllStyle = 'button' | 'link' | 'pill';

export const READ_ALL_STYLES: { value: ReadAllStyle; label: string; description: string }[] = [
    { value: 'button', label: 'ปุ่ม', description: 'ปุ่มสี่เหลี่ยมมุมมน' },
    { value: 'link', label: 'ลิงก์ข้อความ', description: 'ข้อความขีดเส้นใต้' },
    { value: 'pill', label: 'ปุ่มมนใหญ่', description: 'ปุ่มโค้งมน คล้ายวงรี' },
];

/** ลิงก์ปลายทางที่รับ: URL เต็ม, path ภายในเว็บ (ขึ้นต้น /), anchor (#), mailto:, tel: — ตรงกับ READ_ALL_URL_REGEX ฝั่ง backend */
export const READ_ALL_URL_PATTERN = /^(https?:\/\/|\/|#|mailto:|tel:)\S*$/i;
