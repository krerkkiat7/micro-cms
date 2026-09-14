<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import type { FileItem } from '@/types';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
    profileImage: FileItem | null;
}>();

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'โปรไฟล์' },
];
</script>

<template>
    <Head title="โปรไฟล์" />

    <AdminLayout>
        <template #header>
            <PageHeader title="โปรไฟล์" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    :profile-image="profileImage"
                />
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <UpdatePasswordForm />
            </div>
        </div>
    </AdminLayout>
</template>
