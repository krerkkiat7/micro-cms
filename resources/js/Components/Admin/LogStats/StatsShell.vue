<script setup lang="ts">
import PageHeader from '@/Components/Admin/PageHeader.vue';
import ReportFilterBar from '@/Components/Admin/Report/ReportFilterBar.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import { LOG_STATS } from '@/utils/logStats';
import type { LogStatsKey } from '@/utils/logStats';
import { filterQuery, provideReportTerms } from '@/utils/report';
import type { CategoryOption, ChartType, ReportFilters } from '@/utils/report';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * โครงหน้าของประวัติ (หลังบ้าน/หน้าบ้าน) ที่มีแท็บสถิติ — header + แท็บ (รายการ + สถิติ ตาม LOG_STATS[log]) + แถบตัวกรอง
 * (ช่วงวันที่ / ช่วงเวลา / ผู้ใช้งาน ถ้าส่ง userOptions) และ provide คำตาม metric ให้ component รายงานกลางข้างใต้
 * แท็บ index = หน้ารายการเดิม (ไม่ส่ง filters → ไม่มีแถบตัวกรองสถิติ)
 */
const props = defineProps<{
    log: LogStatsKey;
    tab: string;
    filters?: ReportFilters;
    userOptions?: CategoryOption[] | null;
    showPeriod?: boolean;
    showChartType?: boolean;
}>();

const chartType = defineModel<ChartType>('chartType', { default: 'bar' });

const config = computed(() => LOG_STATS[props.log]);

provideReportTerms(LOG_STATS[props.log].metric, LOG_STATS[props.log].itemLabel);

// แท็บสถิติส่งตัวกรองต่อกัน (ช่วงวันที่ + ผู้ใช้งาน) — แท็บรายการใช้ตัวกรองของตัวเอง
const tabs = computed(() =>
    config.value.tabs.map((t) => ({
        key: t.key,
        label: t.label,
        href: route(`${config.value.routePrefix}.${t.key}`, t.key !== 'index' && props.filters ? filterQuery(props.filters) : {}),
        active: t.key === props.tab,
    })),
);

const tabTitle = computed(() => config.value.tabs.find((t) => t.key === props.tab)?.label ?? '');

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: config.value.title, href: route(`${config.value.routePrefix}.index`) },
    { label: props.tab === 'index' ? 'รายการ' : `สถิติ - ${tabTitle.value}` },
]);

const exportHref = computed(() =>
    props.filters ? route(`${config.value.routePrefix}.export`, { tab: props.tab, ...filterQuery(props.filters) }) : undefined,
);
</script>

<template>
    <Head :title="`${config.title} - ${tabTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="config.title" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="mb-6 overflow-x-auto">
            <TabNav :tabs="tabs" class="min-w-max" />
        </div>

        <div class="space-y-4">
            <ReportFilterBar
                v-if="filters"
                v-model:chart-type="chartType"
                :filters="filters"
                :route-name="`${config.routePrefix}.${tab}`"
                :select="userOptions ? { param: 'user_id', label: 'ผู้ใช้งาน', allLabel: 'ผู้ใช้งานทั้งหมด', options: userOptions } : undefined"
                :export-href="exportHref"
                :show-period="showPeriod"
                :show-chart-type="showChartType"
            />

            <slot />
        </div>
    </AdminLayout>
</template>
