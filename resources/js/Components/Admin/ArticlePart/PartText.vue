<script setup lang="ts">
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import type { LanguageOption } from '@/types';
import type { PartData } from '@/utils/articleParts';

const props = defineProps<{
    part: PartData;
    languages: LanguageOption[];
    errorPrefix: string;
    formErrors: Record<string, string>;
}>();

function detailError(lang: string, field: string): string | undefined {
    return props.formErrors[`${props.errorPrefix}.detail.${lang}.${field}`];
}
</script>

<template>
    <div class="space-y-4">
        <LangFieldGroup label="หัวเรื่อง" :languages="languages">
            <template #default="{ lang }">
                <TextInput v-model="part.detail[lang.code].title" type="text" />
                <InputError :message="detailError(lang.code, 'title')" />
            </template>
        </LangFieldGroup>

        <LangFieldGroup label="เนื้อหา" :languages="languages">
            <template #default="{ lang }">
                <RichTextEditor v-model="part.detail[lang.code].detail" />
                <InputError :message="detailError(lang.code, 'detail')" />
            </template>
        </LangFieldGroup>
    </div>
</template>
