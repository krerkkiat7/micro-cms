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

// ปุ่มล้างแคชของกลุ่มตั้งค่าระบบเอง (self::OWN_GROUPS ฝั่ง backend) — แยกกล่องจากกลุ่มของโมดูลอื่น
const systemGroups = [
    { group: 'site', label: 'ล้างแคช - ข้อมูลระบบ' },
    { group: 'contact', label: 'ล้างแคช - ข้อมูลติดต่อ' },
    { group: 'social', label: 'ล้างแคช - Social Media' },
    { group: 'google_analytics', label: 'ล้างแคช - Google Analytics' },
    { group: 'smtp', label: 'ล้างแคช - SMTP' },
    { group: 'turnstile', label: 'ล้างแคช - Turnstile' },
    { group: 'login_back', label: 'ล้างแคช - การเข้าสู่ระบบหลังบ้าน' },
];

// ปุ่มล้างแคชของกลุ่มโมดูลบทความ — group ต้องตรงกับ App\Support\Setting::GROUPS ฝั่ง backend เช่นกัน
// (มาลงทะเบียนร่วมทะเบียนเดียวกันกับกลุ่มตั้งค่าระบบ แต่แยกกล่องแสดงผลเพราะเป็นของคนละโมดูล)
const articleGroups = [{ group: 'article', label: 'ล้างแคช - ตั้งค่า' }];

const groupForm = useForm({});

function clearGroup(group: string) {
    groupForm.post(route('admin.system.setting.clearcache.group', group), { preserveScroll: true });
}

const allForm = useForm({});

function clearAll() {
    allForm.post(route('admin.system.setting.clearcache.all'), { preserveScroll: true });
}

const filesForm = useForm({});

function clearFiles() {
    filesForm.post(route('admin.system.setting.clearcache.files'), { preserveScroll: true });
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
                <h2 class="text-base font-semibold text-gray-800">ล้างแคชตั้งค่าระบบ</h2>
                <p class="mt-1 text-sm text-gray-500">
                    ล้างแคชของค่าตั้งค่าระบบแต่ละกลุ่ม — ระบบจะอ่านค่าล่าสุดจากฐานข้อมูลใหม่ในครั้งถัดไป
                </p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <PrimaryButton
                        v-for="item in systemGroups"
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
                <h2 class="text-base font-semibold text-gray-800">ล้างแคชบทความ</h2>
                <p class="mt-1 text-sm text-gray-500">
                    ล้างแคชของโมดูลบทความแต่ละรายการ — ระบบจะอ่านค่าล่าสุดจากฐานข้อมูลใหม่ในครั้งถัดไป
                </p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <PrimaryButton
                        v-for="item in articleGroups"
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
                <h2 class="text-base font-semibold text-gray-800">ล้างแคชไฟล์</h2>
                <p class="mt-1 text-sm text-gray-500">
                    ล้างแคชผลการค้นหาไฟล์ในโมดูลจัดการไฟล์ — ใช้เมื่อลิงก์ไฟล์/รูปภาพแสดงผลไม่ตรงกับข้อมูลล่าสุด
                    (การล้างนี้จะล้างแคชของระบบทั้งหมดไปด้วย เพราะ cache driver ปัจจุบันไม่รองรับการล้างแยกกลุ่ม)
                </p>

                <div class="mt-5">
                    <PrimaryButton type="button" :disabled="filesForm.processing" @click="clearFiles">
                        <Trash2 class="mr-1.5 size-4" /> ล้าง Cache ไฟล์
                    </PrimaryButton>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ล้างแคชทั้งหมด</h2>
                <p class="mt-1 text-sm text-gray-500">
                    ล้างแคชของค่าตั้งค่าทุกกลุ่มในครั้งเดียว รวมถึงตั้งค่าของโมดูลอื่น (เช่น บทความ) ด้วย
                </p>

                <div class="mt-5">
                    <DangerButton type="button" :disabled="allForm.processing" @click="clearAll">
                        <Trash2 class="mr-1.5 size-4" /> ล้างแคชทั้งหมด
                    </DangerButton>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
