<script setup lang="ts">
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import TextInput from '@/Components/TextInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
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

// part ประเภทรูปภาพเดี่ยวมีไฟล์ได้แค่ 1 แถวเสมอ — เผื่อกรณีสร้าง part มาแบบว่าง (เช่นข้อมูลเก่า)
if (props.part.files.length === 0) {
    props.part.files.push(createFileRow('image', props.languages));
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
            <InputLabel value="รูปภาพ" />
            <FilePickerField v-model="row.file" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
            <InputError :message="fileError('file_id')" />
        </div>

        <LangFieldGroup label="Alt text" description="คำอธิบายรูปภาพสำหรับผู้ใช้ screen reader และ SEO" :languages="languages">
            <template #default="{ lang }">
                <TextInput v-model="(row.description[lang.code] as string)" type="text" />
            </template>
        </LangFieldGroup>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <InputLabel value="การจัดตำแหน่ง" />
                <SearchableSelect
                    v-model="(part.setting.alignment as string)"
                    :options="[
                        { value: 'left', label: 'ชิดซ้าย' },
                        { value: 'center', label: 'กึ่งกลาง' },
                        { value: 'right', label: 'ชิดขวา' },
                    ]"
                />
            </div>
            <div>
                <InputLabel value="ขนาดรูปภาพ" />
                <SearchableSelect
                    v-model="(part.setting.size as string)"
                    :options="[
                        { value: 'small', label: 'เล็ก' },
                        { value: 'medium', label: 'กลาง' },
                        { value: 'large', label: 'ใหญ่' },
                        { value: 'full', label: 'เต็มความกว้าง' },
                    ]"
                />
            </div>
            <div class="flex items-end pb-2.5">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <Checkbox v-model:checked="(part.setting.show_caption as boolean)" />
                    แสดง alt text เป็นคำบรรยายใต้รูป
                </label>
            </div>
        </div>
    </div>
</template>
