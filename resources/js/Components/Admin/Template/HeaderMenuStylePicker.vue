<script setup lang="ts">
import VisualPicker from '@/Components/Admin/Template/VisualPicker.vue';
import { HEADER_MENU_STYLES } from '@/utils/template';

/** เลือกรูปแบบการแสดงเมนูใน header — เมนูตัวที่ 2 ในภาพคือเมนูที่เลือกอยู่ (active) */
const model = defineModel<string>({ required: true });
</script>

<template>
    <VisualPicker v-model="model" :options="HEADER_MENU_STYLES" name="header_menu_style" :columns="4">
        <template #default="{ value }">
            <svg viewBox="0 0 120 30" class="h-10 w-full">
                <rect width="120" height="30" rx="3" class="fill-gray-50" />
                <template v-if="value === 'pill'">
                    <rect x="38" y="8" width="26" height="14" rx="7" class="fill-brand-500" />
                </template>
                <rect x="12" y="13.5" width="18" height="3" rx="1" class="fill-gray-400" />
                <rect
                    x="42"
                    y="13.5"
                    width="18"
                    height="3"
                    rx="1"
                    :class="value === 'pill' ? 'fill-white' : 'fill-brand-500'"
                />
                <rect x="72" y="13.5" width="18" height="3" rx="1" class="fill-gray-400" />
                <rect x="98" y="13.5" width="14" height="3" rx="1" class="fill-gray-400" />
                <template v-if="value === 'underline'">
                    <rect x="42" y="20" width="18" height="1.6" class="fill-brand-500" />
                </template>
                <template v-if="value === 'divider'">
                    <rect x="35.5" y="10" width="1" height="10" class="fill-gray-300" />
                    <rect x="65.5" y="10" width="1" height="10" class="fill-gray-300" />
                    <rect x="93.5" y="10" width="1" height="10" class="fill-gray-300" />
                </template>
            </svg>
        </template>
    </VisualPicker>
</template>
