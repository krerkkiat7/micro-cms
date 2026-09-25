<script setup lang="ts">
/**
 * ธงของภาษา (แบบย่อ) — ใช้ร่วมกันระหว่างตัวอย่าง template หลังบ้าน และตัวเลือกภาษาใน header หน้าบ้าน
 * (ตกแต่งเท่านั้น aria-hidden — ชื่อภาษาอยู่ที่ข้อความ/aria-label ของปุ่ม) ภาษาที่ไม่มีธงแสดงเป็นกล่องสีเทา
 */
defineProps<{ code: string }>();
</script>

<template>
    <svg viewBox="0 0 30 20" class="h-3 w-[18px] shrink-0 overflow-hidden rounded-[2px] ring-1 ring-black/10" aria-hidden="true" focusable="false">
        <template v-if="code === 'th'">
            <rect width="30" height="20" fill="#A51931" />
            <rect y="3.33" width="30" height="13.34" fill="#F4F5F8" />
            <rect y="6.67" width="30" height="6.66" fill="#2D2A4A" />
        </template>
        <template v-else-if="code === 'en'">
            <rect width="30" height="20" fill="#012169" />
            <path d="M0 0 L30 20 M30 0 L0 20" stroke="#FFFFFF" stroke-width="4" />
            <path d="M0 0 L30 20 M30 0 L0 20" stroke="#C8102E" stroke-width="1.5" />
            <path d="M15 0 V20 M0 10 H30" stroke="#FFFFFF" stroke-width="6" />
            <path d="M15 0 V20 M0 10 H30" stroke="#C8102E" stroke-width="3.5" />
        </template>
        <rect v-else width="30" height="20" fill="#D1D5DB" />
    </svg>
</template>
