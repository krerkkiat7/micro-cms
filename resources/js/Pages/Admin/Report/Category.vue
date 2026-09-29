<script setup lang="ts">
import ReportShell from '@/Components/Admin/Report/ReportShell.vue';
import ReportFilterBar from '@/Components/Admin/Report/ReportFilterBar.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import { PERIOD_OPTIONS, SERIES_COLORS, filterQuery, formatNumber, percent, reportTerms, useChartType } from '@/utils/report';
import type { ReportFilters, ReportModule, SeriesRow } from '@/utils/report';
import { computed } from 'vue';

interface CategoryRow {
    id: number | null;
    title: string | null;
    views: number;
    sessions: number;
    items: number;
}

const props = defineProps<{
    module: ReportModule;
    filters: ReportFilters;
    rows: CategoryRow[];
    buckets: Omit<SeriesRow, 'views' | 'sessions' | 'ips'>[];
    trend: { id: number; title: string | null; values: number[] }[];
}>();

const chartType = useChartType();
const terms = reportTerms(props.module.metric, props.module.item_label);

const exportHref = computed(() => route(`${props.module.route_prefix}.export`, { tab: 'category', ...filterQuery(props.filters) }));

const total = computed(() => props.rows.reduce((sum, r) => sum + r.views, 0));

const categoryName = (row: { id: number | null; title: string | null }) =>
    row.id === null ? 'ไม่ระบุหมวดหมู่' : (row.title ?? `หมวดหมู่ #${row.id}`);

const shareDatasets = computed(() => [
    { label: terms.count, data: props.rows.map((r) => r.views), color: SERIES_COLORS[0] },
]);

// สีตามหมวดหมู่ตามลำดับยอด (สูงสุด 5 หมวดหมู่ = 5 สีที่ตรวจแล้ว ไม่วนสี)
const trendDatasets = computed(() =>
    props.trend.map((t, i) => ({ label: categoryName(t), data: t.values, color: SERIES_COLORS[i % SERIES_COLORS.length] })),
);

const periodLabel = computed(() => PERIOD_OPTIONS.find((p) => p.value === props.filters.period)?.label ?? '');
</script>

<template>
    <ReportShell :module="module" tab="category" :filters="filters">
        <ReportFilterBar v-model:chart-type="chartType" :filters="filters" :route-name="`${module.route_prefix}.category`" :export-href="exportHref" />

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">{{ terms.count }}แยกตามหมวดหมู่</h3>
                <ViewTrendChart
                    v-if="rows.length > 0"
                    :labels="rows.map(categoryName)"
                    :datasets="shareDatasets"
                    horizontal
                    :height="Math.max(220, rows.length * 32 + 40)"
                />
                <p v-else class="py-10 text-center text-sm text-gray-500">ไม่มีข้อมูลในช่วงนี้</p>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">แนวโน้ม{{ periodLabel }} ของ {{ trend.length }} หมวดหมู่ยอดนิยม</h3>
                <ViewTrendChart
                    v-if="trend.length > 0"
                    :labels="buckets.map((b) => b.label)"
                    :datasets="trendDatasets"
                    :type="chartType"
                />
                <p v-else class="py-10 text-center text-sm text-gray-500">ไม่มีข้อมูลในช่วงนี้</p>
            </section>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">หมวดหมู่</th>
                            <th class="w-28 px-4 py-3 text-right font-medium">{{ terms.count }}</th>
                            <th class="w-36 px-4 py-3 text-right font-medium">{{ terms.unique }}</th>
                            <th class="w-44 px-4 py-3 text-right font-medium">{{ terms.item }}ที่มีการ{{ terms.verb }}</th>
                            <th class="w-28 px-4 py-3 text-right font-medium">เฉลี่ย/{{ terms.item }}</th>
                            <th class="w-24 px-4 py-3 text-right font-medium">สัดส่วน</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in rows" :key="row.id ?? 'none'" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-800">{{ categoryName(row) }}</td>
                            <td class="px-4 py-3 text-right text-gray-900 tabular-nums">{{ formatNumber(row.views) }}</td>
                            <td class="px-4 py-3 text-right text-gray-600 tabular-nums">{{ formatNumber(row.sessions) }}</td>
                            <td class="px-4 py-3 text-right text-gray-600 tabular-nums">{{ formatNumber(row.items) }}</td>
                            <td class="px-4 py-3 text-right text-gray-600 tabular-nums">{{ formatNumber(Math.round(row.views / Math.max(1, row.items))) }}</td>
                            <td class="px-4 py-3 text-right text-gray-500 tabular-nums">{{ percent(row.views, total) }}</td>
                        </tr>
                        <tr v-if="rows.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">ไม่มีข้อมูลในช่วงนี้</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </ReportShell>
</template>
