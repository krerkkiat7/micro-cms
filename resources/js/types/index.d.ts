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
}

/** กลุ่มเมนูหลักใน sidebar หลังบ้าน (มาจาก sys_menu_group) — กดไม่ได้ ใช้เปิด/ปิดกลุ่ม */
export interface MenuGroup {
    id: string;
    name: string;
    icon: string | null;
    items: MenuItem[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    menu: MenuGroup[];
};
