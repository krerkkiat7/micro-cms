<script setup lang="ts">
import { computed } from 'vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import FontStyleToggles from '@/Components/Admin/FontStyleToggles.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { FONT_SIZE_OPTIONS } from '@/utils/pageLayout';

/**
 * การจัดรูปแบบตัวอักษรของข้อความ 1 ส่วนในตั้งค่าติดต่อเรา — แถวแรก: ขนาด + ฟอนต์ + จัดรูปแบบ (ตัวหนา/ตัวเอียง/ขีดเส้นใต้)
 * แถวถัดไป: สี; แก้ค่าในฟอร์มตรง ๆ ผ่านชื่อคีย์ `{part}_font_size`, `{part}_font_family`, `{part}_bold`, `{part}_italic`, `{part}_underline`, `{part}_color`
 */
const props = defineProps<{
    values: Record<string, string>;
    part: string;
    fonts: string[];
}>();

// ฟอร์มของหน้าแม่ (useForm) — แก้ค่าตรงตามชื่อคีย์
function field<T extends string = string>(name: string) {
    return computed<T>({
        get: () => props.values[`${props.part}_${name}`] as T,
        set: (value: T) => {
            props.values[`${props.part}_${name}`] = value;
        },
    });
}

const size = field('font_size');
const family = field('font_family');
const color = field('color');
const bold = field<'Y' | 'N'>('bold');
const italic = field<'Y' | 'N'>('italic');
const underline = field<'Y' | 'N'>('underline');

const fontOptions = computed(() => props.fonts.map((font) => ({ value: font, label: font })));

// ค่าขนาดที่ไม่อยู่ในรายการสำเร็จรูปยังต้องเลือกค้างไว้ได้
const sizeOptions = computed(() =>
    FONT_SIZE_OPTIONS.some((o) => o.value === size.value) ? FONT_SIZE_OPTIONS : [...FONT_SIZE_OPTIONS, { value: size.value, label: `${size.value} px` }],
);
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-end gap-4">
            <div class="w-36">
                <InputLabel value="ขนาดตัวอักษร" />
                <SearchableSelect v-model="size" :options="sizeOptions" />
            </div>
            <div class="min-w-48 flex-1">
                <InputLabel value="ฟอนต์" />
                <SearchableSelect v-model="family" :options="fontOptions" />
            </div>
            <div>
                <InputLabel value="จัดรูปแบบ" />
                <FontStyleToggles v-model:bold="bold" v-model:italic="italic" v-model:underline="underline" />
            </div>
        </div>
        <div>
            <InputLabel value="สีตัวอักษร" />
            <ColorPickerInput v-model="color" />
        </div>
    </div>
</template>
