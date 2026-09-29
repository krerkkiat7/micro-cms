<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import ReportDashboard from '@/Components/Admin/Report/ReportDashboard.vue';
import ReportFilterBar from '@/Components/Admin/Report/ReportFilterBar.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import { formatDateTime } from '@/utils/date';
import { filterQuery, formatNumber, provideReportTerms, useChartType } from '@/utils/report';
import type { Breakdowns, ReportFilters, ReportMetric, ReportSummary, Referrers, SeriesRow } from '@/utils/report';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * รายงานการเข้าชม/คลิกของรายการเดียว (แท็บ "รายงาน" ในหน้าแก้ไขของบทความ / หน้าเพจ / ป้ายโฆษณา)
 * route ทั้งหมดมาจาก module.key: admin.<key>.item.{index,report,report.export} + แท็บของหน้าแก้ไขใน module.tabs
 */
const props = defineProps<{
    module: {
        key: string;
        item_label: string;
        list_label: string;
        metric: ReportMetric;
        has_category: boolean;
        tabs: { label: string; route: string }[];
    };
    item: {
        id: number;
        title: string | null;
        category_title: string | null;
        publish_date: string | null;
        amount: number;
        status: string;
    };
    filters: ReportFilters;
    series: SeriesRow[];
    summary: ReportSummary;
    breakdowns: Breakdowns;
    referrers: Referrers;
    heatmap: number[][];
}>();

const terms = provideReportTerms(props.module.metric, props.module.item_label);
const chartType = useChartType();

const routeBase = computed(() => `admin.${props.module.key}.item`);
const title = computed(() => props.item.title || `${props.module.item_label} #${props.item.id}`);

const tabs = computed(() => [
    ...props.module.tabs.map((tab) => ({ label: tab.label, href: route(tab.route, props.item.id), active: false })),
    { label: 'รายงาน', href: route(`${routeBase.value}.report`, props.item.id), active: true },
]);

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: props.module.list_label, href: route(`${routeBase.value}.index`) },
    { label: title.value, href: route(`${routeBase.value}.edit`, props.item.id) },
    { label: 'รายงาน' },
]);

const exportHref = computed(() => route(`${routeBase.value}.report.export`, { item: props.item.id, ...filterQuery(props.filters) }));
</script>

<template>
    <Head :title="`รายงาน${module.item_label}: ${title}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="title" :breadcrumbs="breadcrumbs" />
        </template>

        <TabNav :tabs="tabs" class="mb-6" />

        <div class="space-y-4">
            <!-- ข้อมูลของรายการ (บริบทของรายงาน) -->
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm shadow-xs">
                <span v-if="module.has_category" class="text-gray-500">หมวดหมู่: <span class="text-gray-800">{{ item.category_title ?? '-' }}</span></span>
                <span v-if="item.publish_date" class="text-gray-500">วันที่เผยแพร่: <span class="text-gray-800">{{ formatDateTime(item.publish_date) }}</span></span>
                <span class="text-gray-500">{{ terms.count }}สะสมทั้งหมด: <span class="font-semibold text-gray-900 tabular-nums">{{ formatNumber(item.amount) }}</span></span>
                <StatusBadge :status="item.status" />
            </div>

            <ReportFilterBar
                v-model:chart-type="chartType"
                :filters="filters"
                :route-name="`${routeBase}.report`"
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
                <BackToListButton :href="route(`${routeBase}.index`)" />
            </div>
        </div>
    </AdminLayout>
</template>
