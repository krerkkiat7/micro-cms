<script setup lang="ts">
import ReportDashboard from '@/Components/Admin/Report/ReportDashboard.vue';
import ReportFilterBar from '@/Components/Admin/Report/ReportFilterBar.vue';
import ReportShell from '@/Components/Admin/Report/ReportShell.vue';
import TopItemTable from '@/Components/Admin/Report/TopItemTable.vue';
import { filterQuery, useChartType } from '@/utils/report';
import type { Breakdowns, CategoryOption, ReportFilters, ReportModule, ReportSummary, Referrers, SeriesRow, TopRow } from '@/utils/report';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    module: ReportModule;
    filters: ReportFilters;
    can: { view_item: boolean };
    categories: CategoryOption[] | null;
    series: SeriesRow[];
    summary: ReportSummary;
    breakdowns: Breakdowns;
    referrers: Referrers;
    heatmap: number[][];
    top: TopRow[];
}>();

const chartType = useChartType();

const exportHref = computed(() => route(`${props.module.route_prefix}.export`, { tab: 'overview', ...filterQuery(props.filters) }));
</script>

<template>
    <ReportShell :module="module" tab="overview" :filters="filters">
        <ReportFilterBar
            v-model:chart-type="chartType"
            :filters="filters"
            :route-name="`${module.route_prefix}.overview`"
            :categories="categories ?? undefined"
            :export-href="exportHref"
        />

        <ReportDashboard
            :series="series"
            :summary="summary"
            :breakdowns="breakdowns"
            :referrers="referrers"
            :heatmap="heatmap"
            :period="filters.period"
            :chart-type="chartType"
            show-items
        >
            <template #after-chart>
                <section>
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">{{ module.item_label }}ยอดนิยม 5 อันดับแรก</h3>
                        <Link :href="route(`${module.route_prefix}.top`, filterQuery(filters))" class="text-sm text-brand-600 hover:text-brand-700">
                            ดู 20 อันดับ
                        </Link>
                    </div>
                    <TopItemTable
                        :rows="top"
                        :total="summary.views"
                        :item-route="can.view_item ? module.item_report_route : null"
                        :show-category="module.has_category"
                    />
                </section>
            </template>
        </ReportDashboard>
    </ReportShell>
</template>
