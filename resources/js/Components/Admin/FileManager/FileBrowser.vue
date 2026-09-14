<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { Download, LayoutGrid, List, RotateCcw, Search, Trash2 } from 'lucide-vue-next';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { fileTypeIcon } from './fileTypeIcons';
import { formatFileSize } from '@/utils/formatFileSize';
import type { FileItem, PaginationLink } from '@/types';

const PER_PAGE_OPTIONS = ['10', '25', '50', '100'];
const SORT_OPTIONS = [
    { value: 'newest', label: 'วันที่อัพโหลดล่าสุด' },
    { value: 'oldest', label: 'วันที่อัพโหลดเก่าสุด' },
    { value: 'name_asc', label: 'ชื่อจาก ก - ฮ' },
    { value: 'name_desc', label: 'ชื่อจาก ฮ - ก' },
];

const props = withDefaults(
    defineProps<{
        folderId: number | null;
        /** โหมดเลือกไฟล์ (ใช้ใน dialog) — true = คลิกแถวเพื่อเลือก/ไม่เลือก แทนการโชว์ปุ่ม download/ลบ */
        selectable?: boolean;
        /** hash_name ที่เลือกอยู่แล้ว (ที่เพิ่งกดในรอบนี้ของ dialog) — ไฮไลต์ขอบให้เห็นว่าเลือกอยู่ */
        selectedHashNames?: string[];
        /** hash_name ที่เลือกไปแล้วจาก field ก่อนหน้า — กดซ้ำไม่ได้ในรอบนี้ */
        disabledHashNames?: string[];
        /** จำกัดนามสกุลที่แสดง (ใช้ตอนเปิดเป็น picker ที่ระบุ accept) */
        accept?: string[];
    }>(),
    {
        selectable: false,
        selectedHashNames: () => [],
        disabledHashNames: () => [],
        accept: undefined,
    },
);

const emit = defineEmits<{
    toggle: [file: FileItem];
}>();

const filters = reactive({ q: '', sort: 'newest', per_page: PER_PAGE_OPTIONS[0] });
const page = ref(1);
const viewMode = ref<'grid' | 'list'>('grid');

const loading = ref(false);
const files = ref<FileItem[]>([]);
const links = ref<PaginationLink[]>([]);
const meta = reactive({ from: 0, to: 0, total: 0 });

const deleteTarget = ref<FileItem | null>(null);
const deleting = ref(false);

function isSelected(file: FileItem): boolean {
    return props.selectedHashNames?.includes(file.hash_name) ?? false;
}

function isDisabled(file: FileItem): boolean {
    return props.disabledHashNames?.includes(file.hash_name) ?? false;
}

/** class ของการ์ด (มุมมองการ์ด) ตามสถานะเลือก/ปิดการเลือก — เส้นขอบรอบการ์ดทั้ง 4 ด้าน */
function cardStateClasses(file: FileItem): string[] {
    if (!props.selectable) {
        return ['border-gray-200', 'hover:border-gray-300'];
    }

    if (isDisabled(file)) {
        return ['cursor-not-allowed', 'border-gray-200', 'opacity-40'];
    }

    return [
        'cursor-pointer',
        isSelected(file) ? 'border-brand-500 ring-2 ring-brand-500/30' : 'border-gray-200 hover:border-gray-300',
    ];
}

/** class ของแถว (มุมมองแถว) ตามสถานะเลือก/ปิดการเลือก — ใช้แถบสีที่ขอบซ้ายแทนเส้นขอบรอบ */
function rowStateClasses(file: FileItem): string[] {
    if (!props.selectable) {
        return ['border-l-transparent'];
    }

    if (isDisabled(file)) {
        return ['cursor-not-allowed', 'border-l-transparent', 'opacity-40'];
    }

    return [
        'cursor-pointer',
        isSelected(file) ? 'border-l-brand-500 bg-brand-50/60' : 'border-l-transparent hover:bg-gray-50',
    ];
}

function thumbnailUrl(file: FileItem, size: number): string {
    return route('admin.system.file.get.thumbnail.size', { size, hashname: file.hash_name });
}

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get(route('admin.system.file.list'), {
            params: {
                folder_id: props.folderId,
                q: filters.q || undefined,
                sort: filters.sort,
                per_page: Number(filters.per_page),
                page: page.value,
            },
        });

        let rows: FileItem[] = data.data;
        if (props.accept?.length) {
            const accept = props.accept;
            rows = rows.filter((f) => !!f.extension && accept.includes(f.extension.toLowerCase()));
        }

        files.value = rows;
        links.value = data.links;
        meta.from = data.from ?? 0;
        meta.to = data.to ?? 0;
        meta.total = data.total ?? 0;
    } finally {
        loading.value = false;
    }
}

function search() {
    page.value = 1;
    load();
}

function resetFilters() {
    filters.q = '';
    filters.sort = 'newest';
    filters.per_page = PER_PAGE_OPTIONS[0];
    page.value = 1;
    load();
}

/** ป้ายกำกับปุ่มเดิน้าเพจ — แทนที่ "« Previous" / "Next »" ของ Laravel ด้วย << / >> ธรรมดา */
function pagerLabel(label: string): string {
    if (label.includes('Previous')) {
        return '<<';
    }

    if (label.includes('Next')) {
        return '>>';
    }

    return label;
}

function goToPage(url: string | null) {
    if (!url) {
        return;
    }

    page.value = Number(new URL(url).searchParams.get('page') ?? '1');
    load();
}

function confirmDelete(file: FileItem) {
    deleteTarget.value = file;
}

async function destroyFile() {
    if (!deleteTarget.value) {
        return;
    }

    deleting.value = true;
    try {
        await axios.delete(route('admin.system.file.destroy', deleteTarget.value.id));
        deleteTarget.value = null;
        await load();
    } finally {
        deleting.value = false;
    }
}

watch(
    () => props.folderId,
    () => {
        page.value = 1;
        load();
    },
);

onMounted(load);

defineExpose({ reload: load });
</script>

<template>
    <div>
        <form class="flex flex-wrap items-end gap-3" @submit.prevent="search">
            <TextInput v-model="filters.q" placeholder="ค้นหาชื่อไฟล์..." class="min-w-[200px] flex-1 text-sm" />
            <SelectInput v-model="filters.sort" class="w-56 shrink-0 text-sm">
                <option v-for="opt in SORT_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </SelectInput>
            <PrimaryButton type="submit"><Search class="mr-1.5 size-4" /> ค้นหา</PrimaryButton>
            <SecondaryButton type="button" @click="resetFilters"><RotateCcw class="mr-1.5 size-4" /> เริ่มใหม่</SecondaryButton>

            <div class="ml-auto flex shrink-0 items-center gap-1 rounded-lg border border-gray-300 bg-white p-1">
                <button
                    type="button"
                    class="rounded p-1.5 transition-colors"
                    :class="viewMode === 'grid' ? 'bg-brand-50 text-brand-600' : 'text-gray-400 hover:text-gray-600'"
                    title="มุมมองการ์ด"
                    @click="viewMode = 'grid'"
                >
                    <LayoutGrid class="size-4" />
                </button>
                <button
                    type="button"
                    class="rounded p-1.5 transition-colors"
                    :class="viewMode === 'list' ? 'bg-brand-50 text-brand-600' : 'text-gray-400 hover:text-gray-600'"
                    title="มุมมองแถว"
                    @click="viewMode = 'list'"
                >
                    <List class="size-4" />
                </button>
            </div>
        </form>

        <!-- มุมมองการ์ด -->
        <div v-if="viewMode === 'grid'" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            <div
                v-for="file in files"
                :key="file.id"
                class="group relative rounded-xl border p-3 transition-colors"
                :class="cardStateClasses(file)"
                @click="selectable && !isDisabled(file) && emit('toggle', file)"
            >
                <div class="flex aspect-square items-center justify-center overflow-hidden rounded-lg bg-gray-50">
                    <img
                        v-if="file.is_image"
                        :src="thumbnailUrl(file, 200)"
                        :alt="file.name"
                        class="size-full object-cover"
                        loading="lazy"
                    />
                    <component :is="fileTypeIcon(file.extension)" v-else class="size-10 text-gray-400" />
                </div>
                <p class="mt-2 truncate text-xs font-medium text-gray-700" :title="file.name">{{ file.name }}</p>
                <p class="text-[11px] text-gray-400">{{ formatFileSize(file.file_size) }} · {{ file.extension?.toUpperCase() }}</p>

                <div v-if="!selectable" class="mt-2 flex items-center gap-2">
                    <a
                        :href="route('admin.system.file.get.download', file.hash_name)"
                        class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-brand-600"
                        title="ดาวน์โหลด"
                    >
                        <Download class="size-4" />
                    </a>
                    <button
                        type="button"
                        class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-red-600"
                        title="ลบ"
                        @click.stop="confirmDelete(file)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </div>

            <p v-if="!loading && files.length === 0" class="col-span-full py-10 text-center text-sm text-gray-400">
                ไม่พบไฟล์ตามเงื่อนไข
            </p>
        </div>

        <!-- มุมมองแถว -->
        <div v-else class="mt-4 divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200">
            <div
                v-for="file in files"
                :key="file.id"
                class="flex items-center gap-3 border-l-4 px-3 py-2 transition-colors"
                :class="rowStateClasses(file)"
                @click="selectable && !isDisabled(file) && emit('toggle', file)"
            >
                <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-50">
                    <img
                        v-if="file.is_image"
                        :src="thumbnailUrl(file, 100)"
                        :alt="file.name"
                        class="size-full object-cover"
                        loading="lazy"
                    />
                    <component :is="fileTypeIcon(file.extension)" v-else class="size-6 text-gray-400" />
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-700" :title="file.name">{{ file.name }}</p>
                    <p class="mt-0.5 flex items-center gap-3 text-xs text-gray-400">
                        <span>{{ formatFileSize(file.file_size) }}</span>
                        <span class="inline-flex items-center gap-1">
                            <component :is="fileTypeIcon(file.extension)" class="size-3.5" />
                            {{ file.extension?.toUpperCase() }}
                        </span>
                    </p>
                </div>

                <div v-if="!selectable" class="flex shrink-0 items-center gap-2">
                    <a
                        :href="route('admin.system.file.get.download', file.hash_name)"
                        class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-brand-600"
                        title="ดาวน์โหลด"
                        @click.stop
                    >
                        <Download class="size-4" />
                    </a>
                    <button
                        type="button"
                        class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-red-600"
                        title="ลบ"
                        @click.stop="confirmDelete(file)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </div>

            <p v-if="!loading && files.length === 0" class="px-3 py-10 text-center text-sm text-gray-400">
                ไม่พบไฟล์ตามเงื่อนไข
            </p>
        </div>

        <!-- paging: ซ้าย = จำนวนรายการ, กลาง = เลขหน้า (ตกลงบรรทัดใหม่ถ้าที่ไม่พอ), ขวา = จำนวนต่อหน้า -->
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <p class="order-1 shrink-0 text-sm text-gray-500">แสดง {{ meta.from }}–{{ meta.to }} จาก {{ meta.total }} รายการ</p>

            <nav
                v-if="links.length > 3"
                class="order-3 flex w-full flex-wrap items-center justify-center gap-1 sm:order-2 sm:w-auto sm:flex-1"
            >
                <button
                    v-for="(link, i) in links"
                    :key="i"
                    type="button"
                    class="rounded-lg border px-3 py-1.5 text-sm transition-colors"
                    :class="
                        link.active
                            ? 'border-brand-500 bg-brand-500 text-white'
                            : link.url
                              ? 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                              : 'cursor-default border-gray-200 text-gray-300'
                    "
                    :disabled="!link.url"
                    @click="goToPage(link.url)"
                >
                    {{ pagerLabel(link.label) }}
                </button>
            </nav>

            <SelectInput v-model="filters.per_page" class="order-2 w-20 shrink-0 !py-1.5 text-sm sm:order-3" @change="search">
                <option v-for="n in PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
            </SelectInput>
        </div>

        <ConfirmDialog
            :show="!!deleteTarget"
            title="ลบไฟล์"
            :message="`ต้องการลบไฟล์ &quot;${deleteTarget?.name}&quot; ใช่หรือไม่?`"
            :processing="deleting"
            @confirm="destroyFile"
            @cancel="deleteTarget = null"
        />
    </div>
</template>
