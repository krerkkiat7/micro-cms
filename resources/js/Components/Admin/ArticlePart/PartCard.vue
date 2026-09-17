<script setup lang="ts">
import { computed } from 'vue';
import { Eye, EyeOff, ListOrdered, Trash2 } from 'lucide-vue-next';
import Checkbox from '@/Components/Checkbox.vue';
import PartText from './PartText.vue';
import PartImage from './PartImage.vue';
import PartImages from './PartImages.vue';
import PartVideo from './PartVideo.vue';
import PartDocument from './PartDocument.vue';
import PartDocuments from './PartDocuments.vue';
import { PART_TYPE_ICONS, PART_TYPE_LABELS } from '@/utils/articleParts';
import type { LanguageOption } from '@/types';
import type { PartData } from '@/utils/articleParts';

const props = defineProps<{
    part: PartData;
    languages: LanguageOption[];
    errorPrefix: string;
    formErrors: Record<string, string>;
}>();

defineEmits<{ remove: []; reorder: [] }>();

const typeMeta: Record<string, { component: unknown }> = {
    text: { component: PartText },
    image: { component: PartImage },
    images: { component: PartImages },
    video: { component: PartVideo },
    document: { component: PartDocument },
    documents: { component: PartDocuments },
};

const meta = computed(() => typeMeta[props.part.part_type]);
const icon = computed(() => PART_TYPE_ICONS[props.part.part_type]);

function toggleStatus() {
    props.part.status = props.part.status === 'Y' ? 'N' : 'Y';
}
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4" :class="part.status === 'N' ? 'opacity-60' : ''">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <button
                type="button"
                class="text-gray-400 hover:text-gray-600"
                title="จัดลำดับเนื้อหา"
                @click="$emit('reorder')"
            >
                <ListOrdered class="size-5" />
            </button>
            <component :is="icon" class="size-4 text-gray-500" />
            <span class="text-sm font-medium text-gray-700">{{ PART_TYPE_LABELS[part.part_type] }}</span>

            <label class="ml-2 flex items-center gap-1.5 text-xs text-gray-500">
                <Checkbox :checked="part.show_title === 'Y'" @update:checked="(v) => (part.show_title = v ? 'Y' : 'N')" />
                แสดงหัวเรื่องที่หน้าบ้าน
            </label>

            <div class="ml-auto flex items-center gap-1">
                <button
                    type="button"
                    class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    :title="part.status === 'Y' ? 'ซ่อน part นี้' : 'แสดง part นี้'"
                    @click="toggleStatus"
                >
                    <Eye v-if="part.status === 'Y'" class="size-4" />
                    <EyeOff v-else class="size-4" />
                </button>
                <button
                    type="button"
                    class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600"
                    title="ลบ part นี้"
                    @click="$emit('remove')"
                >
                    <Trash2 class="size-4" />
                </button>
            </div>
        </div>

        <component :is="meta.component" :part="part" :languages="languages" :error-prefix="errorPrefix" :form-errors="formErrors" />
    </div>
</template>
