<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';

/**
 * ตั้งค่าป้ายโฆษณา = หน้าล้างแคชของโมดูล (ยังไม่มีฟิลด์ตั้งค่าจริง) — รูปแบบเดียวกับแท็บล้างแคชของตั้งค่าบทความ
 */
const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'ตั้งค่าป้ายโฆษณา' },
];

// รายการแคชของโมดูลป้ายโฆษณา — เพิ่มแคชใหม่ = เพิ่มแถวในนี้ + endpoint ที่ BannerSettingController
const items = [
    { key: 'setting', label: 'ล้างแคช - ตั้งค่าป้ายโฆษณา', route: 'admin.banner.setting.clearcache.setting' },
    { key: 'front', label: 'ล้างแคช - ป้ายโฆษณาที่แสดงหน้าบ้าน', route: 'admin.banner.setting.clearcache.front' },
];

const itemForm = useForm({});

function clearItem(name: string) {
    itemForm.post(route(name), { preserveScroll: true });
}

const allForm = useForm({});

function clearAll() {
    allForm.post(route('admin.banner.setting.clearcache.all'), { preserveScroll: true });
}
</script>

<template>
    <Head title="ล้างแคชโมดูลป้ายโฆษณา" />

    <AdminLayout>
        <template #header>
            <PageHeader title="ตั้งค่าป้ายโฆษณา" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ล้างแคชรายรายการ</h2>
                <p class="mt-1 text-sm text-gray-500">
                    ล้างแคชของโมดูลป้ายโฆษณาทีละรายการ — ระบบจะอ่านค่าล่าสุดจากฐานข้อมูลใหม่ในครั้งถัดไป
                </p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <PrimaryButton
                        v-for="item in items"
                        :key="item.key"
                        type="button"
                        :disabled="itemForm.processing"
                        @click="clearItem(item.route)"
                    >
                        <Trash2 class="mr-1.5 size-4" /> {{ item.label }}
                    </PrimaryButton>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ล้างแคชทั้งหมดของป้ายโฆษณา</h2>
                <p class="mt-1 text-sm text-gray-500">ล้างแคชของโมดูลป้ายโฆษณาทุกรายการในครั้งเดียว</p>

                <div class="mt-5">
                    <DangerButton type="button" :disabled="allForm.processing" @click="clearAll">
                        <Trash2 class="mr-1.5 size-4" /> ล้างแคชทั้งหมดของป้ายโฆษณา
                    </DangerButton>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
