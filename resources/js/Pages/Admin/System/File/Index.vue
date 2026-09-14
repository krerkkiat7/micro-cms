<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import FolderList from '@/Components/Admin/FileManager/FolderList.vue';
import FileUploadDropzone from '@/Components/Admin/FileManager/FileUploadDropzone.vue';
import FileBrowser from '@/Components/Admin/FileManager/FileBrowser.vue';

const breadcrumbs = [{ label: 'Dashboard', href: route('admin.dashboard') }, { label: 'จัดการไฟล์' }];

// null = โฟลเดอร์ราก ("ไม่มีโฟลเดอร์") — ค่าเริ่มต้นนี้เองที่ทำให้เข้าหน้าครั้งแรกแล้วเลือกโฟลเดอร์เปล่าให้อัตโนมัติ
const selectedFolderId = ref<number | null>(null);
const browser = ref<InstanceType<typeof FileBrowser> | null>(null);

function onUploaded() {
    browser.value?.reload();
}
</script>

<template>
    <Head title="จัดการไฟล์" />

    <AdminLayout>
        <template #header>
            <PageHeader title="จัดการไฟล์" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[260px_1fr]">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs lg:h-[calc(100vh-220px)]">
                <FolderList v-model="selectedFolderId" />
            </div>

            <div class="min-w-0 rounded-2xl border border-gray-200 bg-white p-6 shadow-xs">
                <FileUploadDropzone :folder-id="selectedFolderId" @uploaded="onUploaded" />

                <div class="mt-6 border-t border-gray-100 pt-6">
                    <FileBrowser ref="browser" :folder-id="selectedFolderId" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
