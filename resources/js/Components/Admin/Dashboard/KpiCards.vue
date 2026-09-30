<script setup lang="ts">
import { METRIC_COLORS } from '@/utils/dashboard';
import type { DashboardKpi } from '@/utils/dashboard';
import { formatDecimal, formatNumber } from '@/utils/report';
import { Link } from '@inertiajs/vue3';
import { ArrowDownRight, ArrowUpRight, Minus } from 'lucide-vue-next';

/** การ์ดตัวเลข 7 วันล่าสุด + % เปลี่ยนแปลงจาก 7 วันก่อนหน้า (รูปแบบเดียวกับ Report/ReportStatCards.vue) */
defineProps<{
    items: DashboardKpi[];
}>();

function changeIcon(value: number | null) {
    if (value === null || value === 0) return Minus;
    return value > 0 ? ArrowUpRight : ArrowDownRight;
}

function changeClass(value: number | null): string {
    if (value === null || value === 0) return 'text-gray-500';
    return value > 0 ? 'text-emerald-700' : 'text-red-700';
}

function changeText(value: number | null): string {
    if (value === null) return '7 วันก่อนหน้าไม่มีข้อมูล';
    return `${value > 0 ? '+' : ''}${formatDecimal(value)}%`;
}
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2" :class="items.length >= 5 ? 'xl:grid-cols-5' : items.length === 4 ? 'xl:grid-cols-4' : 'xl:grid-cols-3'">
        <Link
            v-for="item in items"
            :key="item.key"
            :href="item.href"
            class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs transition hover:border-brand-300"
        >
            <p class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <span class="inline-block size-2 rounded-full" :style="{ backgroundColor: METRIC_COLORS[item.key] ?? '#9ca3af' }" aria-hidden="true"></span>
                {{ item.label }}
            </p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ formatNumber(item.value) }}</p>
            <p class="mt-1 flex flex-wrap items-center gap-x-2 text-xs">
                <span class="inline-flex items-center gap-0.5" :class="changeClass(item.change)">
                    <component :is="changeIcon(item.change)" class="size-3.5" aria-hidden="true" />
                    {{ changeText(item.change) }}
                </span>
                <span v-if="item.hint" class="text-gray-400">· {{ item.hint }}</span>
            </p>
        </Link>
    </div>
</template>
