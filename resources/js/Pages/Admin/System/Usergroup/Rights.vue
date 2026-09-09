<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    group: { id: number; name: string };
}>();

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการกลุ่มผู้ใช้งาน', href: route('admin.system.usergroup.index') },
    { label: props.group.name, href: route('admin.system.usergroup.edit', props.group.id) },
    { label: 'กำหนดสิทธิ์' },
]);

const tabs = computed(() => [
    {
        label: 'ข้อมูลทั่วไป',
        href: route('admin.system.usergroup.edit', props.group.id),
        active: false,
    },
    {
        label: 'กำหนดสิทธิ์',
        href: route('admin.system.usergroup.rights', props.group.id),
        active: true,
    },
]);
</script>

<template>
    <Head :title="`กำหนดสิทธิ์: ${group.name}`" />

    <AdminLayout>
        <template #header>
            <PageHeader title="กำหนดสิทธิ์" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <TabNav :tabs="tabs" />

            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
            >
                <p class="text-sm text-gray-500">
                    หน้ากำหนดสิทธิ์ของกลุ่ม "{{ group.name }}" — จะพัฒนาต่อภายหลัง
                </p>
            </div>
        </div>
    </AdminLayout>
</template>
