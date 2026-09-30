<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

/** เปลือกการ์ดของ Dashboard — หัวข้อ + ลิงก์ "ดูทั้งหมด" (ถ้ามี) */
defineProps<{
    title: string;
    subtitle?: string;
    href?: string;
    linkLabel?: string;
}>();
</script>

<template>
    <section class="flex flex-col rounded-2xl border border-gray-200 bg-white shadow-xs">
        <header class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-gray-800">{{ title }}</h2>
                <p v-if="subtitle" class="mt-0.5 text-xs text-gray-500">{{ subtitle }}</p>
            </div>
            <Link
                v-if="href"
                :href="href"
                class="inline-flex shrink-0 items-center gap-0.5 text-sm font-medium text-brand-600 hover:text-brand-700"
            >
                {{ linkLabel ?? 'ดูทั้งหมด' }}
                <ChevronRight class="size-4" aria-hidden="true" />
            </Link>
        </header>
        <div class="flex-1 p-5">
            <slot />
        </div>
    </section>
</template>
