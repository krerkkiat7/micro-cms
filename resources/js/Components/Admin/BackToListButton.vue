<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, X } from 'lucide-vue-next';

/**
 * ปุ่ม "กลับหน้ารายการ" ของหน้าแก้ไข/รายละเอียดหลังบ้าน และปุ่ม "ยกเลิก" ของหน้าเพิ่ม (`cancel` = ไอคอนกากบาท + ข้อความ "ยกเลิก")
 * — เป็นลิงก์ (Inertia Link) ที่หน้าตาเหมือน SecondaryButton (ไม่ครอบ <button> ไว้ใน <a> เพราะ element ที่กดได้ซ้อนกันผิดหลัก HTML/การเข้าถึง)
 */
defineProps<{
    href: string;
    /** true = ปุ่มยกเลิกของหน้าเพิ่ม (ไอคอนกากบาท) */
    cancel?: boolean;
}>();
</script>

<template>
    <Link
        :href="href"
        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-xs transition-colors hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
    >
        <template v-if="cancel"><X class="mr-1.5 size-4" /> <slot>ยกเลิก</slot></template>
        <template v-else><ArrowLeft class="mr-1.5 size-4" /> <slot>กลับหน้ารายการ</slot></template>
    </Link>
</template>
