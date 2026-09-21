<script setup lang="ts">
import { computed } from 'vue';
import { readAllIconComponent } from '@/utils/readAllButton';
import type { ReadAllIconPosition, ReadAllStyle } from '@/utils/readAllButton';

/**
 * ปุ่ม/ลิงก์ "อ่านทั้งหมด" ของ Slideset จาก article ตามรูปแบบที่เลือก (ปุ่ม / ลิงก์ข้อความ / ปุ่มมนใหญ่) พร้อมไอคอนหน้าหรือหลังข้อความ —
 * ใช้ทั้งในตัวอย่างหน้าโครงสร้างและในการ์ดเลือกรูปแบบของฟอร์มตั้งค่า เป็นแค่ภาพตัวอย่าง (`<span>` ไม่ใช่ลิงก์ กดไม่ได้ ไม่มี URL)
 * สีคงที่ (ปุ่มสีเทาเข้มตัวหนังสือขาว / ลิงก์สีน้ำเงิน) — ยังไม่มีตัวเลือกสีของปุ่ม
 */
const props = defineProps<{
    text: string;
    icon: string;
    iconPosition: ReadAllIconPosition;
    styleType: ReadAllStyle;
    /** ย่อขนาดสำหรับภาพตัวอย่างในการ์ดเลือกรูปแบบ */
    small?: boolean;
}>();

const iconComponent = computed(() => readAllIconComponent(props.icon));

const classes = computed(() => {
    const size = props.small ? 'text-[11px]' : 'text-sm';

    switch (props.styleType) {
        case 'link':
            return `${size} font-medium text-brand-600 underline underline-offset-2`;
        case 'pill':
            return `${size} rounded-full bg-gray-800 font-medium text-white ${props.small ? 'px-4 py-1.5' : 'px-7 py-2.5'}`;
        default:
            return `${size} rounded-md bg-gray-800 font-medium text-white ${props.small ? 'px-2.5 py-1' : 'px-4 py-2'}`;
    }
});
</script>

<template>
    <span class="inline-flex items-center gap-1.5 whitespace-nowrap" :class="classes">
        <component :is="iconComponent" v-if="iconComponent && iconPosition === 'before'" :class="small ? 'size-3' : 'size-4'" />
        {{ text }}
        <component :is="iconComponent" v-if="iconComponent && iconPosition === 'after'" :class="small ? 'size-3' : 'size-4'" />
    </span>
</template>
