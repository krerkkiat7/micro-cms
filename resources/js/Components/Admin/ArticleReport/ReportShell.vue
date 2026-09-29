<script setup lang="ts">
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import { articleReportTabs } from '@/utils/report';
import type { ReportFilters } from '@/utils/report';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * โครงหน้าของเมนูรายงานบทความ — header + breadcrumb + แท็บ (ส่งตัวกรองช่วงวันที่ต่อระหว่างแท็บรายงาน)
 */
const props = defineProps<{
    tab: string;
    tabTitle: string;
    filters?: ReportFilters;
}>();

const tabs = computed(() => articleReportTabs(props.tab, props.filters));

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'รายงานบทความ', href: route('admin.article.report.index') },
    { label: props.tabTitle },
]);
</script>

<template>
    <Head :title="`รายงานบทความ - ${tabTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader title="รายงานบทความ" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="mb-6 overflow-x-auto">
            <TabNav :tabs="tabs" class="min-w-max" />
        </div>

        <div class="space-y-4">
            <slot />
        </div>
    </AdminLayout>
</template>
