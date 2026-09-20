import type { BackgroundFields } from '@/utils/pageLayout';
import type { FileItem, LanguageOption } from '@/types';

/**
 * ฟอร์มข้อมูลทั่วไปของหน้าเพจ (page_item_info + page_item_detail) ใช้ร่วมกันระหว่างหน้าเพิ่ม/แก้ไข —
 * รูปภาพเก็บเป็น array ของไฟล์เต็ม (ตามที่ FilePickerField ต้องการ) แล้วแปลงเป็น id ตอนส่งด้วย toPagePayload()
 */
export interface PageDetailFields {
    title: string;
    intro_text: string;
    slug: string;
    meta_title: string;
    meta_description: string;
    meta_keywords: string;
    og_title: string;
    og_description: string;
}

export interface PageItemFormData extends BackgroundFields {
    intro_image: FileItem[];
    status: string;
    detail: Record<string, PageDetailFields>;
}

export function emptyPageDetail(): PageDetailFields {
    return {
        title: '',
        intro_text: '',
        slug: '',
        meta_title: '',
        meta_description: '',
        meta_keywords: '',
        og_title: '',
        og_description: '',
    };
}

/** ฟอร์มเปล่าสำหรับหน้าเพิ่มหน้าเพจ (ทุกภาษาที่เปิดใช้งานมีชุดฟิลด์ว่างของตัวเอง) */
export function emptyPageItemForm(languages: LanguageOption[]): PageItemFormData {
    const detail: Record<string, PageDetailFields> = {};
    languages.forEach((lang) => {
        detail[lang.code] = emptyPageDetail();
    });

    return {
        intro_image: [],
        background_color: '',
        background_image: [],
        background_repeat: '',
        background_size: '',
        background_attachment: '',
        background_position: '',
        status: 'Y',
        detail,
    };
}

/** แปลงข้อมูลฟอร์มเป็น payload ส่ง backend — รูปภาพส่งเป็น id */
export function toPagePayload(data: PageItemFormData) {
    const { intro_image, background_image, ...rest } = data;

    return {
        ...rest,
        intro_image_id: intro_image[0]?.id ?? null,
        background_image_id: background_image[0]?.id ?? null,
    };
}
