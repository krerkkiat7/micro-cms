<script setup lang="ts">
import AppLogo from '@/Components/AppLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';

/**
 * layout หน้า auth หลังบ้าน (Login/Register/Forgot/Reset/Confirm/VerifyEmail) — การ์ดกลางจอบนพื้นเทาอ่อน
 * โลโก้ + ชื่อระบบอยู่เหนือการ์ด
 */
defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12">
        <Link href="/admin" class="mb-6 flex flex-col items-center gap-3 text-gray-900">
            <AppLogo class="size-12 shrink-0 stroke-current" />
            <span class="text-lg font-semibold tracking-tight">{{ page.props.siteName }}</span>
        </Link>

        <main class="w-full max-w-md rounded-2xl border border-gray-200 bg-white px-6 py-8 shadow-sm sm:px-8">
            <h1 v-if="title" class="text-2xl font-semibold text-gray-800">{{ title }}</h1>
            <p v-if="description" class="mt-1.5 text-sm text-gray-500">{{ description }}</p>

            <div :class="title || description ? 'mt-8' : ''">
                <slot />
            </div>
        </main>

        <p class="mt-6 text-xs text-gray-400">ระบบจัดการเนื้อหา</p>
    </div>
</template>
