<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';

/**
 * ช่องติ๊กเปิด/ปิดที่เก็บค่าเป็น 'Y' / 'N' (ตาม convention ของโปรเจกต์ที่ไม่ใช้ boolean) — ใช้ในฟอร์มตั้งค่าเฉพาะประเภท widget
 */
defineProps<{
    label: string;
    hint?: string;
}>();

const model = defineModel<'Y' | 'N'>({ required: true });
</script>

<template>
    <label class="flex cursor-pointer items-start gap-2.5">
        <Checkbox class="mt-0.5" :checked="model === 'Y'" @update:checked="model = $event ? 'Y' : 'N'" />
        <span class="text-sm text-gray-700">
            {{ label }}
            <span v-if="hint" class="mt-0.5 block text-xs text-gray-500">{{ hint }}</span>
        </span>
    </label>
</template>
