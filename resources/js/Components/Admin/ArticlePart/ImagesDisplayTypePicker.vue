<script setup lang="ts">
import { IMAGES_DISPLAY_TYPES } from '@/utils/articleParts';

/**
 * เลือก "รูปแบบการแสดงผล" ของ part กลุ่มรูปภาพ แบบเห็นภาพตัวอย่างประกอบ (แทน dropdown ธรรมดา)
 * ไดอะแกรมแต่ละแบบเป็น SVG อย่างง่ายวาดขึ้นเอง (ไม่มีรูปตัวอย่างจริงในระบบ) เพื่อสื่อโครงสร้างคร่าว ๆ
 * ของรูปแบบนั้น ๆ ให้พอเห็นภาพก่อนเลือก พร้อมคำอธิบายสั้น ๆ ใต้ไดอะแกรม
 */
const model = defineModel<string>({ required: true });
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <label
            v-for="opt in IMAGES_DISPLAY_TYPES"
            :key="opt.value"
            class="flex cursor-pointer flex-col rounded-xl border p-3 transition-colors"
            :class="model === opt.value ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500' : 'border-gray-200 bg-white hover:border-gray-300'"
        >
            <input v-model="model" type="radio" name="images_display_type" :value="opt.value" class="sr-only" />

            <svg viewBox="0 0 120 80" class="h-20 w-full text-gray-300">
                <!-- thumbnail_carousel: รูปหลักใหญ่ + แถวรูปย่อยด้านล่าง -->
                <template v-if="opt.value === 'thumbnail_carousel'">
                    <rect x="10" y="4" width="100" height="50" rx="4" class="fill-brand-200" />
                    <rect x="10" y="60" width="20" height="16" rx="2" class="fill-brand-400" />
                    <rect x="34" y="60" width="20" height="16" rx="2" class="fill-gray-200" />
                    <rect x="58" y="60" width="20" height="16" rx="2" class="fill-gray-200" />
                    <rect x="82" y="60" width="20" height="16" rx="2" class="fill-gray-200" />
                </template>

                <!-- multi_carousel: แถวรูปเต็ม 3 รูป + เศษรูปโผล่ขอบซ้าย/ขวาบอกว่าเลื่อนได้ -->
                <template v-else-if="opt.value === 'multi_carousel'">
                    <rect x="-6" y="14" width="16" height="52" rx="3" class="fill-gray-200" />
                    <rect x="14" y="14" width="28" height="52" rx="3" class="fill-brand-300" />
                    <rect x="46" y="14" width="28" height="52" rx="3" class="fill-brand-400" />
                    <rect x="78" y="14" width="28" height="52" rx="3" class="fill-brand-300" />
                    <rect x="110" y="14" width="16" height="52" rx="3" class="fill-gray-200" />
                </template>

                <!-- grid_lightbox: ตาราง 3x2 ขนาดเท่ากัน + แว่นขยายกลางรูปหนึ่ง -->
                <template v-else-if="opt.value === 'grid_lightbox'">
                    <rect x="6" y="6" width="34" height="32" rx="3" class="fill-brand-300" />
                    <rect x="43" y="6" width="34" height="32" rx="3" class="fill-brand-200" />
                    <rect x="80" y="6" width="34" height="32" rx="3" class="fill-brand-300" />
                    <rect x="6" y="41" width="34" height="32" rx="3" class="fill-brand-200" />
                    <rect x="43" y="41" width="34" height="32" rx="3" class="fill-brand-400" />
                    <rect x="80" y="41" width="34" height="32" rx="3" class="fill-brand-200" />
                    <circle cx="57" cy="54" r="6" class="fill-none stroke-white" stroke-width="2" />
                    <line x1="61.5" y1="58.5" x2="66" y2="63" class="stroke-white" stroke-width="2" stroke-linecap="round" />
                </template>

                <!-- full_width_slider: รูปเดียวเต็มกว้าง + จุด pagination -->
                <template v-else-if="opt.value === 'full_width_slider'">
                    <rect x="4" y="6" width="112" height="52" rx="4" class="fill-brand-300" />
                    <circle cx="54" cy="70" r="3" class="fill-brand-500" />
                    <circle cx="62" cy="70" r="3" class="fill-gray-300" />
                    <circle cx="70" cy="70" r="3" class="fill-gray-300" />
                </template>

                <!-- masonry_grid: 3 คอลัมน์ ความสูงไม่เท่ากันแบบ Pinterest -->
                <template v-else-if="opt.value === 'masonry_grid'">
                    <rect x="6" y="4" width="34" height="24" rx="3" class="fill-brand-300" />
                    <rect x="6" y="32" width="34" height="44" rx="3" class="fill-brand-200" />
                    <rect x="43" y="4" width="34" height="72" rx="3" class="fill-brand-400" />
                    <rect x="80" y="4" width="34" height="44" rx="3" class="fill-brand-200" />
                    <rect x="80" y="52" width="34" height="24" rx="3" class="fill-brand-300" />
                </template>

                <!-- justified_grid: แถวที่ความกว้างรูปไม่เท่ากันแต่เต็มความกว้างเสมอ -->
                <template v-else-if="opt.value === 'justified_grid'">
                    <rect x="6" y="6" width="68" height="30" rx="3" class="fill-brand-300" />
                    <rect x="78" y="6" width="36" height="30" rx="3" class="fill-brand-200" />
                    <rect x="6" y="40" width="30" height="30" rx="3" class="fill-brand-200" />
                    <rect x="40" y="40" width="46" height="30" rx="3" class="fill-brand-400" />
                    <rect x="90" y="40" width="24" height="30" rx="3" class="fill-brand-300" />
                </template>

                <!-- stacked_cards: การ์ดซ้อนเหลื่อมกันหลายใบ -->
                <template v-else-if="opt.value === 'stacked_cards'">
                    <rect x="34" y="22" width="66" height="42" rx="4" transform="rotate(-6 67 43)" class="fill-gray-200" />
                    <rect x="28" y="18" width="66" height="42" rx="4" transform="rotate(3 61 39)" class="fill-brand-200" />
                    <rect x="20" y="14" width="66" height="42" rx="4" class="fill-brand-400" />
                </template>
            </svg>

            <span class="mt-2 text-sm font-medium text-gray-800">{{ opt.label }}</span>
            <span class="mt-0.5 text-xs text-gray-500">{{ opt.description }}</span>
        </label>
    </div>
</template>
