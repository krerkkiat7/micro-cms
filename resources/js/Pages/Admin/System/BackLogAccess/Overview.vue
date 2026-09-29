<script setup lang="ts">
import DurationCards from '@/Components/Admin/LogStats/DurationCards.vue';
import PageStatsTable from '@/Components/Admin/LogStats/PageStatsTable.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import UserStatsTable from '@/Components/Admin/BackLogAccess/UserStatsTable.vue';
import ReportSeriesTable from '@/Components/Admin/Report/ReportSeriesTable.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import type { DurationSummary, PageStatRow, UserStatRow } from '@/utils/logStats';
import { PERIOD_OPTIONS, SERIES_COLORS, filterQuery, reportTerms, useChartType } from '@/utils/report';
import type { CategoryOption, ReportFilters, ReportSummary, SeriesRow } from '@/utils/report';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[];
    durationCap: number;
    series: SeriesRow[];
    summary: ReportSummary;
    duration: DurationSummary;
    users: UserStatRow[];
    pages: PageStatRow[];
}>();

const chartType = useChartType();
const terms = reportTerms('access', 'ผู้ใช้งาน');

const periodLabel = computed(() => PERIOD_OPTIONS.find((p) => p.value === props.filters.period)?.label ?? '');

const datasets = computed(() => [
    { label: terms.count, data: props.series.map((r) => r.views), color: SERIES_COLORS[0] },
    { label: terms.unique, data: props.series.map((r) => r.sessions), color: SERIES_COLORS[1] },
]);
</script>

<template>
    <StatsShell log="backAccess" v-model:chart-type="chartType" tab="overview" :filters="filters" :user-options="userOptions">
        <ReportStatCards :summary="summary" :show-items="!filters.user_id" />

        <DurationCards :duration="duration" :cap="durationCap" />

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-2 text-sm font-semibold text-gray-800">{{ terms.count }}{{ periodLabel }}</h3>
            <ViewTrendChart :labels="series.map((r) => r.label)" :datasets="datasets" :type="chartType" />
        </section>

        <div class="grid gap-4 2xl:grid-cols-2">
            <section v-if="!filters.user_id">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">ผู้ใช้งานที่เข้าใช้มากที่สุด 5 อันดับ</h3>
                    <Link :href="route('admin.system.backlog.access.user', filterQuery(filters))" class="text-sm text-brand-600 hover:text-brand-700">ดูทั้งหมด</Link>
                </div>
                <UserStatsTable :rows="users" :total="summary.views" :filters="filters" compact />
            </section>
            <section :class="{ '2xl:col-span-2': filters.user_id }">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">หน้าจอที่ใช้มากที่สุด 5 อันดับ</h3>
                    <Link :href="route('admin.system.backlog.access.page', filterQuery(filters))" class="text-sm text-brand-600 hover:text-brand-700">ดูทั้งหมด</Link>
                </div>
                <PageStatsTable :rows="pages" :total="summary.views" compact />
            </section>
        </div>

        <section>
            <h3 class="mb-2 text-sm font-semibold text-gray-800">ตารางข้อมูล{{ periodLabel }}</h3>
            <ReportSeriesTable :rows="series" :period="filters.period" />
        </section>
    </StatsShell>
</template>
