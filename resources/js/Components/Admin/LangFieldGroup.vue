<script setup lang="ts">
import type { LanguageOption } from '@/types';
import { languageLabel } from '@/utils/languages';

/**
 * กรอบครอบฟิลด์ที่แยกตามภาษา — กรอกทุกภาษาพร้อมกันในกรอบเดียว เพื่อให้เห็นว่าเป็น "ฟิลด์เดียวกัน"
 * (ดู docs/PRD-article.md §0 "รูปแบบการกรอกข้อมูล") ภาษาหลักจะมีป้าย required ต่อท้าย label ของช่องนั้น ๆ เอง
 * (ผ่าน slot) — ตัว fieldset นี้แค่แสดงป้ายภาษากำกับแต่ละช่อง ไม่ตัดสินใจว่าฟิลด์ required หรือไม่
 */
defineProps<{
    label: string;
    languages: LanguageOption[];
}>();
</script>

<template>
    <fieldset class="rounded-xl border border-gray-200 p-4">
        <legend class="px-1 text-sm font-medium text-gray-700">{{ label }}</legend>
        <div class="grid gap-4" :class="languages.length > 1 ? 'sm:grid-cols-2' : ''">
            <div v-for="lang in languages" :key="lang.code">
                <div class="mb-1.5 flex items-center gap-1.5">
                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium uppercase tracking-wide text-gray-500">
                        {{ lang.code }}
                    </span>
                    <span class="text-xs text-gray-500">{{ languageLabel(lang.code) }}</span>
                    <span v-if="lang.is_default" class="text-xs text-brand-600">(ภาษาหลัก)</span>
                </div>
                <slot :lang="lang" />
            </div>
        </div>
    </fieldset>
</template>
