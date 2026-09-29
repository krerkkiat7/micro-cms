<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

/**
 * ปุ่ม "กลับหน้ารายการ" ของหน้าแก้ไข/เพิ่ม/รายละเอียดหลังบ้าน — เป็นลิงก์ (Inertia Link) ที่หน้าตาเหมือน SecondaryButton + ไอคอนลูกศร
 * (ไม่ครอบ <button> ไว้ใน <a> เพราะ element ที่กดได้ซ้อนกันผิดหลัก HTML/การเข้าถึง)
 */
defineProps<{
    href: string;
}>();
</script>

<template>
    <Link
        :href="href"
        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-xs transition-colors hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
    >
        <ArrowLeft class="mr-1.5 size-4" /> <slot>กลับหน้ารายการ</slot>
    </Link>
</template>
