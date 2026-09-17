<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Check, ChevronsUpDown } from 'lucide-vue-next';

/**
 * dropdown แบบพิมพ์ค้นหาตัวเลือกได้ — component มาตรฐานสำหรับ dropdown ทุกจุดในระบบ (แทนที่ SelectInput.vue
 * เดิมที่เป็น native <select> ล้วน ๆ ไปทั้งหมดแล้ว) ค่า v-model เป็น string เสมอ ผูกกับ option.value ที่ตรงกันเท่านั้น
 * ไม่ได้ทำ ARIA/keyboard ครบทุกกรณีแบบ headless UI library — พอสำหรับใช้เมาส์/คีย์บอร์ดพื้นฐาน (ลูกศร/Enter/Esc)
 */
interface Option {
    value: string;
    label: string;
    /** ปิดตัวเลือกนี้ตัวเดียว (เลือกไม่ได้) — เทียบเคียง <option disabled> ของ native select */
    disabled?: boolean;
}

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    options: Option[];
    placeholder?: string;
    disabled?: boolean;
    /** ผูกกับ <label for="..."> ได้เหมือน native <select> — bind ตรงกับปุ่ม/ช่องค้นหาที่กำลังแสดงอยู่ ไม่ใช่ div ครอบนอก */
    id?: string;
}>();

const model = defineModel<string>({ required: true });

const open = ref(false);
const query = ref('');
const highlighted = ref(0);
const root = ref<HTMLElement | null>(null);
const searchInput = ref<HTMLInputElement | null>(null);

const selectedOption = computed(() => props.options.find((o) => o.value === model.value) ?? null);

const filteredOptions = computed(() => {
    const term = query.value.trim().toLowerCase();

    return term === '' ? props.options : props.options.filter((o) => o.label.toLowerCase().includes(term));
});

function openList() {
    if (props.disabled) {
        return;
    }

    open.value = true;
    query.value = '';
    const idx = props.options.findIndex((o) => o.value === model.value);
    highlighted.value = idx !== -1 ? idx : 0;
    nextTick(() => searchInput.value?.focus());
}

function closeList() {
    open.value = false;
    query.value = '';
}

function pick(option: Option) {
    if (option.disabled) {
        return;
    }

    model.value = option.value;
    closeList();
}

function onTriggerKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
        e.preventDefault();
        openList();
    }
}

function onSearchKeydown(e: KeyboardEvent) {
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlighted.value = Math.min(highlighted.value + 1, filteredOptions.value.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlighted.value = Math.max(highlighted.value - 1, 0);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const opt = filteredOptions.value[highlighted.value];
        if (opt) {
            pick(opt);
        }
    } else if (e.key === 'Escape') {
        e.preventDefault();
        closeList();
    }
}

watch(query, () => {
    highlighted.value = 0;
});

function onClickOutside(e: MouseEvent) {
    if (open.value && root.value && !root.value.contains(e.target as Node)) {
        closeList();
    }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside));
</script>

<template>
    <div ref="root" class="relative">
        <button
            v-if="!open"
            :id="id"
            type="button"
            class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-left text-sm shadow-xs transition-colors focus:border-brand-500 focus:outline-none focus:ring-3 focus:ring-brand-500/10 disabled:bg-gray-50 disabled:text-gray-500"
            :class="selectedOption ? 'text-gray-900' : 'text-gray-400'"
            :disabled="disabled"
            v-bind="$attrs"
            @click="openList"
            @keydown="onTriggerKeydown"
        >
            <span class="truncate">{{ selectedOption?.label ?? placeholder ?? 'เลือก' }}</span>
            <ChevronsUpDown class="ml-2 size-4 shrink-0 text-gray-400" />
        </button>

        <input
            v-else
            :id="id"
            ref="searchInput"
            v-model="query"
            type="text"
            :placeholder="selectedOption?.label ?? placeholder ?? 'พิมพ์เพื่อค้นหา'"
            class="block w-full rounded-lg border border-brand-500 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-xs outline-none ring-3 ring-brand-500/10"
            v-bind="$attrs"
            @keydown="onSearchKeydown"
            @blur="closeList"
        />

        <ul
            v-if="open"
            class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
        >
            <li v-if="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-400">ไม่พบตัวเลือกที่ตรงกับคำค้นหา</li>
            <li v-for="(opt, index) in filteredOptions" :key="opt.value">
                <button
                    type="button"
                    class="flex w-full items-center justify-between px-3 py-2 text-left text-sm"
                    :class="
                        opt.disabled
                            ? 'cursor-not-allowed text-gray-300'
                            : index === highlighted
                              ? 'bg-brand-50 text-brand-700'
                              : 'text-gray-700 hover:bg-gray-50'
                    "
                    :disabled="opt.disabled"
                    @mousedown.prevent="pick(opt)"
                    @mouseenter="!opt.disabled && (highlighted = index)"
                >
                    <span class="truncate">{{ opt.label }}</span>
                    <Check v-if="opt.value === model" class="ml-2 size-4 shrink-0 text-brand-600" />
                </button>
            </li>
        </ul>
    </div>
</template>
