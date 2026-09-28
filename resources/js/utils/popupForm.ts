import type { FileItem, LanguageOption } from '@/types';

/**
 * ชนิดข้อมูล/ค่าตั้งต้น/ตัวเลือกของฟอร์ม popup (ใช้ร่วมหน้าเพิ่มและแก้ไข) — ฝั่ง backend ดู
 * PopupItemController / PopupItemValidationRules (docs/PRD-popup.md)
 */

export type PopupPartType = 'image_text' | 'image' | 'text';

export interface PopupPart {
    /** key ฝั่ง client สำหรับ v-for/ลากสลับ (part ถูกสร้างใหม่ทุกครั้งที่บันทึก จึงไม่ใช้ id จริง) */
    _key: string;
    part_type: PopupPartType;
    /** FilePickerField ใช้ array เสมอ — เลือกได้ 1 รูป */
    image: FileItem[];
    image_size: string;
    url: string;
    link_target: string;
    status: 'Y' | 'N';
    /** ข้อความ (rich text) แยกตามภาษา */
    detail: Record<string, string>;
}

export interface PopupItemFormData {
    name: string;
    display_type: string;
    show_dismiss_today: string;
    show_arrows: string;
    show_dots: string;
    autoplay: string;
    slide_interval: string;
    slide_speed: string;
    menu_mode: string;
    menu_ids: number[];
    publish_date: string | null;
    publish_down: string | null;
    sort_order: string;
    status: string;
    parts: PopupPart[];
}

/** ข้อมูล part ที่ controller ส่งมาในหน้าแก้ไข */
export interface PopupPartFromServer {
    id: number;
    part_type: PopupPartType;
    image: FileItem | null;
    image_size: string;
    url: string;
    link_target: string;
    status: 'Y' | 'N';
    detail: Record<string, string>;
}

/** 1 โหนดของ tree เมนูหน้าบ้าน (FrontMenuTree::adminCheckTree) */
export interface PopupMenuNode {
    id: number;
    name: string;
    menu_type: string;
    status: string;
    selectable: boolean;
    children: PopupMenuNode[];
}

export const POPUP_DISPLAY_TYPE_OPTIONS = [
    { value: 'modal', label: 'Modal', description: 'แสดงกลางจอพร้อมพื้นหลังทึบ ต้องปิดก่อนใช้งานหน้าเว็บต่อ' },
    { value: 'floating', label: 'Floating (ลอย)', description: 'ลอยกลางจอ ไม่มีพื้นหลัง ยังใช้งานหน้าเว็บด้านหลังได้' },
];

export const POPUP_PART_TYPE_OPTIONS: { value: PopupPartType; label: string }[] = [
    { value: 'image_text', label: 'รูปภาพ + ข้อความ' },
    { value: 'image', label: 'รูปภาพ' },
    { value: 'text', label: 'ข้อความ' },
];

export const POPUP_PART_TYPE_LABELS: Record<PopupPartType, string> = {
    image_text: 'รูปภาพ + ข้อความ',
    image: 'รูปภาพ',
    text: 'ข้อความ',
};

export const POPUP_IMAGE_SIZE_OPTIONS = [
    { value: 'full', label: 'เต็มความกว้าง' },
    { value: 'large', label: 'ใหญ่' },
    { value: 'medium', label: 'กลาง' },
    { value: 'small', label: 'เล็ก' },
];

export const POPUP_MENU_MODE_OPTIONS = [
    { value: 'all', label: 'ทุกหน้า' },
    { value: 'selected', label: 'เมนูที่ระบุ' },
    { value: 'none', label: 'ไม่กำหนด' },
];

export const POPUP_DISPLAY_ORDER_OPTIONS = [
    { value: 'publish_desc', label: 'วันที่เผยแพร่ใหม่สุด' },
    { value: 'publish_asc', label: 'วันที่เผยแพร่เก่าสุด' },
    { value: 'sort_desc', label: 'ลำดับมากสุด' },
    { value: 'sort_asc', label: 'ลำดับน้อยสุด' },
];

let keySeq = 0;

function nextKey(): string {
    keySeq += 1;
    return `popup-part-${Date.now()}-${keySeq}`;
}

export function partHasImage(type: PopupPartType): boolean {
    return type !== 'text';
}

export function partHasText(type: PopupPartType): boolean {
    return type !== 'image';
}

export function createPopupPart(languages: LanguageOption[]): PopupPart {
    return {
        _key: nextKey(),
        part_type: 'image_text',
        image: [],
        image_size: 'full',
        url: '',
        link_target: '_blank',
        status: 'Y',
        detail: Object.fromEntries(languages.map((lang) => [lang.code, ''])),
    };
}

export function popupPartsFromServer(parts: PopupPartFromServer[], languages: LanguageOption[]): PopupPart[] {
    return parts.map((part) => ({
        _key: nextKey(),
        part_type: part.part_type,
        image: part.image ? [part.image] : [],
        image_size: part.image_size,
        url: part.url ?? '',
        link_target: part.link_target,
        status: part.status,
        detail: Object.fromEntries(languages.map((lang) => [lang.code, part.detail?.[lang.code] ?? ''])),
    }));
}

export function popupPartsToPayload(parts: PopupPart[]) {
    return parts.map((part) => ({
        part_type: part.part_type,
        image_id: partHasImage(part.part_type) ? (part.image[0]?.id ?? null) : null,
        image_size: part.image_size,
        url: part.url,
        link_target: part.link_target,
        status: part.status,
        detail: partHasText(part.part_type) ? part.detail : {},
    }));
}

/** ชื่อย่อของ part สำหรับ dialog เรียงลำดับ — ชื่อรูปภาพ หรือข้อความภาษาหลักแบบตัดแท็กออก */
export function popupPartSummary(part: PopupPart, languages: LanguageOption[]): string {
    const defaultLang = languages.find((lang) => lang.is_default)?.code ?? languages[0]?.code;
    const html = defaultLang ? part.detail[defaultLang] : '';
    const text = partHasText(part.part_type) && html ? new DOMParser().parseFromString(html, 'text/html').body.textContent?.trim() : '';

    if (text) return text;
    if (partHasImage(part.part_type) && part.image[0]) return part.image[0].name;

    return '(ยังไม่มีข้อมูล)';
}

/** วันเวลาปัจจุบันรูปแบบที่ DateTimeInput ใช้ (Y-m-d H:i:s) */
export function nowDateTime(): string {
    const d = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
}
