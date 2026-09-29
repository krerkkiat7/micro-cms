<script setup lang="ts">
import StatTiles from '@/Components/Admin/LogStats/StatTiles.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import BreakdownList from '@/Components/Admin/Report/BreakdownList.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import { ACTION_TYPE_LABELS, userLabel } from '@/utils/logStats';
import type { ActionCounts, ActionModuleRow, ActionUserRow } from '@/utils/logStats';
import { PERIOD_OPTIONS, SERIES_COLORS, filterQuery, formatDecimal, formatNumber, useChartType } from '@/utils/report';
import type { CategoryOption, ReportFilters, ReportSummary, SeriesRow } from '@/utils/report';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[] | null;
    counts: ActionCounts;
    summary: ReportSummary;
    buckets: Omit<SeriesRow, 'views' | 'sessions' | 'ips'>[];
    trend: Record<string, number[]>;
    users: ActionUserRow[];
    modules: ActionModuleRow[];
}>();

const chartType = useChartType();

const periodLabel = computed(() => PERIOD_OPTIONS.find((p) => p.value === props.filters.period)?.label ?? '');

const TYPES = ['create', 'update', 'delete', 'view'] as const;

const changes = computed(() => props.counts.create + props.counts.update + props.counts.delete);

const tiles = computed(() => [
    {
        label: 'การกระทำทั้งหมด',
        value: formatNumber(props.summary.views),
        hint:
            props.summary.change.views === null
                ? 'ช่วงก่อนหน้าไม่มีข้อมูล'
                : `${props.summary.change.views > 0 ? '+' : ''}${formatDecimal(props.summary.change.views)}% จากช่วงก่อนหน้า`,
    },
    { label: 'เปลี่ยนแปลงข้อมูล', value: formatNumber(changes.value), hint: `เพิ่ม ${formatNumber(props.counts.create)} · แก้ไข ${formatNumber(props.counts.update)} · ลบ ${formatNumber(props.counts.delete)}` },
    {
        label: 'ลบข้อมูล',
        value: formatNumber(props.counts.delete),
        hint: 'ควรตรวจสอบถ้าสูงผิดปกติ',
        tone: props.counts.delete > 0 ? ('warning' as const) : undefined,
    },
    { label: 'เปิดดูข้อมูล', value: formatNumber(props.counts.view), hint: `อื่น ๆ (เช่น ส่งออก) ${formatNumber(props.counts.other)}` },
    { label: 'ผู้กระทำ', value: formatNumber(props.summary.sessions), hint: `จาก IP ${formatNumber(props.summary.ips)} รายการ` },
    { label: 'โมดูล / ข้อมูลที่ถูกกระทำ', value: `${formatNumber(props.counts.modules)} / ${formatNumber(props.counts.records)}`, hint: 'module_code / (โมดูล, id) ไม่ซ้ำ' },
]);

// สีตามประเภท (ลำดับคงที่ ไม่ขึ้นกับอันดับ)
const datasets = computed(() =>
    TYPES.map((key, i) => ({ label: ACTION_TYPE_LABELS[key], data: props.trend[key] ?? [], color: SERIES_COLORS[i] })),
);

const typeItems = computed(() =>
    (['create', 'update', 'delete', 'view', 'other'] as const)
        .map((key) => ({ label: ACTION_TYPE_LABELS[key], views: props.counts[key] }))
        .filter((i) => i.views > 0),
);

const userItems = computed(() => props.users.map((u) => ({ label: userLabel(u), views: u.total })));
const moduleItems = computed(() => props.modules.map((m) => ({ label: m.module ?? '-', views: m.total })));
</script>

<template>
    <StatsShell v-model:chart-type="chartType" log="backAction" tab="overview" :filters="filters" :user-options="userOptions">
        <StatTiles :items="tiles" :cols="6" />

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-2 text-sm font-semibold text-gray-800">การกระทำ{{ periodLabel }} แยกตามประเภท</h3>
            <ViewTrendChart :labels="buckets.map((b) => b.label)" :datasets="datasets" :type="chartType" />
        </section>

        <div class="grid gap-4 md:grid-cols-3">
            <BreakdownList title="ประเภทการกระทำ" :items="typeItems" />
            <div>
                <BreakdownList v-if="!filters.user_id" title="ผู้กระทำมากที่สุด 5 อันดับ" :items="userItems" />
                <Link v-if="!filters.user_id" :href="route('admin.system.backlog.action.user', filterQuery(filters))" class="mt-2 inline-block text-sm text-brand-600 hover:text-brand-700">
                    ดูทั้งหมด
                </Link>
            </div>
            <div>
                <BreakdownList title="โมดูลที่มีการกระทำมากที่สุด 5 อันดับ" :items="moduleItems" />
                <Link :href="route('admin.system.backlog.action.module', filterQuery(filters))" class="mt-2 inline-block text-sm text-brand-600 hover:text-brand-700">
                    ดูทั้งหมด
                </Link>
            </div>
        </div>
    </StatsShell>
</template>
