<script setup lang="ts">
import { ChevronRight } from 'lucide-vue-next';
import FrontLink from '@/Components/Front/FrontLink.vue';
import { useFront } from '@/composables/useFront';
import type { Crumb } from '@/utils/front';

/**
 * breadcrumb ของหน้าบ้าน (WAI-ARIA breadcrumb pattern) — <nav aria-label> + <ol>, รายการสุดท้ายคือหน้าปัจจุบัน (aria-current, ไม่มีลิงก์)
 * ข้อมูลเดียวกันถูกส่งเป็น JSON-LD BreadcrumbList ใน <head> ด้วย (SeoMeta)
 */
defineProps<{ items: Crumb[] }>();

const { t } = useFront();
</script>

<template>
    <nav :aria-label="t('breadcrumb')" class="text-sm">
        <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-gray-600">
            <li v-for="(item, index) in items" :key="index" class="inline-flex items-center gap-1.5">
                <ChevronRight v-if="index > 0" class="size-3.5 shrink-0 text-gray-400" aria-hidden="true" />
                <FrontLink v-if="item.url && index < items.length - 1" :href="item.url" class="text-brand-700 underline-offset-2 hover:underline">{{ item.name }}</FrontLink>
                <span v-else :aria-current="index === items.length - 1 ? 'page' : undefined" class="font-medium text-gray-800">{{ item.name }}</span>
            </li>
        </ol>
    </nav>
</template>
