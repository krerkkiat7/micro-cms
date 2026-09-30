<script setup lang="ts">
import type { DashboardContent } from '@/utils/dashboard';
import { formatNumber } from '@/utils/report';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

/** ภาพรวมเนื้อหา — เผยแพร่อยู่ตอนนี้ / ทั้งหมด ต่อโมดูล */
defineProps<{
    items: DashboardContent[];
}>();

function ratio(item: DashboardContent): string {
    return item.total > 0 ? `${(item.published / item.total) * 100}%` : '0%';
}
</script>

<template>
    <ul class="-my-2 divide-y divide-gray-100">
        <li v-for="item in items" :key="item.key">
            <Link :href="item.href" class="group flex items-center gap-4 py-3">
                <span class="w-24 shrink-0 text-sm font-medium text-gray-700 group-hover:text-brand-600">{{ item.label }}</span>
                <div class="hidden h-2 flex-1 rounded-full bg-gray-100 sm:block" aria-hidden="true">
                    <div class="h-2 rounded-full bg-brand-500" :style="{ width: ratio(item) }"></div>
                </div>
                <span class="ml-auto shrink-0 text-right text-sm text-gray-500 sm:ml-0 sm:w-36">
                    <span class="font-semibold text-gray-900 tabular-nums">{{ formatNumber(item.published) }}</span>
                    / {{ formatNumber(item.total) }} เผยแพร่อยู่
                </span>
                <ChevronRight class="size-4 shrink-0 text-gray-300 group-hover:text-brand-500" aria-hidden="true" />
            </Link>
        </li>
    </ul>
</template>
