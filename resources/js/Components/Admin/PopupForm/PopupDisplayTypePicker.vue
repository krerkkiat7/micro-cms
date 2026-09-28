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
                    <!-- รูปภาพลอยอย่างเดียว + แถบ "ไม่แสดงวันนี้อีก" และปุ่มปิดใต้รูป -->
                    <rect x="39" y="10" width="44" height="40" rx="3" class="fill-gray-900/20" />
                    <rect x="38" y="8" width="44" height="40" rx="3" class="fill-brand-300" />
                    <circle cx="48" cy="18" r="4" class="fill-white/80" />
                    <path d="M40 46 L56 28 L66 38 L72 32 L80 46 Z" class="fill-brand-500" />
                    <rect x="44" y="54" width="24" height="6" rx="3" class="fill-gray-700" />
                    <circle cx="74" cy="57" r="3.5" class="fill-gray-700" />
                </template>
            </svg>
        </template>
    </VisualPicker>
</template>
