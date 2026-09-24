<script setup lang="ts">
import VisualPicker from '@/Components/Admin/Template/VisualPicker.vue';
import { ASIDE_DISPLAYS } from '@/utils/template';

/** เลือกรูปแบบการแสดง aside — แถบข้าง / เต็มจอ (`side` = ฝั่งที่ไอคอนเปิดอยู่ ใช้วาดให้แถบข้างออกฝั่งเดียวกัน) */
const model = defineModel<string>({ required: true });

defineProps<{ side: 'left' | 'right' }>();
</script>

<template>
    <VisualPicker v-model="model" :options="ASIDE_DISPLAYS" name="aside_display_type" :columns="2">
        <template #default="{ value }">
            <svg viewBox="0 0 120 60" class="h-16 w-full">
                <rect width="120" height="60" rx="3" class="fill-gray-200" />
                <rect width="120" height="10" rx="2" class="fill-white" />
                <rect x="8" y="16" width="104" height="38" rx="2" class="fill-gray-300" />
                <template v-if="value === 'drawer'">
                    <rect width="120" height="60" rx="3" class="fill-gray-900/30" />
                    <rect :x="side === 'left' ? 0 : 76" width="44" height="60" class="fill-white" />
                    <rect :x="side === 'left' ? 6 : 82" y="10" width="28" height="3" class="fill-gray-500" />
                    <rect :x="side === 'left' ? 6 : 82" y="18" width="24" height="3" class="fill-gray-400" />
                    <rect :x="side === 'left' ? 6 : 82" y="26" width="30" height="3" class="fill-gray-400" />
                    <rect :x="side === 'left' ? 6 : 82" y="34" width="20" height="3" class="fill-gray-400" />
                </template>
                <template v-else>
                    <rect width="120" height="60" rx="3" class="fill-white" />
                    <rect x="44" y="12" width="32" height="4" class="fill-gray-500" />
                    <rect x="40" y="22" width="40" height="4" class="fill-gray-400" />
                    <rect x="46" y="32" width="28" height="4" class="fill-gray-400" />
                    <rect x="42" y="42" width="36" height="4" class="fill-gray-400" />
                </template>
            </svg>
        </template>
    </VisualPicker>
</template>
