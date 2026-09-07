<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="โปรไฟล์" />

    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">โปรไฟล์</h1>
            <p class="mt-1 text-sm text-gray-500">จัดการข้อมูลบัญชีและรหัสผ่านของคุณ</p>
        </template>

        <div class="max-w-3xl space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                />
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <UpdatePasswordForm />
            </div>

            <div class="rounded-2xl border border-red-200 bg-white p-6 shadow-xs lg:p-8">
                <DeleteUserForm />
            </div>
        </div>
    </AdminLayout>
</template>
