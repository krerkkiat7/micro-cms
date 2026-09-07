<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { FileText, ShieldCheck, Users } from 'lucide-vue-next';

const props = defineProps<{
    can: {
        articleCreate: boolean;
        systemUserView: boolean;
    };
}>();

const stats = [
    { label: 'บทความทั้งหมด', value: '—', icon: FileText, hint: 'ยังไม่มีโมดูลเนื้อหา' },
    { label: 'ผู้ใช้งาน', value: '—', icon: Users, hint: 'ยังไม่มีโมดูลผู้ใช้' },
    { label: 'สิทธิ์ของคุณ', value: [props.can.articleCreate, props.can.systemUserView].filter(Boolean).length, icon: ShieldCheck, hint: 'จากที่ตรวจในหน้านี้' },
];
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">ยินดีต้อนรับเข้าสู่ระบบจัดการ</p>
        </template>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-500"
                    >
                        <component :is="stat.icon" class="size-5" />
                    </span>
                </div>
                <p class="mt-4 text-2xl font-semibold text-gray-800">{{ stat.value }}</p>
                <p class="text-sm font-medium text-gray-600">{{ stat.label }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ stat.hint }}</p>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-xs">
            <h2 class="text-base font-semibold text-gray-800">สถานะสิทธิ์การใช้งาน</h2>
            <ul class="mt-4 space-y-2 text-sm">
                <li class="flex items-center gap-2">
                    <span
                        class="inline-block size-2 rounded-full"
                        :class="can.articleCreate ? 'bg-emerald-500' : 'bg-gray-300'"
                    ></span>
                    <code class="text-gray-600">article.create</code>
                    <span class="text-gray-400">— {{ can.articleCreate ? 'มีสิทธิ์' : 'ไม่มีสิทธิ์' }}</span>
                </li>
                <li class="flex items-center gap-2">
                    <span
                        class="inline-block size-2 rounded-full"
                        :class="can.systemUserView ? 'bg-emerald-500' : 'bg-gray-300'"
                    ></span>
                    <code class="text-gray-600">system.user.view</code>
                    <span class="text-gray-400">— {{ can.systemUserView ? 'มีสิทธิ์' : 'ไม่มีสิทธิ์' }}</span>
                </li>
            </ul>
        </div>
    </AdminLayout>
</template>
