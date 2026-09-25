import type { CSSProperties } from 'vue';
import type { FileItem, LanguageOption } from '@/types';
import { defaultSetting, settingFromServer } from '@/utils/pageWidget';
import { customTextSettingToPayload, isCustomTextWidget } from '@/utils/pageWidgetCustomText';
import type { CustomTextSetting } from '@/utils/pageWidgetCustomText';

/**
 * โครงสร้างหน้าเพจ แถว (row) → คอลัมน์ (column) → widget (ดู docs/PRD-page.md) — ชนิดข้อมูลตรงกับ
 * `page_item_row/column/widget` ฝั่ง backend; ทุกอย่างถูกแก้ในหน่วยความจำของหน้า "โครงสร้าง" แล้วส่งขึ้นไป
 * บันทึกทีเดียวทั้งชุด (รายการที่มี `id` = ของเดิม, `id = null` = สร้างใหม่)
 */

export interface LayoutDetail {
    title: string;
    subtitle: string;
    intro_text: string;
}

export type LayoutDetailMap = Record<string, LayoutDetail>;

/** ฟิลด์พื้นหลังที่แถว/คอลัมน์/widget/หน้ามีเหมือนกัน — สตริงว่าง '' = ไม่ระบุ (ส่งไป backend เป็น null) */
export interface BackgroundFields {
    background_color: string;
    /** FilePickerField ทำงานกับ array เสมอ (เลือกได้ไฟล์เดียว) */
    background_image: FileItem[];
    background_repeat: string;
    background_size: string;
    background_attachment: string;
    background_position: string;
}

/** ระยะขอบด้านใน (padding) ของแถว/คอลัมน์/widget — ตรงกับ App\Support\PageSpacing ฝั่ง backend (หน่วย px) */
export interface PaddingFields {
    /** Y = เว้นระยะขอบด้านในตามค่า 4 ด้าน, N = ไม่เว้น (ค่าเริ่มต้น) */
    use_padding: 'Y' | 'N';
    padding_top: number;
    padding_right: number;
    padding_bottom: number;
    padding_left: number;
}

/** ระยะห่างระหว่างคอลัมน์ในแถว (px) — เฉพาะแถว */
export interface GapFields {
    /** แนวนอน: ระหว่างคอลัมน์ที่อยู่ข้างกัน */
    gap_x: number;
    /** แนวตั้ง: เมื่อคอลัมน์ขึ้นบรรทัดใหม่ หรือแสดงบนมือถือ */
    gap_y: number;
}

export const PADDING_SIDES = ['top', 'right', 'bottom', 'left'] as const;
export type PaddingSide = (typeof PADDING_SIDES)[number];

/** ช่วงค่าที่ backend รับ (PageSpacing::PADDING_MIN/MAX, GAP_MIN/MAX) */
export const PADDING_MAX = 200;
export const GAP_MAX = 120;

/** ระยะห่างระหว่างคอลัมน์เริ่มต้น 24px (= 1.5rem ที่เว็บส่วนใหญ่ใช้) — ตรงกับ PageSpacing::DEFAULT_GAP */
export const DEFAULT_GAP = 24;

export type TextAlign = 'left' | 'center' | 'right';

/** การจัดรูปแบบตัวอักษรของข้อความ 1 ส่วน (หัวเรื่อง / หัวเรื่องรอง / ข้อความเกริ่นนำ) */
export interface TextStyle {
    /** ขนาดตัวอักษร (px) */
    font_size: number;
    font_family: string;
    align: TextAlign;
    /** รหัสสี hex เท่านั้น (ไม่มี transparent) */
    color: string;
    /** ตัวหนา — มีเฉพาะข้อความของแถว/คอลัมน์/widget (ที่อื่นที่ใช้ TextStyle ร่วม เช่น หัวข้อ part ของ Custom Text เก็บตัวหนาแยกไว้เอง) */
    bold?: 'Y' | 'N';
}

/** ส่วนของข้อความที่จัดรูปแบบได้ — ตรงกับ App\Support\PageTextStyle::PARTS (คอลัมน์ `<part>_font_size` ฯลฯ ฝั่ง backend) */
export const TEXT_PARTS = ['title', 'subtitle', 'intro_text'] as const;
export type TextPart = (typeof TEXT_PARTS)[number];

/** การจัดรูปแบบของข้อความทั้ง 3 ส่วนของแถว/คอลัมน์/widget */
export interface TextStyles {
    title_style: TextStyle;
    subtitle_style: TextStyle;
    intro_text_style: TextStyle;
}

export interface WidgetData extends BackgroundFields, TextStyles, PaddingFields {
    _key: string;
    id: number | null;
    status: 'Y' | 'N';
    show_title: 'Y' | 'N';
    widget_type: string;
    setting: Record<string, unknown>;
    detail: LayoutDetailMap;
}

export interface ColumnData extends BackgroundFields, TextStyles, PaddingFields {
    _key: string;
    id: number | null;
    status: 'Y' | 'N';
    show_title: 'Y' | 'N';
    /** ความกว้างใน grid 12 (1 - 12) */
    column_size: number;
    detail: LayoutDetailMap;
    widgets: WidgetData[];
}

export interface RowData extends BackgroundFields, TextStyles, PaddingFields, GapFields {
    _key: string;
    id: number | null;
    status: 'Y' | 'N';
    show_title: 'Y' | 'N';
    /** Y = เนื้อหาอยู่ใน container (จำกัดความกว้าง), N = เต็มความกว้าง */
    use_container: 'Y' | 'N';
    detail: LayoutDetailMap;
    columns: ColumnData[];
}

/** ข้อมูลประกอบฟอร์มตั้งค่า widget เฉพาะประเภท (PageWidgetRegistry::options() ฝั่ง backend) — รายการหมวดหมู่ที่เลือกได้ */
export interface WidgetOptions {
    banner_categories: { id: number; title: string | null }[];
    article_categories: { id: number; title: string | null }[];
}

/** ค่าที่ dialog ตั้งค่าของแต่ละชั้นแก้ไขได้ (ไม่รวมลูก) — dialog แก้บนสำเนาแล้วส่งกลับเมื่อกด "ตกลง" */
export type RowSettings = Pick<RowData, 'detail' | 'show_title' | 'use_container'> & BackgroundFields & TextStyles & PaddingFields & GapFields;
export type ColumnSettings = Pick<ColumnData, 'detail' | 'show_title' | 'column_size'> & BackgroundFields & TextStyles & PaddingFields;
export type WidgetSettings = Pick<WidgetData, 'detail' | 'show_title' | 'widget_type' | 'setting'> & BackgroundFields & TextStyles & PaddingFields;

/** รูปแบบข้อมูลที่ backend ส่งมา (PageItemController::rowToArray) — การจัดรูปแบบมาเป็นคอลัมน์แบน `<part>_<ค่า>` */
type ServerTextStyle = Record<`${TextPart}_${'font_size' | 'font_family' | 'align' | 'color' | 'bold'}`, string | number>;

interface ServerBackground {
    background_color: string | null;
    background_image: FileItem | null;
    background_repeat: string | null;
    background_size: string | null;
    background_attachment: string | null;
    background_position: string | null;
}

interface ServerWidget extends ServerBackground, ServerTextStyle, PaddingFields {
    id: number;
    status: 'Y' | 'N';
    show_title: 'Y' | 'N';
    widget_type: string;
    setting: Record<string, unknown> | unknown[] | null;
    detail: LayoutDetailMap;
}

interface ServerColumn extends ServerBackground, ServerTextStyle, PaddingFields {
    id: number;
    status: 'Y' | 'N';
    show_title: 'Y' | 'N';
    column_size: number;
    detail: LayoutDetailMap;
    widgets: ServerWidget[];
}

export interface ServerRow extends ServerBackground, ServerTextStyle, PaddingFields, GapFields {
    id: number;
    status: 'Y' | 'N';
    show_title: 'Y' | 'N';
    use_container: 'Y' | 'N';
    detail: LayoutDetailMap;
    columns: ServerColumn[];
}

export const SHOW_OPTIONS = [
    { value: 'Y', label: 'แสดง' },
    { value: 'N', label: 'ไม่แสดง' },
];

export const CONTAINER_OPTIONS = [
    { value: 'Y', label: 'อยู่ใน Container (จำกัดความกว้าง)' },
    { value: 'N', label: 'เต็มความกว้าง' },
];

// ---- การจัดรูปแบบตัวอักษร ----

export const DEFAULT_FONT_FAMILY = 'Sarabun';
export const DEFAULT_TEXT_ALIGN: TextAlign = 'center';
export const DEFAULT_TEXT_COLOR = '#000000';

/** ขนาดตัวอักษร (px) ที่เลือกได้ (backend รับ 8 - 120) */
export const FONT_SIZE_OPTIONS = [12, 14, 16, 18, 20, 22, 24, 26, 28, 32, 36, 40, 44, 48, 56, 64, 72, 80, 96].map((size) => ({
    value: String(size),
    label: `${size} px`,
}));

export const TEXT_ALIGN_OPTIONS: { value: TextAlign; label: string }[] = [
    { value: 'left', label: 'ชิดซ้าย' },
    { value: 'center', label: 'กึ่งกลาง' },
    { value: 'right', label: 'ชิดขวา' },
];

/** ขนาดตัวอักษรเริ่มต้นของ [หัวเรื่อง, หัวเรื่องรอง, ข้อความเกริ่นนำ] แต่ละชั้น — ต้องตรงกับค่า default ใน migration
 *  2026_09_20_000002 (แถว = หัวเรื่อง h2, คอลัมน์ = h3, widget = h4) */
const DEFAULT_TEXT_SIZES = {
    row: [32, 20, 16],
    column: [24, 18, 16],
    widget: [20, 16, 14],
} as const;

export type LayoutLevel = keyof typeof DEFAULT_TEXT_SIZES;

/** ชนิดแท็กของหัวเรื่องแต่ละชั้น (หัวเรื่องรองและข้อความเกริ่นนำเป็น div ธรรมดาเสมอ) */
export const HEADING_TAGS = { row: 'h2', column: 'h3', widget: 'h4' } as const;

/** ตัวหนาเริ่มต้น: หัวเรื่องหนา อีก 2 ส่วนไม่หนา — ตรงกับ PageTextStyle::DEFAULT_BOLD / migration 2026_10_03_000002 */
const DEFAULT_BOLD: Record<TextPart, 'Y' | 'N'> = { title: 'Y', subtitle: 'N', intro_text: 'N' };

function defaultTextStyle(fontSize: number, bold: 'Y' | 'N'): TextStyle {
    return { font_size: fontSize, font_family: DEFAULT_FONT_FAMILY, align: DEFAULT_TEXT_ALIGN, color: DEFAULT_TEXT_COLOR, bold };
}

export function defaultTextStyles(level: LayoutLevel): TextStyles {
    const [title, subtitle, intro] = DEFAULT_TEXT_SIZES[level];

    return {
        title_style: defaultTextStyle(title, DEFAULT_BOLD.title),
        subtitle_style: defaultTextStyle(subtitle, DEFAULT_BOLD.subtitle),
        intro_text_style: defaultTextStyle(intro, DEFAULT_BOLD.intro_text),
    };
}

/** สไตล์ CSS ของข้อความ 1 ส่วนตามที่ตั้งค่า — ฟอนต์ตามด้วย sans-serif เป็น fallback */
export function textStyleCss(style: TextStyle): CSSProperties {
    const css: CSSProperties = {
        fontSize: `${style.font_size}px`,
        fontFamily: `'${style.font_family}', sans-serif`,
        textAlign: style.align,
        color: style.color,
    };

    if (style.bold) {
        css.fontWeight = style.bold === 'Y' ? 700 : 400;
    }

    return css;
}

function textStylesFromServer(server: ServerTextStyle): TextStyles {
    const one = (part: TextPart): TextStyle => ({
        font_size: Number(server[`${part}_font_size`]),
        font_family: String(server[`${part}_font_family`]),
        align: server[`${part}_align`] as TextAlign,
        color: String(server[`${part}_color`]),
        bold: server[`${part}_bold`] === 'Y' ? 'Y' : 'N',
    });

    return { title_style: one('title'), subtitle_style: one('subtitle'), intro_text_style: one('intro_text') };
}

function textStylesToPayload(styles: TextStyles): Record<string, string | number> {
    const payload: Record<string, string | number> = {};

    TEXT_PARTS.forEach((part) => {
        const style = styles[`${part}_style`];
        payload[`${part}_font_size`] = style.font_size;
        payload[`${part}_font_family`] = style.font_family;
        payload[`${part}_align`] = style.align;
        payload[`${part}_color`] = style.color;
        payload[`${part}_bold`] = style.bold ?? DEFAULT_BOLD[part];
    });

    return payload;
}

// ---- ระยะขอบด้านใน / ระยะห่าง ----

/** padding เริ่มต้น [บน, ขวา, ล่าง, ซ้าย] (px) ที่ใช้เมื่อเปิด — ต้องตรงกับ PageSpacing::DEFAULT_PADDING / migration 2026_10_03_000001 */
const DEFAULT_PADDING: Record<LayoutLevel, [number, number, number, number]> = {
    row: [48, 16, 48, 16],
    column: [16, 16, 16, 16],
    widget: [16, 16, 16, 16],
};

export function defaultPadding(level: LayoutLevel): PaddingFields {
    const [top, right, bottom, left] = DEFAULT_PADDING[level];

    return { use_padding: 'N', padding_top: top, padding_right: right, padding_bottom: bottom, padding_left: left };
}

/** สำเนาเฉพาะฟิลด์ padding — ใช้สร้าง draft ใน dialog ตั้งค่า และแปลงข้อมูลจาก/ไป backend */
export function pickPadding(source: PaddingFields): PaddingFields {
    return {
        use_padding: source.use_padding,
        padding_top: Number(source.padding_top),
        padding_right: Number(source.padding_right),
        padding_bottom: Number(source.padding_bottom),
        padding_left: Number(source.padding_left),
    };
}

export function pickGap(source: GapFields): GapFields {
    return { gap_x: Number(source.gap_x), gap_y: Number(source.gap_y) };
}

/** สไตล์ padding สำหรับตัวอย่างในหน้าโครงสร้าง — ปิดอยู่ = ไม่เว้นระยะ */
export function paddingStyle(source: PaddingFields): CSSProperties {
    if (source.use_padding !== 'Y') {
        return {};
    }

    return { padding: `${source.padding_top}px ${source.padding_right}px ${source.padding_bottom}px ${source.padding_left}px` };
}

/** สไตล์ระยะห่างระหว่างคอลัมน์ของแถว สำหรับตัวอย่างในหน้าโครงสร้าง */
export function gapStyle(source: GapFields): CSSProperties {
    return { columnGap: `${source.gap_x}px`, rowGap: `${source.gap_y}px` };
}

let keySeed = 0;

function nextKey(prefix: string): string {
    keySeed += 1;

    return `${prefix}-${Date.now()}-${keySeed}`;
}

export function emptyDetailMap(languages: LanguageOption[]): LayoutDetailMap {
    const detail: LayoutDetailMap = {};
    languages.forEach((lang) => {
        detail[lang.code] = { title: '', subtitle: '', intro_text: '' };
    });

    return detail;
}

/** สำเนาแบบลึกของ object ธรรมดา (ใช้ตอนแก้ไขในสำเนาของ dialog แล้วค่อย apply กลับ) */
export function cloneDeep<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
}

/** พื้นหลังเริ่มต้นของแถว/คอลัมน์/widget ที่เพิ่มใหม่ = โปร่งใส */
function newBackground(): BackgroundFields {
    return {
        background_color: 'transparent',
        background_image: [],
        background_repeat: '',
        background_size: '',
        background_attachment: '',
        background_position: '',
    };
}

/** สร้าง widget ใหม่ของประเภทที่เลือก (ค่าตั้งค่าเริ่มต้นตามประเภท) — ยังไม่ถูกใส่ลงหน้า จนกว่าผู้ใช้กด "ตกลง" ใน dialog ตั้งค่า */
export function createWidget(languages: LanguageOption[], widgetType: string): WidgetData {
    return {
        _key: nextKey('widget'),
        id: null,
        status: 'Y',
        show_title: 'Y',
        widget_type: widgetType,
        setting: defaultSetting(widgetType, languages.map((l) => l.code)),
        detail: emptyDetailMap(languages),
        ...newBackground(),
        ...defaultTextStyles('widget'),
        ...defaultPadding('widget'),
    };
}

/** สร้างคอลัมน์ใหม่ — ค่าเริ่มต้นความกว้าง = ช่องที่เหลือในแถว (12 - ผลรวมปัจจุบัน) ถ้าเต็ม/เกินแล้วใช้ 12 (ตกบรรทัดใหม่) */
export function createColumn(languages: LanguageOption[], existing: ColumnData[] = []): ColumnData {
    const used = existing.reduce((sum, c) => sum + c.column_size, 0);
    const remaining = 12 - used;

    return {
        _key: nextKey('column'),
        id: null,
        status: 'Y',
        show_title: 'N',
        column_size: remaining > 0 ? remaining : 12,
        detail: emptyDetailMap(languages),
        widgets: [],
        ...newBackground(),
        ...defaultTextStyles('column'),
        ...defaultPadding('column'),
    };
}

/** สร้างแถวใหม่ พร้อมคอลัมน์ขนาด 12 อยู่ข้างใน 1 คอลัมน์ */
export function createRow(languages: LanguageOption[]): RowData {
    return {
        _key: nextKey('row'),
        id: null,
        status: 'Y',
        show_title: 'N',
        use_container: 'Y',
        detail: emptyDetailMap(languages),
        columns: [createColumn(languages)],
        ...newBackground(),
        ...defaultTextStyles('row'),
        ...defaultPadding('row'),
        gap_x: DEFAULT_GAP,
        gap_y: DEFAULT_GAP,
    };
}

function backgroundFromServer(server: ServerBackground): BackgroundFields {
    return {
        background_color: server.background_color ?? '',
        background_image: server.background_image ? [server.background_image] : [],
        background_repeat: server.background_repeat ?? '',
        background_size: server.background_size ?? '',
        background_attachment: server.background_attachment ?? '',
        background_position: server.background_position ?? '',
    };
}

export function layoutFromServer(rows: ServerRow[]): RowData[] {
    return rows.map((row) => ({
        _key: nextKey('row'),
        id: row.id,
        status: row.status,
        show_title: row.show_title,
        use_container: row.use_container,
        detail: cloneDeep(row.detail),
        ...backgroundFromServer(row),
        ...textStylesFromServer(row),
        ...pickPadding(row),
        ...pickGap(row),
        columns: row.columns.map((column) => ({
            _key: nextKey('column'),
            id: column.id,
            status: column.status,
            show_title: column.show_title,
            column_size: column.column_size,
            detail: cloneDeep(column.detail),
            ...backgroundFromServer(column),
            ...textStylesFromServer(column),
            ...pickPadding(column),
            widgets: column.widgets.map((widget) => ({
                _key: nextKey('widget'),
                id: widget.id,
                status: widget.status,
                show_title: widget.show_title,
                widget_type: widget.widget_type,
                // PHP ส่ง array ว่างมาเป็น [] — ฝั่งหน้าจอใช้เป็น object เสมอ และผสานกับค่าเริ่มต้นของประเภทกันคีย์ขาด
                setting: settingFromServer(widget.widget_type, widget.setting),
                detail: cloneDeep(widget.detail),
                ...backgroundFromServer(widget),
                ...textStylesFromServer(widget),
                ...pickPadding(widget),
            })),
        })),
    }));
}

function backgroundToPayload(data: BackgroundFields) {
    return {
        background_color: data.background_color,
        background_image_id: data.background_image[0]?.id ?? null,
        background_repeat: data.background_repeat,
        background_size: data.background_size,
        background_attachment: data.background_attachment,
        background_position: data.background_position,
    };
}

/** แปลงโครงสร้างในหน้าจอเป็น payload ส่ง backend (ตัด `_key`/ไฟล์เต็ม เหลือ id) — ลำดับใน array = sort_order */
export function layoutToPayload(rows: RowData[]) {
    return rows.map((row) => ({
        id: row.id,
        status: row.status,
        show_title: row.show_title,
        use_container: row.use_container,
        detail: row.detail,
        ...backgroundToPayload(row),
        ...textStylesToPayload(row),
        ...pickPadding(row),
        ...pickGap(row),
        columns: row.columns.map((column) => ({
            id: column.id,
            status: column.status,
            show_title: column.show_title,
            column_size: column.column_size,
            detail: column.detail,
            ...backgroundToPayload(column),
            ...textStylesToPayload(column),
            ...pickPadding(column),
            widgets: column.widgets.map((widget) => ({
                id: widget.id,
                status: widget.status,
                show_title: widget.show_title,
                widget_type: widget.widget_type,
                // customtext เก็บ setting เป็นรายการ part ที่มี FileItem[]/`_key` ของหน้าจอปนอยู่ (ดู settingFromServer) ต้องแปลงกลับก่อนส่ง
                setting: isCustomTextWidget(widget.widget_type) ? customTextSettingToPayload(widget.setting as unknown as CustomTextSetting) : widget.setting,
                detail: widget.detail,
                ...backgroundToPayload(widget),
                ...textStylesToPayload(widget),
                ...pickPadding(widget),
            })),
        })),
    }));
}

/** สำเนาเฉพาะฟิลด์พื้นหลังของแถว/คอลัมน์/widget — ใช้สร้าง draft ใน dialog ตั้งค่า */
export function pickBackground(source: BackgroundFields): BackgroundFields {
    return cloneDeep({
        background_color: source.background_color,
        background_image: source.background_image,
        background_repeat: source.background_repeat,
        background_size: source.background_size,
        background_attachment: source.background_attachment,
        background_position: source.background_position,
    });
}

/** สำเนาเฉพาะการจัดรูปแบบตัวอักษรทั้ง 3 ส่วน — ใช้สร้าง draft ใน dialog ตั้งค่า */
export function pickTextStyles(source: TextStyles): TextStyles {
    return cloneDeep({
        title_style: source.title_style,
        subtitle_style: source.subtitle_style,
        intro_text_style: source.intro_text_style,
    });
}

/** ชื่อที่แสดงบนแถบจัดการ — ชื่อของภาษาหลัก ถ้าว่างใช้ $fallback (เช่น "แถวที่ 1") */
export function displayTitle(detail: LayoutDetailMap, languages: LanguageOption[], fallback: string): string {
    const defaultLang = languages.find((l) => l.is_default)?.code;
    const title = defaultLang ? detail[defaultLang]?.title : '';

    return title && title.trim() !== '' ? title : fallback;
}

/** ข้อความ 1 ส่วน (title/subtitle/intro_text) ของภาษาหลัก — ใช้แสดงในตัวอย่างหน้าจอ */
export function defaultLangText(detail: LayoutDetailMap, languages: LanguageOption[], part: TextPart): string {
    const defaultLang = languages.find((l) => l.is_default)?.code;

    return (defaultLang ? detail[defaultLang]?.[part] : '')?.trim() ?? '';
}

/** สไตล์พื้นหลัง (สี/รูป + CSS 4 ค่า) ของแถว/คอลัมน์/widget/หน้า สำหรับแสดงในหน้าโครงสร้าง */
export function backgroundStyle(bg: BackgroundFields): CSSProperties {
    const style: CSSProperties = {};

    if (bg.background_color) {
        style.backgroundColor = bg.background_color;
    }

    const image = bg.background_image[0];
    if (image) {
        style.backgroundImage = `url(${route('admin.system.file.get', image.hash_name)})`;
        if (bg.background_repeat) style.backgroundRepeat = bg.background_repeat;
        if (bg.background_size) style.backgroundSize = bg.background_size;
        if (bg.background_attachment) style.backgroundAttachment = bg.background_attachment;
        if (bg.background_position) style.backgroundPosition = bg.background_position;
    }

    return style;
}
