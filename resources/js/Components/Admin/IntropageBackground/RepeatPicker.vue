<script setup lang="ts">
import { BACKGROUND_REPEAT_STYLES } from '@/utils/intropageBackground';

/**
 * เลือก CSS background-repeat แบบเห็นภาพประกอบ (ไดอะแกรม SVG) — เทียบเคียง
 * ArticlePart/ImagesDisplayTypePicker.vue: กรอบพื้นหลัง + ตารางสี่เหลี่ยมเล็ก ๆ จำลองการเรียงซ้ำของรูป
 * ตามแต่ละแบบ (เต็มพื้นที่ / รูปเดียว / แถวเดียวแนวนอน / แถวเดียวแนวตั้ง)
 */
const model = defineModel<string>({ required: true });

const SIZE = 12;
const COL_XS = [14, 30, 46, 62, 78, 94];
const ROW_YS = [16, 32, 48];

function squaresFor(value: string): { x: number; y: number }[] {
    if (value === 'repeat') {
        return COL_XS.flatMap((x) => ROW_YS.map((y) => ({ x, y })));
    }
    if (value === 'repeat-x') {
        return COL_XS.map((x) => ({ x, y: 34 }));
    }
    if (value === 'repeat-y') {
        return ROW_YS.map((y) => ({ x: 54, y }));
    }
    if (value === 'no-repeat') {
        return [{ x: 20, y: 22 }];
    }

    return [];
}
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <label
            v-for="opt in BACKGROUND_REPEAT_STYLES"
            :key="opt.value"
            class="flex cursor-pointer flex-col rounded-xl border p-3 transition-colors"
            :class="model === opt.value ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500' : 'border-gray-200 bg-white hover:border-gray-300'"
        >
            <input v-model="model" type="radio" name="background_repeat" :value="opt.value" class="sr-only" />

            <svg viewBox="0 0 120 80" class="h-20 w-full text-gray-300">
                <rect x="8" y="10" width="104" height="60" rx="4" class="fill-gray-50 stroke-gray-300" stroke-width="2" />
                <line v-if="opt.value === ''" x1="45" y1="40" x2="75" y2="40" class="stroke-gray-300" stroke-width="4" stroke-linecap="round" />
                <rect
                    v-for="(sq, i) in squaresFor(opt.value)"
                    :key="i"
                    :x="sq.x"
                    :y="sq.y"
                    :width="SIZE"
                    :height="SIZE"
                    rx="2"
                    class="fill-brand-400"
                />
            </svg>

            <span class="mt-2 text-sm font-medium text-gray-800">{{ opt.label }}</span>
            <span class="mt-0.5 text-xs text-gray-500">{{ opt.description }}</span>
        </label>
    </div>
</template>
