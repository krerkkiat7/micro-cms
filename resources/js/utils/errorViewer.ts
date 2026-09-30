/**
 * ชนิดข้อมูลของหน้า "ตรวจสอบ Error" (Admin\System\ErrorViewerController / App\Support\Report\ErrorLogReader)
 */

export type ErrorSide = 'front' | 'admin';

export const SIDE_LABEL: Record<ErrorSide, string> = {
    front: 'หน้าบ้าน',
    admin: 'หลังบ้าน',
};

/** รูปแบบรหัสอ้างอิง (ตรงกับ App\Support\ErrorReference) */
export const REFERENCE_PATTERN = /^ERR-[2-9A-Z]{8}$/;

export interface ErrorRow {
    side: ErrorSide;
    datetime: string | null;
    reference: string;
    class: string;
    class_short: string;
    message: string;
    file: string | null;
    url: string;
    method: string | null;
    user_id: number | null;
    user_name: string | null;
}

export interface ErrorGroup {
    class: string;
    file: string | null;
    count: number;
    last_at: string | null;
    reference: string | null;
}

export interface ErrorDetail {
    side: ErrorSide;
    datetime: string | null;
    reference: string;
    class: string;
    message: string;
    file: string | null;
    url: string | null;
    method: string | null;
    route: string | null;
    user_id: number | null;
    user_name: string | null;
    user_deleted: boolean;
    front_user_id: number | null;
    ip: string | null;
    user_agent: string | null;
    referer: string | null;
    input_keys: string[];
    trace: string | null;
}

export interface ErrorRowPage {
    data: ErrorRow[];
    current_page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
}

/** ชื่อ class แบบสั้น (ตัด namespace) */
export function shortClass(name: string): string {
    return name.split('\\').pop() || name || '-';
}
