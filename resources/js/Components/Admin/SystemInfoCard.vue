<script setup lang="ts">
import { computed } from 'vue';
import { formatDateTime } from '@/utils/date';

/**
 * การ์ด "ข้อมูลระบบ" ของหน้าแก้ไขหลังบ้าน (รูปแบบเดียวกับหน้าแก้ไขผู้ใช้งาน) — `audit` มาจาก App\Support\SystemInfo::audit()
 * แสดงวันเวลาสร้าง/ผู้สร้าง → วันเวลาปรับปรุงล่าสุด/ผู้ปรับปรุง → (ถ้ามี) วันเวลาปรับปรุงโครงสร้างล่าสุด/ผู้ปรับปรุงโครงสร้าง
 * เรียง 2 คอลัมน์ให้วันเวลากับผู้กระทำอยู่คู่กันในแถวเดียว; `prepend`/`append` = รายการเพิ่มเติมก่อน/หลัง เช่น จำนวนบทความ
 */
export interface SystemAudit {
    created_at: string | null;
    created_by: string | null;
    updated_at: string | null;
    updated_by: string | null;
    layout_updated_at?: string | null;
    layout_updated_by?: string | null;
}

export interface SystemInfoItem {
    label: string;
    value: string | number | null;
}

const props = withDefaults(
    defineProps<{
        audit: SystemAudit;
        title?: string;
        prepend?: SystemInfoItem[];
        append?: SystemInfoItem[];
        /** แสดงเป็นเนื้อหาในกล่องอื่น (เช่น dialog) ไม่ครอบกรอบการ์ด */
        bare?: boolean;
    }>(),
    { title: 'ข้อมูลระบบ', prepend: () => [], append: () => [], bare: false },
);

const items = computed<SystemInfoItem[]>(() => {
    const audit = props.audit;
    const list: SystemInfoItem[] = [
        ...props.prepend,
        { label: 'วันเวลาที่สร้าง', value: formatDateTime(audit.created_at) },
        { label: 'สร้างโดย', value: audit.created_by },
        { label: 'วันเวลาที่ปรับปรุงล่าสุด', value: formatDateTime(audit.updated_at) },
        { label: 'ปรับปรุงล่าสุดโดย', value: audit.updated_by },
    ];

    if ('layout_updated_at' in audit) {
        list.push(
            { label: 'วันเวลาที่ปรับปรุงโครงสร้างล่าสุด', value: formatDateTime(audit.layout_updated_at) },
            { label: 'ปรับปรุงโครงสร้างล่าสุดโดย', value: audit.layout_updated_by ?? null },
        );
    }

    return [...list, ...props.append];
});

function display(value: string | number | null): string {
    return value === null || value === '' ? '-' : typeof value === 'number' ? value.toLocaleString('th-TH') : value;
}
</script>

<template>
    <div :class="bare ? '' : 'rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8'">
        <h2 class="text-base font-semibold text-gray-800">{{ title }}</h2>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div v-for="item in items" :key="item.label">
                <dt class="text-sm text-gray-500">{{ item.label }}</dt>
                <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ display(item.value) }}</dd>
            </div>
        </dl>
    </div>
</template>
