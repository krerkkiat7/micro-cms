<script setup lang="ts">
/**
 * ตัวเลือกสั้น ๆ 2-3 ค่า (เช่น ซ้าย/กึ่งกลาง/ขวา, เต็มหน้าจอ/ตาม container) แบบปุ่มเรียงติดกัน — เห็นทุกตัวเลือกพร้อมกัน
 */
const model = defineModel<string>({ required: true });

defineProps<{
    options: { value: string; label: string }[];
    disabled?: boolean;
}>();
</script>

<template>
    <div class="inline-flex flex-wrap rounded-lg border border-gray-300 bg-white p-0.5 shadow-xs">
        <button
            v-for="opt in options"
            :key="opt.value"
            type="button"
            class="rounded-md px-3 py-1.5 text-sm transition-colors disabled:cursor-not-allowed"
            :class="model === opt.value ? 'bg-brand-500 font-medium text-white' : 'text-gray-600 hover:bg-gray-100'"
            :disabled="disabled"
            @click="model = opt.value"
        >
            {{ opt.label }}
        </button>
    </div>
</template>
