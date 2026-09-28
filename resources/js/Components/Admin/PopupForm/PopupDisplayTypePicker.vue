<script setup lang="ts">
import VisualPicker from '@/Components/Admin/Template/VisualPicker.vue';
import { POPUP_DISPLAY_TYPE_OPTIONS } from '@/utils/popupForm';

/** เลือกรูปแบบการแสดงของ popup — modal (มีพื้นหลังทึบ) / floating (ลอยกลางจอ ไม่มีพื้นหลัง) */
const model = defineModel<string>({ required: true });
</script>

<template>
    <VisualPicker v-model="model" :options="POPUP_DISPLAY_TYPE_OPTIONS" name="popup_display_type" :columns="2">
        <template #default="{ value }">
            <svg viewBox="0 0 120 70" class="h-16 w-full">
                <!-- หน้าเว็บด้านหลัง -->
                <rect width="120" height="70" rx="4" class="fill-white stroke-gray-200" stroke-width="1" />
                <rect x="0" y="0" width="120" height="10" rx="3" class="fill-gray-200" />
                <rect x="8" y="16" width="50" height="4" rx="2" class="fill-gray-300" />
                <rect x="8" y="24" width="104" height="3" rx="1.5" class="fill-gray-200" />
                <rect x="8" y="31" width="96" height="3" rx="1.5" class="fill-gray-200" />
                <rect x="8" y="38" width="104" height="3" rx="1.5" class="fill-gray-200" />
                <rect x="8" y="45" width="88" height="3" rx="1.5" class="fill-gray-200" />
                <rect x="8" y="52" width="100" height="3" rx="1.5" class="fill-gray-200" />
                <template v-if="value === 'modal'">
                    <rect width="120" height="70" rx="4" class="fill-gray-900/50" />
                    <rect x="36" y="12" width="48" height="46" rx="3" class="fill-white" />
                    <rect x="40" y="16" width="40" height="22" rx="2" class="fill-brand-300" />
                    <rect x="40" y="42" width="34" height="2.5" rx="1" class="fill-gray-400" />
                    <rect x="54" y="50" width="12" height="5" rx="1.5" class="fill-gray-300" />
                    <rect x="68" y="50" width="12" height="5" rx="1.5" class="fill-brand-500" />
                </template>
                <template v-else>
                    <rect x="37" y="14" width="48" height="44" rx="3" class="fill-gray-900/15" />
                    <rect x="36" y="12" width="48" height="44" rx="3" class="fill-white stroke-gray-300" stroke-width="1" />
                    <circle cx="79" cy="16" r="2.5" class="fill-gray-400" />
                    <rect x="40" y="20" width="40" height="22" rx="2" class="fill-brand-300" />
                    <rect x="40" y="46" width="34" height="2.5" rx="1" class="fill-gray-400" />
                    <rect x="48" y="51" width="24" height="2" rx="1" class="fill-brand-400" />
                </template>
            </svg>
        </template>
    </VisualPicker>
</template>
