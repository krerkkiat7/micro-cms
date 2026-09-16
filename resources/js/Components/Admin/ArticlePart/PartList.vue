<script setup lang="ts">
import draggable from 'vuedraggable';
import { Plus } from 'lucide-vue-next';
import PartCard from './PartCard.vue';
import { createPart } from '@/utils/articleParts';
import type { PartData, PartType } from '@/utils/articleParts';
import type { LanguageOption } from '@/types';

const props = defineProps<{
    languages: LanguageOption[];
    formErrors: Record<string, string>;
}>();

const parts = defineModel<PartData[]>({ required: true });

const addOptions: { type: PartType; label: string }[] = [
    { type: 'text', label: 'ข้อความ' },
    { type: 'image', label: 'รูปภาพเดี่ยว' },
    { type: 'images', label: 'กลุ่มรูปภาพ' },
    { type: 'video', label: 'วิดีโอ' },
    { type: 'document', label: 'เอกสารเดี่ยว' },
    { type: 'documents', label: 'กลุ่มเอกสาร' },
];

function addPart(type: PartType) {
    parts.value.push(createPart(type, props.languages));
}

function removePart(index: number) {
    parts.value.splice(index, 1);
}
</script>

<template>
    <div class="space-y-4">
        <draggable :list="parts" item-key="_key" handle=".part-drag-handle" class="space-y-4">
            <template #item="{ element, index }">
                <PartCard
                    :part="element"
                    :languages="languages"
                    :error-prefix="`parts.${index}`"
                    :form-errors="formErrors"
                    @remove="removePart(index)"
                />
            </template>
        </draggable>

        <p v-if="parts.length === 0" class="rounded-xl border border-dashed border-gray-300 py-8 text-center text-sm text-gray-400">
            ยังไม่มีเนื้อหา — กดปุ่มด้านล่างเพื่อเพิ่มเนื้อหา
        </p>

        <div class="flex flex-wrap gap-2">
            <button
                v-for="opt in addOptions"
                :key="opt.type"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                @click="addPart(opt.type)"
            >
                <Plus class="size-3.5" /> {{ opt.label }}
            </button>
        </div>
    </div>
</template>
