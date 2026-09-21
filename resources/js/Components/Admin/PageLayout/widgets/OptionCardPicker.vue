<script setup lang="ts">
/**
 * ตัวเลือกแบบการ์ด (radio) ที่แสดงภาพตัวอย่างพร้อมชื่อของแต่ละตัวเลือก — ใช้แทน dropdown เมื่อเป็นการเลือก "หน้าตา" (ไอคอน รูปแบบปุ่ม ตำแหน่ง)
 * เพื่อให้ผู้ใช้เห็นว่าแต่ละตัวเลือกหน้าตาเป็นอย่างไรก่อนเลือก (ตาม pattern ของ ArticlePart/ImagesDisplayTypePicker) ส่วนภาพตัวอย่างของแต่ละตัวเลือก
 * ส่งผ่าน slot `visual` (รับ `option`); `columns` = class grid ของ Tailwind ที่ต้องเป็นชื่อเต็ม เช่น 'grid-cols-3 sm:grid-cols-4'
 */
interface Option {
    value: string;
    label: string;
    description?: string;
}

defineProps<{
    options: Option[];
    columns?: string;
    /** ชื่อกลุ่ม radio (ต้องไม่ซ้ำกันในหน้าเดียวกัน) */
    name: string;
}>();

const model = defineModel<string>({ required: true });
</script>

<template>
    <div class="grid gap-2" :class="columns ?? 'grid-cols-2 sm:grid-cols-4'" role="radiogroup">
        <label
            v-for="option in options"
            :key="option.value"
            class="flex cursor-pointer flex-col items-center gap-1.5 rounded-lg border px-2 py-2.5 text-center transition-colors"
            :class="model === option.value ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500' : 'border-gray-200 bg-white hover:border-gray-300'"
        >
            <input v-model="model" type="radio" :name="name" :value="option.value" class="sr-only" />
            <div class="flex min-h-9 w-full items-center justify-center text-gray-700">
                <slot name="visual" :option="option" />
            </div>
            <span class="text-xs font-medium text-gray-700">{{ option.label }}</span>
            <span v-if="option.description" class="text-[11px] leading-tight text-gray-400">{{ option.description }}</span>
        </label>
    </div>
</template>
