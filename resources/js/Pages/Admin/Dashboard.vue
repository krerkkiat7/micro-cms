<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/Admin/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

// const page = usePage();

// // ดึง Array สิทธิ์ของผู้ใช้
// const permissions = computed(() => page.props.auth.user?.permissions || []);

// // ฟังก์ชันสำหรับเช็กว่ามีสิทธิ์นี้ใน Array หรือไม่ (เหมือนระบบเดิมของคุณ)
// const can = (actionCode) => permissions.value.includes(actionCode);

// รับ props ที่ส่งมาจาก Controller ตรงๆ
const props = defineProps<{
    can: {
        articleCreate: boolean;
        systemUserView: boolean;
    };
}>();
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        You're logged in!
                    </div>
                    <div class="p-6 text-gray-900" v-if="can.articleCreate">
                        article.create
                    </div>
                    <div class="p-6 text-gray-900" v-if="can.systemUserView">
                        system.user.view
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
