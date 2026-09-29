<script setup lang="ts">
import AudienceBreakdowns from '@/Components/Admin/Report/AudienceBreakdowns.vue';
import ReportSeriesTable from '@/Components/Admin/Report/ReportSeriesTable.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import WeekHourHeatmap from '@/Components/Admin/Report/WeekHourHeatmap.vue';
import { PERIOD_OPTIONS, SERIES_COLORS } from '@/utils/report';
import type { Breakdowns, ChartType, ReportPeriod, ReportSummary, Referrers, SeriesRow } from '@/utils/report';
import { computed } from 'vue';

/**
 * เนื้อหารายงานแบบครบชุด: การ์ดสรุป → กราฟ + ตารางรายช่วง → สัดส่วนผู้เข้าชม → heatmap
 * ใช้ร่วมกันระหว่างรายงานรายบทความและแท็บภาพรวมของเมนูรายงาน (slot `after-chart` = เนื้อหาเสริมหลังกราฟ)
 */
const props = defineProps<{
    series: SeriesRow[];
    summary: ReportSummary;
    breakdowns: Breakdowns;
    referrers: Referrers;
    heatmap: number[][];
    period: ReportPeriod;
    chartType: ChartType;
    showItems?: boolean;
}>();

const periodLabel = computed(() => PERIOD_OPTIONS.find((p) => p.value === props.period)?.label ?? '');

const datasets = computed(() => [
    { label: 'ยอดเข้าชม', data: props.series.map((r) => r.views), color: SERIES_COLORS[0] },
    { label: 'ผู้เข้าชมไม่ซ้ำ (session)', data: props.series.map((r) => r.sessions), color: SERIES_COLORS[1] },
]);
</script>

<template>
    <div class="space-y-4">
        <ReportStatCards :summary="summary" :show-items="showItems" />

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-2 text-sm font-semibold text-gray-800">ยอดเข้าชม{{ periodLabel }}</h3>
            <ViewTrendChart :labels="series.map((r) => r.label)" :datasets="datasets" :type="chartType" />
        </section>

        <slot name="after-chart" />

        <section>
            <h3 class="mb-2 text-sm font-semibold text-gray-800">ตารางข้อมูล{{ periodLabel }}</h3>
            <ReportSeriesTable :rows="series" :period="period" />
        </section>

        <section>
            <h3 class="mb-2 text-sm font-semibold text-gray-800">ผู้เข้าชมและแหล่งที่มา</h3>
            <AudienceBreakdowns :breakdowns="breakdowns" :referrers="referrers" />
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-3 text-sm font-semibold text-gray-800">ช่วงเวลาที่มีผู้เข้าชม (วัน × ชั่วโมง)</h3>
            <WeekHourHeatmap :grid="heatmap" />
        </section>
    </div>
</template>
