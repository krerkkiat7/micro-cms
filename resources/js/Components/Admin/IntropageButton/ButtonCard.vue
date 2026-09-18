<script setup lang="ts">
import { computed } from 'vue';
import { Home, ListOrdered, Link as LinkIcon, Trash2 } from 'lucide-vue-next';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { LINK_TARGET_OPTIONS } from '@/utils/options';
import { BUTTON_TYPE_LABELS } from '@/utils/intropageButtons';
import type { LanguageOption } from '@/types';
import type { ButtonData } from '@/utils/intropageButtons';

const props = defineProps<{
    button: ButtonData;
    languages: LanguageOption[];
    errorPrefix: string;
    formErrors: Record<string, string>;
}>();

defineEmits<{ remove: []; reorder: [] }>();

const isHome = computed(() => props.button.button_type === 'home');

const displayTypeOptions = [
    { value: 'text', label: 'ข้อความ' },
    { value: 'image', label: 'รูปภาพ' },
];

function fieldError(field: string): string | undefined {
    return props.formErrors[`${props.errorPrefix}.${field}`];
}

function textError(lang: string): string | undefined {
    return props.formErrors[`${props.errorPrefix}.texts.${lang}`];
}
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <button type="button" class="text-gray-400 hover:text-gray-600" title="จัดลำดับปุ่ม" @click="$emit('reorder')">
                <ListOrdered class="size-5" />
            </button>
            <Home v-if="isHome" class="size-4 text-gray-500" />
            <LinkIcon v-else class="size-4 text-gray-500" />
            <span class="text-sm font-medium text-gray-700">{{ BUTTON_TYPE_LABELS[button.button_type] }}</span>

            <div class="ml-auto flex items-center gap-1">
                <button
                    v-if="!isHome"
                    type="button"
                    class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600"
                    title="ลบปุ่มนี้"
                    @click="$emit('remove')"
                >
                    <Trash2 class="size-4" />
                </button>
            </div>
        </div>

        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="รูปแบบปุ่ม" />
                    <SearchableSelect v-model="button.button_display_type" :options="displayTypeOptions" />
                    <InputError :message="fieldError('button_display_type')" />
                </div>

                <div v-if="!isHome">
                    <InputLabel value="เปิดลิงก์แบบ" />
                    <SearchableSelect v-model="button.link_target" :options="LINK_TARGET_OPTIONS" />
                    <InputError :message="fieldError('link_target')" />
                </div>
            </div>

            <div v-if="!isHome">
                <InputLabel value="ลิงก์ที่ไปได้" required />
                <TextInput v-model="button.url" type="text" placeholder="https://..." />
                <InputError :message="fieldError('url')" />
            </div>

            <template v-if="button.button_display_type === 'text'">
                <LangFieldGroup label="ข้อความปุ่ม" :languages="languages" required>
                    <template #default="{ lang }">
                        <TextInput v-model="button.texts[lang.code]" type="text" />
                        <InputError :message="textError(lang.code)" />
                    </template>
                </LangFieldGroup>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="สีตัวอักษร" />
                        <ColorPickerInput v-model="button.text_color" />
                        <InputError :message="fieldError('text_color')" />
                    </div>
                    <div>
                        <InputLabel value="สีพื้นหลัง" />
                        <ColorPickerInput v-model="button.background_color" />
                        <InputError :message="fieldError('background_color')" />
                    </div>
                </div>
            </template>

            <div v-else>
                <InputLabel value="รูปภาพปุ่ม" required />
                <FilePickerField v-model="button.button_image" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                <InputError :message="fieldError('button_image_id')" />
            </div>
        </div>
    </div>
</template>
