<script setup lang="ts">
import { ref } from 'vue';
import { Trash2 } from 'lucide-vue-next';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FilePickerDialog from './FilePickerDialog.vue';
import { fileTypeIcon } from './fileTypeIcons';
import { formatFileSize } from '@/utils/formatFileSize';
import type { FileItem } from '@/types';

/**
 * ฟิลด์ "เลือกไฟล์" แบบ reusable สำหรับฝังในฟอร์มของโมดูลอื่น (เช่น รูปโปรไฟล์ผู้ใช้, รูปปกบทความ
 * ในอนาคต) — v-model เป็น array ของไฟล์ที่เลือกไว้เสมอ (ต่อให้ multiple=false ก็มีอย่างมาก 1 รายการ)
 *
 * ตัวอย่างการใช้งาน:
 *   <FilePickerField v-model="form.cover_images" :accept="['jpg','jpeg','png','webp']" />
 *   <FilePickerField v-model="form.avatar" :multiple="false" :accept="['jpg','jpeg','png']" />
 */
const props = withDefaults(
    defineProps<{
        multiple?: boolean;
        /** จำกัดนามสกุลไฟล์ที่เลือกได้ เช่น ['jpg','jpeg','png','webp'] — ไม่ระบุ = เลือกได้ทุกนามสกุลที่อัพโหลดได้ */
        accept?: string[];
    }>(),
    {
        multiple: false,
        accept: undefined,
    },
);

const model = defineModel<FileItem[]>({ required: true });

const showDialog = ref(false);

function onSelect(files: FileItem[]) {
    // เลือกไฟล์ใหม่ต่อท้ายรายการเดิมเสมอ (ไม่ทับของเดิม) — ยกเว้นโหมดเลือกได้ 1 ไฟล์ ให้แทนที่
    model.value = props.multiple ? [...model.value, ...files] : files.slice(0, 1);
    showDialog.value = false;
}

function remove(file: FileItem) {
    model.value = model.value.filter((f) => f.hash_name !== file.hash_name);
}
</script>

<template>
    <div>
        <SecondaryButton type="button" @click="showDialog = true">เลือกไฟล์</SecondaryButton>

        <ul v-if="model.length" class="mt-3 space-y-2">
            <li
                v-for="file in model"
                :key="file.hash_name"
                class="flex items-center gap-3 rounded-lg border border-gray-200 px-3 py-2"
            >
                <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded bg-gray-100 ring-1 ring-inset ring-gray-200">
                    <img
                        v-if="file.is_image"
                        :src="route('admin.system.file.get.thumbnail.size', { size: 80, hashname: file.hash_name })"
                        :alt="file.name"
                        class="size-full object-contain"
                    />
                    <component :is="fileTypeIcon(file.extension)" v-else class="size-5 text-gray-400" />
                </div>
                <a
                    :href="route('admin.system.file.get.download', file.hash_name)"
                    class="min-w-0 flex-1 truncate text-sm text-brand-600 hover:underline"
                >
                    {{ file.name }}
                </a>
                <span class="shrink-0 text-xs text-gray-400">{{ formatFileSize(file.file_size) }}</span>
                <button
                    type="button"
                    class="shrink-0 rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-red-600"
                    title="เอาออก"
                    @click="remove(file)"
                >
                    <Trash2 class="size-4" />
                </button>
            </li>
        </ul>

        <FilePickerDialog
            :show="showDialog"
            :multiple="multiple"
            :accept="accept"
            :already-selected="model.map((f) => f.hash_name)"
            @close="showDialog = false"
            @select="onSelect"
        />
    </div>
</template>
