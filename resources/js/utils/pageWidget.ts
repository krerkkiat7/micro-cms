import { reactive } from 'vue';
import { LINK_TARGET_OPTIONS } from '@/utils/options';
import type { TextStyle } from '@/utils/pageLayout';
import { customTextSettingFromServer, defaultCustomTextSetting, isCustomTextWidget } from '@/utils/pageWidgetCustomText';
import { READ_ALL_DEFAULT_BACKGROUND, READ_ALL_DEFAULT_COLORS, READ_ALL_URL_PATTERN } from '@/utils/readAllButton';
import type { ReadAllIcon, ReadAllIconPosition, ReadAllPosition, ReadAllStyle } from '@/utils/readAllButton';

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
        available: true,
    },
    {
        value: 'slidesetbanner',
        label: 'Slideset จาก banner',
        description: 'การ์ดป้ายโฆษณาหลายใบที่เลื่อนดูได้ (รูป หัวเรื่อง ข้อความเกริ่นนำ) ข้อมูลจากป้ายโฆษณา (banner)',
        layout: 'slideset',
        source: 'banner',
        available: true,
    },
    {
        value: 'slidesetarticle',
        label: 'Slideset จาก article',
        description: 'การ์ดบทความหลายใบที่เลื่อนดูได้ (รูป หัวเรื่อง ข้อความเกริ่นนำ วันที่ จำนวนเข้าชม) ข้อมูลจากบทความ (article)',
        layout: 'slideset',
        source: 'article',
        available: true,
    },
    {
        value: 'gridbanner',
        label: 'Grid จาก banner',
        description: 'กล่องเรียงต่อเนื่องหลายคอลัมน์ (ไม่เลื่อน) ข้อมูลจากป้ายโฆษณา (banner)',
        layout: 'grid',
        source: 'banner',
        available: true,
    },
    {
        value: 'gridarticle',
        label: 'Grid จาก article',
        description: 'กล่องเรียงต่อเนื่องหลายคอลัมน์ (ไม่เลื่อน) ข้อมูลจากบทความ (article)',
        layout: 'grid',
        source: 'article',
        available: true,
    },
    {
        value: 'customtext',
        label: 'Custom Text',
        description: 'กรอกเนื้อหาเอง แบ่งเป็นส่วน ๆ คล้าย part ของบทความ',
        layout: 'text',
        source: 'custom',
        available: true,
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

// ---- Slideshow (จาก banner / จาก article) ----

export type YesNo = 'Y' | 'N';

/** ค่าตั้งค่าที่ Slideshow ทุกแหล่งข้อมูลมีเหมือนกัน (ต่างกันแค่คีย์หมวดหมู่และตัวเลือกการเรียงลำดับ) */
export interface SlideshowCommonSetting {
    sort_by: 'publish_desc' | 'publish_asc' | 'order_asc' | 'order_desc';
    /** จำนวนที่แสดงสูงสุด (0 = แสดงทั้งหมด) */
    max_items: number;
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
    /** การจัดรูปแบบตัวอักษรของหัวเรื่อง/ข้อความเกริ่นนำที่ซ้อนบนภาพ (ชื่อฟิลด์ตามคอลัมน์ `<part>_font_size` ฯลฯ ของ backend) */
    title_font_size: number;
    title_font_family: string;
    title_color: string;
    intro_text_font_size: number;
    intro_text_font_family: string;
    intro_text_color: string;
}

export interface SlideshowBannerSetting extends SlideshowCommonSetting {
    /** หมวดหมู่ banner ที่ดึงมาแสดง (จำเป็นต้องเลือก) */
    banner_category_info_id: number | null;
}

export interface SlideshowArticleSetting extends SlideshowCommonSetting {
    /** หมวดหมู่ article ที่ดึงมาแสดง (จำเป็นต้องเลือก) */
    article_category_info_id: number | null;
}

/** ค่าตั้งค่าของ Slideshow ประเภทใดก็ได้ — คีย์หมวดหมู่ขึ้นกับประเภท (ดู SLIDESHOW_TYPES) */
export type SlideshowSetting = SlideshowBannerSetting | SlideshowArticleSetting;

export const SLIDESHOW_SORT_OPTIONS = [
    { value: 'publish_desc', label: 'วันที่เผยแพร่ล่าสุด' },
    { value: 'publish_asc', label: 'วันที่เผยแพร่เก่าสุด' },
    { value: 'order_asc', label: 'ลำดับน้อยไปมาก' },
    { value: 'order_desc', label: 'ลำดับมากไปน้อย' },
];

/** ส่วนที่ widget "ดึงรายการจากหมวดหมู่" ทุกกลุ่ม (Slideshow / Slideset) ใช้ร่วมกัน — ตรงกับ CategoryListWidget ฝั่ง backend */
export interface ListTypeConfig {
    /** ชื่อฟิลด์หมวดหมู่ใน setting (= คอลัมน์ในตารางตั้งค่าของประเภท) */
    categoryKey: 'banner_category_info_id' | 'article_category_info_id';
    /** คีย์รายการหมวดหมู่ใน `widgetOptions` ที่ backend ส่งมา */
    optionsKey: 'banner_categories' | 'article_categories';
    categoryLabel: string;
    /** ตัวเลือกการเรียงลำดับที่ใช้ได้กับแหล่งข้อมูลนี้ (บทความไม่มีลำดับต่อรายการ จึงเรียงตามวันที่เผยแพร่ได้อย่างเดียว) */
    sortOptions: { value: string; label: string }[];
    /** ข้อความเมื่อหมวดหมู่ที่เลือกไม่มีรายการที่เผยแพร่อยู่ (ในตัวอย่าง) */
    emptyText: string;
}

export interface SlideshowTypeConfig extends ListTypeConfig {
    /** คำอธิบายใต้ "แสดงหัวเรื่องบนภาพ" */
    titleHint: string;
    /** คำอธิบายใต้ตัวเลือก "กดลิงก์ได้" */
    linkHint: string;
}

/** ประเภท widget ที่เป็น Slideshow — ตรงกับ SlideshowBannerWidget / SlideshowArticleWidget ฝั่ง backend */
export const SLIDESHOW_TYPES: Record<string, SlideshowTypeConfig> = {
    slideshowbanner: {
        categoryKey: 'banner_category_info_id',
        optionsKey: 'banner_categories',
        categoryLabel: 'หมวดหมู่ banner',
        sortOptions: SLIDESHOW_SORT_OPTIONS,
        titleHint: 'หัวเรื่องของ banner',
        emptyText: 'ไม่มี banner ที่เผยแพร่อยู่ในหมวดหมู่นี้',
        linkHint: 'ใช้ลิงก์ของ banner — banner ที่ไม่มีลิงก์จะกดไม่ได้',
    },
    slideshowarticle: {
        categoryKey: 'article_category_info_id',
        optionsKey: 'article_categories',
        categoryLabel: 'หมวดหมู่ article',
        sortOptions: SLIDESHOW_SORT_OPTIONS.filter((o) => o.value.startsWith('publish_')),
        titleHint: 'หัวเรื่องของบทความ',
        emptyText: 'ไม่มีบทความที่เผยแพร่อยู่ในหมวดหมู่นี้',
        linkHint: 'ลิงก์ไปหน้าบทความ',
    },
};

export function slideshowConfig(type: string): SlideshowTypeConfig | undefined {
    return SLIDESHOW_TYPES[type];
}

// ---- Slideset (การ์ดหลายใบที่เลื่อนได้) ----

/**
 * ค่าตั้งค่าของ Slideset (จาก article / จาก banner) — ชื่อฟิลด์ตรงกับคอลัมน์ `page_item_widget_slideset<แหล่ง>` (ดู SlidesetWidget ฝั่ง backend)
 * ฟิลด์เฉพาะ article (วันที่/จำนวนเข้าชม/ปุ่มอ่านทั้งหมด) และคีย์หมวดหมู่ (ต่างกันตามแหล่ง) เป็น optional — ใช้ตาม SlidesetTypeConfig
 */
export interface SlidesetSetting {
    article_category_info_id?: number | null;
    banner_category_info_id?: number | null;
    sort_by: 'publish_desc' | 'publish_asc' | 'order_asc' | 'order_desc';
    /** จำนวนที่แสดงสูงสุด (0 = แสดงทั้งหมด) */
    max_items: number;
    show_arrows: YesNo;
    show_dots: YesNo;
    autoplay: YesNo;
    autoplay_interval: number;
    transition_speed: number;
    /** จำนวนการ์ดต่อแถวตามขนาดหน้าจอ (1 - 6) */
    per_row_pc: number;
    per_row_notebook: number;
    per_row_tablet: number;
    per_row_mobile: number;
    show_image: YesNo;
    aspect_ratio: '16:9' | '21:9' | '4:3' | '1:1';
    image_fit: 'cover' | 'contain';
    /** สีพื้นหลังกรอบรูป (hex หรือ transparent) — ใช้เมื่อ image_fit = contain */
    image_background: string;
    image_clickable: YesNo;
    link_target: '_self' | '_blank';
    /** กล่องของการ์ด: แสดงเส้นขอบ + สีเส้นขอบ / มุมมน / สีพื้นหลังของแต่ละรายการ */
    show_border: YesNo;
    border_color: string;
    rounded_corners: YesNo;
    item_background: string;
    show_title: YesNo;
    title_font_size: number;
    title_bold: YesNo;
    title_font_family: string;
    title_color: string;
    title_align: 'left' | 'center' | 'right';
    title_clickable: YesNo;
    title_lines: number;
    show_intro_text: YesNo;
    intro_text_font_size: number;
    intro_text_bold: YesNo;
    intro_text_font_family: string;
    intro_text_color: string;
    intro_text_align: 'left' | 'center' | 'right';
    intro_text_clickable: YesNo;
    intro_text_lines: number;
    // ---- เฉพาะ article ----
    show_date?: YesNo;
    date_font_size?: number;
    date_bold?: YesNo;
    date_font_family?: string;
    date_color?: string;
    show_views?: YesNo;
    views_font_size?: number;
    views_bold?: YesNo;
    views_font_family?: string;
    views_color?: string;
    show_read_all?: YesNo;
    read_all_position?: ReadAllPosition;
    /** ข้อความแทน "อ่านทั้งหมด" แยกภาษา (ภาษา → ข้อความ; ว่าง = ใช้ข้อความมาตรฐาน) */
    read_all_text?: Record<string, string>;
    read_all_icon?: ReadAllIcon;
    read_all_icon_position?: ReadAllIconPosition;
    read_all_style?: ReadAllStyle;
    /** ตัวอักษรของปุ่ม (ทุกรูปแบบ) + สีพื้นหลัง (ใช้เมื่อเป็นแบบปุ่ม/ปุ่มมนใหญ่) */
    read_all_font_size?: number;
    read_all_font_family?: string;
    read_all_color?: string;
    read_all_background?: string;
    read_all_url?: string;
    read_all_link_target?: '_self' | '_blank';
}

export interface SlidesetTypeConfig extends ListTypeConfig {
    /** มีส่วนวันที่เผยแพร่/จำนวนเข้าชมของการ์ด (article) */
    hasMeta: boolean;
    /** มีปุ่ม "อ่านทั้งหมด" (article) */
    hasReadAll: boolean;
}

/** ประเภท widget ที่เป็น Slideset — ตรงกับ SlidesetArticleWidget / SlidesetBannerWidget ฝั่ง backend */
export const SLIDESET_TYPES: Record<string, SlidesetTypeConfig> = {
    slidesetarticle: {
        categoryKey: 'article_category_info_id',
        optionsKey: 'article_categories',
        categoryLabel: 'หมวดหมู่ article',
        sortOptions: SLIDESHOW_SORT_OPTIONS.filter((o) => o.value.startsWith('publish_')),
        emptyText: 'ไม่มีบทความที่เผยแพร่อยู่ในหมวดหมู่นี้',
        hasMeta: true,
        hasReadAll: true,
    },
    slidesetbanner: {
        categoryKey: 'banner_category_info_id',
        optionsKey: 'banner_categories',
        categoryLabel: 'หมวดหมู่ banner',
        sortOptions: SLIDESHOW_SORT_OPTIONS,
        emptyText: 'ไม่มี banner ที่เผยแพร่อยู่ในหมวดหมู่นี้',
        hasMeta: false,
        hasReadAll: false,
    },
};

export function slidesetConfig(type: string): SlidesetTypeConfig | undefined {
    return SLIDESET_TYPES[type];
}

/**
 * ค่าตั้งค่าของ widget ที่แสดงรายการเป็น "การ์ด/แถว" ของแต่ละรายการ (Slideset หรือ Grid) — ใช้เป็นชนิดของ prop `setting` ที่ component
 * ของส่วนย่อยบนการ์ด (เช่น `widgets/SlidesetTextFields.vue`) รับ เพราะสองประเภทนี้ใช้ชื่อฟิลด์ของแต่ละส่วน (`<part>_font_size` ฯลฯ) ตรงกัน
 */
export type CardListSetting = SlidesetSetting | GridSetting;

// ---- Grid (กล่องเรียงต่อเนื่องหลายคอลัมน์ ไม่เลื่อน) ----

/** รูปแบบการแสดงผลของ Grid — ตรงกับ GridArticleWidget::DISPLAY_TYPES ฝั่ง backend */
export type GridDisplayType = 'card' | 'row_image' | 'row_date';

/**
 * ค่าตั้งค่าของ Grid (จาก article / จาก banner) — ชื่อฟิลด์ตรงกับคอลัมน์ `page_item_widget_grid<แหล่ง>` (ดู GridArticleWidget/
 * GridBannerWidget ฝั่ง backend) ส่วนของการ์ด/ข้อความ ใช้ชุดฟิลด์เดียวกับ Slideset (ไม่มี carousel เพราะ Grid ไม่เลื่อน)
 * ฟิลด์เฉพาะ article (วันที่/จำนวนเข้าชม/ปุ่มอ่านทั้งหมด/กล่องวันที่เผยแพร่) และคีย์หมวดหมู่ (ต่างกันตามแหล่ง) เป็น optional — ใช้ตาม GridTypeConfig
 */
export interface GridSetting {
    article_category_info_id?: number | null;
    banner_category_info_id?: number | null;
    sort_by: 'publish_desc' | 'publish_asc' | 'order_asc' | 'order_desc';
    /** จำนวนที่แสดงสูงสุด (0 = แสดงทั้งหมด) */
    max_items: number;
    display_type: GridDisplayType;
    /** จำนวนคอลัมน์ต่อแถวตามขนาดหน้าจอ (1 - 6) */
    per_row_pc: number;
    per_row_notebook: number;
    per_row_tablet: number;
    per_row_mobile: number;
    link_target: '_self' | '_blank';
    show_image: YesNo;
    /** ความกว้างของพื้นที่แสดงรูปภาพ (%) — ใช้เฉพาะรูปแบบ "แถวที่มีรูปภาพ" (5 - 50) */
    image_width_percent: number;
    aspect_ratio: '16:9' | '21:9' | '4:3' | '1:1';
    image_fit: 'cover' | 'contain';
    image_background: string;
    image_clickable: YesNo;
    /** กล่องของการ์ด/แถว: แสดงเส้นขอบ + สีเส้นขอบ / มุมมน / สีพื้นหลังของแต่ละรายการ */
    show_border: YesNo;
    border_color: string;
    rounded_corners: YesNo;
    item_background: string;
    show_title: YesNo;
    title_font_size: number;
    title_bold: YesNo;
    title_font_family: string;
    title_color: string;
    title_align: 'left' | 'center' | 'right';
    title_clickable: YesNo;
    title_lines: number;
    show_intro_text: YesNo;
    intro_text_font_size: number;
    intro_text_bold: YesNo;
    intro_text_font_family: string;
    intro_text_color: string;
    intro_text_align: 'left' | 'center' | 'right';
    intro_text_clickable: YesNo;
    intro_text_lines: number;
    // ---- เฉพาะ article ----
    show_date?: YesNo;
    date_font_size?: number;
    date_bold?: YesNo;
    date_font_family?: string;
    date_color?: string;
    show_views?: YesNo;
    views_font_size?: number;
    views_bold?: YesNo;
    views_font_family?: string;
    views_color?: string;
    /** ตัวอักษรของ "กล่องวันที่เผยแพร่" (ใช้แทนรูปภาพในรูปแบบ row_date) — แยกเลขวัน (ตัวใหญ่) กับเดือน/ปี (ตัวเล็ก) คนละส่วนกัน */
    date_day_font_size?: number;
    date_day_bold?: YesNo;
    date_day_font_family?: string;
    date_day_color?: string;
    date_month_font_size?: number;
    date_month_bold?: YesNo;
    date_month_font_family?: string;
    date_month_color?: string;
    date_box_background?: string;
    show_read_all?: YesNo;
    read_all_position?: ReadAllPosition;
    /** ข้อความแทน "อ่านทั้งหมด" แยกภาษา (ภาษา → ข้อความ; ว่าง = ใช้ข้อความมาตรฐาน) */
    read_all_text?: Record<string, string>;
    read_all_icon?: ReadAllIcon;
    read_all_icon_position?: ReadAllIconPosition;
    read_all_style?: ReadAllStyle;
    read_all_font_size?: number;
    read_all_font_family?: string;
    read_all_color?: string;
    read_all_background?: string;
    read_all_url?: string;
    read_all_link_target?: '_self' | '_blank';
}

export interface GridTypeConfig extends ListTypeConfig {
    /** มีวันที่เผยแพร่/จำนวนเข้าชม/กล่องวันที่เผยแพร่ (article) */
    hasMeta: boolean;
    /** มีปุ่ม "อ่านทั้งหมด" (article) */
    hasReadAll: boolean;
    /** ตัวเลือก "รูปแบบการแสดงผล" ที่ใช้ได้กับแหล่งข้อมูลนี้ (banner ไม่มี "แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ") */
    displayTypeOptions: { value: GridDisplayType; label: string; description: string }[];
}

export const GRID_DISPLAY_TYPE_OPTIONS: { value: GridDisplayType; label: string; description: string }[] = [
    { value: 'card', label: 'การ์ด', description: 'ทุกส่วนเปิด/ปิดเองได้' },
    { value: 'row_image', label: 'แถวที่มีรูปภาพ', description: 'แบ่ง 2 ส่วน: รูป (กว้างเป็น %) + ข้อมูล' },
    { value: 'row_date', label: 'แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ', description: 'แบ่ง 2 ส่วน: กล่องวันที่ + ข้อมูล' },
];

/** ประเภท widget ที่เป็น Grid — ตรงกับ GridArticleWidget / GridBannerWidget ฝั่ง backend */
export const GRID_TYPES: Record<string, GridTypeConfig> = {
    gridarticle: {
        categoryKey: 'article_category_info_id',
        optionsKey: 'article_categories',
        categoryLabel: 'หมวดหมู่ article',
        sortOptions: SLIDESHOW_SORT_OPTIONS.filter((o) => o.value.startsWith('publish_')),
        emptyText: 'ไม่มีบทความที่เผยแพร่อยู่ในหมวดหมู่นี้',
        hasMeta: true,
        hasReadAll: true,
        displayTypeOptions: GRID_DISPLAY_TYPE_OPTIONS,
    },
    gridbanner: {
        categoryKey: 'banner_category_info_id',
        optionsKey: 'banner_categories',
        categoryLabel: 'หมวดหมู่ banner',
        sortOptions: SLIDESHOW_SORT_OPTIONS,
        emptyText: 'ไม่มี banner ที่เผยแพร่อยู่ในหมวดหมู่นี้',
        hasMeta: false,
        hasReadAll: false,
        displayTypeOptions: GRID_DISPLAY_TYPE_OPTIONS.filter((o) => o.value !== 'row_date'),
    },
};

export function gridConfig(type: string): GridTypeConfig | undefined {
    return GRID_TYPES[type];
}

export const GRID_IMAGE_WIDTH_RANGE = { min: 5, max: 50 } as const;

/** หมวดหมู่/ตัวเลือกร่วมของ widget ที่ดึงรายการจากหมวดหมู่ (Slideshow, Slideset หรือ Grid) */
export function listConfig(type: string): ListTypeConfig | undefined {
    return slideshowConfig(type) ?? slidesetConfig(type) ?? gridConfig(type);
}

export const SLIDESET_PER_ROW_RANGE = { min: 1, max: 6 } as const;

/** ขนาดหน้าจอที่กำหนดจำนวนการ์ดต่อแถว (breakpoint = ความกว้างหน้าจอขั้นต่ำ px — ตรงกับ Tailwind xl/lg/md ที่หน้าบ้านจะใช้) */
export const SLIDESET_DEVICES = [
    { key: 'pc', label: 'PC', range: '1280 px ขึ้นไป', minWidth: 1280 },
    { key: 'notebook', label: 'Notebook', range: '1024 - 1279 px', minWidth: 1024 },
    { key: 'tablet', label: 'Tablet', range: '768 - 1023 px', minWidth: 768 },
    { key: 'mobile', label: 'Mobile', range: 'ต่ำกว่า 768 px', minWidth: 0 },
] as const;

export type SlidesetDevice = (typeof SLIDESET_DEVICES)[number]['key'];

/** ขนาดหน้าจอที่ตรงกับความกว้างหน้าต่างเบราว์เซอร์ตอนนี้ — ใช้เป็นค่าเริ่มต้นของตัวเลือกดูตัวอย่างตามขนาดหน้าจอ */
export function currentDevice(width: number = typeof window === 'undefined' ? 1280 : window.innerWidth): SlidesetDevice {
    return SLIDESET_DEVICES.find((d) => width >= d.minWidth)!.key;
}

export const SLIDESET_LINES_OPTIONS = [1, 2, 3].map((n) => ({ value: String(n), label: `${n} บรรทัด` }));

export const SLIDESET_PER_ROW_OPTIONS = [1, 2, 3, 4, 5, 6].map((n) => ({ value: String(n), label: `${n} รายการ` }));

/** สีพื้นหลังเริ่มต้นของกรอบรูปเมื่อแสดงแบบ contain (เทาอ่อน = bg-gray-100 เหมือนกรอบรูปในหน้าจัดการไฟล์) */
export const SLIDESET_DEFAULT_IMAGE_BACKGROUND = '#F3F4F6';

/** สีเส้นขอบเริ่มต้นของกล่อง/แถวที่ครอบแต่ละรายการ (เทาอ่อน = border-gray-200 — ตรงกับ CategoryListWidget::DEFAULT_BORDER_COLOR ฝั่ง backend) */
export const DEFAULT_BORDER_COLOR = '#E5E7EB';

export const SLIDESET_IMAGE_FIT_OPTIONS = [
    { value: 'cover', label: 'Cover — เต็มกรอบ (ครอปส่วนเกิน)' },
    { value: 'contain', label: 'Contain — เห็นทั้งภาพ (เว้นขอบ)' },
];

export const SLIDESHOW_INTERVAL_RANGE = { min: 1, max: 60 } as const;
export const SLIDESHOW_MAX_ITEMS_LIMIT = 1000;
export const SLIDESHOW_SPEED_RANGE = { min: 100, max: 3000 } as const;

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

/** ค่าตั้งค่าเริ่มต้นของ Slideshow ประเภทนั้น — ต้องตรงกับ `defaults()` ของ SlideshowWidget ฝั่ง backend */
export function defaultSlideshowSetting(type: string): SlideshowSetting {
    const common: SlideshowCommonSetting = {
        sort_by: 'publish_desc',
        max_items: 0,
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
        // ข้อความบนภาพ: ขนาด/ฟอนต์/สี (default ขาว เพราะซ้อนบนภาพ)
        title_font_size: 20,
        title_font_family: 'Sarabun',
        title_color: '#FFFFFF',
        intro_text_font_size: 16,
        intro_text_font_family: 'Sarabun',
        intro_text_color: '#FFFFFF',
    };

    return type === 'slideshowarticle' ? { ...common, article_category_info_id: null } : { ...common, banner_category_info_id: null };
}

/**
 * ค่าตั้งค่าเริ่มต้นของ Slideset — ต้องตรงกับ `fields()` ของ SlidesetArticleWidget / SlidesetBannerWidget ฝั่ง backend
 * `languages` = รหัสภาษาที่เปิดใช้ (ไว้สร้างช่องข้อความแยกภาษาของปุ่มอ่านทั้งหมด)
 */
export function defaultSlidesetSetting(type: string, languages: string[] = []): SlidesetSetting {
    const article = type === 'slidesetarticle';
    const common: SlidesetSetting = {
        sort_by: 'publish_desc',
        max_items: 0,
        show_arrows: 'Y',
        show_dots: 'Y',
        autoplay: 'N',
        autoplay_interval: 5,
        transition_speed: 500,
        per_row_pc: 4,
        per_row_notebook: 3,
        per_row_tablet: 2,
        per_row_mobile: 1,
        show_image: 'Y',
        aspect_ratio: '16:9',
        image_fit: 'cover',
        image_background: SLIDESET_DEFAULT_IMAGE_BACKGROUND,
        image_clickable: 'Y',
        link_target: '_self',
        show_border: 'Y',
        border_color: DEFAULT_BORDER_COLOR,
        rounded_corners: 'Y',
        item_background: '#FFFFFF',
        show_title: 'Y',
        title_font_size: 18,
        title_bold: 'Y',
        title_font_family: 'Sarabun',
        title_color: '#000000',
        title_align: 'left',
        title_clickable: 'Y',
        title_lines: 1,
        // ข้อความเกริ่นนำ: article แสดงเป็นค่าเริ่มต้น, banner ซ่อน
        show_intro_text: article ? 'Y' : 'N',
        intro_text_font_size: 14,
        intro_text_bold: 'N',
        intro_text_font_family: 'Sarabun',
        intro_text_color: '#000000',
        intro_text_align: 'left',
        intro_text_clickable: 'N',
        intro_text_lines: 2,
    };

    if (!article) {
        return { ...common, banner_category_info_id: null };
    }

    return {
        ...common,
        article_category_info_id: null,
        show_date: 'Y',
        date_font_size: 12,
        date_bold: 'N',
        date_font_family: 'Sarabun',
        date_color: '#667085',
        show_views: 'N',
        views_font_size: 12,
        views_bold: 'N',
        views_font_family: 'Sarabun',
        views_color: '#667085',
        show_read_all: 'N',
        read_all_position: 'bottom_center',
        read_all_text: Object.fromEntries(languages.map((code) => [code, ''])),
        read_all_icon: 'arrow_right',
        read_all_icon_position: 'after',
        read_all_style: 'button',
        read_all_font_size: 14,
        read_all_font_family: 'Sarabun',
        read_all_color: READ_ALL_DEFAULT_COLORS.button,
        read_all_background: READ_ALL_DEFAULT_BACKGROUND,
        read_all_url: '',
        read_all_link_target: '_self',
    };
}

/**
 * ค่าตั้งค่าเริ่มต้นของ Grid — ต้องตรงกับ `fields()` ของ GridArticleWidget / GridBannerWidget ฝั่ง backend
 * `languages` = รหัสภาษาที่เปิดใช้ (ไว้สร้างช่องข้อความแยกภาษาของปุ่มอ่านทั้งหมด — เฉพาะ article)
 */
export function defaultGridSetting(type: string, languages: string[] = []): GridSetting {
    const article = type === 'gridarticle';
    const common: GridSetting = {
        sort_by: 'publish_desc',
        max_items: 0,
        display_type: 'card',
        per_row_pc: 4,
        per_row_notebook: 3,
        per_row_tablet: 2,
        per_row_mobile: 1,
        link_target: '_self',
        show_image: 'Y',
        image_width_percent: 20,
        aspect_ratio: '16:9',
        image_fit: 'cover',
        image_background: SLIDESET_DEFAULT_IMAGE_BACKGROUND,
        image_clickable: 'Y',
        show_border: 'Y',
        border_color: DEFAULT_BORDER_COLOR,
        rounded_corners: 'Y',
        item_background: '#FFFFFF',
        show_title: 'Y',
        title_font_size: 18,
        title_bold: 'Y',
        title_font_family: 'Sarabun',
        title_color: '#000000',
        title_align: 'left',
        title_clickable: 'Y',
        title_lines: 1,
        show_intro_text: 'N',
        intro_text_font_size: 14,
        intro_text_bold: 'N',
        intro_text_font_family: 'Sarabun',
        intro_text_color: '#000000',
        intro_text_align: 'left',
        intro_text_clickable: 'N',
        intro_text_lines: 2,
    };

    if (!article) {
        return { ...common, banner_category_info_id: null };
    }

    return {
        ...common,
        article_category_info_id: null,
        show_date: 'Y',
        date_font_size: 12,
        date_bold: 'N',
        date_font_family: 'Sarabun',
        date_color: '#667085',
        show_views: 'N',
        views_font_size: 12,
        views_bold: 'N',
        views_font_family: 'Sarabun',
        views_color: '#667085',
        date_day_font_size: 18,
        date_day_bold: 'Y',
        date_day_font_family: 'Sarabun',
        date_day_color: '#374151',
        date_month_font_size: 11,
        date_month_bold: 'N',
        date_month_font_family: 'Sarabun',
        date_month_color: '#9CA3AF',
        date_box_background: SLIDESET_DEFAULT_IMAGE_BACKGROUND,
        show_read_all: 'N',
        read_all_position: 'bottom_center',
        read_all_text: Object.fromEntries(languages.map((code) => [code, ''])),
        read_all_icon: 'arrow_right',
        read_all_icon_position: 'after',
        read_all_style: 'button',
        read_all_font_size: 14,
        read_all_font_family: 'Sarabun',
        read_all_color: READ_ALL_DEFAULT_COLORS.button,
        read_all_background: READ_ALL_DEFAULT_BACKGROUND,
        read_all_url: '',
        read_all_link_target: '_self',
    };
}

/** ค่าตั้งค่าเริ่มต้นของ widget ประเภทนั้น (ประเภทที่ไม่มีการตั้งค่า = object ว่าง) */
export function defaultSetting(type: string, languages: string[] = []): Record<string, unknown> {
    if (slideshowConfig(type)) {
        return { ...defaultSlideshowSetting(type) };
    }

    if (slidesetConfig(type)) {
        return { ...defaultSlidesetSetting(type, languages) };
    }

    if (gridConfig(type)) {
        return { ...defaultGridSetting(type, languages) };
    }

    // customtext เก็บเป็นรายการ part แทนฟิลด์แบน จึงไม่ผสานแบบ shallow merge เหมือนประเภทอื่น (ดู settingFromServer ด้านล่าง)
    return isCustomTextWidget(type) ? { ...defaultCustomTextSetting() } : {};
}

/**
 * ผสานค่าที่ backend ส่งมากับค่าเริ่มต้น (กันคีย์ขาดหาย) — PHP ส่ง array ว่างมาเป็น [] จึงรับได้ทั้ง array/object/null
 * customtext เก็บเป็นรายการ part (ไม่ใช่ฟิลด์แบน) จึงต้อง hydrate ทีละ part (เติม `_key`/ห่อไฟล์เป็น FileItem[]) แยกต่างหาก
 * แทนการ shallow merge ธรรมดาเหมือนประเภทอื่น
 */
export function settingFromServer(type: string, setting: Record<string, unknown> | unknown[] | null | undefined): Record<string, unknown> {
    if (isCustomTextWidget(type)) {
        return customTextSettingFromServer(setting) as unknown as Record<string, unknown>;
    }

    const values = setting && !Array.isArray(setting) ? setting : {};

    return { ...defaultSetting(type), ...values };
}

/**
 * ตรวจค่าตั้งค่าก่อนกด "ตกลง" — คืนข้อความ error ตามชื่อฟิลด์ (ว่าง = ผ่าน) backend ตรวจซ้ำอีกชั้นตอนบันทึกโครงสร้าง
 */
export function validateSetting(type: string, setting: Record<string, unknown>): Record<string, string> {
    const errors: Record<string, string> = {};
    const config = listConfig(type);

    if (config) {
        const s = setting as unknown as SlideshowCommonSetting & Record<string, unknown>;

        if (!s[config.categoryKey]) {
            errors[config.categoryKey] = `กรุณาเลือก${config.categoryLabel}`;
        }

        if (!Number.isInteger(s.max_items) || s.max_items < 0 || s.max_items > SLIDESHOW_MAX_ITEMS_LIMIT) {
            errors.max_items = `จำนวนที่แสดงสูงสุดต้องอยู่ระหว่าง 0 - ${SLIDESHOW_MAX_ITEMS_LIMIT} (0 = แสดงทั้งหมด)`;
        }

        // การเลื่อนอัตโนมัติ (carousel) — เฉพาะ Slideshow และ Slideset (Grid ไม่เลื่อน จึงไม่มีฟิลด์พวกนี้)
        const carousel = slideshowConfig(type) ?? slidesetConfig(type);

        if (carousel) {
            if (!Number.isInteger(s.autoplay_interval) || s.autoplay_interval < SLIDESHOW_INTERVAL_RANGE.min || s.autoplay_interval > SLIDESHOW_INTERVAL_RANGE.max) {
                errors.autoplay_interval = `ระยะเวลาค้างต่อภาพต้องอยู่ระหว่าง ${SLIDESHOW_INTERVAL_RANGE.min} - ${SLIDESHOW_INTERVAL_RANGE.max} วินาที`;
            }

            if (!Number.isInteger(s.transition_speed) || s.transition_speed < SLIDESHOW_SPEED_RANGE.min || s.transition_speed > SLIDESHOW_SPEED_RANGE.max) {
                errors.transition_speed = `ความเร็วในการเปลี่ยนภาพต้องอยู่ระหว่าง ${SLIDESHOW_SPEED_RANGE.min} - ${SLIDESHOW_SPEED_RANGE.max} มิลลิวินาที`;
            }
        }

        // ปุ่ม "อ่านทั้งหมด" — Slideset จาก article และ Grid จาก article
        const slideset = slidesetConfig(type);
        const grid = gridConfig(type);
        const readAll = slideset?.hasReadAll ? slideset : grid?.hasReadAll ? grid : undefined;

        if (readAll && s.show_read_all === 'Y') {
            const url = String(s.read_all_url ?? '').trim();

            if (url === '') {
                errors.read_all_url = 'กรุณากรอกลิงก์ปลายทางของปุ่มอ่านทั้งหมด (ต้องกรอกเมื่อเปิดแสดงปุ่ม)';
            } else if (!READ_ALL_URL_PATTERN.test(url)) {
                errors.read_all_url = 'ลิงก์ปลายทางต้องขึ้นต้นด้วย http://, https:// หรือ / (หรือ #, mailto:, tel:)';
            }
        }

        // จำนวนที่แสดงต่อแถวตามขนาดหน้าจอ — Slideset และ Grid
        if (slideset ?? grid) {
            SLIDESET_DEVICES.forEach(({ key, label }) => {
                const value = s[`per_row_${key}`] as number;

                if (!Number.isInteger(value) || value < SLIDESET_PER_ROW_RANGE.min || value > SLIDESET_PER_ROW_RANGE.max) {
                    errors[`per_row_${key}`] = `จำนวนที่แสดงต่อแถว (${label}) ต้องอยู่ระหว่าง ${SLIDESET_PER_ROW_RANGE.min} - ${SLIDESET_PER_ROW_RANGE.max}`;
                }
            });
        }

        // Grid: ความกว้างของพื้นที่แสดงรูปภาพ — ใช้เฉพาะรูปแบบ "แถวที่มีรูปภาพ"
        if (grid && s.display_type === 'row_image') {
            const value = s.image_width_percent as number;

            if (!Number.isInteger(value) || value < GRID_IMAGE_WIDTH_RANGE.min || value > GRID_IMAGE_WIDTH_RANGE.max) {
                errors.image_width_percent = `ความกว้างของพื้นที่แสดงรูปภาพต้องอยู่ระหว่าง ${GRID_IMAGE_WIDTH_RANGE.min} - ${GRID_IMAGE_WIDTH_RANGE.max}%`;
            }
        }
    }

    return errors;
}

/**
 * มองฟิลด์ตัวอักษรแบบแบนของ setting (`<part>_font_size`, `<part>_font_family`, `<part>_color` และ `<part>_align` ถ้ามี) เป็น `TextStyle`
 * ให้ใช้กับ TextStyleFields ได้ตรง ๆ — อ่าน/เขียนผ่าน object เดิมของ setting เสมอ (ส่วนที่ไม่มี `<part>_align` ใช้ซ่อนตัวเลือกจัดตำแหน่งด้วย show-align=false)
 */
export function settingTextStyle(setting: object, part: string): TextStyle {
    const record = setting as Record<string, number | string>;

    return reactive({
        get font_size() {
            return Number(record[`${part}_font_size`]);
        },
        set font_size(value: number) {
            record[`${part}_font_size`] = value;
        },
        get font_family() {
            return String(record[`${part}_font_family`]);
        },
        set font_family(value: string) {
            record[`${part}_font_family`] = value;
        },
        get color() {
            return String(record[`${part}_color`]);
        },
        set color(value: string) {
            record[`${part}_color`] = value;
        },
        get align() {
            return (record[`${part}_align`] ?? 'left') as TextStyle['align'];
        },
        set align(value: TextStyle['align']) {
            if (`${part}_align` in record) {
                record[`${part}_align`] = value;
            }
        },
    }) as TextStyle;
}
