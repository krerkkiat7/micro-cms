export interface User {
    id: number;
    titlename: string | null;
    firstname: string;
    lastname: string;
    name: string; // accessor: คำนำหน้า + ชื่อ + นามสกุล
    email: string;
    email_verified_at?: string;
    mobile?: string | null;
    phone?: string | null;
    line?: string | null;
    facebook?: string | null;
    /** hash_name ของรูปโปรไฟล์ (file_info) — null = ยังไม่ได้เลือก/ไฟล์ถูกลบไปแล้ว */
    profile_image_hash_name?: string | null;
    permissions?: string[];
}

/** เมนูย่อยใน sidebar หลังบ้าน (มาจาก sys_menu) */
export interface MenuItem {
    id: string;
    name: string;
    /** ชื่อไอคอน lucide (PascalCase) — null = ใช้ไอคอน fallback */
    icon: string | null;
    routeName: string | null;
    /** URL ที่ resolve แล้ว — null ถ้ายังไม่มี route จริง */
    href: string | null;
    /** pattern สำหรับ `route().current()` (รองรับ wildcard) — null ถ้าไม่มี route */
    activePattern: string | null;
}

/** กลุ่มเมนูหลักใน sidebar หลังบ้าน (มาจาก sys_menu_group) — กดไม่ได้ ใช้เปิด/ปิดกลุ่ม */
export interface MenuGroup {
    id: string;
    name: string;
    icon: string | null;
    items: MenuItem[];
}

/** ตัวเลือกกลุ่มผู้ใช้งานสำหรับ dropdown */
export interface UserGroupOption {
    id: number;
    name: string;
}

/** รหัสภาษาที่ระบบเปิดใช้งาน (sys_setting: site.lang_selected/lang_default) — ใช้สร้างฟอร์มข้อมูลแยกภาษา */
export interface LanguageOption {
    code: string;
    is_default: boolean;
}

/** ลิงก์หน้าใน paginator ของ Laravel */
export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

/** ผลลัพธ์ paginate() ของ Laravel (โครงสร้างแบน) */
export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

/** โฟลเดอร์ในโมดูลจัดการไฟล์ (folder_info) */
export interface FolderItem {
    id: number;
    name: string;
    files_count?: number;
}

/** ไฟล์ในโมดูลจัดการไฟล์ (file_info) */
export interface FileItem {
    id: number;
    name: string;
    hash_name: string;
    extension: string | null;
    file_size: number;
    is_image: boolean;
    created_at: string;
}

/** โหนดสิทธิ์ (sys_action) แบบ tree — ใช้ในหน้ากำหนดสิทธิ์ของกลุ่มผู้ใช้งาน */
export interface ActionNode {
    id: string;
    code: string;
    name: string;
    children: ActionNode[];
}

/** กลุ่มสิทธิ์ (sys_action_group) พร้อมต้นไม้สิทธิ์ภายใน */
export interface ActionGroupNode {
    id: string;
    name: string;
    total: number;
    actions: ActionNode[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    /** ชื่อไซต์จากการตั้งค่าระบบ (sys_setting กลุ่ม site) — fallback เป็น .env APP_NAME ถ้าไม่ได้ตั้งค่า */
    siteName: string;
    /** URL โลโก้ที่ตั้งค่าไว้ (sys_setting: site.logo_id) — null ถ้ายังไม่ได้ตั้งค่า (ดู Components/AppLogo.vue) */
    appLogoUrl: string | null;
    menu: MenuGroup[];
    flash?: {
        success?: string | null;
        successId?: string | null;
    };
    /** โทเคน log_back_access ของการเข้าหน้านี้ — null ถ้าหน้านี้ไม่ได้บันทึก log */
    accessLog?: {
        token: string | null;
    };
};
