<script setup lang="ts">
import { computed, ref } from 'vue';
import { Check, Palette } from 'lucide-vue-next';

/**
 * ตัวเลือกสี — แสดงชุดสีสำเร็จรูปให้คลิกเลือก หรือกด "กำหนดเอง" เพื่อเปิด color picker/กรอกรหัสสีเอง
 * v-model เป็น string เสมอ (รหัสสี hex เช่น '#465fff') — ค่าว่าง '' = ยังไม่ได้เลือก
 */
const PRESET_COLORS: { value: string; label: string }[] = [
    { value: '#ffffff', label: 'ขาว' },
    { value: '#000000', label: 'ดำ' },
    { value: '#667085', label: 'เทา' },
    { value: '#465fff', label: 'Brand' },
    { value: '#f04438', label: 'แดง' },
    { value: '#f79009', label: 'ส้ม' },
    { value: '#fdb022', label: 'เหลือง' },
    { value: '#12b76a', label: 'เขียว' },
    { value: '#06aed4', label: 'ฟ้า' },
    { value: '#7a5af8', label: 'ม่วง' },
    { value: '#ee46bc', label: 'ชมพู' },
];

const model = defineModel<string>({ default: '' });

const isPreset = computed(() => PRESET_COLORS.some((c) => c.value.toLowerCase() === model.value.toLowerCase()));
const showCustom = ref(!isPreset.value && model.value !== '');

function selectPreset(value: string) {
    showCustom.value = false;
    model.value = value;
}

function openCustom() {
    showCustom.value = true;
    if (model.value === '') {
        model.value = '#000000';
    }
}
</script>

<template>
    <div class="space-y-2">
        <div class="flex flex-wrap items-center gap-2">
            <button
                v-for="color in PRESET_COLORS"
                :key="color.value"
                type="button"
                class="flex size-7 items-center justify-center rounded-full border border-gray-300 shadow-xs transition-transform hover:scale-110"
                :style="{ backgroundColor: color.value }"
                :title="color.label"
                @click="selectPreset(color.value)"
            >
                <Check
                    v-if="!showCustom && model.toLowerCase() === color.value.toLowerCase()"
                    class="size-4"
                    :class="['#ffffff', '#fdb022', '#f79009'].includes(color.value) ? 'text-gray-700' : 'text-white'"
                />
            </button>

            <button
                type="button"
                class="flex size-7 items-center justify-center rounded-full border-2 transition-colors"
                :class="showCustom ? 'border-brand-500 bg-brand-50 text-brand-600' : 'border-dashed border-gray-300 text-gray-400 hover:text-gray-600'"
                title="กำหนดเอง"
                @click="openCustom"
            >
                <Palette class="size-3.5" />
            </button>
        </div>

        <div v-if="showCustom" class="flex items-center gap-2">
            <input v-model="model" type="color" class="size-8 cursor-pointer rounded border border-gray-300 p-0.5" />
            <input
                v-model="model"
                type="text"
                placeholder="#000000"
                class="w-28 rounded-lg border border-gray-300 px-2.5 py-1.5 text-sm text-gray-700 focus:border-brand-500 focus:ring-brand-500"
            />
        </div>
    </div>
</template>
