/**
 * ชนิดข้อมูลของสถิติประวัติการใช้งานหลังบ้าน (ตรงกับ App\Support\Report\BackLogAccessReport)
 */

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
