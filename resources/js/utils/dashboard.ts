import type { ContactusProcessStatus } from '@/utils/contactus';

/**
 * ชนิดข้อมูลของหน้า Dashboard หลังบ้าน (App\Support\Report\DashboardReport)
 * ส่วนที่ผู้ใช้ไม่มีสิทธิ์จะเป็น null / array ว่าง — หน้าจอไม่ต้องเช็กสิทธิ์ซ้ำ แค่ดูว่ามีข้อมูลส่งมาหรือไม่
 */

export interface DashboardShortcut {
    key: string;
    label: string;
    href: string;
}

export type AttentionTone = 'brand' | 'amber' | 'red';

export interface DashboardAttention {
    key: string;
    label: string;
    count: number;
    tone: AttentionTone;
    href: string;
}

export interface DashboardKpi {
    key: string;
    label: string;
    value: number;
    /** % เปลี่ยนแปลงจากช่วงก่อนหน้า — null = ช่วงก่อนหน้าไม่มีข้อมูล */
    change: number | null;
    hint: string;
    href: string;
}

export interface DashboardTrend {
    labels: string[];
    datasets: { key: string; label: string; data: number[] }[];
}

export interface DashboardTopArticle {
    rank: number;
    title: string | null;
    category_title: string | null;
    views: number;
    deleted: boolean;
    /** null = ไม่มีสิทธิ์ดูบทความ / บทความถูกลบแล้ว */
    href: string | null;
}

export interface DashboardContact {
    id: number;
    fullname: string;
    subject: string | null;
    process_status: ContactusProcessStatus;
    process_status_label: string;
    created_at: string | null;
    href: string;
}

export interface DashboardContent {
    key: string;
    label: string;
    published: number;
    total: number;
    href: string;
}

export interface DashboardAction {
    id: number;
    user_name: string | null;
    module_code: string | null;
    action_type: string | null;
    value_string: string | null;
    created_at: string | null;
}

export interface DashboardList<T> {
    items: T[];
    href: string;
}

export interface DashboardData {
    attention: DashboardAttention[];
    kpis: DashboardKpi[];
    trend: DashboardTrend | null;
    topArticles: DashboardList<DashboardTopArticle> | null;
    recentContacts: DashboardList<DashboardContact> | null;
    content: DashboardContent[];
    recentActions: DashboardList<DashboardAction> | null;
}

/** สีของเส้นกราฟ/จุดของการ์ดตัวเลข — ผูกกับ key ให้สีตรงกันทั้งการ์ดและกราฟ */
export const METRIC_COLORS: Record<string, string> = {
    front: '#465fff',
    article: '#eb6834',
    page: '#1baf7a',
    banner: '#eda100',
    contactus: '#e87ba4',
};

export const ACTION_TYPE_LABEL: Record<string, string> = {
    create: 'เพิ่ม',
    view: 'ดู',
    update: 'แก้ไข',
    delete: 'ลบ',
};

export const ACTION_TYPE_CLASS: Record<string, string> = {
    create: 'bg-green-100 text-green-700',
    view: 'bg-sky-100 text-sky-700',
    update: 'bg-amber-100 text-amber-700',
    delete: 'bg-red-100 text-red-700',
};
