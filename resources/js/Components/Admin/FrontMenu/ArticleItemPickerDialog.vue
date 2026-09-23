<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { ArrowDown, ArrowUp, ArrowUpDown, Search, X } from 'lucide-vue-next';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Pagination from '@/Components/Pagination.vue';
import { categoryBadgeClass } from '@/utils/categoryBadge';

/**
 * Dialog เลือกบทความ 1 รายการ (ประเภทเมนู "บทความ - รายละเอียดบทความ") — โครงเทียบเคียง FilePickerDialog.vue
 * แต่เรียบง่ายกว่า (ไม่มีโฟลเดอร์/อัพโหลด) ดึงรายการผ่าน admin.system.menu.pick.articles — แสดงเป็นตาราง
 * (ชื่อ/หมวดหมู่/วันที่เผยแพร่) เฉพาะบทความที่เผยแพร่อยู่จริง (backend กรองให้แล้ว)
 */
interface ArticleRow {
    id: number;
    title: string;
    category_id: number | null;
    category_title: string | null;
    publish_date: string | null;
}

const props = defineProps<{
    show: boolean;
    categories: { value: string; label: string }[];
}>();

const emit = defineEmits<{
    close: [];
    select: [article: ArticleRow];
}>();

const filters = reactive({ q: '', category_id: '' });
const page = ref(1);
const loading = ref(false);
const rows = ref<ArticleRow[]>([]);
const meta = reactive({ currentPage: 1, lastPage: 1 });
const sort = ref('title');
const direction = ref<'asc' | 'desc'>('asc');

const categoryFilterOptions = () => [{ value: '', label: 'ทุกหมวดหมู่' }, ...props.categories];

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get(route('admin.system.menu.pick.articles'), {
            params: {
                q: filters.q || undefined,
                category_id: filters.category_id || undefined,
                page: page.value,
                sort: sort.value,
                direction: direction.value,
            },
        });

        rows.value = data.data;
        meta.currentPage = data.current_page;
        meta.lastPage = data.last_page;
    } finally {
        loading.value = false;
    }
}

function search() {
    page.value = 1;
    load();
}

function goToPage(target: number) {
    page.value = target;
    load();
}

function sortBy(column: string) {
    direction.value = sort.value === column && direction.value === 'asc' ? 'desc' : 'asc';
    sort.value = column;
    page.value = 1;
    load();
}

function sortIcon(column: string) {
    if (sort.value !== column) return ArrowUpDown;
    return direction.value === 'asc' ? ArrowUp : ArrowDown;
}

function choose(article: ArticleRow) {
    emit('select', article);
}

function close() {
    emit('close');
}

function formatDate(value: string | null): string {
    if (!value) return '-';

    return new Date(value.replace(' ', 'T')).toLocaleString('th-TH', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.show) {
        close();
    }
}

watch(
    () => props.show,
    (show) => {
        if (show) {
            filters.q = '';
            filters.category_id = '';
            page.value = 1;
            sort.value = 'title';
            direction.value = 'asc';
            load();
        }
    },
);

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

                <div class="relative flex h-[80vh] w-full max-w-3xl flex-col rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h2 class="text-base font-semibold text-gray-800">เลือกบทความ</h2>
                        <button type="button" class="rounded p-1 text-gray-400 hover:bg-gray-100" @click="close">
                            <X class="size-5" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-4">
                        <form class="mb-4 grid gap-2 sm:grid-cols-[1fr_auto_auto]" @submit.prevent="search">
                            <TextInput v-model="filters.q" placeholder="ค้นหาชื่อ, ข้อความเกริ่นนำ" class="text-sm" />
                            <SearchableSelect v-model="filters.category_id" :options="categoryFilterOptions()" class="sm:w-48" />
                            <PrimaryButton type="submit"><Search class="size-4" /></PrimaryButton>
                        </form>

                        <div v-if="rows.length" class="overflow-hidden rounded-xl border border-gray-200">
                            <table class="w-full min-w-[560px] text-left text-sm">
                                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="px-4 py-2.5 font-medium">
                                            <button type="button" class="inline-flex items-center gap-1 transition-colors hover:text-gray-700" @click="sortBy('title')">
                                                ชื่อ
                                                <component :is="sortIcon('title')" class="size-3.5" :class="sort === 'title' ? 'text-brand-500' : 'text-gray-400'" />
                                            </button>
                                        </th>
                                        <th class="w-40 px-4 py-2.5 font-medium">
                                            <button type="button" class="inline-flex items-center gap-1 transition-colors hover:text-gray-700" @click="sortBy('category')">
                                                หมวดหมู่
                                                <component :is="sortIcon('category')" class="size-3.5" :class="sort === 'category' ? 'text-brand-500' : 'text-gray-400'" />
                                            </button>
                                        </th>
                                        <th class="w-44 px-4 py-2.5 font-medium">
                                            <button type="button" class="inline-flex items-center gap-1 transition-colors hover:text-gray-700" @click="sortBy('publish_date')">
                                                วันที่เผยแพร่
                                                <component :is="sortIcon('publish_date')" class="size-3.5" :class="sort === 'publish_date' ? 'text-brand-500' : 'text-gray-400'" />
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="article in rows"
                                        :key="article.id"
                                        class="cursor-pointer transition-colors hover:bg-brand-50/60"
                                        @click="choose(article)"
                                    >
                                        <td class="px-4 py-2.5 text-gray-700">{{ article.title }}</td>
                                        <td class="px-4 py-2.5">
                                            <span
                                                v-if="article.category_title"
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                :class="categoryBadgeClass(article.category_id)"
                                            >
                                                {{ article.category_title }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-600">{{ formatDate(article.publish_date) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p v-else-if="!loading" class="py-10 text-center text-sm text-gray-400">ไม่พบบทความ</p>

                        <Pagination
                            v-if="meta.lastPage > 1"
                            class="mt-4"
                            :current-page="meta.currentPage"
                            :last-page="meta.lastPage"
                            @navigate="goToPage"
                        />
                    </div>

                    <div class="flex items-center justify-end border-t border-gray-100 px-6 py-4">
                        <SecondaryButton type="button" @click="close">ยกเลิก</SecondaryButton>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
