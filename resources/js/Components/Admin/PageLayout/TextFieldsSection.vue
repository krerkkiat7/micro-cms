<script setup lang="ts">
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import TextStyleFields from './TextStyleFields.vue';
import type { LayoutDetailMap, TextPart, TextStyle } from '@/utils/pageLayout';
import type { LanguageOption } from '@/types';

/**
 * ข้อความ 1 ส่วนของแถว/คอลัมน์/widget ใน dialog ตั้งค่า — ช่องกรอกแยกตามภาษา (หัวเรื่อง/หัวเรื่องรอง = บรรทัดเดียว,
 * ข้อความเกริ่นนำ = หลายบรรทัด) ตามด้วยการจัดรูปแบบตัวอักษรของข้อความส่วนนั้น (`TextStyleFields`)
 */
defineProps<{
    label: string;
    part: TextPart;
    languages: LanguageOption[];
    detail: LayoutDetailMap;
    textStyle: TextStyle;
    fonts: string[];
    multiline?: boolean;
}>();
</script>

<template>
    <div class="space-y-2">
        <LangFieldGroup :label="label" :languages="languages">
            <template #default="{ lang }">
                <Textarea v-if="multiline" v-model="detail[lang.code][part]" rows="2" maxlength="2000" />
                <TextInput v-else v-model="detail[lang.code][part]" type="text" maxlength="250" />
            </template>
        </LangFieldGroup>

        <div class="rounded-xl bg-gray-50 p-4">
            <p class="mb-3 text-xs font-medium text-gray-500">การจัดรูปแบบตัวอักษร — {{ label }}</p>
            <TextStyleFields :style="textStyle" :fonts="fonts" />
        </div>
    </div>
</template>
