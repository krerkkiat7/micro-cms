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

/** สีเริ่มต้นของตัวอักษร: ปุ่ม/ปุ่มมนใหญ่ = ขาวบนพื้นเทาเข้ม, ลิงก์ข้อความ = น้ำเงิน — ตรงกับ SlidesetArticleWidget ฝั่ง backend (ค่า default ของปุ่ม) */
export const READ_ALL_DEFAULT_COLORS: Record<ReadAllStyle, string> = { button: '#FFFFFF', pill: '#FFFFFF', link: '#2563EB' };

/** สีพื้นหลังเริ่มต้นของปุ่ม (ใช้เฉพาะแบบปุ่ม/ปุ่มมนใหญ่) */
export const READ_ALL_DEFAULT_BACKGROUND = '#1F2937';

/** ลิงก์ปลายทางที่รับ: URL เต็ม, path ภายในเว็บ (ขึ้นต้น /), anchor (#), mailto:, tel: — ตรงกับ READ_ALL_URL_REGEX ฝั่ง backend */
export const READ_ALL_URL_PATTERN = /^(https?:\/\/|\/|#|mailto:|tel:)\S*$/i;

/**
 * ฟิลด์ตั้งค่าปุ่ม "อ่านทั้งหมด" ที่ widget กลุ่ม article ใช้ร่วมกัน (Slideset จาก article, Grid จาก article) — ดู HasReadAllButton ฝั่ง backend
 * ใช้เป็นชนิดของ prop `setting` ของ `widgets/ReadAllFields.vue` (component ฟอร์มที่ใช้ร่วมกัน)
 */
export interface ReadAllSettingFields {
    show_read_all: 'Y' | 'N';
    read_all_position: ReadAllPosition;
    /** ข้อความแทน "อ่านทั้งหมด" แยกภาษา (ภาษา → ข้อความ; ว่าง = ใช้ข้อความมาตรฐาน) */
    read_all_text: Record<string, string>;
    read_all_icon: ReadAllIcon;
    read_all_icon_position: ReadAllIconPosition;
    read_all_style: ReadAllStyle;
    read_all_font_size: number;
    read_all_font_family: string;
    read_all_color: string;
    read_all_background: string;
    /** ประเภทลิงก์ปลายทาง: เลือกจากเมนูหน้าบ้าน (default) / กำหนด URL เอง */
    read_all_link_type: ReadAllLinkType;
    /** เมนูหน้าบ้านที่เลือก (front_menu_info.id) — ใช้เมื่อ read_all_link_type = menu */
    read_all_menu_id: number | null;
    read_all_url: string;
    read_all_link_target: '_self' | '_blank';
}

export type ReadAllLinkType = 'menu' | 'custom';

export const READ_ALL_LINK_TYPES: { value: ReadAllLinkType; label: string }[] = [
    { value: 'menu', label: 'เมนู' },
    { value: 'custom', label: 'กำหนดเอง' },
];

/** เมนูหน้าบ้านสำหรับ dropdown เลือกปลายทาง (FrontMenuTree::pickerOptions ฝั่ง backend — แบนตาม tree + ระดับความลึก) */
export interface FrontMenuPickerOption {
    id: number;
    name: string;
    depth: number;
    menu_type: string;
    /** false = เมนูหัวข้อ/ไม่กำหนด (แสดงให้เห็นโครง แต่เลือกไม่ได้) */
    selectable: boolean;
    /** true = เมนูเดิมที่บันทึกไว้แต่ถูกปิดใช้งานภายหลัง (ส่งมาเพิ่มเฉพาะหน้าแก้ไขบางหน้า) */
    inactive?: boolean;
}

/** non-breaking space สำหรับเยื้องระดับเมนู (ช่องว่างปกติถูกยุบเมื่อแสดงผล) */
const NBSP = String.fromCharCode(160);

/**
 * ตัวเลือก SearchableSelect ของเมนูหน้าบ้าน — เยื้องตามระดับ, เมนูหัวข้อ/ไม่กำหนดเลือกไม่ได้ (ไม่มีลิงก์ของตัวเอง)
 * ใช้ร่วมกันระหว่างปุ่ม "อ่านทั้งหมด" ของ widget และลิงก์ของป้ายโฆษณา
 */
export function frontMenuSelectOptions(menus: FrontMenuPickerOption[]): { value: string; label: string; disabled: boolean }[] {
    return menus.map((menu) => ({
        value: String(menu.id),
        label:
            `${NBSP.repeat(menu.depth * 3)}${menu.depth > 0 ? '└ ' : ''}${menu.name || `เมนู #${menu.id}`}` +
            `${menu.selectable ? '' : ' (หัวข้อ)'}${menu.inactive ? ' (ไม่ใช้งาน)' : ''}`,
        disabled: !menu.selectable,
    }));
}
