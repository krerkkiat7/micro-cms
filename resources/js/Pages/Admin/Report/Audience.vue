<script setup lang="ts">
import ReportShell from '@/Components/Admin/Report/ReportShell.vue';
import AudienceBreakdowns from '@/Components/Admin/Report/AudienceBreakdowns.vue';
import ReportFilterBar from '@/Components/Admin/Report/ReportFilterBar.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import { filterQuery } from '@/utils/report';
import type { Breakdowns, CategoryOption, ReportFilters, ReportModule, ReportSummary, Referrers } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    module: ReportModule;
    filters: ReportFilters;
    categories: CategoryOption[] | null;
    breakdowns: Breakdowns;
    referrers: Referrers;
    summary: ReportSummary;
}>();

const exportHref = computed(() => route(`${props.module.route_prefix}.export`, { tab: 'audience', ...filterQuery(props.filters) }));
</script>

<template>
    <ReportShell :module="module" tab="audience" :filters="filters">
        <ReportFilterBar
            :filters="filters"
            :route-name="`${module.route_prefix}.audience`"
            :categories="categories ?? undefined"
            :export-href="exportHref"
            :show-period="false"
            :show-chart-type="false"
        />

        <ReportStatCards :summary="summary" show-items />

        <AudienceBreakdowns :breakdowns="breakdowns" :referrers="referrers" show-hosts />
    </ReportShell>
</template>
