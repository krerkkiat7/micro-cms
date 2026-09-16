<script setup lang="ts">
import { computed } from 'vue';
import { File as FileIcon, FileText, Files, GripVertical, Image as ImageIcon, Images, Video, Trash2 } from 'lucide-vue-next';
import PartText from './PartText.vue';
import PartImage from './PartImage.vue';
import PartImages from './PartImages.vue';
import PartVideo from './PartVideo.vue';
import PartDocument from './PartDocument.vue';
import PartDocuments from './PartDocuments.vue';
import type { LanguageOption } from '@/types';
import type { PartData } from '@/utils/articleParts';

const props = defineProps<{
    part: PartData;
    languages: LanguageOption[];
    errorPrefix: string;
    formErrors: Record<string, string>;
}>();

defineEmits<{ remove: [] }>();

const typeMeta: Record<string, { label: string; icon: unknown; component: unknown }> = {
    text: { label: 'ข้อความ', icon: FileText, component: PartText },
    image: { label: 'รูปภาพเดี่ยว', icon: ImageIcon, component: PartImage },
    images: { label: 'กลุ่มรูปภาพ', icon: Images, component: PartImages },
    video: { label: 'วิดีโอ', icon: Video, component: PartVideo },
    document: { label: 'เอกสารเดี่ยว', icon: FileIcon, component: PartDocument },
    documents: { label: 'กลุ่มเอกสาร', icon: Files, component: PartDocuments },
};

const meta = computed(() => typeMeta[props.part.part_type]);
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
        <div class="mb-4 flex items-center gap-2">
            <button type="button" class="part-drag-handle cursor-grab text-gray-400 hover:text-gray-600" title="ลากเพื่อสลับลำดับ">
                <GripVertical class="size-5" />
            </button>
            <component :is="meta.icon" class="size-4 text-gray-500" />
            <span class="text-sm font-medium text-gray-700">{{ meta.label }}</span>
            <button
                type="button"
                class="ml-auto rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600"
                title="ลบ part นี้"
                @click="$emit('remove')"
            >
                <Trash2 class="size-4" />
            </button>
        </div>

        <component :is="meta.component" :part="part" :languages="languages" :error-prefix="errorPrefix" :form-errors="formErrors" />
    </div>
</template>
