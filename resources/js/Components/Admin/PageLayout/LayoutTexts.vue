<script setup lang="ts">
import { computed } from 'vue';
import { HEADING_TAGS, defaultLangText, textStyleCss } from '@/utils/pageLayout';
import type { LayoutDetailMap, LayoutLevel, TextStyles } from '@/utils/pageLayout';
import type { LanguageOption } from '@/types';

/**
 * ตัวอย่างการแสดง "หัวเรื่อง / หัวเรื่องรอง / ข้อความเกริ่นนำ" ของแถว/คอลัมน์/widget ในหน้าโครงสร้าง ตามการจัดรูปแบบที่ตั้งไว้
 * — แสดงเมื่อเปิด "แสดงหัวเรื่อง" (`show`) เท่านั้น และแสดงเฉพาะข้อความที่กรอกแล้ว (ภาษาหลัก); หัวเรื่องใช้แท็ก h2 (แถว) /
 * h3 (คอลัมน์) / h4 (widget) ส่วนหัวเรื่องรองและข้อความเกริ่นนำเป็น div ธรรมดา (ให้ตรงกับที่หน้าบ้านจะ render)
 */
const props = defineProps<{
    level: LayoutLevel;
    show: boolean;
    detail: LayoutDetailMap;
    styles: TextStyles;
    languages: LanguageOption[];
}>();

const title = computed(() => defaultLangText(props.detail, props.languages, 'title'));
const subtitle = computed(() => defaultLangText(props.detail, props.languages, 'subtitle'));
const intro = computed(() => defaultLangText(props.detail, props.languages, 'intro_text'));
</script>

<template>
    <div v-if="show && (title || subtitle || intro)" class="space-y-1">
        <component :is="HEADING_TAGS[level]" v-if="title" class="font-bold leading-snug" :style="textStyleCss(styles.title_style)">
            {{ title }}
        </component>
        <div v-if="subtitle" class="leading-snug" :style="textStyleCss(styles.subtitle_style)">{{ subtitle }}</div>
        <div v-if="intro" class="whitespace-pre-line leading-relaxed" :style="textStyleCss(styles.intro_text_style)">{{ intro }}</div>
    </div>
</template>
