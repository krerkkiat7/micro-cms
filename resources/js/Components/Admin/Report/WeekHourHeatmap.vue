<script setup lang="ts">
import { WEEKDAYS, formatNumber } from '@/utils/report';
import { computed } from 'vue';

/**
 * heatmap ยอดเข้าชมตามวันในสัปดาห์ × ชั่วโมง (สีเดียว อ่อน→เข้ม ตามจำนวน) — วางเมาส์ดูตัวเลขได้ทุกช่อง
 */
const props = defineProps<{
    grid: number[][];
}>();

// ramp สีน้ำเงิน brand (อ่อน → เข้ม) — ช่องที่เป็น 0 ใช้เทาอ่อน
const RAMP = ['#dde9ff', '#c2d6ff', '#9cb9ff', '#7592ff', '#465fff', '#3641f5', '#2a31d8', '#252dae'];

const max = computed(() => Math.max(0, ...props.grid.flat()));

function cellColor(value: number): string {
    if (value <= 0 || max.value === 0) return '#f3f4f6';
    const index = Math.min(RAMP.length - 1, Math.floor((value / max.value) * (RAMP.length - 1) + 0.0001));
    return RAMP[Math.max(0, index)];
}

const hours = Array.from({ length: 24 }, (_, i) => i);
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] border-separate border-spacing-0.5 text-xs">
            <thead>
                <tr>
                    <th class="w-20" />
                    <th v-for="h in hours" :key="h" class="pb-1 text-center font-normal text-gray-500">
                        {{ h % 3 === 0 ? String(h).padStart(2, '0') : '' }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, day) in grid" :key="day">
                    <th class="pr-2 text-left font-normal whitespace-nowrap text-gray-600">{{ WEEKDAYS[day] }}</th>
                    <td
                        v-for="h in hours"
                        :key="h"
                        class="h-7 rounded-sm"
                        :style="{ backgroundColor: cellColor(row[h] ?? 0) }"
                        :title="`${WEEKDAYS[day]} ${String(h).padStart(2, '0')}:00–${String(h).padStart(2, '0')}:59 · ${formatNumber(row[h] ?? 0)} ครั้ง`"
                    />
                </tr>
            </tbody>
        </table>
        <div class="mt-3 flex items-center justify-end gap-2 text-xs text-gray-500">
            <span>น้อย</span>
            <span class="flex gap-0.5">
                <span class="size-3 rounded-sm bg-gray-100" />
                <span v-for="c in RAMP" :key="c" class="size-3 rounded-sm" :style="{ backgroundColor: c }" />
            </span>
            <span>มาก ({{ formatNumber(max) }})</span>
        </div>
    </div>
</template>
