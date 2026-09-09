const dateFmt = new Intl.DateTimeFormat('th-TH', { dateStyle: 'medium' });
const dateTimeFmt = new Intl.DateTimeFormat('th-TH', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

/** วันที่แบบสั้น (คืน '-' เมื่อค่าว่างหรือ parse ไม่ได้) */
export function formatDate(value?: string | null): string {
    if (!value) return '-';
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? '-' : dateFmt.format(d);
}

/** วันเวลา (คืน '-' เมื่อค่าว่างหรือ parse ไม่ได้) */
export function formatDateTime(value?: string | null): string {
    if (!value) return '-';
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? '-' : dateTimeFmt.format(d);
}
