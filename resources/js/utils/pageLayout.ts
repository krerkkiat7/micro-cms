import type { CSSProperties } from 'vue';
import type { FileItem, LanguageOption } from '@/types';

/**
 * โครงสร้างหน้าเพจ แถว (row) → คอลัมน์ (column) → widget (ดู docs/PRD-page.md) — ชนิดข้อมูลตรงกับ
 * `page_item_row/column/widget` ฝั่ง backend; ทุกอย่างถูกแก้ในหน่วยความจำของหน้า "โครงสร้าง" แล้วส่งขึ้นไป
 * บันทึกทีเดียวทั้งชุด (รายการที่มี `id` = ของเดิม, `id = null` = สร้างใหม่)
 */

export interface LayoutDetail {
    title: string;
    intro_text: string;
}

export type LayoutDetailMap = Record<string, LayoutDetail>;

/** ฟิลด์พื้นหลังที่แถว/คอลัมน์/หน้ามีเหมือนกัน — สตริงว่าง '' = ไม่ระบุ (ส่งไป backend เป็น null) */
export interface BackgroundFields {
    background_color: string;
    /** FilePickerField ทำงานกับ array เสมอ (เลือกได้ไฟล์เดียว) */
    background_image: FileItem[];
    background_repeat: string;
    background_size: string;
    background_attachment: string;
    background_position: string;
}

export interface WidgetData {
    _key: string;
    id: number | null;
    status: 'Y' | 'N';
    show_title: 'Y' | 'N';
    widget_type: string;
    setting: Record<string, unknown>;
    detail: LayoutDetailMap;
}

export interface ColumnData extends BackgroundFields {
    _key: string;
    id: number | null;
    status: 'Y' | 'N';
    show_title: 'Y' | 'N';
    /** ความกว้างใน grid 12 (1 - 12) */
    column_size: number;
    detail: LayoutDetailMap;
    widgets: WidgetData[];
}

export interface RowData extends BackgroundFields {
    _key: string;
    id: number | null;
    status: 'Y' | 'N';
    show_title: 'Y' | 'N';
    /** Y = เนื้อหาอยู่ใน container (จำกัดความกว้าง), N = เต็มความกว้าง */
    use_container: 'Y' | 'N';
    detail: LayoutDetailMap;
    columns: ColumnData[];
}

/** ค่าที่ dialog ตั้งค่าของแต่ละชั้นแก้ไขได้ (ไม่รวมลูก) — dialog แก้บนสำเนาแล้วส่งกลับเมื่อกด "ตกลง" */
export type RowSettings = Pick<RowData, 'detail' | 'show_title' | 'use_container'> & BackgroundFields;
export type ColumnSettings = Pick<ColumnData, 'detail' | 'show_title' | 'column_size'> & BackgroundFields;
export type WidgetSettings = Pick<WidgetData, 'detail' | 'show_title' | 'widget_type'>;

/** รูปแบบข้อมูลที่ backend ส่งมา (PageItemController::rowToArray) */
interface ServerBackground {
    background_color: string | null;
    background_image: FileItem | null;
    background_repeat: string | null;
    background_size: string | null;
    background_attachment: string | null;
    background_position: string | null;
}

interface ServerWidget {
    id: number;
    status: 'Y' | 'N';
    show_title: 'Y' | 'N';
    widget_type: string;
    setting: Record<string, unknown> | unknown[] | null;
    detail: LayoutDetailMap;
}

interface ServerColumn extends ServerBackground {
    id: number;
    status: 'Y' | 'N';
    show_title: 'Y' | 'N';
    column_size: number;
    detail: LayoutDetailMap;
    widgets: ServerWidget[];
}

export interface ServerRow extends ServerBackground {
    id: number;
    status: 'Y' | 'N';
    show_title: 'Y' | 'N';
    use_container: 'Y' | 'N';
    detail: LayoutDetailMap;
    columns: ServerColumn[];
}

/** ประเภท widget ที่เลือกได้ — ยังรอกำหนดรายละเอียด มี placeholder ประเภทเดียว (ให้ตรงกับ PageItemWidget::TYPES) */
export const WIDGET_TYPES = [{ value: 'placeholder', label: 'Widget (รอกำหนดประเภท)' }];

export const DEFAULT_WIDGET_TYPE = WIDGET_TYPES[0].value;

export function widgetTypeLabel(type: string): string {
    return WIDGET_TYPES.find((t) => t.value === type)?.label ?? type;
}

export const SHOW_OPTIONS = [
    { value: 'Y', label: 'แสดง' },
    { value: 'N', label: 'ไม่แสดง' },
];

export const CONTAINER_OPTIONS = [
    { value: 'Y', label: 'อยู่ใน Container (จำกัดความกว้าง)' },
    { value: 'N', label: 'เต็มความกว้าง' },
];

let keySeed = 0;

function nextKey(prefix: string): string {
    keySeed += 1;

    return `${prefix}-${Date.now()}-${keySeed}`;
}

export function emptyDetailMap(languages: LanguageOption[]): LayoutDetailMap {
    const detail: LayoutDetailMap = {};
    languages.forEach((lang) => {
        detail[lang.code] = { title: '', intro_text: '' };
    });

    return detail;
}

/** สำเนาแบบลึกของ object ธรรมดา (ใช้ตอนแก้ไขในสำเนาของ dialog แล้วค่อย apply กลับ) */
export function cloneDeep<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
}

function emptyBackground(): BackgroundFields {
    return {
        background_color: '',
        background_image: [],
        background_repeat: '',
        background_size: '',
        background_attachment: '',
        background_position: '',
    };
}

export function createWidget(languages: LanguageOption[]): WidgetData {
    return {
        _key: nextKey('widget'),
        id: null,
        status: 'Y',
        show_title: 'Y',
        widget_type: DEFAULT_WIDGET_TYPE,
        setting: {},
        detail: emptyDetailMap(languages),
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
        ...emptyBackground(),
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
        ...emptyBackground(),
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
        columns: row.columns.map((column) => ({
            _key: nextKey('column'),
            id: column.id,
            status: column.status,
            show_title: column.show_title,
            column_size: column.column_size,
            detail: cloneDeep(column.detail),
            ...backgroundFromServer(column),
            widgets: column.widgets.map((widget) => ({
                _key: nextKey('widget'),
                id: widget.id,
                status: widget.status,
                show_title: widget.show_title,
                widget_type: widget.widget_type,
                // PHP ส่ง array ว่างมาเป็น [] — ฝั่งหน้าจอใช้เป็น object เสมอ
                setting: Array.isArray(widget.setting) ? {} : (widget.setting ?? {}),
                detail: cloneDeep(widget.detail),
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
        columns: row.columns.map((column) => ({
            id: column.id,
            status: column.status,
            show_title: column.show_title,
            column_size: column.column_size,
            detail: column.detail,
            ...backgroundToPayload(column),
            widgets: column.widgets.map((widget) => ({
                id: widget.id,
                status: widget.status,
                show_title: widget.show_title,
                widget_type: widget.widget_type,
                setting: widget.setting,
                detail: widget.detail,
            })),
        })),
    }));
}

/** ชื่อที่แสดงบนแถบจัดการ — ชื่อของภาษาหลัก ถ้าว่างใช้ $fallback (เช่น "แถวที่ 1") */
export function displayTitle(detail: LayoutDetailMap, languages: LanguageOption[], fallback: string): string {
    const defaultLang = languages.find((l) => l.is_default)?.code;
    const title = defaultLang ? detail[defaultLang]?.title : '';

    return title && title.trim() !== '' ? title : fallback;
}

/** ข้อความเกริ่นนำของภาษาหลัก (ใช้แสดงย่อในการ์ด widget) */
export function displayIntro(detail: LayoutDetailMap, languages: LanguageOption[]): string {
    const defaultLang = languages.find((l) => l.is_default)?.code;

    return (defaultLang ? detail[defaultLang]?.intro_text : '') ?? '';
}

/** สไตล์พื้นหลัง (สี/รูป + CSS 4 ค่า) ของแถว/คอลัมน์/หน้า สำหรับแสดงในหน้าโครงสร้าง */
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
