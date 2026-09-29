<script setup lang="ts">
import ReportShell from '@/Components/Admin/ArticleReport/ReportShell.vue';
import TopArticleTable from '@/Components/Admin/ArticleReport/TopArticleTable.vue';
import ReportDashboard from '@/Components/Admin/Report/ReportDashboard.vue';
import ReportFilterBar from '@/Components/Admin/Report/ReportFilterBar.vue';
import { filterQuery, useChartType } from '@/utils/report';
import type { Breakdowns, CategoryOption, ReportFilters, ReportSummary, Referrers, SeriesRow, TopRow } from '@/utils/report';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    categories: CategoryOption[];
    series: SeriesRow[];
    summary: ReportSummary;
    breakdowns: Breakdowns;
    referrers: Referrers;
    heatmap: number[][];
    top: TopRow[];
}>();

const chartType = useChartType();

const exportHref = computed(() => route('admin.article.report.export', { tab: 'overview', ...filterQuery(props.filters) }));
</script>

<template>
    <ReportShell tab="overview" tab-title="ภาพรวม" :filters="filters">
        <ReportFilterBar
            v-model:chart-type="chartType"
            :filters="filters"
            route-name="admin.article.report.overview"
            :categories="categories"
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
                        <h3 class="text-sm font-semibold text-gray-800">บทความยอดนิยม 5 อันดับแรก</h3>
                        <Link :href="route('admin.article.report.top', filterQuery(filters))" class="text-sm text-brand-600 hover:text-brand-700">
                            ดู 20 อันดับ
                        </Link>
                    </div>
                    <TopArticleTable :rows="top" :total="summary.views" />
                </section>
            </template>
        </ReportDashboard>
    </ReportShell>
</template>
