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
    { label: 'ตั้งค่าบทความ', href: route('admin.article.setting.index') },
    { label: 'ล้างแคช' },
]);

const tabs = computed(() => [
    { label: 'ตั้งค่า', href: route('admin.article.setting.index'), active: false },
    { label: 'ล้างแคช', href: route('admin.article.setting.clearcache'), active: true },
]);

// รายการแคชของโมดูลบทความ — ตอนนี้มีแค่ "ตั้งค่า" อนาคตถ้าเพิ่มแคชอื่น (เช่น รายละเอียดบทความ/รายการบทความ
// ที่ใช้ในหน้าบ้าน) ให้เพิ่มแถวในนี้ + endpoint ล้างแคชของตัวเองที่ ArticleSettingController
const items = [{ key: 'setting', label: 'ล้างแคช - ตั้งค่าบทความ' }];

const itemForm = useForm({});

function clearItem() {
    itemForm.post(route('admin.article.setting.clearcache.setting'), { preserveScroll: true });
}

const allForm = useForm({});

function clearAll() {
    allForm.post(route('admin.article.setting.clearcache.all'), { preserveScroll: true });
}
</script>

<template>
    <Head title="ล้างแคชโมดูลบทความ" />

    <AdminLayout>
        <template #header>
            <PageHeader title="ล้างแคช" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <TabNav :tabs="tabs" />

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ล้างแคชรายรายการ</h2>
                <p class="mt-1 text-sm text-gray-500">
                    ล้างแคชของโมดูลบทความทีละรายการ — ระบบจะอ่านค่าล่าสุดจากฐานข้อมูลใหม่ในครั้งถัดไป
                </p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <PrimaryButton
                        v-for="item in items"
                        :key="item.key"
                        type="button"
                        :disabled="itemForm.processing"
                        @click="clearItem"
                    >
                        <Trash2 class="mr-1.5 size-4" /> {{ item.label }}
                    </PrimaryButton>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ล้างแคชทั้งหมดของบทความ</h2>
                <p class="mt-1 text-sm text-gray-500">ล้างแคชของโมดูลบทความทุกรายการในครั้งเดียว</p>

                <div class="mt-5">
                    <DangerButton type="button" :disabled="allForm.processing" @click="clearAll">
                        <Trash2 class="mr-1.5 size-4" /> ล้างแคชทั้งหมดของบทความ
                    </DangerButton>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
