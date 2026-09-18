<script setup lang="ts">
import { BACKGROUND_SIZE_STYLES } from '@/utils/intropageBackground';

/**
 * เลือก CSS background-size แบบเห็นภาพประกอบ (ไดอะแกรม SVG) — เทียบเคียง
 * ArticlePart/ImagesDisplayTypePicker.vue: กรอบพื้นหลัง + สี่เหลี่ยมจำลองรูปภาพ ขนาด/ตำแหน่งต่างกันไปตาม
 * แบบ (auto = เล็กกว่ากรอบวางมุมบนซ้าย, cover = ขยายล้นกรอบแล้วตัดขอบ, contain = พอดีความกว้างแต่เหลือ
 * ขอบว่างบน-ล่าง)
 */
const model = defineModel<string>({ required: true });
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <label
            v-for="opt in BACKGROUND_SIZE_STYLES"
            :key="opt.value"
            class="flex cursor-pointer flex-col rounded-xl border p-3 transition-colors"
            :class="model === opt.value ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500' : 'border-gray-200 bg-white hover:border-gray-300'"
        >
            <input v-model="model" type="radio" name="background_size" :value="opt.value" class="sr-only" />

            <svg viewBox="0 0 120 80" class="h-20 w-full text-gray-300">
                <defs>
                    <clipPath id="intropage-bgsize-clip">
                        <rect x="8" y="10" width="104" height="60" rx="4" />
                    </clipPath>
                </defs>
                <rect x="8" y="10" width="104" height="60" rx="4" class="fill-gray-50 stroke-gray-300" stroke-width="2" />

                <!-- ไม่ระบุ -->
                <line v-if="opt.value === ''" x1="45" y1="40" x2="75" y2="40" class="stroke-gray-300" stroke-width="4" stroke-linecap="round" />

                <!-- auto: รูปขนาดจริง เล็กกว่ากรอบ วางมุมบนซ้าย เห็นพื้นที่ว่างรอบ ๆ -->
                <rect v-else-if="opt.value === 'auto'" x="8" y="10" width="52" height="36" class="fill-brand-300" />

                <!-- cover: รูปขยายล้นกรอบแล้วถูกตัดขอบ (clip ตามกรอบ) -->
                <g v-else-if="opt.value === 'cover'" clip-path="url(#intropage-bgsize-clip)">
                    <rect x="-8" y="-4" width="136" height="88" class="fill-brand-300" />
                </g>

                <!-- contain: รูปพอดีความกว้าง แต่เตี้ยกว่ากรอบ เห็นขอบว่างบน-ล่าง -->
                <rect v-else x="8" y="22" width="104" height="36" class="fill-brand-300" />
            </svg>

            <span class="mt-2 text-sm font-medium text-gray-800">{{ opt.label }}</span>
            <span class="mt-0.5 text-xs text-gray-500">{{ opt.description }}</span>
        </label>
    </div>
</template>
