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
    { label: 'ตั้งค่าระบบ', href: route('admin.system.setting.index') },
    { label: 'ล้างแคช' },
]);

const tabs = computed(() => [
    { label: 'ตั้งค่าระบบ', href: route('admin.system.setting.index'), active: false },
    { label: 'ล้างแคช', href: route('admin.system.setting.clearcache'), active: true },
]);

// รายการปุ่มล้างแคชรายกลุ่ม — group ต้องตรงกับ App\Support\Setting::GROUPS ฝั่ง backend
const groups = [
    { group: 'site', label: 'ล้างแคช - ตั้งค่าระบบ : ข้อมูลระบบ' },
    { group: 'smtp', label: 'ล้างแคช - ตั้งค่าระบบ : SMTP' },
    { group: 'turnstile', label: 'ล้างแคช - ตั้งค่าระบบ : Turnstile' },
    { group: 'login_back', label: 'ล้างแคช - ตั้งค่าระบบ : การเข้าสู่ระบบหลังบ้าน' },
];

const groupForm = useForm({});

function clearGroup(group: string) {
    groupForm.post(route('admin.system.setting.clearcache.group', group), { preserveScroll: true });
}

const allForm = useForm({});

function clearAll() {
    allForm.post(route('admin.system.setting.clearcache.all'), { preserveScroll: true });
}
</script>

<template>
    <Head title="ล้างแคช" />

    <AdminLayout>
        <template #header>
            <PageHeader title="ล้างแคช" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <TabNav :tabs="tabs" />

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ล้างแคชรายกลุ่ม</h2>
                <p class="mt-1 text-sm text-gray-500">
                    ล้างแคชของค่าตั้งค่าระบบแต่ละกลุ่ม — ระบบจะอ่านค่าล่าสุดจากฐานข้อมูลใหม่ในครั้งถัดไป
                </p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <PrimaryButton
                        v-for="item in groups"
                        :key="item.group"
                        type="button"
                        :disabled="groupForm.processing"
                        @click="clearGroup(item.group)"
                    >
                        <Trash2 class="mr-1.5 size-4" /> {{ item.label }}
                    </PrimaryButton>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ล้างแคชทั้งหมด</h2>
                <p class="mt-1 text-sm text-gray-500">ล้างแคชของค่าตั้งค่าระบบทุกกลุ่มในครั้งเดียว</p>

                <div class="mt-5">
                    <DangerButton type="button" :disabled="allForm.processing" @click="clearAll">
                        <Trash2 class="mr-1.5 size-4" /> ล้างแคชทั้งหมด
                    </DangerButton>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
