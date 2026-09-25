<script setup lang="ts">
import type { Component } from 'vue';

/**
 * ตัวเลือกสั้น ๆ 2-3 ค่า (เช่น ซ้าย/กึ่งกลาง/ขวา, เต็มหน้าจอ/ตาม container) แบบปุ่มเรียงติดกัน (segmented control) — เห็นทุกตัวเลือกพร้อมกัน
 * `icon` (ไม่บังคับ) = ไอคอนสื่อความหมายแสดงหน้าข้อความ
 */
const model = defineModel<string>({ required: true });

defineProps<{
    options: { value: string; label: string; icon?: Component }[];
    disabled?: boolean;
}>();
</script>

<template>
    <div class="inline-flex flex-wrap rounded-lg border border-gray-300 bg-white p-0.5 shadow-xs">
        <button
            v-for="opt in options"
            :key="opt.value"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm transition-colors disabled:cursor-not-allowed"
            :class="model === opt.value ? 'bg-brand-500 font-medium text-white' : 'text-gray-600 hover:bg-gray-100'"
            :disabled="disabled"
            @click="model = opt.value"
        >
            <component :is="opt.icon" v-if="opt.icon" class="size-4" aria-hidden="true" />
            {{ opt.label }}
        </button>
    </div>
</template>
