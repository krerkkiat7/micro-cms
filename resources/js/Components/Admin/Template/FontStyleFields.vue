<script setup lang="ts">
import { computed } from 'vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import SegmentedChoice from '@/Components/Admin/Template/SegmentedChoice.vue';
import YesNoCheckbox from '@/Components/Admin/Template/YesNoCheckbox.vue';
import { FONT_SIZE_OPTIONS } from '@/utils/pageLayout';
import { ALIGN_OPTIONS } from '@/utils/template';

/**
 * ชุดตั้งค่าตัวอักษร (สี / ขนาด / ฟอนต์ / ตัวหนา / ตำแหน่ง) ที่เก็บเป็นคอลัมน์แบน `<prefix>_color`, `<prefix>_font_size`,
 * `<prefix>_font_family`, `<prefix>_bold`, `<prefix>_align` บน object ของโซน — แก้ property ของ `fields` ตรง ๆ
 * (ไม่ตั้งชื่อ prop ว่า style — ดูข้อควรระวังใน CLAUDE.md)
 */
const props = withDefaults(
    defineProps<{
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        fields: Record<string, any>;
        prefix: string;
        fonts: string[];
        bold?: boolean;
        align?: boolean;
    }>(),
    { bold: true, align: false },
);

const fontOptions = computed(() => props.fonts.map((font) => ({ value: font, label: font })));

const sizeOptions = computed(() => {
    const current = String(props.fields[`${props.prefix}_font_size`]);

    return FONT_SIZE_OPTIONS.some((o) => o.value === current) ? FONT_SIZE_OPTIONS : [...FONT_SIZE_OPTIONS, { value: current, label: `${current} px` }];
});

const size = computed({
    get: () => String(props.fields[`${props.prefix}_font_size`]),
    set: (value: string) => {
        // eslint-disable-next-line vue/no-mutating-props
        props.fields[`${props.prefix}_font_size`] = Number(value);
    },
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <InputLabel value="สีตัวอักษร" />
            <ColorPickerInput v-model="fields[`${prefix}_color`]" />
        </div>
        <div>
            <InputLabel value="ขนาดตัวอักษร" />
            <SearchableSelect v-model="size" :options="sizeOptions" />
        </div>
        <div>
            <InputLabel value="ฟอนต์" />
            <SearchableSelect v-model="fields[`${prefix}_font_family`]" :options="fontOptions" />
        </div>
        <div v-if="align">
            <InputLabel value="ตำแหน่ง" />
            <SegmentedChoice v-model="fields[`${prefix}_align`]" :options="ALIGN_OPTIONS" />
        </div>
        <div v-if="bold" class="flex items-end pb-2">
            <YesNoCheckbox v-model="fields[`${prefix}_bold`]" label="ตัวหนา" />
        </div>
    </div>
</template>
