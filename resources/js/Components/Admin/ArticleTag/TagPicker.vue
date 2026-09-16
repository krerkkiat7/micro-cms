<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';
import { Plus, X } from 'lucide-vue-next';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import NewTagDialog from './NewTagDialog.vue';
import type { LanguageOption } from '@/types';

interface TagChip {
    id: number;
    name: string;
}

/**
 * เลือก/สร้างแท็กของบทความ — ไม่มีรายการมาให้เลือกล่วงหน้า พิมพ์ค้นหาจากชื่อภาษาหลักแบบ autocomplete
 * เลือกจากผลลัพธ์ = ใช้แท็กเดิม, พิมพ์แล้วกด "เพิ่ม" โดยไม่ได้เลือก: ถ้ามีแท็กชื่อนี้อยู่แล้วก็ใช้ตัวนั้น
 * เหมือนกัน แต่ถ้ายังไม่มีจะเปิด dialog ให้กรอกชื่อแท็กใหม่ครบทุกภาษาก่อนสร้างจริง
 */
const props = defineProps<{
    languages: LanguageOption[];
    initialChips?: TagChip[];
}>();

const modelIds = defineModel<number[]>({ required: true });

const chips = ref<TagChip[]>(props.initialChips ? [...props.initialChips] : []);
watch(
    chips,
    (value) => {
        modelIds.value = value.map((c) => c.id);
    },
    { immediate: true, deep: true },
);

function isSelected(id: number): boolean {
    return chips.value.some((c) => c.id === id);
}

const query = ref('');
const results = ref<TagChip[]>([]);
const showResults = ref(false);
let searchTimer: ReturnType<typeof setTimeout> | null = null;

async function fetchResults(term: string): Promise<TagChip[]> {
    const { data } = await axios.get<{ data: TagChip[] }>(route('admin.article.tag.search'), { params: { q: term } });

    return data.data;
}

function search() {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    const term = query.value.trim();

    if (term === '') {
        results.value = [];
        showResults.value = false;

        return;
    }

    searchTimer = setTimeout(async () => {
        const found = await fetchResults(term);
        results.value = found.filter((t) => !isSelected(t.id));
        showResults.value = true;
    }, 250);
}

function pick(tag: TagChip) {
    if (!isSelected(tag.id)) {
        chips.value.push(tag);
    }
    query.value = '';
    results.value = [];
    showResults.value = false;
}

function remove(id: number) {
    chips.value = chips.value.filter((c) => c.id !== id);
}

const showNewTagDialog = ref(false);

async function add() {
    const term = query.value.trim();

    if (term === '') {
        return;
    }

    const found = await fetchResults(term);
    const exact = found.find((t) => t.name.trim().toLowerCase() === term.toLowerCase());

    if (exact) {
        pick(exact);

        return;
    }

    showNewTagDialog.value = true;
}

function onCreated(tag: TagChip) {
    pick(tag);
    showNewTagDialog.value = false;
}
</script>

<template>
    <div>
        <div v-if="chips.length" class="mb-2 flex flex-wrap gap-2">
            <span
                v-for="chip in chips"
                :key="chip.id"
                class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-sm text-brand-700"
            >
                {{ chip.name }}
                <button type="button" class="text-brand-400 hover:text-brand-700" @click="remove(chip.id)">
                    <X class="size-3.5" />
                </button>
            </span>
        </div>

        <div class="flex gap-2">
            <div class="relative flex-1">
                <TextInput
                    v-model="query"
                    type="text"
                    placeholder="พิมพ์ชื่อแท็ก แล้วเลือกจากรายการ หรือกดเพิ่ม"
                    @input="search"
                    @keydown.enter.prevent="add"
                />
                <ul v-if="showResults && results.length" class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                    <li v-for="r in results" :key="r.id">
                        <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50" @click="pick(r)">
                            {{ r.name }}
                        </button>
                    </li>
                </ul>
            </div>
            <SecondaryButton type="button" @click="add">
                <Plus class="mr-1 size-4" /> เพิ่ม
            </SecondaryButton>
        </div>

        <NewTagDialog
            :show="showNewTagDialog"
            :languages="languages"
            :default-name="query"
            @close="showNewTagDialog = false"
            @created="onCreated"
        />
    </div>
</template>
