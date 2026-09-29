<script setup lang="ts">
import { Bold, Italic, Underline } from 'lucide-vue-next';

/**
 * ปุ่มจัดรูปแบบตัวอักษร ตัวหนา / ตัวเอียง / ขีดเส้นใต้ แบบไอคอน (กดสลับเปิด-ปิด เหมือนแถบเครื่องมือของโปรแกรมพิมพ์เอกสาร)
 * ค่าเก็บเป็น 'Y' / 'N' ตาม convention ของโปรเจกต์ — ส่งเฉพาะ v-model ที่ใช้ (ไม่ส่ง = ไม่แสดงปุ่มนั้น)
 */
const bold = defineModel<'Y' | 'N'>('bold');
const italic = defineModel<'Y' | 'N'>('italic');
const underline = defineModel<'Y' | 'N'>('underline');

const buttons = [
    { key: 'bold', label: 'ตัวหนา', icon: Bold, model: bold },
    { key: 'italic', label: 'ตัวเอียง', icon: Italic, model: italic },
    { key: 'underline', label: 'ขีดเส้นใต้', icon: Underline, model: underline },
] as const;
</script>

<template>
    <div class="inline-flex overflow-hidden rounded-lg border border-gray-300 bg-white shadow-xs" role="group" aria-label="จัดรูปแบบตัวอักษร">
        <template v-for="button in buttons" :key="button.key">
            <button
                v-if="button.model.value !== undefined"
                type="button"
                class="flex size-[42px] items-center justify-center border-r border-gray-200 transition-colors last:border-r-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500"
                :class="button.model.value === 'Y' ? 'bg-brand-50 text-brand-700' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700'"
                :title="button.label"
                :aria-label="button.label"
                :aria-pressed="button.model.value === 'Y'"
                @click="button.model.value = button.model.value === 'Y' ? 'N' : 'Y'"
            >
                <component :is="button.icon" class="size-4" :stroke-width="button.model.value === 'Y' ? 2.75 : 2" />
            </button>
        </template>
    </div>
</template>
