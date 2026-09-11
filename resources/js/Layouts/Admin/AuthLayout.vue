<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- ฝั่งฟอร์ม -->
        <div class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-16">
            <div class="mx-auto w-full max-w-md">
                <Link href="/" class="mb-8 flex items-center gap-2.5 text-gray-900">
                    <ApplicationLogo class="size-9 shrink-0 stroke-current" />
                    <span class="text-lg font-semibold tracking-tight">{{ page.props.siteName }}</span>
                </Link>

                <h1 v-if="title" class="text-2xl font-semibold text-gray-800">{{ title }}</h1>
                <p v-if="description" class="mt-1.5 text-sm text-gray-500">{{ description }}</p>

                <div class="mt-8">
                    <slot />
                </div>
            </div>
        </div>

        <!-- ฝั่ง branding (จอใหญ่) -->
        <div class="relative hidden w-1/2 overflow-hidden bg-admin-900 lg:block">
            <div
                class="absolute inset-0 flex flex-col items-center justify-center gap-6 px-16 text-center"
            >
                <ApplicationLogo class="size-16 stroke-current text-white/90" />
                <p class="text-xl font-medium text-white">ระบบจัดการเนื้อหา</p>
                <p class="max-w-sm text-sm text-gray-400">
                    เข้าสู่ระบบเพื่อจัดการเนื้อหาและตั้งค่าเว็บไซต์ของคุณ
                </p>
            </div>
        </div>
    </div>
</template>
