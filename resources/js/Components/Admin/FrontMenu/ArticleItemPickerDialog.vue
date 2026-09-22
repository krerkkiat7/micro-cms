<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { Search, X } from 'lucide-vue-next';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';

/**
 * Dialog เลือกบทความ 1 รายการ (ประเภทเมนู "บทความ - รายละเอียดบทความ") — โครงเทียบเคียง FilePickerDialog.vue
 * แต่เรียบง่ายกว่า (ไม่มีโฟลเดอร์/อัพโหลด) ดึงรายการผ่าน admin.system.menu.pick.articles
 */
interface ArticleRow {
    id: number;
    title: string;
}

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
    select: [article: ArticleRow];
}>();

const filters = reactive({ q: '' });
const page = ref(1);
const loading = ref(false);
const rows = ref<ArticleRow[]>([]);
const meta = reactive({ currentPage: 1, lastPage: 1 });

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get(route('admin.system.menu.pick.articles'), {
            params: { q: filters.q || undefined, page: page.value },
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

function choose(article: ArticleRow) {
    emit('select', article);
}

function close() {
    emit('close');
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
            page.value = 1;
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

                <div class="relative flex h-[75vh] w-full max-w-lg flex-col rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h2 class="text-base font-semibold text-gray-800">เลือกบทความ</h2>
                        <button type="button" class="rounded p-1 text-gray-400 hover:bg-gray-100" @click="close">
                            <X class="size-5" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-4">
                        <form class="mb-4 flex gap-2" @submit.prevent="search">
                            <TextInput v-model="filters.q" placeholder="ค้นหาชื่อบทความ..." class="flex-1 text-sm" />
                            <PrimaryButton type="submit"><Search class="size-4" /></PrimaryButton>
                        </form>

                        <ul v-if="rows.length" class="space-y-1.5">
                            <li v-for="article in rows" :key="article.id">
                                <button
                                    type="button"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-left text-sm text-gray-700 hover:border-brand-500 hover:bg-brand-50/60"
                                    @click="choose(article)"
                                >
                                    {{ article.title }}
                                </button>
                            </li>
                        </ul>

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
