<script setup lang="ts">
import { INTROPAGE_DISPLAY_SIZES } from '@/utils/intropageDisplay';

/**
 * เลือก "ขนาดการแสดงผล" ของสื่อหลักหน้า Intropage แบบเห็นภาพตัวอย่างประกอบ (แทน dropdown ธรรมดา)
 * เทียบเคียง ArticlePart/ImagesDisplayTypePicker.vue — ไดอะแกรมเป็นกรอบ "หน้าจอ" (เส้นขอบ + แถบหัวจำลอง
 * browser) เดียวกันทุกตัวเลือก ต่างกันแค่ตำแหน่ง/ความกว้างของแท่งสื่อหลัก (สีน้ำเงิน) และ container ประเภท
 * `container_*` มีกรอบเส้นประซ้อนด้านในแทน "container เนื้อหา" ที่แคบกว่าจอ ให้เห็นความต่างจาก `screen_*`
 * ที่วัดเทียบกับกรอบหน้าจอเต็ม ๆ ตรง ๆ
 */
const model = defineModel<string>({ required: true });

const MEDIA_BOX: Record<string, { x: number; width: number }> = {
    screen_100: { x: 8, width: 104 },
    screen_75: { x: 21, width: 78 },
    screen_50: { x: 34, width: 52 },
    screen_25: { x: 47, width: 26 },
    container_100: { x: 24, width: 72 },
    container_75: { x: 33, width: 54 },
    container_50: { x: 42, width: 36 },
    container_25: { x: 51, width: 18 },
};
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <label
            v-for="opt in INTROPAGE_DISPLAY_SIZES"
            :key="opt.value"
            class="flex cursor-pointer flex-col rounded-xl border p-3 transition-colors"
            :class="model === opt.value ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500' : 'border-gray-200 bg-white hover:border-gray-300'"
        >
            <input v-model="model" type="radio" name="display_size" :value="opt.value" class="sr-only" />

            <svg viewBox="0 0 120 80" class="h-20 w-full text-gray-300">
                <!-- กรอบหน้าจอจำลอง (เส้นขอบ + แถบหัว browser) เหมือนกันทุกตัวเลือก ให้เทียบสัดส่วนกันได้ -->
                <rect x="4" y="10" width="112" height="60" rx="4" class="fill-white stroke-gray-300" stroke-width="2" />
                <rect x="4" y="10" width="112" height="10" rx="4" class="fill-gray-100" />
                <circle cx="10" cy="15" r="1.4" class="fill-gray-300" />
                <circle cx="15" cy="15" r="1.4" class="fill-gray-300" />
                <circle cx="20" cy="15" r="1.4" class="fill-gray-300" />

                <!-- container_* เท่านั้น: กรอบเส้นประแทน container เนื้อหาที่แคบกว่าจอ -->
                <rect
                    v-if="opt.value.startsWith('container')"
                    x="24"
                    y="24"
                    width="72"
                    height="42"
                    rx="3"
                    class="fill-none stroke-gray-400"
                    stroke-width="1.5"
                    stroke-dasharray="3 2"
                />

                <!-- แท่งสื่อหลัก — กว้าง/ตำแหน่งตามสัดส่วนที่เลือก -->
                <rect :x="MEDIA_BOX[opt.value].x" y="34" :width="MEDIA_BOX[opt.value].width" height="22" rx="3" class="fill-brand-400" />
            </svg>

            <span class="mt-2 text-sm font-medium text-gray-800">{{ opt.label }}</span>
            <span class="mt-0.5 text-xs text-gray-500">{{ opt.description }}</span>
        </label>
    </div>
</template>
