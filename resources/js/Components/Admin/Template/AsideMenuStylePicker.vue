<script setup lang="ts">
import VisualPicker from '@/Components/Admin/Template/VisualPicker.vue';
import { ASIDE_MENU_STYLES } from '@/utils/template';

/** เลือกรูปแบบการแสดงเมนูใน aside */
const model = defineModel<string>({ required: true });
</script>

<template>
    <VisualPicker v-model="model" :options="ASIDE_MENU_STYLES" name="aside_menu_style" :columns="4">
        <template #default="{ value }">
            <svg viewBox="0 0 60 60" class="h-20 w-full">
                <rect width="60" height="60" rx="3" class="fill-white stroke-gray-200" stroke-width="1" />
                <template v-if="value === 'list'">
                    <rect x="6" y="8" width="32" height="3" class="fill-gray-500" />
                    <rect x="6" y="15" width="48" height="0.6" class="fill-gray-200" />
                    <rect x="12" y="19" width="26" height="2.5" class="fill-gray-400" />
                    <rect x="12" y="25" width="22" height="2.5" class="fill-gray-400" />
                    <rect x="6" y="31" width="48" height="0.6" class="fill-gray-200" />
                    <rect x="6" y="36" width="30" height="3" class="fill-gray-500" />
                    <rect x="6" y="43" width="48" height="0.6" class="fill-gray-200" />
                    <rect x="6" y="48" width="26" height="3" class="fill-gray-500" />
                </template>
                <template v-else-if="value === 'accordion'">
                    <rect x="6" y="8" width="30" height="3" class="fill-gray-500" />
                    <path d="M48 8 l3 3 l3 -3" class="fill-none stroke-gray-500" stroke-width="1.2" />
                    <rect x="6" y="15" width="48" height="16" rx="1.5" class="fill-brand-50" />
                    <rect x="11" y="19" width="26" height="2.5" class="fill-brand-400" />
                    <rect x="11" y="25" width="22" height="2.5" class="fill-brand-400" />
                    <rect x="6" y="37" width="30" height="3" class="fill-gray-500" />
                    <path d="M48 40 l3 -3 l3 3" class="fill-none stroke-gray-500" stroke-width="1.2" transform="rotate(90 51 38.5)" />
                    <rect x="6" y="47" width="26" height="3" class="fill-gray-500" />
                </template>
                <template v-else-if="value === 'drilldown'">
                    <path d="M9 7 l-3 3 l3 3" class="fill-none stroke-brand-500" stroke-width="1.2" />
                    <rect x="12" y="8.5" width="18" height="3" class="fill-brand-500" />
                    <rect x="6" y="17" width="48" height="0.6" class="fill-gray-200" />
                    <rect x="6" y="22" width="30" height="3" class="fill-gray-500" />
                    <path d="M50 21 l3 2.5 l-3 2.5" class="fill-none stroke-gray-400" stroke-width="1.2" />
                    <rect x="6" y="31" width="26" height="3" class="fill-gray-500" />
                    <rect x="6" y="40" width="32" height="3" class="fill-gray-500" />
                    <path d="M50 39 l3 2.5 l-3 2.5" class="fill-none stroke-gray-400" stroke-width="1.2" />
                </template>
                <template v-else-if="value === 'large'">
                    <rect x="12" y="9" width="36" height="6" rx="1" class="fill-gray-500" />
                    <rect x="16" y="21" width="28" height="6" rx="1" class="fill-gray-500" />
                    <rect x="10" y="33" width="40" height="6" rx="1" class="fill-gray-500" />
                    <rect x="18" y="45" width="24" height="6" rx="1" class="fill-gray-500" />
                </template>
            </svg>
        </template>
    </VisualPicker>
</template>
