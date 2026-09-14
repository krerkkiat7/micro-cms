<script setup lang="ts">
import { onMounted, ref } from 'vue';
import axios from 'axios';
import { Folder, FolderPlus, Loader2 } from 'lucide-vue-next';
import TextInput from '@/Components/TextInput.vue';
import type { FolderItem } from '@/types';

// null = โฟลเดอร์ราก ("ไม่มีโฟลเดอร์" pseudo item) — ค่าเริ่มต้นของ parent จึงเท่ากับ
// "เลือกโฟลเดอร์เปล่าให้ก่อนเลย" ตอนเข้าหน้าครั้งแรกโดยอัตโนมัติอยู่แล้ว
const selected = defineModel<number | null>({ required: true });

const folders = ref<FolderItem[]>([]);
const rootCount = ref(0);
const loading = ref(true);
const newFolderName = ref('');
const creating = ref(false);
const error = ref('');

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get<{ data: FolderItem[]; root_count: number }>(route('admin.system.file.folders'));
        folders.value = data.data;
        rootCount.value = data.root_count;
    } finally {
        loading.value = false;
    }
}

async function createFolder() {
    const name = newFolderName.value.trim();

    if (!name || creating.value) {
        return;
    }

    creating.value = true;
    error.value = '';

    try {
        const { data } = await axios.post<{ data: FolderItem }>(route('admin.system.file.folders.store'), { name });
        folders.value.push(data.data);
        folders.value.sort((a, b) => a.name.localeCompare(b.name, 'th'));
        newFolderName.value = '';
        selected.value = data.data.id;
    } catch (e: unknown) {
        const response = (e as { response?: { data?: { errors?: { name?: string[] } } } }).response;
        error.value = response?.data?.errors?.name?.[0] ?? 'สร้างโฟลเดอร์ไม่สำเร็จ';
    } finally {
        creating.value = false;
    }
}

onMounted(load);

defineExpose({ reload: load });
</script>

<template>
    <div class="flex h-full flex-col">
        <form class="mb-3 flex gap-2" @submit.prevent="createFolder">
            <TextInput
                v-model="newFolderName"
                placeholder="เพิ่มโฟลเดอร์ใหม่..."
                class="flex-1 !py-2 text-sm"
            />
            <button
                type="submit"
                class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-gray-500 transition-colors hover:bg-gray-50 disabled:opacity-50"
                :disabled="creating || !newFolderName.trim()"
                title="เพิ่มโฟลเดอร์"
            >
                <FolderPlus class="size-4" />
            </button>
        </form>
        <p v-if="error" class="-mt-2 mb-2 text-xs text-red-600">{{ error }}</p>

        <div class="flex-1 space-y-1 overflow-y-auto">
            <button
                type="button"
                class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors"
                :class="selected === null ? 'bg-brand-50 text-brand-700' : 'text-gray-700 hover:bg-gray-50'"
                @click="selected = null"
            >
                <span class="flex min-w-0 items-center gap-2">
                    <Folder class="size-4 shrink-0" />
                    <span class="truncate">[ไม่มีโฟลเดอร์]</span>
                </span>
                <span class="shrink-0 text-xs text-gray-400">{{ rootCount }}</span>
            </button>

            <button
                v-for="folder in folders"
                :key="folder.id"
                type="button"
                class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors"
                :class="selected === folder.id ? 'bg-brand-50 text-brand-700' : 'text-gray-700 hover:bg-gray-50'"
                @click="selected = folder.id"
            >
                <span class="flex min-w-0 items-center gap-2">
                    <Folder class="size-4 shrink-0" />
                    <span class="truncate">{{ folder.name }}</span>
                </span>
                <span v-if="folder.files_count !== undefined" class="shrink-0 text-xs text-gray-400">
                    {{ folder.files_count }}
                </span>
            </button>

            <Loader2 v-if="loading" class="mx-auto mt-3 size-5 animate-spin text-gray-400" />
            <p v-else-if="folders.length === 0" class="px-3 py-2 text-xs text-gray-400">ยังไม่มีโฟลเดอร์</p>
        </div>
    </div>
</template>
