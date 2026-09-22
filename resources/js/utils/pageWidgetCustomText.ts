import { FileText, Image as ImageIcon, Images, type LucideIcon, Video } from 'lucide-vue-next';
import type { FileItem, LanguageOption } from '@/types';

/**
 * widget "Custom Text" (ดู docs/PRD-page.md) — กรอกเนื้อหาเอง แบ่งเป็น "part" เรียงลำดับได้หลายรายการต่อ 1 widget
 * เหมือนระบบ part ของบทความ (@/utils/articleParts) แต่ตัดประเภทเอกสารออก (เน้นข้อความ/สื่อ) และหัวเรื่องของแต่ละ part
 * จัดรูปแบบได้เอง (ขนาด/ฟอนต์/จัดตำแหน่ง/สี) — ชนิดที่รองรับตรงกับ App\Support\PageWidget\CustomTextWidget::PART_TYPES เป๊ะ ๆ
 */
export type CustomTextPartType = 'text' | 'image' | 'images' | 'video';

/** ป้ายชื่อภาษาไทยของแต่ละประเภท part — ใช้ทั้งใน PartCard.vue และ PartReorderDialog.vue */
export const CUSTOMTEXT_PART_TYPE_LABELS: Record<CustomTextPartType, string> = {
    text: 'ข้อความ',
    image: 'รูปภาพเดี่ยว',
    images: 'กลุ่มรูปภาพ',
    video: 'วิดีโอ',
};

/** ไอคอนของแต่ละประเภท part — ใช้ร่วมกันใน PartCard.vue, CustomTextFields.vue (ปุ่มเพิ่ม part) และ PartReorderDialog.vue */
export const CUSTOMTEXT_PART_TYPE_ICONS: Record<CustomTextPartType, LucideIcon> = {
    text: FileText,
    image: ImageIcon,
    images: Images,
    video: Video,
};

/** ไฟล์ 1 แถวของ part (รูปภาพในกลุ่ม, หรือไฟล์เดี่ยวของ image/video) — โครงสร้างเดียวกับ PartFileRow ของบทความ */
export interface CustomTextPartFileRow {
    _key: string;
    /** FilePickerField ทำงานกับ array เสมอ (เลือกได้ไฟล์เดียวต่อแถว) */
    file: FileItem[];
    /** เฉพาะ part ประเภทวิดีโอ — รูปภาพหน้าปก */
    cover_image: FileItem[];
    video_type: 'file' | 'youtube';
    youtube_url: string;
    /** ข้อมูลเสริมของไฟล์นี้ — รูปร่างต่างกันไปตามประเภท part (ดูคอมเมนต์ใน createFileRow) เป็น `any` โดยตั้งใจ เหมือน PartFileRow.description */
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    description: Record<string, any>;
}

/** หัวข้อ/เนื้อหาของ part ต่อภาษา 1 ภาษา */
export interface CustomTextPartDetailLang {
    title: string;
    detail: string;
}

/** part 1 รายการในฟอร์ม (ก่อนแปลงเป็น payload ส่งให้ backend) */
export interface CustomTextPartData {
    _key: string;
    part_type: CustomTextPartType;
    images_display_type: string;
    /** แสดงหัวเรื่องของ part นี้หรือไม่ (Y/N ตาม convention ของโปรเจกต์) */
    show_title: 'Y' | 'N';
    /** แสดง/ซ่อน part นี้ทั้งอัน — คนละความหมายกับการลบ */
    status: 'Y' | 'N';
    /** ตั้งค่าที่ไม่แยกภาษาของ part — รูปร่างต่างกันไปตามประเภท (เหมือน PartData.setting ของบทความ) */
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setting: Record<string, any>;
    /** การจัดรูปแบบหัวเรื่องของ part นี้ (ใช้กับ settingTextStyle(part, 'title') จาก @/utils/pageWidget ได้ตรง ๆ ยกเว้น title_bold ที่แยกต่างหาก) */
    title_font_size: number;
    title_bold: 'Y' | 'N';
    title_font_family: string;
    title_align: 'left' | 'center' | 'right';
    title_color: string;
    detail: Record<string, CustomTextPartDetailLang>;
    files: CustomTextPartFileRow[];
}

export interface CustomTextSetting {
    parts: CustomTextPartData[];
}

export function isCustomTextWidget(type: string): boolean {
    return type === 'customtext';
}

let keySeed = 0;

function nextKey(prefix: string): string {
    keySeed += 1;

    return `${prefix}-${Date.now()}-${keySeed}`;
}

function createEmptyDetail(languages: LanguageOption[]): Record<string, CustomTextPartDetailLang> {
    const detail: Record<string, CustomTextPartDetailLang> = {};
    languages.forEach((lang) => {
        detail[lang.code] = { title: '', detail: '' };
    });

    return detail;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function defaultPartSetting(type: CustomTextPartType): Record<string, any> {
    switch (type) {
        case 'image':
            return { alignment: 'center', size: 'large', show_caption: false };
        case 'images':
            return { columns: '3', autoplay: false, interval_ms: '4000' };
        case 'video':
            return { alignment: 'center', player_size: 'large' };
        default:
            return {};
    }
}

/** สร้างแถวไฟล์เปล่าของ part — description เริ่มต้นตามประเภท part (alt_text ต่อภาษา / ตั้งค่าไฟล์) เหมือน createFileRow ของบทความ */
export function createCustomTextFileRow(type: CustomTextPartType, languages: LanguageOption[]): CustomTextPartFileRow {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    let description: Record<string, any> = {};

    if (type === 'image' || type === 'images') {
        const altText: Record<string, string> = {};
        languages.forEach((lang) => {
            altText[lang.code] = '';
        });
        description = altText;
    } else if (type === 'video') {
        description = { autoplay: false, controls: true };
    }

    return {
        _key: nextKey('ctfile'),
        file: [],
        cover_image: [],
        video_type: 'file',
        youtube_url: '',
        description,
    };
}

export function createCustomTextPart(type: CustomTextPartType, languages: LanguageOption[]): CustomTextPartData {
    const part: CustomTextPartData = {
        _key: nextKey('ctpart'),
        part_type: type,
        images_display_type: type === 'images' ? 'grid_lightbox' : '',
        show_title: 'Y',
        status: 'Y',
        setting: defaultPartSetting(type),
        title_font_size: 20,
        title_bold: 'Y',
        title_font_family: 'Sarabun',
        title_align: 'left',
        title_color: '#000000',
        detail: createEmptyDetail(languages),
        files: [],
    };

    if (type === 'image' || type === 'video') {
        part.files.push(createCustomTextFileRow(type, languages));
    }

    return part;
}

export function defaultCustomTextSetting(): CustomTextSetting {
    return { parts: [] };
}

/** หาข้อความหัวเรื่องของ part สำหรับแสดงแบบย่อ (เช่นใน PartReorderDialog.vue) — ใช้ของภาษาหลักก่อน แล้วค่อย fallback ไปภาษาอื่นที่กรอกไว้ */
export function customTextPartDisplayTitle(part: CustomTextPartData, languages: LanguageOption[]): string {
    const defaultLang = languages.find((lang) => lang.is_default)?.code;
    const defaultTitle = defaultLang ? part.detail[defaultLang]?.title : '';

    if (defaultTitle && defaultTitle.trim() !== '') {
        return defaultTitle;
    }

    const fallback = Object.values(part.detail).find((d) => d.title.trim() !== '');

    return fallback?.title ?? '(ไม่มีชื่อ)';
}

/** แปลงข้อมูล part ที่ backend ส่งมา (App\Support\PageWidget\CustomTextWidget::toArray) ให้เป็นรูปแบบที่ฟอร์มฝั่งนี้ใช้งาน */
export function customTextPartsFromServer(
    parts: Array<{
        part_type: CustomTextPartType;
        images_display_type: string | null;
        show_title: 'Y' | 'N';
        status: 'Y' | 'N';
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        setting: Record<string, any> | null;
        title_font_size: number;
        title_bold: 'Y' | 'N';
        title_font_family: string;
        title_align: 'left' | 'center' | 'right';
        title_color: string;
        detail: Record<string, CustomTextPartDetailLang>;
        files: Array<{
            file: FileItem | null;
            cover_image: FileItem | null;
            video_type: string | null;
            youtube_url: string | null;
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            description: Record<string, any> | null;
        }>;
    }>,
): CustomTextPartData[] {
    return parts.map((part) => ({
        _key: nextKey('ctpart'),
        part_type: part.part_type,
        images_display_type: part.images_display_type ?? (part.part_type === 'images' ? 'grid_lightbox' : ''),
        show_title: part.show_title ?? 'Y',
        status: part.status ?? 'Y',
        setting: part.setting && Object.keys(part.setting).length > 0 ? part.setting : defaultPartSetting(part.part_type),
        title_font_size: part.title_font_size,
        title_bold: part.title_bold,
        title_font_family: part.title_font_family,
        title_align: part.title_align,
        title_color: part.title_color,
        detail: part.detail,
        files: part.files.map((row) => ({
            _key: nextKey('ctfile'),
            file: row.file ? [row.file] : [],
            cover_image: row.cover_image ? [row.cover_image] : [],
            video_type: (row.video_type as 'file' | 'youtube') ?? 'file',
            youtube_url: row.youtube_url ?? '',
            description: row.description ?? {},
        })),
    }));
}

/** แปลง part ในฟอร์มเป็น payload สำหรับส่งให้ backend (unwrap FileItem[] กลับเป็น id เดี่ยว) */
export function customTextPartsToPayload(parts: CustomTextPartData[]) {
    return parts.map((part) => ({
        part_type: part.part_type,
        images_display_type: part.part_type === 'images' ? part.images_display_type : null,
        show_title: part.show_title,
        status: part.status,
        setting: part.setting,
        title_font_size: part.title_font_size,
        title_bold: part.title_bold,
        title_font_family: part.title_font_family,
        title_align: part.title_align,
        title_color: part.title_color,
        detail: part.detail,
        files: part.files.map((row) => ({
            file_id: row.file[0]?.id ?? null,
            cover_image_id: part.part_type === 'video' ? (row.cover_image[0]?.id ?? null) : null,
            video_type: part.part_type === 'video' ? row.video_type : null,
            youtube_url: part.part_type === 'video' && row.video_type === 'youtube' ? row.youtube_url : null,
            description: row.description,
        })),
    }));
}

/** ผสานค่าที่ backend ส่งมากับค่าเริ่มต้น (กันคีย์ขาดหาย) — ใช้ใน settingFromServer() ของ @/utils/pageWidget */
export function customTextSettingFromServer(setting: Record<string, unknown> | unknown[] | null | undefined): CustomTextSetting {
    const raw = setting && !Array.isArray(setting) ? (setting as { parts?: unknown[] }) : {};

    return { parts: Array.isArray(raw.parts) ? customTextPartsFromServer(raw.parts as never) : [] };
}

/** แปลงค่าตั้งค่าทั้งก้อนเป็น payload สำหรับส่งให้ backend — ใช้ใน layoutToPayload() ของ @/utils/pageLayout */
export function customTextSettingToPayload(setting: CustomTextSetting) {
    return { parts: customTextPartsToPayload(setting.parts) };
}
