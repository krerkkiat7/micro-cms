<script setup lang="ts">
import draggable from 'vuedraggable';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import { GripVertical, Plus, Trash2 } from 'lucide-vue-next';
import { createFileRow } from '@/utils/articleParts';
import type { LanguageOption } from '@/types';
import type { PartData } from '@/utils/articleParts';

const props = defineProps<{
    part: PartData;
    languages: LanguageOption[];
    errorPrefix: string;
    formErrors: Record<string, string>;
}>();

function addDocument() {
    props.part.files.push(createFileRow('documents', props.languages));
}

function removeDocument(index: number) {
    props.part.files.splice(index, 1);
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
            <InputLabel value="ไฟล์เอกสารในกลุ่ม" />
            <draggable
                :list="part.files"
                item-key="_key"
                handle=".document-drag-handle"
                ghost-class="drag-ghost"
                :animation="150"
                class="space-y-2"
            >
                <template #item="{ element, index }">
                    <div class="flex flex-wrap items-start gap-3 rounded-lg border border-gray-200 bg-white p-3">
                        <button type="button" class="document-drag-handle mt-2 cursor-grab text-gray-400 hover:text-gray-600">
                            <GripVertical class="size-5" />
                        </button>
                        <div class="min-w-[220px] flex-1">
                            <FilePickerField v-model="element.file" />
                        </div>
                        <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                            <Checkbox v-model:checked="(element.description.pdf_preview as boolean)" />
                            preview ถ้าเป็น PDF
                        </label>
                        <button
                            type="button"
                            class="mt-2 rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600"
                            title="ลบไฟล์นี้"
                            @click="removeDocument(index)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </template>
            </draggable>

            <p v-if="part.files.length === 0" class="mt-2 text-sm text-gray-400">ยังไม่มีไฟล์เอกสาร</p>

            <SecondaryButton type="button" class="mt-3" @click="addDocument">
                <Plus class="mr-1.5 size-4" /> เพิ่มไฟล์
            </SecondaryButton>
        </div>
    </div>
</template>

<style scoped>
/* placeholder ที่ตำแหน่งที่จะวาง (เหมือนตัวอย่าง Simple List ของ SortableJS) ให้เห็นขอบเขตชัดเจน
ระหว่างลาก แยกจากรายการที่กำลังถูกลากอยู่ */
.drag-ghost {
    opacity: 0.4;
    background-color: #eff6ff;
    border: 2px dashed #93c5fd;
}
</style>

