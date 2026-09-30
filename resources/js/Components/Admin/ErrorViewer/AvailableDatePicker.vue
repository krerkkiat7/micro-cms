<script setup lang="ts">
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

/**
 * เลือกวันที่จากปฏิทินรายเดือน — กดได้เฉพาะวันที่อยู่ใน `dates` (วันที่มีข้อมูล) วันอื่นจางและกดไม่ได้
 * เลื่อนเดือนได้เฉพาะช่วงเดือนที่มีข้อมูล; ปุ่ม "วันล่าสุด" = วันที่ใหม่สุดใน `dates`
 * v-model เป็น 'YYYY-MM-DD'; คีย์บอร์ด: ลูกศรเลื่อนไปวันที่มีข้อมูลถัดไป/ก่อนหน้า, Enter เลือก, Esc ปิด
 */
const props = defineProps<{
    /** วันที่ที่มีข้อมูล 'YYYY-MM-DD' (ลำดับใดก็ได้) */
    dates: string[];
}>();

const model = defineModel<string | null>({ required: true });

const THAI_MONTHS = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
const WEEKDAYS = ['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา'];

const open = ref(false);
const root = ref<HTMLElement | null>(null);
const grid = ref<HTMLElement | null>(null);

const available = computed(() => new Set(props.dates));
const sorted = computed(() => [...props.dates].sort());
const firstMonth = computed(() => sorted.value[0]?.slice(0, 7) ?? null);
const lastMonth = computed(() => sorted.value[sorted.value.length - 1]?.slice(0, 7) ?? null);

// เดือนที่แสดง 'YYYY-MM'
const month = ref((model.value ?? sorted.value[sorted.value.length - 1] ?? today()).slice(0, 7));
const focused = ref<string | null>(model.value);

watch(model, (value) => {
    if (value) month.value = value.slice(0, 7);
});

function today(): string {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function shiftMonth(value: string, delta: number): string {
    const [y, m] = value.split('-').map(Number);
    const d = new Date(y, m - 1 + delta, 1);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
}

const canPrev = computed(() => firstMonth.value !== null && month.value > firstMonth.value);
const canNext = computed(() => lastMonth.value !== null && month.value < lastMonth.value);

const monthLabel = computed(() => {
    const [y, m] = month.value.split('-').map(Number);
    return `${THAI_MONTHS[m - 1]} ${y + 543}`;
});

/** ช่องของปฏิทิน (เริ่มวันจันทร์) — null = ช่องว่างก่อนวันที่ 1 */
const cells = computed(() => {
    const [y, m] = month.value.split('-').map(Number);
    const offset = (new Date(y, m - 1, 1).getDay() + 6) % 7;
    const days = new Date(y, m, 0).getDate();
    const list: (string | null)[] = Array(offset).fill(null);

    for (let day = 1; day <= days; day++) {
        list.push(`${month.value}-${String(day).padStart(2, '0')}`);
    }

    return list;
});

const buttonLabel = computed(() => {
    if (!model.value) return 'เลือกวันที่';
    const [y, m, d] = model.value.split('-').map(Number);
    return `${d} ${THAI_MONTHS[m - 1]} ${y + 543}`;
});

function select(date: string): void {
    if (!available.value.has(date)) return;
    model.value = date;
    open.value = false;
}

function toggle(): void {
    open.value = !open.value;

    if (open.value) {
        focused.value = model.value ?? sorted.value[sorted.value.length - 1] ?? null;
        if (focused.value) month.value = focused.value.slice(0, 7);
        nextTick(focusCell);
    }
}

function focusCell(): void {
    grid.value?.querySelector<HTMLButtonElement>(`[data-date="${focused.value}"]`)?.focus();
}

/** ลูกศร: ไปวันที่มีข้อมูลถัดไป/ก่อนหน้า (ข้ามเดือนได้) */
function onKeydown(e: KeyboardEvent): void {
    const list = sorted.value;
    const index = focused.value ? list.indexOf(focused.value) : -1;

    if (['ArrowRight', 'ArrowDown'].includes(e.key) && index < list.length - 1) {
        focused.value = list[index + 1];
    } else if (['ArrowLeft', 'ArrowUp'].includes(e.key) && index > 0) {
        focused.value = list[index - 1];
    } else if (e.key === 'Escape') {
        open.value = false;
        return;
    } else {
        return;
    }

    e.preventDefault();
    month.value = focused.value!.slice(0, 7);
    nextTick(focusCell);
}

function onDocumentClick(e: MouseEvent): void {
    if (open.value && root.value && !root.value.contains(e.target as Node)) open.value = false;
}

onMounted(() => document.addEventListener('click', onDocumentClick));
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick));
</script>

<template>
    <div ref="root" class="relative inline-block">
        <button
            type="button"
            class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-xs hover:border-brand-300 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="dates.length === 0"
            :aria-expanded="open"
            aria-haspopup="dialog"
            @click="toggle"
        >
            <CalendarDays class="size-4 text-gray-500" aria-hidden="true" />
            {{ buttonLabel }}
        </button>

        <div
            v-if="open"
            class="absolute right-0 z-30 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-3 shadow-lg"
            role="dialog"
            aria-label="เลือกวันที่"
            @keydown="onKeydown"
        >
            <div class="mb-2 flex items-center justify-between">
                <button type="button" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 disabled:opacity-30" :disabled="!canPrev" aria-label="เดือนก่อนหน้า" @click="month = shiftMonth(month, -1)">
                    <ChevronLeft class="size-4" />
                </button>
                <span class="text-sm font-semibold text-gray-800">{{ monthLabel }}</span>
                <button type="button" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 disabled:opacity-30" :disabled="!canNext" aria-label="เดือนถัดไป" @click="month = shiftMonth(month, 1)">
                    <ChevronRight class="size-4" />
                </button>
            </div>

            <div ref="grid" role="grid" class="grid grid-cols-7 gap-1 text-center text-sm">
                <span v-for="day in WEEKDAYS" :key="day" class="py-1 text-xs font-medium text-gray-400" role="columnheader">{{ day }}</span>
                <template v-for="(cell, index) in cells" :key="cell ?? `blank-${index}`">
                    <span v-if="!cell" aria-hidden="true"></span>
                    <button
                        v-else
                        type="button"
                        role="gridcell"
                        :data-date="cell"
                        :tabindex="cell === focused ? 0 : -1"
                        :aria-disabled="!available.has(cell)"
                        :aria-selected="cell === model"
                        class="relative rounded-lg py-1.5 tabular-nums"
                        :class="
                            cell === model
                                ? 'bg-brand-500 font-semibold text-white'
                                : available.has(cell)
                                  ? 'font-medium text-gray-800 hover:bg-brand-50'
                                  : 'cursor-default text-gray-300'
                        "
                        @click="select(cell)"
                    >
                        {{ Number(cell.slice(8)) }}
                        <span
                            v-if="available.has(cell) && cell !== model"
                            class="absolute bottom-0.5 left-1/2 size-1 -translate-x-1/2 rounded-full bg-red-500"
                            aria-hidden="true"
                        ></span>
                    </button>
                </template>
            </div>

            <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2 text-xs text-gray-500">
                <span class="inline-flex items-center gap-1"><span class="size-1.5 rounded-full bg-red-500"></span> วันที่มีข้อมูล</span>
                <button
                    v-if="sorted.length"
                    type="button"
                    class="font-medium text-brand-600 hover:text-brand-700"
                    @click="select(sorted[sorted.length - 1])"
                >
                    วันล่าสุด
                </button>
            </div>
        </div>
    </div>
</template>
