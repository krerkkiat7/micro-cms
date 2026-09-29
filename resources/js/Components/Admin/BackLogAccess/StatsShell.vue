<script setup lang="ts">
import PageHeader from '@/Components/Admin/PageHeader.vue';
import ReportFilterBar from '@/Components/Admin/Report/ReportFilterBar.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import { filterQuery, provideReportTerms } from '@/utils/report';
import type { CategoryOption, ChartType, ReportFilters } from '@/utils/report';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * โครงหน้าสถิติของประวัติการใช้งานหลังบ้าน — header + แท็บ (รายการ + สถิติ) + แถบตัวกรอง (ช่วงวันที่ / ช่วงเวลา / ผู้ใช้งาน)
 * provide คำชุด "การเข้าหน้าจอ" ให้ component รายงานกลาง (Components/Admin/Report/*) ข้างใต้
 */
const props = defineProps<{
    tab: string;
    filters?: ReportFilters;
    userOptions?: CategoryOption[];
    showPeriod?: boolean;
    showChartType?: boolean;
}>();

const chartType = defineModel<ChartType>('chartType', { default: 'bar' });

provideReportTerms('access', 'ผู้ใช้งาน');

const BACKLOG_STATS_TABS: { key: string; label: string }[] = [
    { key: 'index', label: 'รายการ' },
    { key: 'overview', label: 'ภาพรวม' },
    { key: 'user', label: 'ผู้ใช้งาน' },
    { key: 'page', label: 'หน้าจอ' },
    { key: 'device', label: 'อุปกรณ์และเครือข่าย' },
    { key: 'time', label: 'ช่วงเวลา' },
];

// แท็บสถิติส่งตัวกรองต่อกัน (ช่วงวันที่ + ผู้ใช้งาน) — แท็บรายการใช้ตัวกรองของตัวเอง
const tabs = computed(() =>
    BACKLOG_STATS_TABS.map((t) => ({
        key: t.key,
        label: t.label,
        href: route(`admin.system.backlog.access.${t.key}`, t.key !== 'index' && props.filters ? filterQuery(props.filters) : {}),
        active: t.key === props.tab,
    })),
);

const tabTitle = computed(() => BACKLOG_STATS_TABS.find((t) => t.key === props.tab)?.label ?? '');

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'ประวัติการใช้งานหลังบ้าน', href: route('admin.system.backlog.access.index') },
    { label: props.tab === 'index' ? 'รายการ' : `สถิติ - ${tabTitle.value}` },
]);

const exportHref = computed(() =>
    props.filters ? route('admin.system.backlog.access.export', { tab: props.tab, ...filterQuery(props.filters) }) : undefined,
);
</script>

<template>
    <Head :title="`ประวัติการใช้งานหลังบ้าน - ${tabTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader title="ประวัติการใช้งานหลังบ้าน" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="mb-6 overflow-x-auto">
            <TabNav :tabs="tabs" class="min-w-max" />
        </div>

        <div class="space-y-4">
            <ReportFilterBar
                v-if="filters"
                v-model:chart-type="chartType"
                :filters="filters"
                :route-name="`admin.system.backlog.access.${tab}`"
                :select="userOptions ? { param: 'user_id', label: 'ผู้ใช้งาน', allLabel: 'ผู้ใช้งานทั้งหมด', options: userOptions } : undefined"
                :export-href="exportHref"
                :show-period="showPeriod"
                :show-chart-type="showChartType"
            />

            <slot />
        </div>
    </AdminLayout>
</template>
