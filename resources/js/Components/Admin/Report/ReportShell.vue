<script setup lang="ts">
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import { provideReportTerms, reportTabs } from '@/utils/report';
import type { ReportFilters, ReportModule } from '@/utils/report';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * โครงหน้าของเมนูรายงานของโมดูล (บทความ / หน้าเพจ / ป้ายโฆษณา) — header + breadcrumb + แท็บ
 * ส่งตัวกรองช่วงวันที่ต่อระหว่างแท็บรายงาน และ provide คำ (เข้าชม/คลิก) ให้ component รายงานข้างใต้
 */
const props = defineProps<{
    module: ReportModule;
    tab: string;
    filters?: ReportFilters;
}>();

provideReportTerms(props.module.metric, props.module.item_label);

const tabs = computed(() => reportTabs(props.module, props.tab, props.filters));
const tabTitle = computed(() => tabs.value.find((t) => t.key === props.tab)?.label ?? '');

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: props.module.title, href: route(`${props.module.route_prefix}.index`) },
    { label: tabTitle.value },
]);
</script>

<template>
    <Head :title="`${module.title} - ${tabTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="module.title" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="mb-6 overflow-x-auto">
            <TabNav :tabs="tabs" class="min-w-max" />
        </div>

        <div class="space-y-4">
            <slot />
        </div>
    </AdminLayout>
</template>
