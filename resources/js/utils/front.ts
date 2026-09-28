import type { CSSProperties } from 'vue';
import type { AsideZone, FooterZone, HeaderZone } from '@/utils/template';
import type { BackgroundFields } from '@/utils/pageLayout';

/**
 * ชนิดข้อมูล + ตัวช่วยของหน้าบ้าน — ข้อมูลทั้งหมดมาจาก backend (App\Support\Front\*) ที่แปลงให้พร้อมแสดงแล้ว:
 * URL ไฟล์/ลิงก์ถูกสร้างฝั่ง server (ไม่ประกอบเองจาก hash — เผื่อไฟล์เฉพาะสมาชิกในอนาคต), ข้อความเป็นภาษาที่แสดงอยู่แล้ว
 */

/** พื้นหลังที่ backend ส่งมา (สี + รูป + CSS 4 ค่า) */
export interface FrontBackground {
    color: string | null;
    image_url: string | null;
    repeat: string | null;
    size: string | null;
    attachment: string | null;
    position: string | null;
}

export interface FrontTextStyle {
    font_size: number;
    font_family: string;
    align: 'left' | 'center' | 'right';
    color: string;
    /** ตัวหนา (ข้อความของแถว/คอลัมน์/widget ในหน้าเพจ) — ไม่ระบุ = ตามแท็ก/คลาสของที่ใช้ */
    bold?: boolean;
}

/** โซนของ template — ฟิลด์พื้นหลังเป็น URL แล้ว (App\Support\Front\FrontLayoutData) */
type FrontZone<T> = Omit<T, keyof BackgroundFields> & {
    background_color: string | null;
    background_image_url: string | null;
    background_repeat: string | null;
    background_size: string | null;
    background_attachment: string | null;
    background_position: string | null;
};

export type FrontHeaderZone = FrontZone<HeaderZone>;
export type FrontBodyZone = FrontZone<BackgroundFields>;
export type FrontFooterZone = FrontZone<FooterZone>;
export type FrontAsideZone = FrontZone<AsideZone>;

export interface FrontMenuItem {
    id: number;
    name: string;
    menu_type: string;
    /** null = ไม่มีลิงก์ (เมนูหัวข้อ / ปลายทางไม่พร้อมใช้) */
    url: string | null;
    target: '_self' | '_blank';
    children: FrontMenuItem[];
}

export interface FrontSocial {
    key: string;
    label: string;
    url: string;
}

/** ข้อความส่วนติดต่อผู้ใช้ (lang/<code>/front.php) */
export type FrontTranslations = Record<string, string | Record<string, string>>;

/** prop `front` ที่ทุกหน้าหน้าบ้านได้รับ (FrontLayoutData::forLanguage) */
export interface FrontLayoutProps {
    lang: string;
    languages: string[];
    defaultLanguage: string;
    site: { name: string; description: string | null; logoUrl: string | null };
    contact: { owner: string | null; address: string | null; phone: string | null; fax: string | null; mobile: string | null; email: string | null };
    social: FrontSocial[];
    copyright: { year: string; owner: string };
    template: { header: FrontHeaderZone; body: FrontBodyZone; footer: FrontFooterZone; aside: FrontAsideZone };
    fontsUrl: string | null;
    menu: FrontMenuItem[];
    homeUrl: string;
    t: FrontTranslations;
}

/** prop `seo` (App\Support\Front\SeoMeta) */
export interface SeoData {
    title: string;
    fullTitle: string;
    description: string;
    keywords: string;
    canonical: string;
    robots: string;
    og: { type: string; title: string; description: string; image: string | null; url: string; site_name: string; locale: string };
    twitter: string;
    alternates: { hreflang: string; href: string }[];
    jsonLd: Record<string, unknown>[];
}

export interface Crumb {
    name: string;
    url: string | null;
}

/** ส่วนหัวของหน้า (รูป + หัวเรื่องตามเมนูที่ชี้มาหน้านี้) — front_menu_info */
export interface HeroData {
    image_url: string | null;
    image_aspect_ratio: string;
    image_fit: 'cover' | 'contain';
    image_background: string;
    title: string;
    title_style: { font_size: number; font_family: string; color: string; bold: boolean };
    subtitle: string;
    subtitle_style: { font_size: number; font_family: string; color: string; bold: boolean };
    content_align: string;
    use_container: boolean;
    show_breadcrumb: boolean;
}

export interface PageHeaderData {
    hero: HeroData | null;
    breadcrumb: Crumb[];
    showBreadcrumb: boolean;
    activeMenuIds: number[];
}

/** ไฟล์ 1 รายการ (App\Support\Front\FrontFile::fromFileInfo) */
export interface FrontFileData {
    name: string;
    extension: string | null;
    file_size: number;
    is_image: boolean;
    url: string;
    download_url: string;
    thumb_url: string | null;
}

/** part ของเนื้อหา (บทความ / Custom Text) — App\Support\Front\FrontParts */
export interface FrontPart {
    id: number;
    type: 'text' | 'image' | 'images' | 'video' | 'document' | 'documents';
    images_display_type: string;
    title: string;
    title_style: { font_size: number; bold: boolean; font_family: string; align: 'left' | 'center' | 'right'; color: string } | null;
    html: string;
    setting: {
        alignment: 'left' | 'center' | 'right';
        size: 'small' | 'medium' | 'large' | 'full';
        player_size: 'small' | 'medium' | 'large' | 'full';
        show_caption: boolean;
        columns: number;
        autoplay: boolean;
        interval_ms: number;
    };
    files: {
        file: FrontFileData | null;
        cover: FrontFileData | null;
        video_type: 'file' | 'youtube';
        youtube_id: string | null;
        alt: string;
        autoplay: boolean;
        controls: boolean;
        pdf_preview: boolean;
        show_file_size: boolean;
    }[];
}

// ---------------------------------------------------------------- ตัวช่วย

/** style พื้นหลังจากข้อมูลของ backend */
export function backgroundCss(bg: FrontBackground | null | undefined): CSSProperties {
    const style: CSSProperties = {};

    if (!bg) return style;
    if (bg.color) style.backgroundColor = bg.color;

    if (bg.image_url) {
        style.backgroundImage = `url("${bg.image_url}")`;
        if (bg.repeat) style.backgroundRepeat = bg.repeat;
        if (bg.size) style.backgroundSize = bg.size;
        if (bg.attachment) style.backgroundAttachment = bg.attachment;
        if (bg.position) style.backgroundPosition = bg.position;
    }

    return style;
}

/** style พื้นหลังของโซน template */
export function zoneBackgroundCss(zone: { background_color: string | null; background_image_url: string | null; background_repeat: string | null; background_size: string | null; background_attachment: string | null; background_position: string | null }): CSSProperties {
    return backgroundCss({
        color: zone.background_color,
        image_url: zone.background_image_url,
        repeat: zone.background_repeat,
        size: zone.background_size,
        attachment: zone.background_attachment,
        position: zone.background_position,
    });
}

export function textStyleCss(style: FrontTextStyle): CSSProperties {
    const css: CSSProperties = {
        fontSize: `${style.font_size}px`,
        fontFamily: `'${style.font_family}', sans-serif`,
        textAlign: style.align,
        color: style.color,
    };

    if (style.bold !== undefined) {
        css.fontWeight = style.bold ? 700 : 400;
    }

    return css;
}

export function fontCss(style: { font_size: number; font_family: string; color: string; bold?: boolean }): CSSProperties {
    return {
        fontSize: `${style.font_size}px`,
        fontFamily: `'${style.font_family}', sans-serif`,
        color: style.color,
        fontWeight: style.bold ? 700 : 400,
    };
}

/** ข้อความส่วนติดต่อผู้ใช้ + แทนค่า :name */
export function translate(t: FrontTranslations, key: string, replace: Record<string, string | number> = {}): string {
    const value = key.split('.').reduce<unknown>((acc, part) => (acc && typeof acc === 'object' ? (acc as Record<string, unknown>)[part] : undefined), t);
    let text = typeof value === 'string' ? value : key;

    Object.entries(replace).forEach(([name, val]) => {
        text = text.replaceAll(`:${name}`, String(val));
    });

    return text;
}

/** locale ของ Intl ตามภาษา (ไทยใช้ปฏิทินพุทธศักราช) */
export function intlLocale(lang: string): string {
    return lang === 'th' ? 'th-TH' : lang === 'en' ? 'en-GB' : lang;
}

export function formatDate(value: string | null | undefined, lang: string): string {
    if (!value) return '';

    const date = new Date(value.length === 10 ? `${value}T00:00:00` : value);

    return Number.isNaN(date.getTime()) ? '' : date.toLocaleDateString(intlLocale(lang), { day: 'numeric', month: 'long', year: 'numeric' });
}

/**
 * วันที่ + เวลา (ชั่วโมง:นาที) ตามเวลาที่บันทึกไว้ฝั่ง server — อ่านตัวเลขจากสตริง ISO ตรง ๆ ไม่แปลงเป็นโซนเวลาของเบราว์เซอร์
 * (ผู้ชมต่างประเทศเห็นเวลาเดียวกับที่ตั้งไว้ในหลังบ้าน) `timeText` = ข้อความรูปแบบเวลาตามภาษา เช่น "เวลา :time น."
 */
export function formatDateTime(value: string | null | undefined, lang: string, timeText = ':time'): string {
    const match = value?.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
    if (!match) return formatDate(value, lang);

    const [, y, m, d, hh, mm] = match;
    const date = new Date(Number(y), Number(m) - 1, Number(d)).toLocaleDateString(intlLocale(lang), { day: 'numeric', month: 'long', year: 'numeric' });

    return `${date} ${timeText.replace(':time', `${hh}:${mm}`)}`;
}

export function formatShortDate(value: string | null | undefined, lang: string): string {
    if (!value) return '';

    const date = new Date(value.length === 10 ? `${value}T00:00:00` : value);

    return Number.isNaN(date.getTime()) ? '' : date.toLocaleDateString(intlLocale(lang), { day: 'numeric', month: 'short', year: 'numeric' });
}

export function formatNumber(value: number, lang: string): string {
    return value.toLocaleString(intlLocale(lang));
}

/** ขนาดไฟล์อ่านง่าย */
export function formatBytes(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

/** จัดตำแหน่งบล็อก (9 ทิศตามค่า CSS background-position) เป็น class ของ flex container */
export function positionClasses(position: string): string {
    const [first, second] = (position || 'center').split(' ');
    const vertical = ['top', 'bottom'].includes(first) ? first : ['top', 'bottom'].includes(second) ? second : 'center';
    const horizontal = ['left', 'right'].includes(first) ? first : ['left', 'right'].includes(second) ? second : 'center';

    const justify = { left: 'justify-start text-left', center: 'justify-center text-center', right: 'justify-end text-right' }[horizontal as 'left'];
    const align = { top: 'items-start', center: 'items-center', bottom: 'items-end' }[vertical as 'top'];

    return `${justify} ${align}`;
}

/**
 * เงาใต้ข้อความที่ซ้อนบนภาพของ Slideshow ตามตำแหน่งแนวตั้งของข้อความ (ให้อ่านออกทุกภาพ): บน = ไล่เงาจากขอบบน,
 * ล่าง = ไล่เงาจากขอบล่าง, กลาง = เงาจางทั้งภาพ — ใช้คู่กับ positionClasses() (ทั้งหน้าบ้านและตัวอย่างในหลังบ้าน)
 */
export function slideshowOverlayClass(position: string): string {
    const parts = (position || 'center').split(' ');

    if (parts.includes('top')) return 'bg-gradient-to-b from-black/70 via-black/25 to-transparent';
    if (parts.includes('bottom')) return 'bg-gradient-to-t from-black/75 via-black/25 to-transparent';

    return 'bg-black/35';
}

/** อัตราส่วนภาพ "16:9" → CSS aspect-ratio */
export function aspectRatio(value: string): string | undefined {
    return /^\d+:\d+$/.test(value) ? value.replace(':', ' / ') : undefined;
}
