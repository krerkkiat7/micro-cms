<script setup lang="ts">
import { computed } from 'vue';
import { AlignCenter, AlignLeft, AlignRight } from 'lucide-vue-next';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { FONT_SIZE_OPTIONS, TEXT_ALIGN_OPTIONS } from '@/utils/pageLayout';
import type { TextAlign, TextStyle } from '@/utils/pageLayout';

/**
 * ชุดตั้งค่าการจัดรูปแบบตัวอักษรของข้อความ 1 ส่วน (หัวเรื่อง / หัวเรื่องรอง / ข้อความเกริ่นนำ) ของแถว/คอลัมน์/widget —
 * ขนาดตัวอักษร, ฟอนต์ (รายการฟอนต์ไทยจาก backend), การจัดตำแหน่ง (ชิดซ้าย/กึ่งกลาง/ชิดขวา) และสี (ไม่มีตัวเลือกโปร่งใส)
 * `style` = object ที่เก็บค่า (แก้ property ภายในตรง ๆ)
 */
const props = defineProps<{
    style: TextStyle;
    fonts: string[];
}>();

const ALIGN_ICONS: Record<TextAlign, typeof AlignLeft> = { left: AlignLeft, center: AlignCenter, right: AlignRight };

const fontOptions = computed(() => props.fonts.map((font) => ({ value: font, label: font })));

// ค่าขนาดที่ไม่อยู่ในรายการสำเร็จรูป (เช่น ข้อมูลเดิม) ยังต้องแสดงและเลือกอยู่ได้
const sizeOptions = computed(() => {
    const current = String(props.style.font_size);

    return FONT_SIZE_OPTIONS.some((o) => o.value === current) ? FONT_SIZE_OPTIONS : [...FONT_SIZE_OPTIONS, { value: current, label: `${current} px` }];
});

const size = computed({
    get: () => String(props.style.font_size),
    set: (value: string) => {
        props.style.font_size = Number(value);
    },
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <InputLabel value="ขนาดตัวอักษร" />
            <SearchableSelect v-model="size" :options="sizeOptions" />
        </div>
        <div>
            <InputLabel value="ฟอนต์" />
            <SearchableSelect v-model="style.font_family" :options="fontOptions" />
        </div>
        <div>
            <InputLabel value="จัดตำแหน่ง" />
            <div class="flex gap-1.5">
                <button
                    v-for="option in TEXT_ALIGN_OPTIONS"
                    :key="option.value"
                    type="button"
                    :title="option.label"
                    class="flex h-9 flex-1 items-center justify-center gap-1.5 rounded-lg border text-sm transition-colors"
                    :class="
                        style.align === option.value
                            ? 'border-brand-500 bg-brand-50 text-brand-700 ring-1 ring-brand-500'
                            : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'
                    "
                    @click="style.align = option.value"
                >
                    <component :is="ALIGN_ICONS[option.value]" class="size-4" />
                </button>
            </div>
        </div>
        <div class="sm:col-span-3">
            <InputLabel value="สีตัวอักษร" />
            <ColorPickerInput v-model="style.color" />
        </div>
    </div>
</template>
