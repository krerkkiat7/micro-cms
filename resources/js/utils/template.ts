import type { CSSProperties } from 'vue';
import type { FileItem } from '@/types';
import { backgroundStyle, cloneDeep } from '@/utils/pageLayout';
import type { BackgroundFields } from '@/utils/pageLayout';

/**
 * ชนิดข้อมูล/ตัวเลือก/ตัวช่วยของโมดูลจัดการ Template (system.template) — ฝั่ง backend ดู App\Support\Template\TemplateZone
 * (ชื่อฟิลด์ของแต่ละโซนต้องตรงกันทั้งสองฝั่ง) และ App\Support\Template\TemplatePreset
 */

export type YN = 'Y' | 'N';
export type Align = 'left' | 'center' | 'right';
export type Width = 'full' | 'container';

export interface HeaderZone extends BackgroundFields {
    status: YN;
    layout_type: 'topbar_main' | 'main_menubar' | 'main_only';
    sticky: YN;
    logo_status: YN;
    logo_align: Align;
    logo_display: 'image' | 'image_name' | 'name';
    logo_action: 'none' | 'home';
    menu_align: Align;
    menu_style: 'plain' | 'underline' | 'pill' | 'divider';
    menu_text_color: string;
    menu_active_color: string;
    lang_status: YN;
    lang_display: 'code' | 'flag' | 'flag_code';
    lang_select: 'all' | 'dropdown';
    social_status: YN;
    search_status: YN;
    fontsize_status: YN;
    fontsize_display: 'icon' | 'text';
    contrast_status: YN;
    contrast_display: 'icon' | 'text';
    main_width: Width;
    main_text_color: string;
    topbar_width: Width;
    topbar_background_color: string;
    topbar_text_color: string;
    menubar_width: Width;
    menubar_background_color: string;
}

export type BodyZone = BackgroundFields;

export interface FooterZone extends BackgroundFields {
    status: YN;
    layout_type: 'site_contact_menu' | 'site_contact_center' | 'site_contact_block';
    width: Width;
    heading_color: string;
    heading_font_size: number;
    heading_font_family: string;
    heading_bold: YN;
    text_color: string;
    text_font_size: number;
    text_font_family: string;
    text_bold: YN;
    show_address: YN;
    show_phone: YN;
    show_fax: YN;
    show_mobile: YN;
    show_email: YN;
    show_social: YN;
    show_menu: YN;
    copyright_status: YN;
    copyright_width: Width;
    copyright_background_color: string;
    copyright_text_color: string;
    copyright_font_size: number;
    copyright_font_family: string;
    copyright_align: Align;
    copyright_show_owner: YN;
}

export interface AsideZone extends BackgroundFields {
    status: YN;
    toggle_position: 'left' | 'right';
    display_type: 'fullscreen' | 'drawer';
    text_color: string;
    menu_style: 'list' | 'accordion' | 'drilldown' | 'large';
}

export interface TemplateZones {
    header: HeaderZone;
    body: BodyZone;
    footer: FooterZone;
    aside: AsideZone;
}

export type ZoneName = keyof TemplateZones;

/** รูปแบบที่ backend ส่งมา (TemplateController::layout) — พื้นหลังเป็น FileItem เดี่ยว/null และค่าว่างเป็น null */
type ServerZone<T> = Omit<T, keyof BackgroundFields> & {
    background_color: string | null;
    background_image: FileItem | null;
    background_image_id?: number | null;
    background_repeat: string | null;
    background_size: string | null;
    background_attachment: string | null;
    background_position: string | null;
};

export type ServerZones = { [K in ZoneName]: ServerZone<TemplateZones[K]> };

/** เมนูหน้าบ้าน (tree) สำหรับแสดงตัวอย่าง — App\Support\FrontMenuTree */
export interface PreviewMenu {
    id: number;
    name: string;
    /** App\Support\FrontMenuType (heading / none / external / article_category / article_item / page) */
    menu_type: string;
    children: PreviewMenu[];
}

/** ประเภทเมนูที่เป็นลิงก์จริง (ของในระบบ + ลิงก์ภายนอก) — เมนูย่อยใน footer แสดงเฉพาะประเภทเหล่านี้ */
export const LINK_MENU_TYPES = ['external', 'article_category', 'article_item', 'page'];

/** ข้อมูลจริงของระบบที่ใช้แสดงตัวอย่าง (TemplateController::previewData) */
export interface TemplatePreviewData {
    siteName: string;
    logoUrl: string | null;
    languages: string[];
    defaultLanguage: string;
    contact: { owner: string | null; address: string | null; phone: string | null; fax: string | null; mobile: string | null; email: string | null };
    social: string[];
    copyright: { year: string; owner: string };
    menu: PreviewMenu[];
}

function zoneFromServer<T extends BackgroundFields>(zone: ServerZone<T>): T {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const { background_image_id, ...rest } = zone;

    return {
        ...rest,
        background_color: zone.background_color ?? '',
        background_image: zone.background_image ? [zone.background_image] : [],
        background_repeat: zone.background_repeat ?? '',
        background_size: zone.background_size ?? '',
        background_attachment: zone.background_attachment ?? '',
        background_position: zone.background_position ?? '',
    } as unknown as T;
}

export function zonesFromServer(zones: ServerZones): TemplateZones {
    return {
        header: zoneFromServer<HeaderZone>(zones.header),
        body: zoneFromServer<BodyZone>(zones.body),
        footer: zoneFromServer<FooterZone>(zones.footer),
        aside: zoneFromServer<AsideZone>(zones.aside),
    };
}

function zoneToPayload(zone: BackgroundFields): Record<string, unknown> {
    const { background_image, ...rest } = cloneDeep(zone);

    return {
        ...rest,
        background_color: rest.background_color || null,
        background_image_id: background_image[0]?.id ?? null,
        // ไม่มีรูปพื้นหลัง = ค่า CSS 4 ตัวไม่มีความหมาย เคลียร์ทิ้ง
        background_repeat: (background_image.length && rest.background_repeat) || null,
        background_size: (background_image.length && rest.background_size) || null,
        background_attachment: (background_image.length && rest.background_attachment) || null,
        background_position: (background_image.length && rest.background_position) || null,
    };
}

export function zonesToPayload(zones: TemplateZones): Record<ZoneName, Record<string, unknown>> {
    return {
        header: zoneToPayload(zones.header),
        body: zoneToPayload(zones.body),
        footer: zoneToPayload(zones.footer),
        aside: zoneToPayload(zones.aside),
    };
}

/** style พื้นหลังของโซน — ใช้ตัวเดียวกับหน้าโครงสร้างของ page */
export function zoneBackgroundStyle(zone: BackgroundFields): CSSProperties {
    return backgroundStyle(zone);
}

// ---------------------------------------------------------------- ตัวเลือก

interface Choice<T extends string = string> {
    value: T;
    label: string;
    description?: string;
}

/** แม่แบบตั้งต้นตอนเพิ่ม — ต้องตรงกับ TemplatePreset::OPTIONS */
export const TEMPLATE_PRESETS: Choice[] = [
    { value: 'classic', label: 'องค์กร / หน่วยงาน', description: 'แถบบนสีเข้ม (social / ภาษา / เครื่องมือช่วยอ่าน) + แถบหลักสีขาว โลโก้ซ้าย เมนูขวา, footer 3 ส่วน' },
    { value: 'corporate', label: 'แถวเมนูเด่น', description: 'แถบหลักสีขาว + แถวเมนูสีแบรนด์เต็มจอ เหมาะกับเว็บที่มีเมนูเยอะ' },
    { value: 'centered', label: 'จัดกึ่งกลาง', description: 'โลโก้และเมนูอยู่กึ่งกลาง เมนูคั่นด้วยเส้น, footer จัดกึ่งกลาง' },
    { value: 'minimal', label: 'เรียบง่าย', description: 'แถบหลักแถบเดียวติดด้านบนตอนเลื่อน, เมนูข้างแบบเต็มจอ' },
    { value: 'dark', label: 'โทนมืด', description: 'header / footer / เมนูข้างโทนมืด เนื้อหาพื้นอ่อน' },
];

export const HEADER_LAYOUTS: Choice<HeaderZone['layout_type']>[] = [
    { value: 'topbar_main', label: 'มีแถบบน และแถบหลัก', description: 'แถบบนเล็กสำหรับเครื่องมือ + แถบหลักมีโลโก้และเมนู' },
    { value: 'main_menubar', label: 'มีแถบหลัก และแถวเมนู', description: 'แถบหลักมีโลโก้และเครื่องมือ + แถวเมนูแยกด้านล่าง' },
    { value: 'main_only', label: 'มีแถบหลักเท่านั้น', description: 'โลโก้ เมนู และเครื่องมืออยู่ในแถบเดียว' },
];

export const HEADER_MENU_STYLES: Choice<HeaderZone['menu_style']>[] = [
    { value: 'plain', label: 'ตัวอักษรเรียบ', description: 'เมนูที่เลือกอยู่เปลี่ยนสีตัวอักษร' },
    { value: 'underline', label: 'เส้นใต้', description: 'มีเส้นใต้เมนูที่เลือกอยู่/ชี้อยู่' },
    { value: 'pill', label: 'พื้นมน', description: 'เมนูที่เลือกอยู่มีพื้นหลังมน' },
    { value: 'divider', label: 'คั่นด้วยเส้น', description: 'มีเส้นตั้งคั่นระหว่างเมนู' },
];

export const FOOTER_LAYOUTS: Choice<FooterZone['layout_type']>[] = [
    { value: 'site_contact_menu', label: 'ข้อมูลไซต์, ข้อมูลติดต่อ และเมนู', description: '3 คอลัมน์: ข้อมูลไซต์ / ข้อมูลติดต่อ / เมนู' },
    { value: 'site_contact_center', label: 'ข้อมูลไซต์, ข้อมูลติดต่อ (จัดกึ่งกลาง)', description: 'เรียงต่อกันกึ่งกลางหน้าจอ' },
    { value: 'site_contact_block', label: 'ข้อมูลไซต์, ข้อมูลติดต่อ (จัดเป็นบล็อก)', description: 'แบ่งเป็น 2 บล็อกซ้าย-ขวา' },
];

export const ASIDE_DISPLAYS: Choice<AsideZone['display_type']>[] = [
    { value: 'drawer', label: 'แถบข้าง', description: 'เลื่อนออกมาจากขอบจอ กว้างประมาณ 320px' },
    { value: 'fullscreen', label: 'เต็มจอ', description: 'ทับทั้งหน้าจอ' },
];

export const ASIDE_MENU_STYLES: Choice<AsideZone['menu_style']>[] = [
    { value: 'list', label: 'รายการ', description: 'เมนูเรียงลงมามีเส้นคั่น เมนูย่อยแสดงเยื้องเข้าไป' },
    { value: 'accordion', label: 'ย่อ/ขยาย', description: 'กดที่เมนูเพื่อขยายเมนูย่อย' },
    { value: 'drilldown', label: 'เลื่อนเข้าเมนูย่อย', description: 'กดแล้วเลื่อนเข้าหน้าเมนูย่อย มีปุ่มย้อนกลับ' },
    { value: 'large', label: 'ตัวอักษรใหญ่', description: 'เมนูหลักตัวใหญ่จัดกึ่งกลาง เหมาะกับแบบเต็มจอ' },
];

export const ALIGN_OPTIONS: Choice<Align>[] = [
    { value: 'left', label: 'ซ้าย' },
    { value: 'center', label: 'กึ่งกลาง' },
    { value: 'right', label: 'ขวา' },
];

export const WIDTH_OPTIONS: Choice<Width>[] = [
    { value: 'full', label: 'เต็มหน้าจอ' },
    { value: 'container', label: 'ตาม container' },
];

export const LOGO_DISPLAY_OPTIONS: Choice<HeaderZone['logo_display']>[] = [
    { value: 'image', label: 'รูปเท่านั้น' },
    { value: 'image_name', label: 'รูปและชื่อเว็บ' },
    { value: 'name', label: 'ชื่อเว็บเท่านั้น' },
];

export const LOGO_ACTION_OPTIONS: Choice<HeaderZone['logo_action']>[] = [
    { value: 'none', label: 'แสดงเท่านั้น' },
    { value: 'home', label: 'ลิงก์ไปหน้าแรก' },
];

export const LANG_DISPLAY_OPTIONS: Choice<HeaderZone['lang_display']>[] = [
    { value: 'code', label: 'ตัวอักษรย่อ' },
    { value: 'flag', label: 'ธง' },
    { value: 'flag_code', label: 'ธงและตัวอักษรย่อ' },
];

export const LANG_SELECT_OPTIONS: Choice<HeaderZone['lang_select']>[] = [
    { value: 'all', label: 'แสดงทั้งหมด' },
    { value: 'dropdown', label: 'เป็นตัวเลือก' },
];

export const FONTSIZE_DISPLAY_OPTIONS: Choice<'icon' | 'text'>[] = [
    { value: 'icon', label: 'ไอคอน + , -' },
    { value: 'text', label: 'ตัวอักษร (ก ก ก)' },
];

export const CONTRAST_DISPLAY_OPTIONS: Choice<'icon' | 'text'>[] = [
    { value: 'icon', label: 'ไอคอน' },
    { value: 'text', label: 'ข้อความ' },
];

export const ASIDE_TOGGLE_OPTIONS: Choice<AsideZone['toggle_position']>[] = [
    { value: 'left', label: 'ซ้าย' },
    { value: 'right', label: 'ขวา' },
];

export const LOADING_TYPES: Choice<'spinner' | 'image'>[] = [
    { value: 'spinner', label: 'ตัวหมุนของระบบ' },
    { value: 'image', label: 'รูปภาพจากไฟล์' },
];

export const LOADING_SPINNERS: Choice<'ring' | 'dots' | 'bar'>[] = [
    { value: 'ring', label: 'วงแหวน' },
    { value: 'dots', label: 'จุด 3 จุด' },
    { value: 'bar', label: 'แถบวิ่ง' },
];

/** ป้ายชื่อ social ตามคีย์ของ sys_setting กลุ่ม social */
export const SOCIAL_LABELS: Record<string, string> = {
    facebook: 'Facebook',
    youtube: 'YouTube',
    x: 'X',
    instagram: 'Instagram',
    tiktok: 'TikTok',
    line: 'LINE',
};

/** class ของ flex สำหรับจัดตำแหน่ง */
export const JUSTIFY: Record<Align, string> = {
    left: 'justify-start',
    center: 'justify-center',
    right: 'justify-end',
};

/** แท็บของหน้าแก้ไข template (ข้อมูลทั่วไป / โครงสร้าง / Custom CSS/JS / หน้า Loading) — แต่ละแท็บเป็นคนละ route */
export function templateTabs(id: number, active: 'edit' | 'layout' | 'code' | 'loading') {
    return [
        { label: 'ข้อมูลทั่วไป', href: route('admin.system.template.edit', id), active: active === 'edit' },
        { label: 'โครงสร้าง', href: route('admin.system.template.layout', id), active: active === 'layout' },
        { label: 'Custom CSS/JS', href: route('admin.system.template.code', id), active: active === 'code' },
        { label: 'หน้า Loading', href: route('admin.system.template.loading', id), active: active === 'loading' },
    ];
}
