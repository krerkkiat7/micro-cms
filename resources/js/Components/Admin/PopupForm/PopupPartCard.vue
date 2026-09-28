<script setup lang="ts">
import { Eye, EyeOff, ListOrdered, Trash2 } from 'lucide-vue-next';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import SegmentedChoice from '@/Components/Admin/Template/SegmentedChoice.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import PopupPartTypePicker from './PopupPartTypePicker.vue';
import { LINK_TARGET_OPTIONS } from '@/utils/options';
import { POPUP_IMAGE_SIZE_OPTIONS, POPUP_PART_TYPE_LABELS, partHasImage, partHasText } from '@/utils/popupForm';
import type { LanguageOption } from '@/types';
import type { PopupPart } from '@/utils/popupForm';

/**
 * การ์ดข้อมูล 1 รายการ (part) ของ popup — แถบจัดการ: เรียงลำดับ (เปิด dialog) / ลูกตา (แสดง/ซ่อน) / ลบ
 * ฟิลด์รูปภาพ/ขนาด ซ่อนเมื่อเป็นแบบข้อความ, ฟิลด์ข้อความซ่อนเมื่อเป็นแบบรูปภาพ (ค่าที่กรอกไว้ยังอยู่จนกว่าจะบันทึก)
 */
const props = defineProps<{
    part: PopupPart;
    index: number;
    languages: LanguageOption[];
    formErrors: Record<string, string | undefined>;
    /** popup แบบ floating — ซ่อนตัวเลือกรูปแบบ (รูปภาพอย่างเดียว) */
    imageOnly?: boolean;
}>();

defineEmits<{ remove: []; reorder: [] }>();

function error(field: string): string | undefined {
    return props.formErrors[`parts.${props.index}.${field}`];
}

function toggleStatus() {
    props.part.status = props.part.status === 'Y' ? 'N' : 'Y';
}
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <button type="button" class="text-gray-400 hover:text-gray-600" title="จัดลำดับข้อมูล" @click="$emit('reorder')">
                <ListOrdered class="size-5" />
            </button>
            <span class="text-sm font-medium text-gray-700">ข้อมูลที่ {{ index + 1 }}</span>
            <span class="text-xs text-gray-500">{{ POPUP_PART_TYPE_LABELS[part.part_type] }}</span>
            <span v-if="part.status === 'N'" class="rounded bg-gray-200 px-2 py-0.5 text-xs text-gray-600">ซ่อนอยู่</span>

            <div class="ml-auto flex items-center gap-1">
                <button
                    type="button"
                    class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    :title="part.status === 'Y' ? 'ซ่อนข้อมูลนี้' : 'แสดงข้อมูลนี้'"
                    @click="toggleStatus"
                >
                    <Eye v-if="part.status === 'Y'" class="size-4" />
                    <EyeOff v-else class="size-4" />
                </button>
                <button type="button" class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600" title="ลบข้อมูลนี้" @click="$emit('remove')">
                    <Trash2 class="size-4" />
                </button>
            </div>
        </div>

        <div class="space-y-4" :class="part.status === 'N' ? 'opacity-60' : ''">
            <div v-if="!imageOnly">
                <InputLabel value="รูปแบบ" required />
                <PopupPartTypePicker v-model="part.part_type" :name="`popup_part_type_${part._key}`" />
                <InputError :message="error('part_type')" />
            </div>

            <div v-if="partHasImage(part.part_type)" class="grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-6">
                    <InputLabel value="รูปภาพ" required />
                    <FilePickerField v-model="part.image" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                    <InputError :message="error('image_id')" />
                </div>
                <div class="sm:col-span-6">
                    <InputLabel value="ขนาด" />
                    <SegmentedChoice v-model="part.image_size" :options="POPUP_IMAGE_SIZE_OPTIONS" />
                    <InputError :message="error('image_size')" />
                </div>
            </div>

            <LangFieldGroup v-if="partHasText(part.part_type)" label="ข้อความ" :languages="languages" required stacked>
                <template #default="{ lang }">
                    <RichTextEditor v-model="part.detail[lang.code]" />
                    <InputError :message="error(`detail.${lang.code}`)" />
                </template>
            </LangFieldGroup>

            <div class="grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <InputLabel value="ลิงค์ปลายทาง" />
                    <TextInput v-model="part.url" type="text" placeholder="https://..." />
                    <InputError :message="error('url')" />
                </div>
                <div class="sm:col-span-2">
                    <InputLabel value="ลิงค์เป้าหมาย" />
                    <SearchableSelect v-model="part.link_target" :options="LINK_TARGET_OPTIONS" />
                    <InputError :message="error('link_target')" />
                </div>
            </div>
        </div>
    </div>
</template>
