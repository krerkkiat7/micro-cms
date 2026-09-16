<script setup lang="ts">
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
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
    props.part.files.push(createFileRow('video', props.languages));
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
            <InputLabel value="ประเภทของวิดีโอ" />
            <SelectInput v-model="row.video_type">
                <option value="file">ไฟล์วิดีโอ</option>
                <option value="youtube">YouTube</option>
            </SelectInput>
        </div>

        <div v-if="row.video_type === 'file'">
            <InputLabel value="ไฟล์วิดีโอ" />
            <FilePickerField v-model="row.file" :accept="['mp4']" />
            <InputError :message="fileError('file_id')" />
        </div>

        <div v-else>
            <InputLabel value="YouTube URL" />
            <TextInput v-model="row.youtube_url" type="text" placeholder="https://www.youtube.com/watch?v=..." />
            <InputError :message="fileError('youtube_url')" />
        </div>

        <div>
            <InputLabel value="รูปภาพหน้าปก" />
            <FilePickerField v-model="row.cover_image" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
            <InputError :message="fileError('cover_image_id')" />
        </div>

        <div class="grid gap-4 sm:grid-cols-4">
            <div>
                <InputLabel value="การจัดตำแหน่ง" />
                <SelectInput v-model="(part.setting.alignment as string)">
                    <option value="left">ชิดซ้าย</option>
                    <option value="center">กึ่งกลาง</option>
                    <option value="right">ชิดขวา</option>
                </SelectInput>
            </div>
            <div>
                <InputLabel value="ขนาดเครื่องเล่น" />
                <SelectInput v-model="(part.setting.player_size as string)">
                    <option value="small">เล็ก</option>
                    <option value="medium">กลาง</option>
                    <option value="large">ใหญ่</option>
                    <option value="full">เต็มความกว้าง</option>
                </SelectInput>
            </div>
            <div class="flex items-end pb-2.5">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <Checkbox v-model:checked="(row.description.autoplay as boolean)" />
                    เล่นอัตโนมัติ
                </label>
            </div>
            <div class="flex items-end pb-2.5">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <Checkbox v-model:checked="(row.description.controls as boolean)" />
                    แสดงปุ่มควบคุมเครื่องเล่น
                </label>
            </div>
        </div>
    </div>
</template>
