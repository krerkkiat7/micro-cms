<script setup lang="ts">
import { computed } from 'vue';
import type { CSSProperties } from 'vue';
import { readAllIconComponent } from '@/utils/readAllButton';
import type { ReadAllIconPosition, ReadAllStyle } from '@/utils/readAllButton';

/**
 * ปุ่ม/ลิงก์ "อ่านทั้งหมด" ของ Slideset จาก article ตามรูปแบบที่เลือก (ปุ่ม / ลิงก์ข้อความ / ปุ่มมนใหญ่) พร้อมไอคอนหน้าหรือหลังข้อความ —
 * ใช้ทั้งในตัวอย่างหน้าโครงสร้างและในการ์ดเลือกรูปแบบของฟอร์มตั้งค่า เป็นแค่ภาพตัวอย่าง (`<span>` ไม่ใช่ลิงก์ กดไม่ได้ ไม่มี URL)
 * ตัวอักษร (ขนาด/ฟอนต์/สี) และสีพื้นหลัง (เฉพาะแบบปุ่ม/ปุ่มมนใหญ่) กำหนดผ่าน props — ไม่ส่งมา = ค่าเริ่มต้นตามรูปแบบ
 * (ปุ่มสีเทาเข้มตัวหนังสือขาว / ลิงก์สีน้ำเงิน) ซึ่งการ์ดเลือกรูปแบบในฟอร์มใช้เพื่อให้เห็นหน้าตาพื้นฐานของแต่ละแบบ
 */
const props = defineProps<{
    text: string;
    icon: string;
    iconPosition: ReadAllIconPosition;
    styleType: ReadAllStyle;
    /** ย่อขนาดสำหรับภาพตัวอย่างในการ์ดเลือกรูปแบบ */
    small?: boolean;
    fontSize?: number;
    fontFamily?: string;
    /** สีตัวอักษร */
    color?: string;
    /** สีพื้นหลัง — ใช้เฉพาะแบบปุ่ม/ปุ่มมนใหญ่ (ลิงก์ข้อความไม่มีพื้นหลัง) */
    background?: string;
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

const customStyle = computed<CSSProperties>(() => {
    const css: CSSProperties = {};

    if (props.fontSize) {
        css.fontSize = `${props.fontSize}px`;
    }

    if (props.fontFamily) {
        css.fontFamily = `'${props.fontFamily}', sans-serif`;
    }

    if (props.color) {
        css.color = props.color;
    }

    if (props.background && props.styleType !== 'link') {
        css.backgroundColor = props.background;
    }

    return css;
});

// ไอคอนโตตามขนาดตัวอักษร
const iconStyle: CSSProperties = { width: '1.2em', height: '1.2em' };
</script>

<template>
    <span class="inline-flex items-center gap-1.5 whitespace-nowrap" :class="classes" :style="customStyle">
        <component :is="iconComponent" v-if="iconComponent && iconPosition === 'before'" class="shrink-0" :style="iconStyle" />
        {{ text }}
        <component :is="iconComponent" v-if="iconComponent && iconPosition === 'after'" class="shrink-0" :style="iconStyle" />
    </span>
</template>
