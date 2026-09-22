<script setup lang="ts">
import draggable from 'vuedraggable';
import { GripVertical, Plus, Trash2 } from 'lucide-vue-next';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import ImagesDisplayTypePicker from '@/Components/Admin/ArticlePart/ImagesDisplayTypePicker.vue';
import { isCarouselDisplayType, isGridDisplayType } from '@/utils/articleParts';
import { createCustomTextFileRow } from '@/utils/pageWidgetCustomText';
import type { LanguageOption } from '@/types';
import type { CustomTextPartData } from '@/utils/pageWidgetCustomText';

/** ใช้ ImagesDisplayTypePicker/รายการรูปแบบแสดงผลร่วมกับ part กลุ่มรูปภาพของบทความ (ตัวเลือกชุดเดียวกันเป๊ะ ๆ) */
const props = defineProps<{
    part: CustomTextPartData;
    languages: LanguageOption[];
    errorPrefix: string;
    formErrors: Record<string, string>;
}>();

function addImage() {
    props.part.files.push(createCustomTextFileRow('images', props.languages));
}

function removeImage(index: number) {
    props.part.files.splice(index, 1);
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <InputLabel value="รูปแบบการแสดงผล" />
            <ImagesDisplayTypePicker v-model="part.images_display_type" class="mt-1" />
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div v-if="isGridDisplayType(part.images_display_type)">
                <InputLabel value="จำนวนคอลัมน์" />
                <TextInput v-model="part.setting.columns" type="number" min="2" max="6" step="1" />
            </div>
            <template v-if="isCarouselDisplayType(part.images_display_type)">
                <div class="flex items-end pb-2.5">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <Checkbox v-model:checked="(part.setting.autoplay as boolean)" />
                        เลื่อนอัตโนมัติ
                    </label>
                </div>
                <div v-if="part.setting.autoplay">
                    <InputLabel value="ความเร็ว (มิลลิวินาที)" />
                    <TextInput v-model="part.setting.interval_ms" type="number" min="1000" step="500" />
                </div>
            </template>
        </div>

        <div>
            <InputLabel value="รูปภาพในกลุ่ม" />
            <draggable :list="part.files" item-key="_key" handle=".image-drag-handle" ghost-class="drag-ghost" :animation="150" class="space-y-2">
                <template #item="{ element, index }">
                    <div class="flex flex-wrap items-start gap-3 rounded-lg border border-gray-200 bg-white p-3">
                        <button type="button" class="image-drag-handle mt-2 cursor-grab text-gray-400 hover:text-gray-600">
                            <GripVertical class="size-5" />
                        </button>
                        <div class="min-w-[220px]">
                            <FilePickerField v-model="element.file" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                        </div>
                        <div class="min-w-[220px] flex-1">
                            <p class="mb-1.5 text-xs font-medium text-gray-500">Alt text ต่อภาษา</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div v-for="lang in languages" :key="lang.code">
                                    <TextInput v-model="(element.description[lang.code] as string)" type="text" :placeholder="lang.code.toUpperCase()" />
                                </div>
                            </div>
                        </div>
                        <button type="button" class="mt-2 rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600" title="ลบรูปนี้" @click="removeImage(index)">
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </template>
            </draggable>

            <p v-if="part.files.length === 0" class="mt-2 text-sm text-gray-400">ยังไม่มีรูปภาพ</p>

            <SecondaryButton type="button" class="mt-3" @click="addImage"> <Plus class="mr-1.5 size-4" /> เพิ่มรูป </SecondaryButton>
        </div>
    </div>
</template>

<style scoped>
/* placeholder ที่ตำแหน่งที่จะวาง ให้เห็นขอบเขตชัดเจนระหว่างลาก แยกจากรายการที่กำลังถูกลากอยู่ */
.drag-ghost {
    opacity: 0.4;
    background-color: #eff6ff;
    border: 2px dashed #93c5fd;
}
</style>
