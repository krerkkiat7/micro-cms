<script setup lang="ts">
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import { createCustomTextFileRow } from '@/utils/pageWidgetCustomText';
import type { LanguageOption } from '@/types';
import type { CustomTextPartData } from '@/utils/pageWidgetCustomText';

const props = defineProps<{
    part: CustomTextPartData;
    languages: LanguageOption[];
    errorPrefix: string;
    formErrors: Record<string, string>;
}>();

if (props.part.files.length === 0) {
    props.part.files.push(createCustomTextFileRow('video', props.languages));
}

const row = props.part.files[0];

function fileError(field: string): string | undefined {
    return props.formErrors[`${props.errorPrefix}.files.0.${field}`];
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <InputLabel value="ประเภทของวิดีโอ" />
            <SearchableSelect
                v-model="row.video_type"
                :options="[
                    { value: 'file', label: 'ไฟล์วิดีโอ' },
                    { value: 'youtube', label: 'YouTube' },
                ]"
            />
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
                <InputLabel value="ขนาดเครื่องเล่น" />
                <SearchableSelect
                    v-model="(part.setting.player_size as string)"
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
