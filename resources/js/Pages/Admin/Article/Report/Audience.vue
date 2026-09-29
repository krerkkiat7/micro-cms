<script setup lang="ts">
import ReportShell from '@/Components/Admin/ArticleReport/ReportShell.vue';
import AudienceBreakdowns from '@/Components/Admin/Report/AudienceBreakdowns.vue';
import ReportFilterBar from '@/Components/Admin/Report/ReportFilterBar.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import { filterQuery } from '@/utils/report';
import type { Breakdowns, CategoryOption, ReportFilters, ReportSummary, Referrers } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    categories: CategoryOption[];
    breakdowns: Breakdowns;
    referrers: Referrers;
    summary: ReportSummary;
}>();

const exportHref = computed(() => route('admin.article.report.export', { tab: 'audience', ...filterQuery(props.filters) }));
</script>

<template>
    <ReportShell tab="audience" tab-title="ผู้เข้าชมและแหล่งที่มา" :filters="filters">
        <ReportFilterBar
            :filters="filters"
            route-name="admin.article.report.audience"
            :categories="categories"
            :export-href="exportHref"
            :show-period="false"
            :show-chart-type="false"
        />

        <ReportStatCards :summary="summary" show-items />

        <AudienceBreakdowns :breakdowns="breakdowns" :referrers="referrers" show-hosts />
    </ReportShell>
</template>
