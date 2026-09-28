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
    url: string;
    link_target: string;
    publish_date: string | null;
    publish_down: string | null;
    sort_order: string;
    status: string;
    detail: Record<string, BannerDetail>;
}

export function emptyBannerDetails(languages: LanguageOption[]): Record<string, BannerDetail> {
    return Object.fromEntries(languages.map((lang) => [lang.code, { title: '', intro_text: '' }]));
}
