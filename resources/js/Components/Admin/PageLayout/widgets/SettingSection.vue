<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';

/**
 * การ์ดแบ่งกลุ่มการตั้งค่าใน dialog ตั้งค่า widget ที่มีหลายฟิลด์ — หัวข้อ + คำอธิบายสั้น ๆ; ถ้าส่ง `enabled` (v-model:enabled 'Y'/'N')
 * หัวข้อจะมีช่องติ๊ก "แสดง/ซ่อน" ส่วนนั้น และเมื่อซ่อนจะพับรายละเอียดการตั้งค่าของส่วนนั้นทิ้ง (ไม่ต้องเลื่อนผ่านค่าที่ไม่ได้ใช้)
 */
defineProps<{
    title: string;
    description?: string;
    /** true = มีช่องติ๊กเปิด/ปิดส่วนนี้ที่หัวการ์ด (ผูกกับ v-model:enabled) */
    toggleable?: boolean;
}>();

const enabled = defineModel<'Y' | 'N'>('enabled', { default: 'Y' });
</script>

<template>
    <section class="rounded-lg border bg-white" :class="toggleable && enabled === 'N' ? 'border-gray-200 bg-gray-50/60' : 'border-gray-200'">
        <header class="flex items-start gap-3 px-4 py-3">
            <label v-if="toggleable" class="flex min-w-0 flex-1 cursor-pointer items-start gap-2.5">
                <Checkbox class="mt-0.5" :checked="enabled === 'Y'" @update:checked="enabled = $event ? 'Y' : 'N'" />
                <span class="min-w-0">
                    <span class="block text-sm font-semibold" :class="enabled === 'Y' ? 'text-gray-800' : 'text-gray-500'">{{ title }}</span>
                    <span v-if="description" class="block text-xs text-gray-500">{{ description }}</span>
                </span>
            </label>
            <div v-else class="min-w-0 flex-1">
                <h4 class="text-sm font-semibold text-gray-800">{{ title }}</h4>
                <p v-if="description" class="text-xs text-gray-500">{{ description }}</p>
            </div>
            <span
                v-if="toggleable"
                class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium"
                :class="enabled === 'Y' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-200 text-gray-500'"
            >
                {{ enabled === 'Y' ? 'แสดง' : 'ซ่อน' }}
            </span>
        </header>

        <div v-if="!toggleable || enabled === 'Y'" class="space-y-4 border-t border-gray-100 p-4">
            <slot />
        </div>
    </section>
</template>
