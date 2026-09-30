<script setup lang="ts">
import AppLogo from '@/Components/AppLogo.vue';

/**
 * layout ของหน้า error หลังบ้าน (Pages/Admin/Error.vue) — พื้นหลังเทาเข้มโทนเดียวกับ sidebar/header หลังบ้าน (`admin-900`)
 * ให้ต่างจากหน้า error หน้าบ้าน (พื้นสว่าง) ชัดเจน; ไม่ใช้ AdminLayout (ต้องมีผู้ใช้/เมนู ซึ่งอาจไม่มีตอนเกิด error)
 */
defineProps<{
    siteName: string;
    homeUrl: string;
    systemLabel: string;
}>();
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-admin-900 px-4 py-12 text-gray-300">
        <a :href="homeUrl" class="mb-8 flex items-center gap-3 text-white">
            <AppLogo class="size-10 shrink-0 stroke-current text-white/90" />
            <span class="flex flex-col leading-tight">
                <span class="text-lg font-semibold tracking-tight">{{ siteName }}</span>
                <span class="text-xs text-gray-400">{{ systemLabel }}</span>
            </span>
        </a>

        <main class="w-full max-w-lg rounded-2xl border border-admin-border bg-admin-800 px-6 py-10 shadow-xl sm:px-10">
            <slot />
        </main>
    </div>
</template>
