<script setup lang="ts">
import VisualPicker from '@/Components/Admin/Template/VisualPicker.vue';
import { CONTACTUS_DISPLAY_TYPE_OPTIONS } from '@/utils/contactus';

/**
 * เลือกรูปแบบการแสดงผลหน้าติดต่อเรา — ภาพประกอบ: ข้อมูลติดต่อ = แถบข้อความเทา, แผนที่ = กล่องเขียว + หมุดแดง,
 * แบบฟอร์ม = ช่องกรอกขอบเทา + ปุ่มสีหลัก
 */
const model = defineModel<string>({ required: true });
</script>

<template>
    <VisualPicker v-model="model" :options="CONTACTUS_DISPLAY_TYPE_OPTIONS" name="contactus_display_type" :columns="3">
        <template #default="{ value }">
            <svg viewBox="0 0 120 90" class="h-24 w-full">
                <rect width="120" height="90" rx="4" class="fill-white stroke-gray-200" stroke-width="1" />

                <!-- แบบเรียงลงมา -->
                <template v-if="value === 'stacked'">
                    <rect x="40" y="6" width="40" height="4" rx="2" class="fill-gray-500" />
                    <rect x="30" y="13" width="60" height="2.5" rx="1" class="fill-gray-300" />
                    <rect x="36" y="18" width="48" height="2.5" rx="1" class="fill-gray-300" />
                    <rect x="12" y="25" width="96" height="16" rx="2" class="fill-emerald-100" />
                    <rect x="12" y="44" width="96" height="16" rx="2" class="fill-emerald-200" />
                    <circle cx="60" cy="51" r="2.5" class="fill-red-500" />
                    <rect x="12" y="64" width="46" height="6" rx="1.5" class="fill-white stroke-gray-300" stroke-width="0.8" />
                    <rect x="62" y="64" width="46" height="6" rx="1.5" class="fill-white stroke-gray-300" stroke-width="0.8" />
                    <rect x="12" y="73" width="96" height="6" rx="1.5" class="fill-white stroke-gray-300" stroke-width="0.8" />
                    <rect x="50" y="82" width="20" height="5" rx="1.5" class="fill-brand-500" />
                </template>

                <!-- แบบแบ่งข้อมูลติดต่อ -->
                <template v-else-if="value === 'split_info'">
                    <rect x="8" y="8" width="36" height="4" rx="2" class="fill-gray-500" />
                    <rect x="8" y="16" width="46" height="2.5" rx="1" class="fill-gray-300" />
                    <rect x="8" y="22" width="40" height="2.5" rx="1" class="fill-gray-300" />
                    <rect x="8" y="28" width="44" height="2.5" rx="1" class="fill-gray-300" />
                    <rect x="8" y="34" width="34" height="2.5" rx="1" class="fill-gray-300" />
                    <rect x="62" y="8" width="50" height="18" rx="2" class="fill-emerald-100" />
                    <rect x="62" y="28" width="50" height="18" rx="2" class="fill-emerald-200" />
                    <circle cx="87" cy="36" r="2.5" class="fill-red-500" />
                    <rect x="8" y="54" width="50" height="6" rx="1.5" class="fill-white stroke-gray-300" stroke-width="0.8" />
                    <rect x="62" y="54" width="50" height="6" rx="1.5" class="fill-white stroke-gray-300" stroke-width="0.8" />
                    <rect x="8" y="63" width="104" height="12" rx="1.5" class="fill-white stroke-gray-300" stroke-width="0.8" />
                    <rect x="50" y="79" width="20" height="5" rx="1.5" class="fill-brand-500" />
                </template>

                <!-- แบบครึ่ง -->
                <template v-else>
                    <rect x="8" y="8" width="36" height="4" rx="2" class="fill-gray-500" />
                    <rect x="8" y="16" width="46" height="2.5" rx="1" class="fill-gray-300" />
                    <rect x="8" y="22" width="40" height="2.5" rx="1" class="fill-gray-300" />
                    <rect x="8" y="28" width="44" height="2.5" rx="1" class="fill-gray-300" />
                    <rect x="8" y="36" width="48" height="20" rx="2" class="fill-emerald-100" />
                    <rect x="8" y="59" width="48" height="23" rx="2" class="fill-emerald-200" />
                    <circle cx="32" cy="70" r="2.5" class="fill-red-500" />
                    <rect x="64" y="8" width="48" height="6" rx="1.5" class="fill-white stroke-gray-300" stroke-width="0.8" />
                    <rect x="64" y="18" width="48" height="6" rx="1.5" class="fill-white stroke-gray-300" stroke-width="0.8" />
                    <rect x="64" y="28" width="48" height="6" rx="1.5" class="fill-white stroke-gray-300" stroke-width="0.8" />
                    <rect x="64" y="38" width="48" height="28" rx="1.5" class="fill-white stroke-gray-300" stroke-width="0.8" />
                    <rect x="92" y="72" width="20" height="5" rx="1.5" class="fill-brand-500" />
                </template>
            </svg>
        </template>
    </VisualPicker>
</template>
