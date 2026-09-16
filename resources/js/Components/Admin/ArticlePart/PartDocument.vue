<script setup lang="ts">
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import { createFileRow } from '@/utils/articleParts';
import type { LanguageOption } from '@/types';
import type { PartData } from '@/utils/articleParts';

const props = defineProps<{
    part: PartData;
    languages: LanguageOption[];
    errorPrefix: string;
    formErrors: Record<string, string>;
}>();

if (props.part.files.length === 0) {
    props.part.files.push(createFileRow('document', props.languages));
}

const row = props.part.files[0];

function fileError(field: string): string | undefined {
    return props.formErrors[`${props.errorPrefix}.files.0.${field}`];
}
</script>

<template>
    <div class="space-y-4">
        <LangFieldGroup label="หัวเรื่อง" :languages="languages">
            <template #default="{ lang }">
                <TextInput v-model="part.detail[lang.code].title" type="text" />
            </template>
        </LangFieldGroup>

        <div>
            <InputLabel value="ไฟล์เอกสาร" />
            <FilePickerField v-model="row.file" />
            <InputError :message="fileError('file_id')" />
        </div>

        <div class="flex flex-wrap gap-6">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <Checkbox v-model:checked="(row.description.pdf_preview as boolean)" />
                แสดง preview ถ้าเป็นไฟล์ PDF
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <Checkbox v-model:checked="(row.description.show_file_size as boolean)" />
                แสดงขนาดไฟล์
            </label>
        </div>
    </div>
</template>
