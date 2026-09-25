import type { CSSProperties } from 'vue';
import type { FrontBackground, FrontPart, FrontTextStyle } from '@/utils/front';

/**
 * ชนิดข้อมูลโครงสร้างหน้าเพจที่หน้าบ้าน (App\Support\Front\PageLayoutReader) — แถว → คอลัมน์ → widget
 * ข้อความเป็นภาษาที่แสดงแล้ว, พื้นหลังเป็น URL แล้ว, widget ที่ดึงรายการจากหมวดหมู่มี `items` (ลิงก์จริง), Custom Text มี `parts`
 */

export interface FrontLayoutBlock {
    id: number;
    title: string;
    subtitle: string;
    intro_text: string;
    title_style: FrontTextStyle;
    subtitle_style: FrontTextStyle;
    intro_text_style: FrontTextStyle;
    background: FrontBackground;
    /** ระยะขอบด้านใน (px) — null = ปิดใช้งาน (ไม่เว้นระยะ) */
    padding?: FrontPadding | null;
}

export interface FrontPadding {
    top: number;
    right: number;
    bottom: number;
    left: number;
}

/** รายการ 1 ชิ้นของ widget ที่ดึงจากหมวดหมู่ (banner / article) */
export interface FrontWidgetItem {
    id: number;
    title: string;
    intro_text: string;
    image_url: string | null;
    url: string | null;
    link_target: '_self' | '_blank';
    /** article: วันที่เผยแพร่ (Y-m-d) + จำนวนเข้าชม */
    date?: string | null;
    views?: number;
}

export interface FrontWidgetData extends FrontLayoutBlock {
    widget_type: string;
    // ค่าตั้งค่าของแต่ละประเภท (ดู utils/pageWidget.ts) — ข้อความแยกภาษาถูกแปลงเป็นข้อความของภาษาที่แสดงแล้ว
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setting: Record<string, any>;
    items: FrontWidgetItem[];
    parts: FrontPart[];
}

export interface FrontColumnData extends FrontLayoutBlock {
    column_size: number;
    widgets: FrontWidgetData[];
}

export interface FrontRowData extends FrontLayoutBlock {
    use_container: boolean;
    /** ระยะห่างระหว่างคอลัมน์ แนวนอน / แนวตั้ง (px) */
    gap_x?: number;
    gap_y?: number;
    columns: FrontColumnData[];
}

export interface FrontPageData {
    id: number;
    title: string;
    background: FrontBackground;
    rows: FrontRowData[];
    fontsUrl: string | null;
}

/**
 * แท็กหัวเรื่องตามระดับ (h2 - h6) — หน้าเพจใช้ h1 เป็นชื่อหน้า แถวเริ่มที่ h2 แล้วเลื่อนลงทีละระดับเฉพาะชั้นที่มีหัวเรื่องแสดงจริง
 * (แถวไม่มีหัวเรื่อง = คอลัมน์ได้ h2 ต่อไปเลย ฯลฯ) ลำดับหัวเรื่องจึงไม่กระโดดข้ามระดับ
 */
export function headingTag(level: number): string {
    return `h${Math.min(6, Math.max(2, level))}`;
}

/**
 * นับการคลิกลิงก์ของ banner — ยิง sendBeacon ไป front.banner.click (ไม่รอผล ไม่ขวางการเปิดลิงก์; fallback fetch keepalive)
 * ฝั่งเซิร์ฟเวอร์ข้ามบอทและไม่นับซ้ำใน session เดียวกันเอง (App\Support\Front\ViewCounter)
 */
export function trackBannerClick(id: number, lang: string): void {
    const data = new FormData();
    data.append('id', String(id));
    data.append('lang', lang);

    try {
        const url = route('front.banner.click');

        if (navigator.sendBeacon?.(url, data)) return;

        void fetch(url, { method: 'POST', body: data, keepalive: true, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(() => {});
    } catch {
        /* การนับคลิกพลาดได้ ไม่กระทบผู้ใช้ */
    }
}

/** ระยะห่างระหว่างคอลัมน์เริ่มต้น (px) — ตรงกับ App\Support\PageSpacing::DEFAULT_GAP (ใช้เมื่อข้อมูลใน cache เก่ายังไม่มีค่า) */
const DEFAULT_GAP = 24;

/** style padding ของแถว/คอลัมน์/widget — ปิดใช้งาน/ไม่มีค่า = ไม่เว้นระยะ */
export function paddingCss(padding: FrontPadding | null | undefined): CSSProperties {
    if (!padding) return {};

    return { padding: `${padding.top}px ${padding.right}px ${padding.bottom}px ${padding.left}px` };
}

/** style ระยะห่างระหว่างคอลัมน์ของ grid ในแถว */
export function gapCss(row: FrontRowData): CSSProperties {
    return { columnGap: `${row.gap_x ?? DEFAULT_GAP}px`, rowGap: `${row.gap_y ?? DEFAULT_GAP}px` };
}

/** จำนวนบรรทัด → class line-clamp (ต้องเป็นชื่อเต็มให้ Tailwind สแกนเจอ) */
export const LINE_CLAMP: Record<number, string> = { 1: 'line-clamp-1', 2: 'line-clamp-2', 3: 'line-clamp-3' };

export const JUSTIFY_CLASS: Record<string, string> = { left: 'justify-start', center: 'justify-center', right: 'justify-end' };

/**
 * สไตล์ตัวอักษรของส่วนหนึ่งในการ์ด (title / intro_text / date / views / date_day / date_month) จากคอลัมน์แบน `<part>_font_size` ฯลฯ
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function partCss(setting: Record<string, any>, part: string, withAlign = false): CSSProperties {
    const css: CSSProperties = {
        fontSize: `${setting[`${part}_font_size`]}px`,
        fontFamily: `'${setting[`${part}_font_family`]}', sans-serif`,
        color: String(setting[`${part}_color`] ?? ''),
        fontWeight: setting[`${part}_bold`] === 'Y' ? 700 : 400,
    };

    if (withAlign && setting[`${part}_align`]) {
        css.textAlign = setting[`${part}_align`];
    }

    return css;
}

/** กล่องของการ์ด/แถว: เส้นขอบ + สี / สีพื้นหลังของแต่ละรายการ */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function itemBoxCss(setting: Record<string, any>): CSSProperties {
    return {
        backgroundColor: setting.item_background,
        borderWidth: setting.show_border === 'Y' ? '1px' : '0',
        borderStyle: 'solid',
        borderColor: setting.border_color,
    };
}

/** กรอบรูปของการ์ด: อัตราส่วน + สีพื้นหลัง (เมื่อ contain) */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function imageFrameCss(setting: Record<string, any>): CSSProperties {
    return {
        aspectRatio: String(setting.aspect_ratio ?? '16:9').replace(':', ' / '),
        backgroundColor: setting.image_fit === 'contain' ? setting.image_background : '#F3F4F6',
    };
}

/** เป้าหมายการเปิดลิงก์ของรายการ — ตั้งค่า widget หรือของ banner เอง ข้อใดข้อหนึ่งเป็นหน้าต่างใหม่ = เปิดหน้าต่างใหม่ */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function itemTarget(setting: Record<string, any>, item: FrontWidgetItem): '_self' | '_blank' {
    return setting.link_target === '_blank' || item.link_target === '_blank' ? '_blank' : '_self';
}

/** จำนวนต่อแถวแต่ละขนาดหน้าจอ → CSS variable ของ .front-grid (resources/css/app.css) */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function perRowVars(setting: Record<string, any>): CSSProperties {
    const clamp = (value: unknown) => Math.max(1, Math.min(6, Number(value) || 1));

    return {
        '--cols-mobile': clamp(setting.per_row_mobile),
        '--cols-tablet': clamp(setting.per_row_tablet),
        '--cols-notebook': clamp(setting.per_row_notebook),
        '--cols-pc': clamp(setting.per_row_pc),
    } as CSSProperties;
}
