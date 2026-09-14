<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { X } from 'lucide-vue-next';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FolderList from './FolderList.vue';
import FileUploadDropzone from './FileUploadDropzone.vue';
import FileBrowser from './FileBrowser.vue';
import type { FileItem } from '@/types';

/**
 * Dialog เลือกไฟล์แบบ reusable — ยกส่วนจัดการไฟล์ (โฟลเดอร์ + browser โหมด selectable) มาไว้ใน modal
 * ใช้คู่กับ FilePickerField.vue เพื่อฝังใน field ของฟอร์มโมดูลอื่น (ยังไม่มีโมดูลจริงผูกไว้ในตอนนี้)
 */
const props = withDefaults(
    defineProps<{
        show: boolean;
        multiple?: boolean;
        accept?: string[];
        /** hash_name ที่ field แม่เลือกไว้แล้วก่อนเปิด dialog รอบนี้ — เลือกซ้ำไม่ได้ */
        alreadySelected?: string[];
    }>(),
    {
        multiple: true,
        accept: undefined,
        alreadySelected: () => [],
    },
);

const emit = defineEmits<{
    close: [];
    select: [files: FileItem[]];
}>();

const selectedFolderId = ref<number | null>(null);
const pending = ref<FileItem[]>([]);
const browser = ref<InstanceType<typeof FileBrowser> | null>(null);

function onUploaded() {
    browser.value?.reload();
}

const pendingHashNames = computed(() => pending.value.map((f) => f.hash_name));

function toggle(file: FileItem) {
    const idx = pending.value.findIndex((f) => f.hash_name === file.hash_name);

    if (idx !== -1) {
        pending.value.splice(idx, 1);

        return;
    }

    if (!props.multiple) {
        pending.value = [file];

        return;
    }

    pending.value.push(file);
}

function confirmSelection() {
    emit('select', pending.value);
    pending.value = [];
}

function close() {
    pending.value = [];
    emit('close');
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.show) {
        close();
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-500/75" @click="close" />

                <div class="relative flex h-[85vh] w-full max-w-5xl flex-col rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h2 class="text-base font-semibold text-gray-800">เลือกไฟล์</h2>
                        <button type="button" class="rounded p-1 text-gray-400 hover:bg-gray-100" @click="close">
                            <X class="size-5" />
                        </button>
                    </div>

                    <div class="grid min-h-0 flex-1 grid-cols-[220px_1fr] gap-4 overflow-hidden p-6">
                        <div class="overflow-y-auto border-r border-gray-100 pr-4">
                            <FolderList v-model="selectedFolderId" />
                        </div>
                        <div class="overflow-y-auto">
                            <FileUploadDropzone :folder-id="selectedFolderId" :accept="accept" @uploaded="onUploaded" />

                            <div class="mt-4">
                                <FileBrowser
                                    ref="browser"
                                    :folder-id="selectedFolderId"
                                    selectable
                                    :accept="accept"
                                    :selected-hash-names="pendingHashNames"
                                    :disabled-hash-names="alreadySelected"
                                    @toggle="toggle"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4">
                        <p class="text-sm text-gray-500">เลือกแล้ว {{ pending.length }} ไฟล์</p>
                        <div class="flex gap-3">
                            <SecondaryButton type="button" @click="close">ยกเลิก</SecondaryButton>
                            <PrimaryButton type="button" :disabled="pending.length === 0" @click="confirmSelection">
                                เลือก
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
