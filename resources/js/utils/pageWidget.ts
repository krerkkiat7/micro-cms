import { LINK_TARGET_OPTIONS } from '@/utils/options';

/**
 * ประเภท widget ของโมดูล Page (ดู docs/PRD-page.md) — ทะเบียนฝั่งหน้าจอ ต้องตรงกับ App\Support\PageWidget\* ฝั่ง backend
 * (ชื่อประเภท ตัวเลือก ค่าเริ่มต้น ช่วงตัวเลข) เพิ่มประเภทใหม่ = เพิ่มใน WIDGET_TYPE_DEFS + interface/ค่าเริ่มต้น/ตัวตรวจในไฟล์นี้
 * + component ตั้งค่า/ตัวอย่างใน Components/Admin/PageLayout/widgets/
 */

/** รูปแบบการแสดงผลของ widget (ใช้เลือกภาพประกอบในการ์ดเลือกประเภท) */
export type WidgetLayout = 'slideshow' | 'slideset' | 'grid' | 'text';

/** แหล่งข้อมูลของ widget */
export type WidgetSource = 'banner' | 'article' | 'custom';

export interface WidgetTypeDef {
    value: string;
    label: string;
    description: string;
    layout: WidgetLayout;
    source: WidgetSource;
    /** false = ยังไม่พร้อมใช้ (แสดงในการ์ดเลือกประเภทเป็น "เร็ว ๆ นี้" เลือกไม่ได้) */
    available: boolean;
}

export const WIDGET_TYPE_DEFS: WidgetTypeDef[] = [
    {
        value: 'slideshowbanner',
        label: 'Slideshow จาก banner',
        description: 'ภาพเต็มภาพเดียวที่สไลด์เปลี่ยนภาพได้ ข้อมูลจากป้ายโฆษณา (banner)',
        layout: 'slideshow',
        source: 'banner',
        available: true,
    },
    {
        value: 'slideshowarticle',
        label: 'Slideshow จาก article',
        description: 'ภาพเต็มภาพเดียวที่สไลด์เปลี่ยนภาพได้ ข้อมูลจากบทความ (article)',
        layout: 'slideshow',
        source: 'article',
        available: false,
    },
    {
        value: 'slidesetbanner',
        label: 'Slideset จาก banner',
        description: 'การ์ดหลายใบที่เลื่อนดูได้ ข้อมูลจากป้ายโฆษณา (banner)',
        layout: 'slideset',
        source: 'banner',
        available: false,
    },
    {
        value: 'slidesetarticle',
        label: 'Slideset จาก article',
        description: 'การ์ดหลายใบที่เลื่อนดูได้ ข้อมูลจากบทความ (article)',
        layout: 'slideset',
        source: 'article',
        available: false,
    },
    {
        value: 'gridbanner',
        label: 'Grid จาก banner',
        description: 'กล่องเรียงต่อเนื่องแบบ grid ข้อมูลจากป้ายโฆษณา (banner)',
        layout: 'grid',
        source: 'banner',
        available: false,
    },
    {
        value: 'gridarticle',
        label: 'Grid จาก article',
        description: 'กล่องเรียงต่อเนื่องแบบ grid ข้อมูลจากบทความ (article)',
        layout: 'grid',
        source: 'article',
        available: false,
    },
    {
        value: 'customtext',
        label: 'Custom Text',
        description: 'กรอกเนื้อหาเอง แบ่งเป็นส่วน ๆ คล้าย part ของบทความ',
        layout: 'text',
        source: 'custom',
        available: false,
    },
];

/** ประเภทเดิมก่อนมีประเภทจริง — ยังโหลด/บันทึกได้ แต่เลือกสร้างใหม่ไม่ได้ (ไม่อยู่ใน WIDGET_TYPE_DEFS) */
export const LEGACY_WIDGET_TYPE = 'placeholder';

export function widgetTypeDef(type: string): WidgetTypeDef | undefined {
    return WIDGET_TYPE_DEFS.find((def) => def.value === type);
}

export function widgetTypeLabel(type: string): string {
    if (type === LEGACY_WIDGET_TYPE) {
        return 'Widget (ประเภทเดิม — ยังไม่มีการตั้งค่า)';
    }

    return widgetTypeDef(type)?.label ?? type;
}

// ---- Slideshow จาก banner ----

export type YesNo = 'Y' | 'N';

export interface SlideshowBannerSetting {
    /** หมวดหมู่ banner ที่ดึงมาแสดง (จำเป็นต้องเลือก) */
    banner_category_info_id: number | null;
    sort_by: 'publish_desc' | 'publish_asc' | 'order_asc' | 'order_desc';
    show_arrows: YesNo;
    show_dots: YesNo;
    autoplay: YesNo;
    /** ระยะค้างต่อภาพ (วินาที) */
    autoplay_interval: number;
    /** ความเร็วเปลี่ยนภาพ (มิลลิวินาที) */
    transition_speed: number;
    transition_effect: 'slide' | 'fade' | 'zoom';
    aspect_ratio: '16:9' | '21:9' | '4:3' | '1:1';
    is_clickable: YesNo;
    link_target: '_self' | '_blank';
    show_title: YesNo;
    show_intro_text: YesNo;
    text_align: 'left' | 'center' | 'right';
    text_width: 'full' | 'container';
}

export const SLIDESHOW_INTERVAL_RANGE = { min: 1, max: 60 } as const;
export const SLIDESHOW_SPEED_RANGE = { min: 100, max: 3000 } as const;

export const SLIDESHOW_SORT_OPTIONS = [
    { value: 'publish_desc', label: 'วันที่เผยแพร่ล่าสุด' },
    { value: 'publish_asc', label: 'วันที่เผยแพร่เก่าสุด' },
    { value: 'order_asc', label: 'ลำดับน้อยไปมาก' },
    { value: 'order_desc', label: 'ลำดับมากไปน้อย' },
];

export const SLIDESHOW_EFFECT_OPTIONS = [
    { value: 'slide', label: 'เลื่อน (Slide)' },
    { value: 'fade', label: 'จางเปลี่ยนภาพ (Fade)' },
    { value: 'zoom', label: 'ซูมเปลี่ยนภาพ (Zoom)' },
];

export const SLIDESHOW_ASPECT_OPTIONS = [
    { value: '16:9', label: '16:9 (มาตรฐาน)' },
    { value: '21:9', label: '21:9 (แบนเลอร์กว้าง)' },
    { value: '4:3', label: '4:3' },
    { value: '1:1', label: '1:1 (จัตุรัส)' },
];

export const SLIDESHOW_TEXT_ALIGN_OPTIONS = [
    { value: 'left', label: 'ชิดซ้าย' },
    { value: 'center', label: 'กึ่งกลาง' },
    { value: 'right', label: 'ชิดขวา' },
];

export const SLIDESHOW_TEXT_WIDTH_OPTIONS = [
    { value: 'full', label: 'เต็มความกว้าง' },
    { value: 'container', label: 'จำกัดตาม container' },
];

export { LINK_TARGET_OPTIONS };

export function defaultSlideshowBannerSetting(): SlideshowBannerSetting {
    return {
        banner_category_info_id: null,
        sort_by: 'publish_desc',
        show_arrows: 'Y',
        show_dots: 'Y',
        autoplay: 'Y',
        autoplay_interval: 5,
        transition_speed: 500,
        transition_effect: 'slide',
        aspect_ratio: '16:9',
        is_clickable: 'Y',
        link_target: '_self',
        show_title: 'Y',
        show_intro_text: 'N',
        text_align: 'center',
        text_width: 'container',
    };
}

/** ค่าตั้งค่าเริ่มต้นของ widget ประเภทนั้น (ประเภทที่ไม่มีการตั้งค่า = object ว่าง) — ต้องตรงกับ `defaults()` ฝั่ง backend */
export function defaultSetting(type: string): Record<string, unknown> {
    if (type === 'slideshowbanner') {
        return { ...defaultSlideshowBannerSetting() };
    }

    return {};
}

/** ผสานค่าที่ backend ส่งมากับค่าเริ่มต้น (กันคีย์ขาดหาย) — PHP ส่ง array ว่างมาเป็น [] จึงรับได้ทั้ง array/object/null */
export function settingFromServer(type: string, setting: Record<string, unknown> | unknown[] | null | undefined): Record<string, unknown> {
    const values = setting && !Array.isArray(setting) ? setting : {};

    return { ...defaultSetting(type), ...values };
}

/**
 * ตรวจค่าตั้งค่าก่อนกด "ตกลง" — คืนข้อความ error ตามชื่อฟิลด์ (ว่าง = ผ่าน) backend ตรวจซ้ำอีกชั้นตอนบันทึกโครงสร้าง
 */
export function validateSetting(type: string, setting: Record<string, unknown>): Record<string, string> {
    const errors: Record<string, string> = {};

    if (type === 'slideshowbanner') {
        const s = setting as unknown as SlideshowBannerSetting;

        if (!s.banner_category_info_id) {
            errors.banner_category_info_id = 'กรุณาเลือกหมวดหมู่ banner';
        }

        if (!Number.isInteger(s.autoplay_interval) || s.autoplay_interval < SLIDESHOW_INTERVAL_RANGE.min || s.autoplay_interval > SLIDESHOW_INTERVAL_RANGE.max) {
            errors.autoplay_interval = `ระยะเวลาค้างต่อภาพต้องอยู่ระหว่าง ${SLIDESHOW_INTERVAL_RANGE.min} - ${SLIDESHOW_INTERVAL_RANGE.max} วินาที`;
        }

        if (!Number.isInteger(s.transition_speed) || s.transition_speed < SLIDESHOW_SPEED_RANGE.min || s.transition_speed > SLIDESHOW_SPEED_RANGE.max) {
            errors.transition_speed = `ความเร็วในการเปลี่ยนภาพต้องอยู่ระหว่าง ${SLIDESHOW_SPEED_RANGE.min} - ${SLIDESHOW_SPEED_RANGE.max} มิลลิวินาที`;
        }
    }

    return errors;
}
