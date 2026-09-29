<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'ตั้งค่าติดต่อเรา', href: route('admin.contactus.setting.index') },
    { label: 'ล้างแคช' },
]);

const tabs = computed(() => [
    { label: 'ตั้งค่า', href: route('admin.contactus.setting.index'), active: false },
    { label: 'ล้างแคช', href: route('admin.contactus.setting.clearcache'), active: true },
]);

// รายการแคชของโมดูลติดต่อเรา — เพิ่มแคชใหม่ให้เพิ่มแถวในนี้ + endpoint ที่ ContactusSettingController
const items = [
    { key: 'setting', label: 'ล้างแคช - ตั้งค่าติดต่อเรา', route: 'admin.contactus.setting.clearcache.setting' },
    { key: 'front', label: 'ล้างแคช - หน้าติดต่อเราที่แสดงหน้าบ้าน', route: 'admin.contactus.setting.clearcache.front' },
];

const itemForm = useForm({});

function clearItem(routeName: string) {
    itemForm.post(route(routeName), { preserveScroll: true });
}

const allForm = useForm({});

function clearAll() {
    allForm.post(route('admin.contactus.setting.clearcache.all'), { preserveScroll: true });
}
</script>

<template>
    <Head title="ล้างแคชโมดูลติดต่อเรา" />

    <AdminLayout>
        <template #header>
            <PageHeader title="ล้างแคช" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <TabNav :tabs="tabs" />

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ล้างแคชรายรายการ</h2>
                <p class="mt-1 text-sm text-gray-500">ล้างแคชของโมดูลติดต่อเราทีละรายการ — ระบบจะอ่านค่าล่าสุดจากฐานข้อมูลใหม่ในครั้งถัดไป</p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <PrimaryButton v-for="item in items" :key="item.key" type="button" :disabled="itemForm.processing" @click="clearItem(item.route)">
                        <Trash2 class="mr-1.5 size-4" /> {{ item.label }}
                    </PrimaryButton>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ล้างแคชทั้งหมดของติดต่อเรา</h2>
                <p class="mt-1 text-sm text-gray-500">ล้างแคชของโมดูลติดต่อเราทุกรายการในครั้งเดียว</p>

                <div class="mt-5">
                    <DangerButton type="button" :disabled="allForm.processing" @click="clearAll">
                        <Trash2 class="mr-1.5 size-4" /> ล้างแคชทั้งหมดของติดต่อเรา
                    </DangerButton>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
