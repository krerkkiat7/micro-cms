<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head } from '@inertiajs/vue3';

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'ตั้งค่าป้ายโฆษณา' },
];
</script>

<template>
    <Head title="ตั้งค่าป้ายโฆษณา" />

    <AdminLayout>
        <template #header>
            <PageHeader title="ตั้งค่าป้ายโฆษณา" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <p class="text-sm text-gray-500">ยังไม่มีการตั้งค่าสำหรับโมดูลนี้ในขณะนี้</p>
        </div>
    </AdminLayout>
</template>
