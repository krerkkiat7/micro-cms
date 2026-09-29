import { ref, watch } from 'vue';

/**
 * ชนิดข้อมูล/ตัวช่วยของหน้ารายงานการเข้าชม (ตรงกับ App\Support\Report\ViewReport + ArticleReport)
 */

export type ReportPeriod = 'day' | 'week' | 'month' | 'year';
export type ChartType = 'bar' | 'line';

export interface ReportFilters {
    date_from: string;
    date_to: string;
    period: ReportPeriod;
    category_id?: number | null;
}

export interface SeriesRow {
    key: string;
    label: string;
    start: string;
    end: string;
    views: number;
    sessions: number;
    ips: number;
}

export interface ReportSummary {
    views: number;
    sessions: number;
    ips: number;
    items: number;
    days: number;
    active_days: number;
    avg_per_day: number;
    views_per_session: number;
    peak: { label: string; views: number } | null;
    previous: { date_from: string; date_to: string; views: number; sessions: number };
    change: { views: number | null; sessions: number | null };
}

export interface BreakdownItem {
    key: string | null;
    views: number;
    sessions?: number;
}

export interface Breakdowns {
    lang: BreakdownItem[];
    device_type: BreakdownItem[];
    browser: BreakdownItem[];
    platform: BreakdownItem[];
}

export interface Referrers {
    sources: { key: string; views: number }[];
    hosts: { key: string; views: number }[];
}

export interface TopRow {
    rank: number;
    id: number;
    title: string | null;
    category_title: string | null;
    deleted: boolean;
    views: number;
    sessions: number;
    ips: number;
}

export interface CategoryOption {
    id: number;
    title: string | null;
}

export const PERIOD_OPTIONS: { value: ReportPeriod; label: string }[] = [
    { value: 'day', label: 'รายวัน' },
    { value: 'week', label: 'รายสัปดาห์' },
    { value: 'month', label: 'รายเดือน' },
    { value: 'year', label: 'รายปี' },
];

export const PERIOD_UNIT: Record<ReportPeriod, string> = {
    day: 'วัน',
    week: 'สัปดาห์',
    month: 'เดือน',
    year: 'ปี',
};

export const WEEKDAYS = ['จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'อาทิตย์'];

/**
 * สีของ series (ตรวจด้วย validator ของ skill dataviz แล้ว — ผ่านทุกข้อบนพื้นขาว)
 * ช่อง 1 = brand-500 (ยอดเข้าชม), ช่อง 2 = ผู้เข้าชมไม่ซ้ำ, ที่เหลือใช้ตามลำดับเสมอ (ห้ามวน)
 */
export const SERIES_COLORS = ['#465fff', '#eb6834', '#1baf7a', '#eda100', '#e87ba4'];

const DEVICE_LABELS: Record<string, string> = {
    desktop: 'คอมพิวเตอร์',
    mobile: 'มือถือ',
    tablet: 'แท็บเล็ต',
};

const SOURCE_LABELS: Record<string, string> = {
    direct: 'เข้าตรง / ไม่ทราบที่มา',
    internal: 'จากหน้าอื่นในเว็บไซต์',
    search: 'เครื่องมือค้นหา',
    social: 'โซเชียลมีเดีย',
    other: 'เว็บไซต์อื่น',
};

export function deviceLabel(key: string | null): string {
    return key ? (DEVICE_LABELS[key] ?? key) : 'ไม่ทราบ';
}

export function sourceLabel(key: string): string {
    return SOURCE_LABELS[key] ?? key;
}

export function unknownLabel(key: string | null): string {
    return key && key !== '' ? key : 'ไม่ทราบ';
}

const numberFmt = new Intl.NumberFormat('th-TH');
const decimalFmt = new Intl.NumberFormat('th-TH', { maximumFractionDigits: 2 });

export function formatNumber(value: number): string {
    return numberFmt.format(value);
}

export function formatDecimal(value: number): string {
    return decimalFmt.format(value);
}

/** สัดส่วนเป็น % (ทศนิยม 1 ตำแหน่ง) */
export function percent(part: number, total: number): string {
    return total > 0 ? `${decimalFmt.format(Math.round((part / total) * 1000) / 10)}%` : '0%';
}

/** วันที่ Y-m-d ในเวลาท้องถิ่น */
export function isoDate(d: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

/** query string ของตัวกรอง (ส่งต่อระหว่างแท็บ / ลิงก์ส่งออก) */
export function filterQuery(filters: ReportFilters): Record<string, string> {
    const query: Record<string, string> = {
        date_from: filters.date_from,
        date_to: filters.date_to,
        period: filters.period,
    };

    if (filters.category_id) {
        query.category_id = String(filters.category_id);
    }

    return query;
}

/** แท็บของเมนูรายงานบทความ — แท็บรายงานส่งตัวกรองช่วงวันที่ต่อกันได้ */
export function articleReportTabs(active: string, filters?: ReportFilters) {
    const query = filters ? filterQuery(filters) : {};
    const tabs: { key: string; label: string; route: string; carry: boolean }[] = [
        { key: 'index', label: 'รายการเข้าชม', route: 'admin.article.report.index', carry: false },
        { key: 'overview', label: 'ภาพรวม', route: 'admin.article.report.overview', carry: true },
        { key: 'top', label: 'บทความยอดนิยม', route: 'admin.article.report.top', carry: true },
        { key: 'category', label: 'ตามหมวดหมู่', route: 'admin.article.report.category', carry: true },
        { key: 'audience', label: 'ผู้เข้าชมและแหล่งที่มา', route: 'admin.article.report.audience', carry: true },
        { key: 'time', label: 'ช่วงเวลา', route: 'admin.article.report.time', carry: true },
    ];

    return tabs.map((tab) => ({
        label: tab.label,
        href: route(tab.route, tab.carry ? query : {}),
        active: tab.key === active,
    }));
}

/** ชนิดกราฟ (แท่ง/เส้น) — จำไว้ใน localStorage ไม่ส่งไป server */
export function useChartType(defaultType: ChartType = 'bar') {
    const key = 'admin.report.chartType';
    let initial = defaultType;

    try {
        const stored = localStorage.getItem(key);
        if (stored === 'bar' || stored === 'line') initial = stored;
    } catch {
        // localStorage ใช้ไม่ได้ — ใช้ค่าเริ่มต้น
    }

    const chartType = ref<ChartType>(initial);

    watch(chartType, (value) => {
        try {
            localStorage.setItem(key, value);
        } catch {
            // ignore
        }
    });

    return chartType;
}
