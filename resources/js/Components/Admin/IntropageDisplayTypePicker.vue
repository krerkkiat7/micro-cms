<script setup lang="ts">
import { INTROPAGE_DISPLAY_TYPES } from '@/utils/intropageDisplay';

/**
 * เลือก "ประเภทการแสดงผล" ของสื่อหลักหน้า Intropage แบบเห็นภาพตัวอย่างประกอบ (แทน dropdown ธรรมดา)
 * เทียบเคียง ArticlePart/ImagesDisplayTypePicker.vue — ไดอะแกรมแต่ละแบบเป็น SVG อย่างง่ายวาดขึ้นเอง
 */
const model = defineModel<string>({ required: true });
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <label
            v-for="opt in INTROPAGE_DISPLAY_TYPES"
            :key="opt.value"
            class="flex cursor-pointer flex-col rounded-xl border p-3 transition-colors"
            :class="model === opt.value ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500' : 'border-gray-200 bg-white hover:border-gray-300'"
        >
            <input v-model="model" type="radio" name="display_type" :value="opt.value" class="sr-only" />

            <svg viewBox="0 0 120 80" class="h-20 w-full text-gray-300">
                <!-- image: กรอบรูป + ภูเขา + พระอาทิตย์ (ไอคอนรูปภาพคลาสสิก) -->
                <template v-if="opt.value === 'image'">
                    <defs>
                        <clipPath id="intropage-display-image-clip">
                            <rect x="14" y="10" width="92" height="60" rx="6" />
                        </clipPath>
                    </defs>
                    <rect x="14" y="10" width="92" height="60" rx="6" class="fill-gray-50 stroke-gray-300" stroke-width="2" />
                    <g clip-path="url(#intropage-display-image-clip)">
                        <circle cx="40" cy="28" r="8" class="fill-brand-300" />
                        <path d="M14 66 L46 40 L64 54 L82 36 L106 66 Z" class="fill-brand-400" />
                    </g>
                </template>

                <!-- vdo: กรอบ + ปุ่ม play กลม (วิดีโอไฟล์ที่อัพโหลดเอง) -->
                <template v-else-if="opt.value === 'vdo'">
                    <rect x="14" y="10" width="92" height="60" rx="6" class="fill-gray-50 stroke-gray-300" stroke-width="2" />
                    <circle cx="60" cy="40" r="20" class="fill-brand-200" />
                    <path d="M54 28 L54 52 L76 40 Z" class="fill-brand-500" />
                </template>

                <!-- vdourl: กรอบ + ปุ่ม play + ไอคอนลิงก์มุมล่างขวา (วิดีโอจากลิงก์ภายนอก) -->
                <template v-else-if="opt.value === 'vdourl'">
                    <rect x="14" y="10" width="92" height="60" rx="6" class="fill-gray-50 stroke-gray-300" stroke-width="2" />
                    <circle cx="52" cy="34" r="16" class="fill-brand-200" />
                    <path d="M47 25 L47 43 L64 34 Z" class="fill-brand-500" />
                    <g transform="translate(90,58) rotate(45)">
                        <rect x="-14" y="-5" width="16" height="10" rx="5" class="fill-none stroke-brand-500" stroke-width="2.5" />
                        <rect x="0" y="-5" width="16" height="10" rx="5" class="fill-none stroke-brand-500" stroke-width="2.5" />
                    </g>
                </template>

                <!-- youtubeurl: กรอบเครื่องเล่นฝังวิดีโอ + ไอคอนลูกโลกมุมล่างขวา (ฝังจากเว็บภายนอก) -->
                <template v-else-if="opt.value === 'youtubeurl'">
                    <rect x="14" y="10" width="92" height="60" rx="6" class="fill-gray-50 stroke-gray-300" stroke-width="2" />
                    <rect x="30" y="20" width="60" height="38" rx="6" class="fill-brand-300" />
                    <path d="M53 30 L53 48 L69 39 Z" class="fill-white" />
                    <g transform="translate(96,60)">
                        <circle r="10" class="fill-white stroke-brand-500" stroke-width="2" />
                        <ellipse rx="4.5" ry="10" class="fill-none stroke-brand-500" stroke-width="1.5" />
                        <line x1="-10" y1="0" x2="10" y2="0" class="stroke-brand-500" stroke-width="1.5" />
                    </g>
                </template>
            </svg>

            <span class="mt-2 text-sm font-medium text-gray-800">{{ opt.label }}</span>
            <span class="mt-0.5 text-xs text-gray-500">{{ opt.description }}</span>
        </label>
    </div>
</template>
