<script setup lang="ts">
import { textStyleCss } from '@/utils/front';
import type { FrontLayoutBlock } from '@/utils/frontPage';

/**
 * หัวเรื่อง / หัวเรื่องรอง / ข้อความเกริ่นนำ ของแถว/คอลัมน์/widget ตามการจัดรูปแบบที่ตั้งไว้ — หัวเรื่องใช้ h2 (แถว) / h3 (คอลัมน์) / h4 (widget)
 * (h1 ของหน้าคือชื่อหน้าเพจ) หัวเรื่องรองและเกริ่นนำเป็น <p> ตรงกับตัวอย่างในหลังบ้าน (Admin/PageLayout/LayoutTexts.vue)
 */
defineProps<{
    block: FrontLayoutBlock;
    tag: 'h2' | 'h3' | 'h4';
    headingId?: string;
}>();
</script>

<template>
    <div v-if="block.title || block.subtitle || block.intro_text" class="space-y-1">
        <component :is="tag" v-if="block.title" :id="headingId" class="font-bold leading-snug" :style="textStyleCss(block.title_style)">{{ block.title }}</component>
        <p v-if="block.subtitle" class="leading-snug" :style="textStyleCss(block.subtitle_style)">{{ block.subtitle }}</p>
        <p v-if="block.intro_text" class="whitespace-pre-line leading-relaxed" :style="textStyleCss(block.intro_text_style)">{{ block.intro_text }}</p>
    </div>
</template>
