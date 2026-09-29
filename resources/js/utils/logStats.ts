import type { ReportMetric } from '@/utils/report';

/**
 * ชนิดข้อมูล/การตั้งค่าของหน้าสถิติประวัติ (ตรงกับ App\Support\Report\{AccessLogReport,LoginLogReport,ActionLogReport}
 * + controller ที่ extends LogStatsController)
 */

export type LogStatsKey = 'backAccess' | 'backLogin' | 'backAction' | 'frontAccess';

export interface LogStatsConfig {
    title: string;
    routePrefix: string;
    metric: ReportMetric;
    itemLabel: string;
    /** แท็บแรก "รายการ" = หน้ารายการเดิม (index) */
    tabs: { key: string; label: string }[];
}

export const LOG_STATS: Record<LogStatsKey, LogStatsConfig> = {
    backAccess: {
        title: 'ประวัติการใช้งานหลังบ้าน',
        routePrefix: 'admin.system.backlog.access',
        metric: 'access',
        itemLabel: 'ผู้ใช้งาน',
        tabs: [
            { key: 'index', label: 'รายการ' },
            { key: 'overview', label: 'ภาพรวม' },
            { key: 'user', label: 'ผู้ใช้งาน' },
            { key: 'page', label: 'หน้าจอ' },
            { key: 'device', label: 'อุปกรณ์และเครือข่าย' },
            { key: 'time', label: 'ช่วงเวลา' },
        ],
    },
    backLogin: {
        title: 'ประวัติการเข้าสู่ระบบหลังบ้าน',
        routePrefix: 'admin.system.backlog.login',
        metric: 'login',
        itemLabel: 'ผู้ใช้งาน',
        tabs: [
            { key: 'index', label: 'รายการ' },
            { key: 'overview', label: 'ภาพรวม' },
            { key: 'account', label: 'บัญชีผู้ใช้งาน' },
            { key: 'security', label: 'ความปลอดภัย' },
            { key: 'time', label: 'ช่วงเวลา' },
        ],
    },
    backAction: {
        title: 'ประวัติการกระทำหลังบ้าน',
        routePrefix: 'admin.system.backlog.action',
        metric: 'action',
        itemLabel: 'ผู้ใช้งาน',
        tabs: [
            { key: 'index', label: 'รายการ' },
            { key: 'overview', label: 'ภาพรวม' },
            { key: 'user', label: 'ผู้ใช้งาน' },
            { key: 'module', label: 'โมดูลและข้อมูล' },
            { key: 'time', label: 'ช่วงเวลา' },
        ],
    },
    frontAccess: {
        title: 'ประวัติการใช้งานหน้าบ้าน',
        routePrefix: 'admin.system.frontlog.access',
        metric: 'front',
        itemLabel: 'ผู้ใช้งาน',
        tabs: [
            { key: 'index', label: 'รายการ' },
            { key: 'overview', label: 'ภาพรวม' },
            { key: 'page', label: 'หน้าที่เข้าชม' },
            { key: 'source', label: 'แหล่งที่มาและภาษา' },
            { key: 'device', label: 'อุปกรณ์และเครือข่าย' },
            { key: 'time', label: 'ช่วงเวลา' },
        ],
    },
};

/** ชื่อประเภทการกระทำ (log_back_action.action_type) */
export const ACTION_TYPE_LABELS: Record<string, string> = {
    create: 'เพิ่ม',
    view: 'ดู',
    update: 'แก้ไข',
    delete: 'ลบ',
    export: 'ส่งออก',
    other: 'อื่น ๆ',
};

/** ชื่อผลลัพธ์การเข้าสู่ระบบ (log_back_login.result) */
export const LOGIN_RESULT_LABELS: Record<string, string> = {
    success: 'สำเร็จ',
    fail: 'ไม่สำเร็จ',
    block: 'ถูกบล็อก',
    logout: 'ออกจากระบบ',
};

/** ชั่วโมงทำการ จ.–ศ. 08:00–17:59 — แยกการใช้งานในเวลา/นอกเวลา/วันหยุดจาก heatmap [7][24] */
export function officeHoursSplit(heatmap: number[][]): { total: number; work: number; off: number; weekend: number } {
    const total = heatmap.reduce((sum, day) => sum + day.reduce((a, b) => a + b, 0), 0);
    const work = heatmap.slice(0, 5).reduce((sum, day) => sum + day.slice(8, 18).reduce((a, b) => a + b, 0), 0);
    const weekend = heatmap.slice(5).reduce((sum, day) => sum + day.reduce((a, b) => a + b, 0), 0);

    return { total, work, weekend, off: total - work - weekend };
}

export interface DurationSummary {
    avg_seconds: number;
    total_seconds: number;
    avg_session_seconds: number;
}

export interface UserStatRow {
    user_id: number | null;
    name: string | null;
    email: string | null;
    group: string | null;
    deleted: boolean;
    views: number;
    sessions: number;
    active_days: number;
    pages: number;
    ips: number;
    total_seconds: number;
    avg_seconds: number;
    first_at: string | null;
    last_at: string | null;
}

export interface PageStatRow {
    title: string | null;
    views: number;
    users: number;
    sessions: number;
    avg_seconds: number;
    total_seconds: number;
    last_at: string | null;
}

export interface IpStatRow {
    ip: string | null;
    views: number;
    users: number;
    last_at: string | null;
}

export function userLabel(row: Pick<UserStatRow, 'user_id' | 'name' | 'deleted'>): string {
    if (row.user_id === null) return 'ไม่ระบุผู้ใช้งาน';
    return `${row.name || `ผู้ใช้งาน #${row.user_id}`}${row.deleted ? ' (ถูกลบแล้ว)' : ''}`;
}

// ---- ประวัติการเข้าสู่ระบบ (LoginLogReport) ----

export interface LoginCounts {
    success: number;
    fail: number;
    block: number;
    logout: number;
    attempts: number;
    success_rate: number;
    accounts: number;
    ips: number;
}

export interface LoginAccountRow {
    username: string | null;
    user_id: number | null;
    name: string | null;
    success: number;
    fail: number;
    block: number;
    logout: number;
    ips: number;
    last_success_at: string | null;
    last_fail_at: string | null;
}

export interface FailedIpRow {
    ip: string | null;
    failed: number;
    success: number;
    usernames: number;
    last_at: string | null;
}

export interface LoginReasonRow {
    key: string;
    result: string;
    views: number;
}

// ---- ประวัติการกระทำ (ActionLogReport) ----

export interface ActionTypeCounts {
    create: number;
    view: number;
    update: number;
    delete: number;
    other: number;
}

export interface ActionCounts extends ActionTypeCounts {
    modules: number;
    records: number;
}

export interface ActionUserRow extends ActionTypeCounts {
    user_id: number | null;
    name: string | null;
    group: string | null;
    deleted: boolean;
    total: number;
    modules: number;
    active_days: number;
    last_at: string | null;
}

export interface ActionModuleRow extends ActionTypeCounts {
    module: string | null;
    total: number;
    users: number;
    last_at: string | null;
}

export interface ActionRecordRow {
    module: string | null;
    ref_id: number;
    name: string | null;
    changes: number;
    users: number;
    last_at: string | null;
}

// ---- ประวัติการใช้งานหน้าบ้าน (AccessLogReport หน้าบ้าน) ----

export interface LandingRow {
    title: string | null;
    sessions: number;
}

export interface BounceSummary {
    sessions: number;
    bounced: number;
    rate: number;
}

/** สีผลลัพธ์การเข้าสู่ระบบ — ใช้สีสถานะ (good / critical / warning) คู่กับชื่อใน legend เสมอ */
export const LOGIN_RESULT_COLORS: Record<string, string> = {
    success: '#0ca30c',
    fail: '#d03b3b',
    block: '#fab219',
};
