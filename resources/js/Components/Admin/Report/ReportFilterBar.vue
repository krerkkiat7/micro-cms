<script setup lang="ts">
import SegmentedChoice from '@/Components/Admin/Template/SegmentedChoice.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import { PERIOD_OPTIONS, isoDate } from '@/utils/report';
import type { CategoryOption, ChartType, ReportFilters } from '@/utils/report';
import { router } from '@inertiajs/vue3';
import { ChartColumn, ChartLine, Download, Search } from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';

/**
 * แถบตัวกรองของหน้ารายงาน — ช่วงวันที่ (+ ปุ่มลัด), รายวัน/สัปดาห์/เดือน/ปี, dropdown กรองเพิ่ม 1 ช่อง, ชนิดกราฟ
 * dropdown: `categories` = กรองหมวดหมู่ (category_id) หรือ `select` = กำหนดเอง (เช่น ผู้ใช้งาน → user_id)
 * เปลี่ยนตัวกรองแล้ว visit ไปที่ routeName เดิมพร้อม query ใหม่; ชนิดกราฟเป็น v-model ฝั่งหน้าจอเท่านั้น
 */
const props = defineProps<{
    filters: ReportFilters;
    routeName: string;
    routeParams?: Record<string, unknown>;
    categories?: CategoryOption[];
    select?: { param: 'category_id' | 'user_id'; label: string; allLabel: string; options: CategoryOption[] };
    exportHref?: string;
    showChartType?: boolean;
    showPeriod?: boolean;
}>();

const chartType = defineModel<ChartType>('chartType', { default: 'bar' });

// dropdown กรองเพิ่ม — categories เป็นรูปแบบย่อของ select สำหรับหมวดหมู่
const selectConfig = computed(() =>
    props.select ??
    (props.categories ? { param: 'category_id' as const, label: 'หมวดหมู่', allLabel: 'ทุกหมวดหมู่', options: props.categories } : null),
);

const selectedValue = (f: ReportFilters) => {
    const value = selectConfig.value ? f[selectConfig.value.param] : null;
    return value ? String(value) : '';
};

const form = reactive({
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    period: props.filters.period as string,
    select_value: selectedValue(props.filters),
});

watch(
    () => props.filters,
    (f) => {
        form.date_from = f.date_from;
        form.date_to = f.date_to;
        form.period = f.period;
        form.select_value = selectedValue(f);
    },
);

const selectOptions = computed(() =>
    selectConfig.value
        ? [
              { value: '', label: selectConfig.value.allLabel },
              ...selectConfig.value.options.map((c) => ({ value: String(c.id), label: c.title ?? `#${c.id}` })),
          ]
        : [],
);

const chartOptions = [
    { value: 'bar', label: 'กราฟแท่ง', icon: ChartColumn },
    { value: 'line', label: 'กราฟเส้น', icon: ChartLine },
];

const chartModel = computed({
    get: () => chartType.value as string,
    set: (v: string) => (chartType.value = v as ChartType),
});

function apply(extra: Record<string, unknown> = {}) {
    router.get(
        route(props.routeName, props.routeParams ?? {}),
        {
            date_from: form.date_from || undefined,
            date_to: form.date_to || undefined,
            period: form.period,
            ...(selectConfig.value ? { [selectConfig.value.param]: form.select_value !== '' ? form.select_value : undefined } : {}),
            ...extra,
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

/** ปุ่มลัดช่วงวันที่ — ช่วงเวลาเลือกให้อัตโนมัติฝั่ง server (ส่ง period ว่าง) */
function quickRange(kind: '7' | '30' | '90' | 'year' | 'lastyear') {
    const today = new Date();
    let from = new Date(today);
    let to = new Date(today);

    if (kind === 'year') {
        from = new Date(today.getFullYear(), 0, 1);
    } else if (kind === 'lastyear') {
        from = new Date(today.getFullYear() - 1, 0, 1);
        to = new Date(today.getFullYear() - 1, 11, 31);
    } else {
        from.setDate(today.getDate() - (Number(kind) - 1));
    }

    form.date_from = isoDate(from);
    form.date_to = isoDate(to);
    apply({ period: undefined });
}

const quickRanges: { value: '7' | '30' | '90' | 'year' | 'lastyear'; label: string }[] = [
    { value: '7', label: '7 วัน' },
    { value: '30', label: '30 วัน' },
    { value: '90', label: '90 วัน' },
    { value: 'year', label: 'ปีนี้' },
    { value: 'lastyear', label: 'ปีที่แล้ว' },
];
</script>

<template>
    <form class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs" @submit.prevent="apply()">
        <div class="flex flex-wrap items-end gap-3">
            <label class="flex flex-col gap-1">
                <span class="text-xs font-medium text-gray-500">ตั้งแต่วันที่</span>
                <TextInput v-model="form.date_from" type="date" class="w-40" />
            </label>
            <label class="flex flex-col gap-1">
                <span class="text-xs font-medium text-gray-500">ถึงวันที่</span>
                <TextInput v-model="form.date_to" type="date" class="w-40" />
            </label>
            <div v-if="selectConfig" class="flex w-64 flex-col gap-1">
                <span class="text-xs font-medium text-gray-500">{{ selectConfig.label }}</span>
                <SearchableSelect
                    v-model="form.select_value"
                    :options="selectOptions"
                    @update:model-value="(v: string) => apply({ [selectConfig!.param]: v !== '' ? v : undefined })"
                />
            </div>
            <PrimaryButton type="submit">
                <Search class="mr-1.5 size-4" /> แสดงข้อมูล
            </PrimaryButton>
            <a
                v-if="exportHref"
                :href="exportHref"
                class="ml-auto inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-xs transition hover:bg-gray-50"
            >
                <Download class="mr-1.5 size-4" /> ส่งออก CSV
            </a>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-xs text-gray-500">ช่วงด่วน:</span>
            <button
                v-for="range in quickRanges"
                :key="range.value"
                type="button"
                class="rounded-full border border-gray-200 px-3 py-1 text-xs text-gray-600 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700"
                @click="quickRange(range.value)"
            >
                {{ range.label }}
            </button>
        </div>

        <div v-if="showPeriod !== false || showChartType !== false" class="mt-3 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-3">
            <SegmentedChoice
                v-if="showPeriod !== false"
                v-model="form.period"
                :options="PERIOD_OPTIONS"
                @update:model-value="(v: string) => apply({ period: v })"
            />
            <SegmentedChoice v-if="showChartType !== false" v-model="chartModel" :options="chartOptions" />
        </div>
    </form>
</template>
