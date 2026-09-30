<script setup lang="ts">
import AppLogo from '@/Components/AppLogo.vue';

/**
 * layout ของหน้า error หลังบ้าน (Pages/Admin/Error.vue) — ไม่ใช้ AdminLayout (ต้องมีผู้ใช้/เมนู ซึ่งอาจไม่มีตอนเกิด error)
 * โครงเดียวกับ AuthLayout: เนื้อหาซ้าย + แถบ branding โทนมืดขวา (จอ lg)
 */
defineProps<{
    siteName: string;
    homeUrl: string;
    systemLabel: string;
}>();
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <div class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-16">
            <div class="mx-auto w-full max-w-md">
                <a :href="homeUrl" class="mb-10 flex items-center gap-2.5 text-gray-900">
                    <AppLogo class="size-9 shrink-0 stroke-current" />
                    <span class="text-lg font-semibold tracking-tight">{{ siteName }}</span>
                </a>

                <main>
                    <slot />
                </main>
            </div>
        </div>

        <div class="relative hidden w-1/2 overflow-hidden bg-admin-900 lg:block" aria-hidden="true">
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-6 px-16 text-center">
                <AppLogo class="size-16 stroke-current text-white/90" />
                <p class="text-xl font-medium text-white">{{ siteName }}</p>
                <p class="max-w-sm text-sm text-gray-400">{{ systemLabel }}</p>
            </div>
        </div>
    </div>
</template>
