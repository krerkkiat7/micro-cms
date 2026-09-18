<script setup lang="ts">
import { BACKGROUND_POSITION_STYLES } from '@/utils/intropageBackground';

/**
 * เลือก CSS background-position แบบเห็นภาพประกอบ (ไดอะแกรม SVG) — เทียบเคียง
 * ArticlePart/ImagesDisplayTypePicker.vue: กรอบพื้นหลัง + เส้นกึ่งกลางแนวนอน/แนวตั้งจาง ๆ เป็นแนวอ้างอิง
 * แล้ววางจุดสีตามตำแหน่งที่เลือก (เหมือนตัว anchor-point picker ทั่วไป) เรียงตัวเลือกเป็น 3x3 ให้ตรงกับ
 * ตำแหน่งจริงบนกรอบ อ่านง่ายกว่า dropdown รายชื่อธรรมดา
 */
const model = defineModel<string>({ required: true });

const DOT: Record<string, { x: number; y: number }> = {
    'top left': { x: 20, y: 18 },
    top: { x: 60, y: 18 },
    'top right': { x: 100, y: 18 },
    left: { x: 20, y: 40 },
    center: { x: 60, y: 40 },
    right: { x: 100, y: 40 },
    'bottom left': { x: 20, y: 62 },
    bottom: { x: 60, y: 62 },
    'bottom right': { x: 100, y: 62 },
};
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-3">
        <label
            v-for="opt in BACKGROUND_POSITION_STYLES"
            :key="opt.value"
            class="flex cursor-pointer flex-col rounded-xl border p-3 transition-colors"
            :class="model === opt.value ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500' : 'border-gray-200 bg-white hover:border-gray-300'"
        >
            <input v-model="model" type="radio" name="background_position" :value="opt.value" class="sr-only" />

            <svg viewBox="0 0 120 80" class="h-20 w-full text-gray-300">
                <rect x="8" y="10" width="104" height="60" rx="4" class="fill-gray-50 stroke-gray-300" stroke-width="2" />

                <!-- ไม่ระบุ -->
                <line v-if="opt.value === ''" x1="45" y1="40" x2="75" y2="40" class="stroke-gray-300" stroke-width="4" stroke-linecap="round" />

                <template v-else>
                    <line x1="8" y1="40" x2="112" y2="40" class="stroke-gray-200" stroke-width="1" stroke-dasharray="2 2" />
                    <line x1="60" y1="10" x2="60" y2="70" class="stroke-gray-200" stroke-width="1" stroke-dasharray="2 2" />
                    <circle :cx="DOT[opt.value].x" :cy="DOT[opt.value].y" r="7" class="fill-brand-500" />
                </template>
            </svg>

            <span class="mt-2 text-sm font-medium text-gray-800">{{ opt.label }}</span>
            <span class="mt-0.5 text-xs text-gray-500">{{ opt.description }}</span>
        </label>
    </div>
</template>
