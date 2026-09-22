<script setup lang="ts">
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import InputError from '@/Components/InputError.vue';
import type { LanguageOption } from '@/types';
import type { CustomTextPartData } from '@/utils/pageWidgetCustomText';

const props = defineProps<{
    part: CustomTextPartData;
    languages: LanguageOption[];
    errorPrefix: string;
    formErrors: Record<string, string>;
}>();

function detailError(lang: string): string | undefined {
    return props.formErrors[`${props.errorPrefix}.detail.${lang}.detail`];
}
</script>

<template>
    <!-- ตัวแก้ไขข้อความ (rich text) แต่ละภาษาแสดงคนละบรรทัด (stacked) แทนแบบ 2 คอลัมน์ปกติ เพราะอยู่ใน dialog ที่มี 2 คอลัมน์อยู่แล้ว
    ซ้อนอีกชั้นจะเบียดเกินไป -->
    <LangFieldGroup label="เนื้อหา" :languages="languages" stacked>
        <template #default="{ lang }">
            <RichTextEditor v-model="part.detail[lang.code].detail" />
            <InputError :message="detailError(lang.code)" />
        </template>
    </LangFieldGroup>
</template>
