import type { FileItem, LanguageOption } from '@/types';

/**
 * ปุ่มด้านล่างของหน้า Intropage (ดู docs/PRD-intropage.md) — ตรงกับ `intropage_item_button.button_type`/
 * `button_display_type` ฝั่ง backend เป๊ะ ๆ
 */
export type ButtonType = 'home' | 'other';
export type ButtonDisplayType = 'text' | 'image';

export const BUTTON_TYPE_LABELS: Record<ButtonType, string> = {
    home: 'เข้าหน้าแรก',
    other: 'ปุ่มเพิ่มเติม',
};

/** ปุ่ม 1 รายการในฟอร์ม (ก่อนแปลงเป็น payload ส่งให้ backend) */
export interface ButtonData {
    _key: string;
    button_type: ButtonType;
    button_display_type: ButtonDisplayType;
    background_color: string;
    text_color: string;
    /** FilePickerField ทำงานกับ array เสมอ (เลือกได้ไฟล์เดียว) */
    button_image: FileItem[];
    /** เฉพาะ button_type = other */
    url: string;
    link_target: '_self' | '_blank';
    /** ข้อความปุ่มต่อภาษา เช่น { th: 'เข้าสู่เว็บไซต์', en: 'Enter Site' } */
    texts: Record<string, string>;
}

let keySeed = 0;

function nextKey(): string {
    keySeed += 1;

    return `button-${Date.now()}-${keySeed}`;
}

function emptyTexts(languages: LanguageOption[]): Record<string, string> {
    const texts: Record<string, string> = {};
    languages.forEach((lang) => {
        texts[lang.code] = '';
    });

    return texts;
}

/** สร้างปุ่ม "เข้าหน้าแรก" เริ่มต้น — เรียกครั้งเดียวตอนสร้างฟอร์มเพิ่ม Intropage ใหม่ (ลบไม่ได้จาก UI) */
export function createHomeButton(languages: LanguageOption[]): ButtonData {
    return {
        _key: nextKey(),
        button_type: 'home',
        button_display_type: 'text',
        background_color: '',
        text_color: '',
        button_image: [],
        url: '',
        link_target: '_self',
        texts: emptyTexts(languages),
    };
}

/** สร้างปุ่มเพิ่มเติม (button_type = other) ว่าง ๆ */
export function createOtherButton(languages: LanguageOption[]): ButtonData {
    return {
        _key: nextKey(),
        button_type: 'other',
        button_display_type: 'text',
        background_color: '',
        text_color: '',
        button_image: [],
        url: '',
        link_target: '_self',
        texts: emptyTexts(languages),
    };
}

/** หาข้อความปุ่มสำหรับแสดงแบบย่อ (เช่นใน ButtonReorderDialog.vue) — ใช้ของภาษาหลักก่อน แล้วค่อย fallback
 *  ไปภาษาอื่นที่กรอกไว้ ถ้าไม่มีเลยให้แสดง "(ไม่มีข้อความ)"
 */
export function buttonDisplayTitle(button: ButtonData, languages: LanguageOption[]): string {
    if (button.button_type === 'home') {
        return BUTTON_TYPE_LABELS.home;
    }

    const defaultLang = languages.find((lang) => lang.is_default)?.code;
    const defaultText = defaultLang ? button.texts[defaultLang] : '';

    if (defaultText && defaultText.trim() !== '') {
        return defaultText;
    }

    const fallback = Object.values(button.texts).find((t) => t.trim() !== '');

    return fallback ?? '(ไม่มีข้อความ)';
}

/** แปลงปุ่มที่ได้จาก backend (หน้าแก้ไข) ให้เป็นรูปแบบที่ฟอร์มฝั่งนี้ใช้งาน */
export function buttonsFromServer(
    buttons: Array<{
        button_type: ButtonType;
        button_display_type: ButtonDisplayType;
        background_color: string | null;
        text_color: string | null;
        button_image: FileItem | null;
        url: string | null;
        link_target: string | null;
        texts: Record<string, string> | null;
    }>,
    languages: LanguageOption[],
): ButtonData[] {
    return buttons.map((button) => ({
        _key: nextKey(),
        button_type: button.button_type,
        button_display_type: button.button_display_type ?? 'text',
        background_color: button.background_color ?? '',
        text_color: button.text_color ?? '',
        button_image: button.button_image ? [button.button_image] : [],
        url: button.url ?? '',
        link_target: (button.link_target as '_self' | '_blank') ?? '_self',
        texts: languages.reduce<Record<string, string>>((acc, lang) => {
            acc[lang.code] = button.texts?.[lang.code] ?? '';

            return acc;
        }, {}),
    }));
}

/** แปลงปุ่มในฟอร์มเป็น payload สำหรับส่งให้ backend (unwrap FileItem[] กลับเป็น id เดี่ยว) */
export function buttonsToPayload(buttons: ButtonData[]) {
    return buttons.map((button) => ({
        button_type: button.button_type,
        button_display_type: button.button_display_type,
        background_color: button.button_display_type === 'text' ? button.background_color : '',
        text_color: button.button_display_type === 'text' ? button.text_color : '',
        button_image_id: button.button_display_type === 'image' ? (button.button_image[0]?.id ?? null) : null,
        url: button.button_type === 'other' ? button.url : '',
        link_target: button.button_type === 'other' ? button.link_target : '',
        texts: button.texts,
    }));
}
