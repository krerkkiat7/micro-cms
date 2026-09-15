<script setup lang="ts">
import type { LanguageOption } from '@/types';
import { languageLabel } from '@/utils/languages';

/**
 * กรอบครอบฟิลด์ที่แยกตามภาษา — กรอกทุกภาษาพร้อมกันในกรอบเดียว เพื่อให้เห็นว่าเป็น "ฟิลด์เดียวกัน"
 * (ดู docs/PRD-article.md §0 "รูปแบบการกรอกข้อมูล") `languages` ที่ส่งมาต้องเรียงลำดับมาแล้ว
 * (ภาษาหลักก่อน ตามด้วยภาษาอื่นเรียงตามตัวอักษร — ทำที่ backend ผ่าน languageOptions())
 *
 * `required` = ฟิลด์นี้จำเป็นต้องกรอกสำหรับภาษาหลัก (แสดง * สีแดงกำกับที่ป้ายภาษาหลักเท่านั้น
 * เพราะภาษาอื่นเป็น optional เสมอตามดีไซน์ของโมดูลนี้)
 */
defineProps<{
    label: string;
    languages: LanguageOption[];
    description?: string;
    required?: boolean;
}>();
</script>

<template>
    <fieldset class="rounded-xl border border-gray-200 p-4">
        <legend class="px-1 text-sm font-medium text-gray-700">{{ label }}</legend>
        <p v-if="description" class="mb-3 mt-0.5 px-1 text-xs text-gray-500">{{ description }}</p>
        <div class="grid gap-4" :class="languages.length > 1 ? 'sm:grid-cols-2' : ''">
            <div v-for="lang in languages" :key="lang.code">
                <div class="mb-1.5 flex items-center gap-1.5">
                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium uppercase tracking-wide text-gray-500">
                        {{ lang.code }}
                    </span>
                    <span class="text-xs text-gray-500">{{ languageLabel(lang.code) }}</span>
                    <span v-if="lang.is_default" class="text-xs text-brand-600">(ภาษาหลัก)</span>
                    <span v-if="required && lang.is_default" class="text-xs font-semibold text-red-600">*</span>
                </div>
                <slot :lang="lang" />
            </div>
        </div>
    </fieldset>
</template>
