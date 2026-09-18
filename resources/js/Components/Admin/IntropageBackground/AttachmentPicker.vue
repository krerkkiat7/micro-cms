<script setup lang="ts">
import { BACKGROUND_ATTACHMENT_STYLES } from '@/utils/intropageBackground';

/**
 * เลือก CSS background-attachment แบบเห็นภาพประกอบ (ไดอะแกรม SVG) — เทียบเคียง
 * ArticlePart/ImagesDisplayTypePicker.vue: กรอบพื้นหลัง (ไอคอนภูเขา+พระอาทิตย์จาง ๆ) + เส้นเนื้อหาจำลอง
 * ด้านล่าง แล้วใช้ลูกศรกับไอคอนหมุดสื่อความหมาย — "เลื่อนตามเนื้อหา" มีลูกศรยาวลากผ่านทั้งพื้นหลังและ
 * เนื้อหา (เลื่อนไปด้วยกัน) ส่วน "ตรึงกับจอ" มีไอคอนหมุดปักที่พื้นหลัง + ลูกศรสั้นเฉพาะช่วงเนื้อหา
 * (เนื้อหาเลื่อนผ่านพื้นหลังที่อยู่กับที่)
 */
const model = defineModel<string>({ required: true });
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-3">
        <label
            v-for="opt in BACKGROUND_ATTACHMENT_STYLES"
            :key="opt.value"
            class="flex cursor-pointer flex-col rounded-xl border p-3 transition-colors"
            :class="model === opt.value ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500' : 'border-gray-200 bg-white hover:border-gray-300'"
        >
            <input v-model="model" type="radio" name="background_attachment" :value="opt.value" class="sr-only" />

            <svg viewBox="0 0 120 80" class="h-20 w-full text-gray-300">
                <defs>
                    <clipPath id="intropage-bgattach-clip">
                        <rect x="10" y="8" width="100" height="64" rx="6" />
                    </clipPath>
                </defs>
                <rect x="10" y="8" width="100" height="64" rx="6" class="fill-gray-50 stroke-gray-300" stroke-width="2" />

                <!-- ไม่ระบุ -->
                <line v-if="opt.value === ''" x1="45" y1="40" x2="75" y2="40" class="stroke-gray-300" stroke-width="4" stroke-linecap="round" />

                <template v-else>
                    <g clip-path="url(#intropage-bgattach-clip)">
                        <circle cx="40" cy="26" r="7" class="fill-brand-200" />
                        <path d="M10 58 L38 36 L54 48 L70 32 L110 58 L110 72 L10 72 Z" class="fill-brand-300" />
                    </g>

                    <!-- เส้นเนื้อหา (จำลองข้อความที่สกรอลล์) -->
                    <rect x="18" y="46" width="68" height="6" rx="2" class="fill-white" />
                    <rect x="18" y="56" width="68" height="6" rx="2" class="fill-white" />
                    <rect x="18" y="66" width="50" height="6" rx="2" class="fill-white" />

                    <!-- scroll: ลูกศรยาวลากผ่านทั้งพื้นหลังและเนื้อหา (เลื่อนไปด้วยกัน) -->
                    <template v-if="opt.value === 'scroll'">
                        <path d="M100 14 L100 62" class="stroke-brand-500" stroke-width="2.5" stroke-linecap="round" />
                        <path d="M94 56 L100 66 L106 56" class="fill-none stroke-brand-500" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    </template>

                    <!-- fixed: หมุดปักพื้นหลัง + ลูกศรสั้นเฉพาะช่วงเนื้อหา (เนื้อหาเลื่อนผ่านพื้นหลังที่อยู่กับที่) -->
                    <template v-else>
                        <g transform="translate(100,18)">
                            <circle r="6" class="fill-white stroke-brand-500" stroke-width="2" />
                            <path d="M0 6 L0 14" class="stroke-brand-500" stroke-width="2.5" stroke-linecap="round" />
                        </g>
                        <path d="M100 42 L100 66" class="stroke-brand-500" stroke-width="2.5" stroke-linecap="round" />
                        <path d="M94 60 L100 68 L106 60" class="fill-none stroke-brand-500" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    </template>
                </template>
            </svg>

            <span class="mt-2 text-sm font-medium text-gray-800">{{ opt.label }}</span>
            <span class="mt-0.5 text-xs text-gray-500">{{ opt.description }}</span>
        </label>
    </div>
</template>
