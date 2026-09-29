<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import ReportDashboard from '@/Components/Admin/Report/ReportDashboard.vue';
import ReportFilterBar from '@/Components/Admin/Report/ReportFilterBar.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import { formatDateTime } from '@/utils/date';
import { filterQuery, formatNumber, useChartType } from '@/utils/report';
import type { Breakdowns, ReportFilters, ReportSummary, Referrers, SeriesRow } from '@/utils/report';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    item: {
        id: number;
        title: string | null;
        category_title: string | null;
        publish_date: string | null;
        view_amount: number;
        status: string;
    };
    filters: ReportFilters;
    series: SeriesRow[];
    summary: ReportSummary;
    breakdowns: Breakdowns;
    referrers: Referrers;
    heatmap: number[][];
}>();

const chartType = useChartType();

const title = computed(() => props.item.title || `บทความ #${props.item.id}`);

const tabs = computed(() => [
    { label: 'ข้อมูลทั่วไป', href: route('admin.article.item.edit', props.item.id), active: false },
    { label: 'รายงาน', href: route('admin.article.item.report', props.item.id), active: true },
]);

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'บทความ', href: route('admin.article.item.index') },
    { label: title.value, href: route('admin.article.item.edit', props.item.id) },
    { label: 'รายงาน' },
]);

const exportHref = computed(() => route('admin.article.item.report.export', { item: props.item.id, ...filterQuery(props.filters) }));
</script>

<template>
    <Head :title="`รายงานบทความ: ${title}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="title" :breadcrumbs="breadcrumbs" />
        </template>

        <TabNav :tabs="tabs" class="mb-6" />

        <div class="space-y-4">
            <!-- ข้อมูลบทความ (บริบทของรายงาน) -->
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm shadow-xs">
                <span class="text-gray-500">หมวดหมู่: <span class="text-gray-800">{{ item.category_title ?? '-' }}</span></span>
                <span class="text-gray-500">วันที่เผยแพร่: <span class="text-gray-800">{{ formatDateTime(item.publish_date) }}</span></span>
                <span class="text-gray-500">ยอดเข้าชมสะสมทั้งหมด: <span class="font-semibold text-gray-900 tabular-nums">{{ formatNumber(item.view_amount) }}</span></span>
                <StatusBadge :status="item.status" />
            </div>

            <ReportFilterBar
                v-model:chart-type="chartType"
                :filters="filters"
                route-name="admin.article.item.report"
                :route-params="{ item: item.id }"
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
            />

            <div class="flex flex-wrap items-center gap-3">
                <BackToListButton :href="route('admin.article.item.index')" />
            </div>
        </div>
    </AdminLayout>
</template>
