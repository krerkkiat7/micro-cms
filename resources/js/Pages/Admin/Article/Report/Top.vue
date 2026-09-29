<script setup lang="ts">
import ReportShell from '@/Components/Admin/ArticleReport/ReportShell.vue';
import TopArticleTable from '@/Components/Admin/ArticleReport/TopArticleTable.vue';
import ReportFilterBar from '@/Components/Admin/Report/ReportFilterBar.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import { SERIES_COLORS, filterQuery } from '@/utils/report';
import type { CategoryOption, ReportFilters, ReportSummary, TopRow } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    can: { view_item: boolean };
    categories: CategoryOption[];
    rows: TopRow[];
    summary: ReportSummary;
}>();

const exportHref = computed(() => route('admin.article.report.export', { tab: 'top', ...filterQuery(props.filters) }));

// ชื่อยาวตัดให้พอดีแกน (ชื่อเต็มอยู่ในตาราง)
const labels = computed(() => props.rows.map((r) => {
    const title = r.title ?? `บทความ #${r.id}`;
    return `${r.rank}. ${title.length > 40 ? `${title.slice(0, 40)}…` : title}`;
}));

const datasets = computed(() => [
    { label: 'ยอดเข้าชม', data: props.rows.map((r) => r.views), color: SERIES_COLORS[0] },
    { label: 'ผู้เข้าชมไม่ซ้ำ (session)', data: props.rows.map((r) => r.sessions), color: SERIES_COLORS[1] },
]);
</script>

<template>
    <ReportShell tab="top" tab-title="บทความยอดนิยม" :filters="filters">
        <ReportFilterBar
            :filters="filters"
            route-name="admin.article.report.top"
            :categories="categories"
            :export-href="exportHref"
            :show-period="false"
            :show-chart-type="false"
        />

        <ReportStatCards :summary="summary" show-items />

        <section v-if="rows.length > 0" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-2 text-sm font-semibold text-gray-800">บทความยอดนิยม 20 อันดับ</h3>
            <ViewTrendChart :labels="labels" :datasets="datasets" horizontal :height="Math.max(240, rows.length * 34 + 60)" />
        </section>

        <TopArticleTable :rows="rows" :total="summary.views" :linkable="can.view_item" />
    </ReportShell>
</template>
