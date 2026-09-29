<script setup lang="ts">
import DurationCards from '@/Components/Admin/LogStats/DurationCards.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import WeekHourHeatmap from '@/Components/Admin/Report/WeekHourHeatmap.vue';
import { officeHoursSplit } from '@/utils/logStats';
import type { DurationSummary } from '@/utils/logStats';
import { SERIES_COLORS, WEEKDAYS, formatNumber, percent, useChartType } from '@/utils/report';
import type { CategoryOption, ReportFilters, ReportSummary } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[];
    durationCap: number;
    summary: ReportSummary;
    duration: DurationSummary;
    heatmap: number[][];
}>();

const chartType = useChartType();

const byWeekday = computed(() => props.heatmap.map((hours) => hours.reduce((sum, v) => sum + v, 0)));
const byHour = computed(() => Array.from({ length: 24 }, (_, h) => props.heatmap.reduce((sum, day) => sum + (day[h] ?? 0), 0)));
const hourLabels = Array.from({ length: 24 }, (_, h) => `${String(h).padStart(2, '0')}:00`);

// เวลาทำการ จ.–ศ. 08:00–17:59 (officeHoursSplit ใน utils/logStats) — ใช้ตรวจสอบการใช้งานนอกเวลาได้
const split = computed(() => officeHoursSplit(props.heatmap));
const total = computed(() => split.value.total);
const workHours = computed(() => split.value.work);
const weekend = computed(() => split.value.weekend);
const offHours = computed(() => split.value.off);

function busiest(values: number[], labels: string[]): string {
    const max = Math.max(...values);
    if (max <= 0) return '-';
    return `${labels[values.indexOf(max)]} (${formatNumber(max)} ครั้ง)`;
}
</script>

<template>
    <StatsShell log="backAccess" v-model:chart-type="chartType" tab="time" :filters="filters" :user-options="userOptions" :show-period="false">
        <ReportStatCards :summary="summary" :show-items="!filters.user_id" />

        <DurationCards :duration="duration" :cap="durationCap" />

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-500">วันที่ใช้งานมากที่สุด</p>
                <p class="mt-1 text-lg font-semibold text-gray-900">{{ busiest(byWeekday, WEEKDAYS) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-500">ชั่วโมงที่ใช้งานมากที่สุด</p>
                <p class="mt-1 text-lg font-semibold text-gray-900">{{ busiest(byHour, hourLabels) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-500">ในเวลาทำการ (จ.–ศ. 08:00–17:59)</p>
                <p class="mt-1 text-lg font-semibold text-gray-900 tabular-nums">
                    {{ formatNumber(workHours) }} <span class="text-sm font-normal text-gray-500">{{ percent(workHours, total) }}</span>
                </p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-500">นอกเวลาทำการ (จ.–ศ.)</p>
                <p class="mt-1 text-lg font-semibold text-gray-900 tabular-nums">
                    {{ formatNumber(offHours) }} <span class="text-sm font-normal text-gray-500">{{ percent(offHours, total) }}</span>
                </p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-500">วันเสาร์–อาทิตย์</p>
                <p class="mt-1 text-lg font-semibold text-gray-900 tabular-nums">
                    {{ formatNumber(weekend) }} <span class="text-sm font-normal text-gray-500">{{ percent(weekend, total) }}</span>
                </p>
            </div>
        </div>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-3 text-sm font-semibold text-gray-800">การเข้าหน้าจอตามวัน × ชั่วโมง</h3>
            <WeekHourHeatmap :grid="heatmap" />
        </section>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">การเข้าหน้าจอตามวันในสัปดาห์</h3>
                <ViewTrendChart
                    :labels="WEEKDAYS"
                    :datasets="[{ label: 'จำนวนการเข้าหน้าจอ', data: byWeekday, color: SERIES_COLORS[0] }]"
                    :type="chartType"
                    :height="280"
                />
            </section>
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">การเข้าหน้าจอตามชั่วโมง</h3>
                <ViewTrendChart
                    :labels="hourLabels"
                    :datasets="[{ label: 'จำนวนการเข้าหน้าจอ', data: byHour, color: SERIES_COLORS[0] }]"
                    :type="chartType"
                    :height="280"
                />
            </section>
        </div>
    </StatsShell>
</template>
