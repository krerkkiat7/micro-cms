import type { FileItem, LanguageOption } from '@/types';

/**
 * เนื้อหาบทความแบบแบ่ง part (ดู docs/PRD-article.md §0) — ชนิดที่รองรับตรงกับ
 * `article_item_part.part_type` ฝั่ง backend เป๊ะ ๆ
 */
export type PartType = 'text' | 'image' | 'images' | 'video' | 'document' | 'documents';

/** ป้ายชื่อภาษาไทยของแต่ละประเภท part — ใช้ทั้งใน PartCard.vue และ PartReorderDialog.vue */
export const PART_TYPE_LABELS: Record<PartType, string> = {
    text: 'ข้อความ',
    image: 'รูปภาพเดี่ยว',
    images: 'กลุ่มรูปภาพ',
    video: 'วิดีโอ',
    document: 'เอกสารเดี่ยว',
    documents: 'กลุ่มเอกสาร',
};

/** รูปแบบแสดงผลของ part ประเภทกลุ่มรูปภาพ — slug ต้องตรงกับที่ backend ยอมรับ (ดู migration) */
export const IMAGES_DISPLAY_TYPES: { value: string; label: string }[] = [
    { value: 'thumbnail_carousel', label: 'Thumbnail Carousel' },
    { value: 'multi_carousel', label: 'Multi-item Carousel' },
    { value: 'grid_lightbox', label: 'Grid Gallery with Lightbox' },
    { value: 'full_width_slider', label: 'Full-width Slider' },
    { value: 'masonry_grid', label: 'Masonry Grid' },
    { value: 'justified_grid', label: 'Justified Grid' },
    { value: 'stacked_cards', label: 'Stacked / Overlapping Cards' },
];

/** display_type ที่เป็นสไลด์/carousel — มีตัวเลือก autoplay/interval ให้ตั้งค่า */
const CAROUSEL_DISPLAY_TYPES = ['thumbnail_carousel', 'multi_carousel', 'full_width_slider'];

/** display_type ที่เป็นกริด — มีตัวเลือกจำนวนคอลัมน์ให้ตั้งค่า */
const GRID_DISPLAY_TYPES = ['grid_lightbox', 'masonry_grid', 'justified_grid'];

export function isCarouselDisplayType(type: string): boolean {
    return CAROUSEL_DISPLAY_TYPES.includes(type);
}

export function isGridDisplayType(type: string): boolean {
    return GRID_DISPLAY_TYPES.includes(type);
}

/** ไฟล์ 1 แถวของ part (รูปภาพ/เอกสารในกลุ่ม, หรือไฟล์เดี่ยวของ image/video/document) */
export interface PartFileRow {
    _key: string;
    /** FilePickerField ทำงานกับ array เสมอ (เลือกได้ไฟล์เดียวต่อแถว) */
    file: FileItem[];
    /** เฉพาะ part ประเภทวิดีโอ — รูปภาพหน้าปก */
    cover_image: FileItem[];
    /** เฉพาะ part ประเภทวิดีโอ */
    video_type: 'file' | 'youtube';
    youtube_url: string;
    /**
     * ข้อมูลเสริมของไฟล์นี้ — รูปร่างต่างกันไปตามประเภท part (ดูคอมเมนต์ในแต่ละ createFileRow)
     * เป็น `any` โดยตั้งใจ: ค่าที่เก็บเป็น JSON เดินทางตรงไป backend โดยไม่ผ่าน validation รูปร่างละเอียด
     * ฝั่ง TypeScript ก็ไม่ควรบังคับ shape ตายตัว (ใช้ `unknown` แล้ว Inertia useForm() แตก เพราะ
     * FormDataType<T> แปลง `unknown` recursive ไม่ได้)
     */
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    description: Record<string, any>;
}

/** หัวข้อ/เนื้อหาของ part ต่อภาษา 1 ภาษา */
export interface PartDetailLang {
    title: string;
    detail: string;
}

/** part 1 รายการในฟอร์ม (ก่อนแปลงเป็น payload ส่งให้ backend) */
export interface PartData {
    _key: string;
    part_type: PartType;
    images_display_type: string;
    /** แสดงหัวเรื่องของ part นี้ที่หน้าบ้านหรือไม่ (Y/N ตาม convention ของโปรเจกต์) */
    show_title: 'Y' | 'N';
    /** แสดง/ซ่อน part นี้ทั้งอันที่หน้าบ้าน (Y/N) — คนละความหมายกับการลบ */
    status: 'Y' | 'N';
    /** ตั้งค่าที่ไม่แยกภาษาของ part — รูปร่างต่างกันไปตามประเภท (ดูคอมเมนต์ใน createPart), เป็น `any` ด้วยเหตุผลเดียวกับ PartFileRow.description */
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setting: Record<string, any>;
    detail: Record<string, PartDetailLang>;
    files: PartFileRow[];
}

/** หาข้อความหัวเรื่องของ part สำหรับแสดงแบบย่อ (เช่นใน PartReorderDialog.vue) — ใช้ของภาษาหลักก่อน
 *  แล้วค่อย fallback ไปภาษาอื่นที่กรอกไว้ ถ้าไม่มีเลยให้แสดง "(ไม่มีชื่อ)"
 */
export function partDisplayTitle(part: PartData, languages: LanguageOption[]): string {
    const defaultLang = languages.find((lang) => lang.is_default)?.code;
    const defaultTitle = defaultLang ? part.detail[defaultLang]?.title : '';

    if (defaultTitle && defaultTitle.trim() !== '') {
        return defaultTitle;
    }

    const fallback = Object.values(part.detail).find((d) => d.title.trim() !== '');

    return fallback?.title ?? '(ไม่มีชื่อ)';
}

let keySeed = 0;

function nextKey(prefix: string): string {
    keySeed += 1;

    return `${prefix}-${Date.now()}-${keySeed}`;
}

export function createEmptyDetail(languages: LanguageOption[]): Record<string, PartDetailLang> {
    const detail: Record<string, PartDetailLang> = {};
    languages.forEach((lang) => {
        detail[lang.code] = { title: '', detail: '' };
    });

    return detail;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function defaultSetting(type: PartType): Record<string, any> {
    switch (type) {
        case 'image':
            // alignment: left/center/right, size: small/medium/large/full
            return { alignment: 'center', size: 'large', show_caption: false };
        case 'images':
            // columns/interval_ms เก็บเป็น string เพื่อผูกกับ TextInput ได้ตรง ๆ (component รับ v-model เป็น string)
            return { columns: '3', autoplay: false, interval_ms: '4000' };
        case 'video':
            // player_size: small/medium/large/full
            return { alignment: 'center', player_size: 'large' };
        default:
            return {};
    }
}

/** สร้างแถวไฟล์เปล่าของ part — description เริ่มต้นตามประเภท part (alt_text ต่อภาษา / ตั้งค่าไฟล์) */
export function createFileRow(type: PartType, languages: LanguageOption[]): PartFileRow {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    let description: Record<string, any> = {};

    if (type === 'image' || type === 'images') {
        // alt_text แยกภาษา เก็บตรงด้วยรหัสภาษาเป็น key เช่น {"th": "...", "en": "..."}
        const altText: Record<string, string> = {};
        languages.forEach((lang) => {
            altText[lang.code] = '';
        });
        description = altText;
    } else if (type === 'video') {
        description = { autoplay: false, controls: true };
    } else if (type === 'document' || type === 'documents') {
        description = { pdf_preview: false, show_file_size: true };
    }

    return {
        _key: nextKey('file'),
        file: [],
        cover_image: [],
        video_type: 'file',
        youtube_url: '',
        description,
    };
}

export function createPart(type: PartType, languages: LanguageOption[]): PartData {
    const part: PartData = {
        _key: nextKey('part'),
        part_type: type,
        images_display_type: type === 'images' ? 'grid_lightbox' : '',
        show_title: 'Y',
        status: 'Y',
        setting: defaultSetting(type),
        detail: createEmptyDetail(languages),
        files: [],
    };

    if (type === 'image' || type === 'video' || type === 'document') {
        part.files.push(createFileRow(type, languages));
    }

    return part;
}

/** แปลงข้อมูล part ที่ได้จาก backend (หน้าแก้ไข) ให้เป็นรูปแบบที่ฟอร์มฝั่งนี้ใช้งาน */
export function partsFromServer(
    parts: Array<{
        part_type: PartType;
        images_display_type: string | null;
        show_title: 'Y' | 'N';
        status: 'Y' | 'N';
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        setting: Record<string, any> | null;
        detail: Record<string, PartDetailLang>;
        files: Array<{
            file: FileItem | null;
            cover_image: FileItem | null;
            video_type: string | null;
            youtube_url: string | null;
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            description: Record<string, any> | null;
        }>;
    }>,
    languages: LanguageOption[],
): PartData[] {
    return parts.map((part) => ({
        _key: nextKey('part'),
        part_type: part.part_type,
        images_display_type: part.images_display_type ?? (part.part_type === 'images' ? 'grid_lightbox' : ''),
        show_title: part.show_title ?? 'Y',
        status: part.status ?? 'Y',
        setting: part.setting && Object.keys(part.setting).length > 0 ? part.setting : defaultSetting(part.part_type),
        detail: languages.reduce<Record<string, PartDetailLang>>((acc, lang) => {
            acc[lang.code] = part.detail?.[lang.code] ?? { title: '', detail: '' };

            return acc;
        }, {}),
        files: part.files.map((row) => ({
            _key: nextKey('file'),
            file: row.file ? [row.file] : [],
            cover_image: row.cover_image ? [row.cover_image] : [],
            video_type: (row.video_type as 'file' | 'youtube') ?? 'file',
            youtube_url: row.youtube_url ?? '',
            description: row.description ?? {},
        })),
    }));
}

/** แปลง part ในฟอร์มเป็น payload สำหรับส่งให้ backend (unwrap FileItem[] กลับเป็น id เดี่ยว) */
export function partsToPayload(parts: PartData[]) {
    return parts.map((part) => ({
        part_type: part.part_type,
        images_display_type: part.part_type === 'images' ? part.images_display_type : null,
        show_title: part.show_title,
        status: part.status,
        setting: part.setting,
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
