<script setup lang="ts">
/**
 * การ์ดตัวเลขแบบกำหนดเอง (label / ค่า / คำอธิบาย) — tone ใช้ไฮไลต์ค่าที่ควรระวัง (สีข้อความ + ไอคอนอยู่ในคำอธิบาย ไม่พึ่งสีอย่างเดียว)
 */
defineProps<{
    items: { label: string; value: string; hint?: string; tone?: 'danger' | 'warning' | 'success' }[];
    cols?: number;
}>();

const TONES: Record<string, string> = {
    danger: 'text-red-700',
    warning: 'text-amber-700',
    success: 'text-emerald-700',
};
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2" :class="{ 'xl:grid-cols-3': cols === 3, 'xl:grid-cols-4': (cols ?? 4) === 4, 'xl:grid-cols-5': cols === 5, 'xl:grid-cols-6': cols === 6 }">
        <div v-for="item in items" :key="item.label" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium text-gray-500">{{ item.label }}</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums" :class="item.tone ? TONES[item.tone] : 'text-gray-900'">{{ item.value }}</p>
            <p v-if="item.hint" class="mt-1 text-xs text-gray-500">{{ item.hint }}</p>
        </div>
    </div>
</template>
