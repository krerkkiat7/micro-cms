<script setup lang="ts">
import DurationCards from '@/Components/Admin/LogStats/DurationCards.vue';
import PageStatsTable from '@/Components/Admin/LogStats/PageStatsTable.vue';
import StatTiles from '@/Components/Admin/LogStats/StatTiles.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import BreakdownList from '@/Components/Admin/Report/BreakdownList.vue';
import ReportSeriesTable from '@/Components/Admin/Report/ReportSeriesTable.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import type { BounceSummary, DurationSummary, LandingRow, PageStatRow } from '@/utils/logStats';
import { PERIOD_OPTIONS, SERIES_COLORS, filterQuery, formatDecimal, formatNumber, reportTerms, useChartType } from '@/utils/report';
import type { ReportFilters, ReportSummary, SeriesRow } from '@/utils/report';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    durationCap: number;
    robotViews: number;
    series: SeriesRow[];
    summary: ReportSummary;
    duration: DurationSummary;
    bounce: BounceSummary;
    pages: PageStatRow[];
    landing: LandingRow[];
}>();

const chartType = useChartType();
const terms = reportTerms('front');

const periodLabel = computed(() => PERIOD_OPTIONS.find((p) => p.value === props.filters.period)?.label ?? '');

const datasets = computed(() => [
    { label: terms.count, data: props.series.map((r) => r.views), color: SERIES_COLORS[0] },
    { label: `${terms.unique}${terms.uniqueSuffix}`, data: props.series.map((r) => r.sessions), color: SERIES_COLORS[1] },
]);

const tiles = computed(() => [
    {
        label: 'อัตราการออกทันที (bounce)',
        value: `${formatDecimal(props.bounce.rate)}%`,
        hint: `${formatNumber(props.bounce.bounced)} จาก ${formatNumber(props.bounce.sessions)} session เปิดหน้าเดียวแล้วออก`,
    },
    { label: 'หน้าต่อการเข้าชม', value: formatDecimal(props.summary.views_per_session), hint: 'จำนวนหน้าเฉลี่ยต่อ session' },
    { label: 'บอท (ไม่นับรวมในสถิติ)', value: formatNumber(props.robotViews), hint: 'เช่น Googlebot / Bingbot — ดูแยกที่แท็บอุปกรณ์และเครือข่าย' },
]);

const landingItems = computed(() => props.landing.map((l) => ({ label: l.title ?? 'ไม่ระบุชื่อหน้า', views: l.sessions })));
</script>

<template>
    <StatsShell v-model:chart-type="chartType" log="frontAccess" tab="overview" :filters="filters">
        <ReportStatCards :summary="summary" />

        <StatTiles :items="tiles" :cols="3" />

        <DurationCards :duration="duration" :cap="durationCap" />

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-2 text-sm font-semibold text-gray-800">{{ terms.count }}{{ periodLabel }}</h3>
            <ViewTrendChart :labels="series.map((r) => r.label)" :datasets="datasets" :type="chartType" />
        </section>

        <div class="grid gap-4 xl:grid-cols-3">
            <section class="xl:col-span-2">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">หน้าที่เข้าชมมากที่สุด 5 อันดับ</h3>
                    <Link :href="route('admin.system.frontlog.access.page', filterQuery(filters))" class="text-sm text-brand-600 hover:text-brand-700">ดูทั้งหมด</Link>
                </div>
                <PageStatsTable :rows="pages" :total="summary.views" page-label="หน้า" :show-users="false" compact />
            </section>
            <BreakdownList title="หน้าแรกที่เข้าชม (landing page)" :items="landingItems" />
        </div>

        <section>
            <h3 class="mb-2 text-sm font-semibold text-gray-800">ตารางข้อมูล{{ periodLabel }}</h3>
            <ReportSeriesTable :rows="series" :period="filters.period" />
        </section>

        <p class="text-xs text-gray-500">ยังไม่มีผู้ใช้งานหน้าบ้าน (จะเพิ่มในรอบถัดไป) — ผู้เข้าชมนับจาก session ของเบราว์เซอร์</p>
    </StatsShell>
</template>
