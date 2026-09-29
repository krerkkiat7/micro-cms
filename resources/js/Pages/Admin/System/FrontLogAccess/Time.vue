<script setup lang="ts">
import DurationCards from '@/Components/Admin/LogStats/DurationCards.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import WeekHourHeatmap from '@/Components/Admin/Report/WeekHourHeatmap.vue';
import type { DurationSummary } from '@/utils/logStats';
import { SERIES_COLORS, WEEKDAYS, formatNumber, useChartType } from '@/utils/report';
import type { ReportFilters, ReportSummary } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    durationCap: number;
    summary: ReportSummary;
    duration: DurationSummary;
    heatmap: number[][];
}>();

const chartType = useChartType();

const byWeekday = computed(() => props.heatmap.map((hours) => hours.reduce((sum, v) => sum + v, 0)));
const byHour = computed(() => Array.from({ length: 24 }, (_, h) => props.heatmap.reduce((sum, day) => sum + (day[h] ?? 0), 0)));
const hourLabels = Array.from({ length: 24 }, (_, h) => `${String(h).padStart(2, '0')}:00`);

function busiest(values: number[], labels: string[]): string {
    const max = Math.max(...values);
    if (max <= 0) return '-';
    return `${labels[values.indexOf(max)]} (${formatNumber(max)} ครั้ง)`;
}
</script>

<template>
    <StatsShell v-model:chart-type="chartType" log="frontAccess" tab="time" :filters="filters" :show-period="false">
        <ReportStatCards :summary="summary" />

        <DurationCards :duration="duration" :cap="durationCap" />

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-500">วันที่มีผู้เข้าชมมากที่สุด</p>
                <p class="mt-1 text-lg font-semibold text-gray-900">{{ busiest(byWeekday, WEEKDAYS) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-500">ชั่วโมงที่มีผู้เข้าชมมากที่สุด</p>
                <p class="mt-1 text-lg font-semibold text-gray-900">{{ busiest(byHour, hourLabels) }}</p>
            </div>
        </div>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-3 text-sm font-semibold text-gray-800">การเปิดหน้าตามวัน × ชั่วโมง</h3>
            <WeekHourHeatmap :grid="heatmap" />
        </section>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">การเปิดหน้าตามวันในสัปดาห์</h3>
                <ViewTrendChart
                    :labels="WEEKDAYS"
                    :datasets="[{ label: 'จำนวนการเปิดหน้า', data: byWeekday, color: SERIES_COLORS[0] }]"
                    :type="chartType"
                    :height="280"
                />
            </section>
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">การเปิดหน้าตามชั่วโมง</h3>
                <ViewTrendChart
                    :labels="hourLabels"
                    :datasets="[{ label: 'จำนวนการเปิดหน้า', data: byHour, color: SERIES_COLORS[0] }]"
                    :type="chartType"
                    :height="280"
                />
            </section>
        </div>
    </StatsShell>
</template>
