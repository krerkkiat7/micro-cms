<script setup lang="ts">
import ReportFilterBar from '@/Components/Admin/Report/ReportFilterBar.vue';
import ReportShell from '@/Components/Admin/Report/ReportShell.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import TopItemTable from '@/Components/Admin/Report/TopItemTable.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import { SERIES_COLORS, filterQuery, reportTerms } from '@/utils/report';
import type { CategoryOption, ReportFilters, ReportModule, ReportSummary, TopRow } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    module: ReportModule;
    filters: ReportFilters;
    can: { view_item: boolean };
    categories: CategoryOption[] | null;
    rows: TopRow[];
    summary: ReportSummary;
}>();

const terms = reportTerms(props.module.metric, props.module.item_label);

const exportHref = computed(() => route(`${props.module.route_prefix}.export`, { tab: 'top', ...filterQuery(props.filters) }));

// ชื่อยาวตัดให้พอดีแกน (ชื่อเต็มอยู่ในตาราง)
const labels = computed(() => props.rows.map((r) => {
    const title = r.title ?? `${terms.item} #${r.id}`;
    return `${r.rank}. ${title.length > 40 ? `${title.slice(0, 40)}…` : title}`;
}));

const datasets = computed(() => [
    { label: terms.count, data: props.rows.map((r) => r.views), color: SERIES_COLORS[0] },
    { label: `${terms.unique} (session)`, data: props.rows.map((r) => r.sessions), color: SERIES_COLORS[1] },
]);
</script>

<template>
    <ReportShell :module="module" tab="top" :filters="filters">
        <ReportFilterBar
            :filters="filters"
            :route-name="`${module.route_prefix}.top`"
            :categories="categories ?? undefined"
            :export-href="exportHref"
            :show-period="false"
            :show-chart-type="false"
        />

        <ReportStatCards :summary="summary" show-items />

        <section v-if="rows.length > 0" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-2 text-sm font-semibold text-gray-800">{{ module.item_label }}ยอดนิยม 20 อันดับ</h3>
            <ViewTrendChart :labels="labels" :datasets="datasets" horizontal :height="Math.max(240, rows.length * 34 + 60)" />
        </section>

        <TopItemTable
            :rows="rows"
            :total="summary.views"
            :item-route="can.view_item ? module.item_report_route : null"
            :show-category="module.has_category"
        />
    </ReportShell>
</template>
