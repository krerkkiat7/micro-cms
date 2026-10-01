import type { LanguageOption } from '@/types';

/** ข้อความต่อภาษาของหมวดหมู่/ป้ายโฆษณา (ตาราง *_detail) */
export interface BannerDetail {
    title: string;
    intro_text: string;
}

export interface BannerCategoryFormData {
    status: string;
    detail: Record<string, BannerDetail>;
}

/** ข้อมูลฟอร์มป้ายโฆษณา — หมวดหมู่เป็น string ตาม SearchableSelect แล้วแปลงเป็นตัวเลขตอนส่ง */
export interface BannerItemFormData {
    banner_category_info_id: string;
    intro_image_id: number | null;
    /** ประเภทลิงก์ (BannerItemInfo::LINK_TYPES) — เลือกก่อน แล้วกรอกเฉพาะฟิลด์ของประเภทนั้น */
    link_type: BannerLinkType;
    /** เมนูปลายทาง (link_type = menu) — string ตาม SearchableSelect แล้วแปลงเป็นตัวเลขตอนส่ง */
    front_menu_info_id: string;
    url: string;
    link_target: string;
    publish_date: string | null;
    publish_down: string | null;
    sort_order: string;
    status: string;
    detail: Record<string, BannerDetail>;
}

export type BannerLinkType = 'none' | 'menu' | 'custom';

export const BANNER_LINK_TYPES: { value: BannerLinkType; label: string }[] = [
    { value: 'none', label: 'ไม่มีลิงก์' },
    { value: 'menu', label: 'เมนู' },
    { value: 'custom', label: 'กำหนดเอง' },
];

/** แปลงค่าในฟอร์มก่อนส่ง — dropdown เก็บเป็น string, backend รับตัวเลข/null */
export function bannerItemPayload(data: BannerItemFormData): Record<string, unknown> {
    return {
        ...data,
        banner_category_info_id: data.banner_category_info_id !== '' ? Number(data.banner_category_info_id) : null,
        front_menu_info_id: data.front_menu_info_id !== '' ? Number(data.front_menu_info_id) : null,
    };
}

export function emptyBannerDetails(languages: LanguageOption[]): Record<string, BannerDetail> {
    return Object.fromEntries(languages.map((lang) => [lang.code, { title: '', intro_text: '' }]));
}
