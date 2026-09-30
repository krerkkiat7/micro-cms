import type { UserGroupOption } from '@/types';

/**
 * แปลงกลุ่มผู้ใช้งานเป็นตัวเลือกของ SearchableSelect — กลุ่มที่ถูกปิดใช้งาน (กลุ่มปัจจุบันของผู้ใช้ที่แก้ไข) ต่อท้ายด้วย "(ไม่ใช้งาน)",
 * กลุ่มระบบที่ผู้ใช้ปัจจุบันเลือกไม่ได้เป็น disabled (ดู UserController::userGroupOptions)
 */
export function userGroupSelectOptions(groups: UserGroupOption[]) {
    return groups.map((g) => ({
        value: String(g.id),
        label: g.status === 'N' ? `${g.name} (ไม่ใช้งาน)` : g.name,
        disabled: g.disabled ?? false,
    }));
}
