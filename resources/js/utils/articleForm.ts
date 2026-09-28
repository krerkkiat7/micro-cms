import type { PartData } from '@/utils/articleParts';
import type { LanguageOption } from '@/types';

/** ฟิลด์ SEO / AEO / GEO ที่หมวดหมู่และบทความใช้ร่วมกัน (เก็บต่อภาษาในตาราง *_detail) */
export interface ArticleSeoFields {
    slug: string;
    meta_title: string;
    meta_description: string;
    meta_keywords: string;
    og_title: string;
    og_description: string;
}

export interface ArticleCategoryDetail extends ArticleSeoFields {
    title: string;
    intro_text: string;
    detail: string;
}

export interface ArticleItemDetail extends ArticleSeoFields {
    title: string;
    intro_text: string;
}

/** ข้อมูลฟอร์มหมวดหมู่บทความ (หน้าเพิ่ม/แก้ไข ใช้ชุดเดียวกัน) */
export interface ArticleCategoryFormData {
    intro_image_id: number | null;
    sort_order: string;
    status: string;
    detail: Record<string, ArticleCategoryDetail>;
}

/** ข้อมูลฟอร์มบทความ — หมวดหมู่เป็น string ตาม SearchableSelect แล้วแปลงเป็นตัวเลขตอนส่ง */
export interface ArticleItemFormData {
    article_category_info_id: string;
    intro_image_id: number | null;
    publish_date: string | null;
    publish_down: string | null;
    status: string;
    tags: number[];
    detail: Record<string, ArticleItemDetail>;
    parts: PartData[];
}

export interface ArticleTagChip {
    id: number;
    name: string;
    status: 'Y' | 'N';
}

function emptySeo(): ArticleSeoFields {
    return { slug: '', meta_title: '', meta_description: '', meta_keywords: '', og_title: '', og_description: '' };
}

export function emptyCategoryDetails(languages: LanguageOption[]): Record<string, ArticleCategoryDetail> {
    return Object.fromEntries(languages.map((lang) => [lang.code, { title: '', intro_text: '', detail: '', ...emptySeo() }]));
}

export function emptyItemDetails(languages: LanguageOption[]): Record<string, ArticleItemDetail> {
    return Object.fromEntries(languages.map((lang) => [lang.code, { title: '', intro_text: '', ...emptySeo() }]));
}
