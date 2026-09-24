<script setup lang="ts">
import VisualPicker from '@/Components/Admin/Template/VisualPicker.vue';
import { HEADER_LAYOUTS } from '@/utils/template';

/** เลือกรูปแบบการแสดง header — แถบบน+แถบหลัก / แถบหลัก+แถวเมนู / แถบหลักเท่านั้น */
const model = defineModel<string>({ required: true });
</script>

<template>
    <VisualPicker v-model="model" :options="HEADER_LAYOUTS" name="header_layout_type">
        <template #default="{ value }">
            <svg viewBox="0 0 120 50" class="h-16 w-full">
                <rect width="120" height="50" rx="3" class="fill-gray-50" />
                <template v-if="value === 'topbar_main'">
                    <rect width="120" height="8" rx="2" class="fill-brand-700" />
                    <rect x="6" y="3" width="14" height="2" class="fill-white/70" />
                    <rect x="86" y="3" width="28" height="2" class="fill-white/70" />
                    <rect y="8" width="120" height="18" class="fill-white" />
                    <rect x="6" y="12" width="18" height="10" rx="2" class="fill-brand-400" />
                    <rect x="66" y="16" width="10" height="2.5" class="fill-gray-400" />
                    <rect x="80" y="16" width="10" height="2.5" class="fill-gray-400" />
                    <rect x="94" y="16" width="10" height="2.5" class="fill-gray-400" />
                </template>
                <template v-else-if="value === 'main_menubar'">
                    <rect width="120" height="18" rx="2" class="fill-white" />
                    <rect x="6" y="4" width="18" height="10" rx="2" class="fill-brand-400" />
                    <rect x="86" y="8" width="28" height="2.5" class="fill-gray-300" />
                    <rect y="18" width="120" height="9" class="fill-brand-600" />
                    <rect x="6" y="21.5" width="10" height="2.5" class="fill-white" />
                    <rect x="20" y="21.5" width="10" height="2.5" class="fill-white" />
                    <rect x="34" y="21.5" width="10" height="2.5" class="fill-white" />
                    <rect x="48" y="21.5" width="10" height="2.5" class="fill-white" />
                </template>
                <template v-else-if="value === 'main_only'">
                    <rect width="120" height="18" rx="2" class="fill-white" />
                    <rect x="6" y="4" width="18" height="10" rx="2" class="fill-brand-400" />
                    <rect x="52" y="8" width="9" height="2.5" class="fill-gray-400" />
                    <rect x="64" y="8" width="9" height="2.5" class="fill-gray-400" />
                    <rect x="76" y="8" width="9" height="2.5" class="fill-gray-400" />
                    <rect x="94" y="6" width="20" height="6" rx="1.5" class="fill-gray-200" />
                </template>
                <rect x="8" y="32" width="104" height="14" rx="2" class="fill-gray-200" />
            </svg>
        </template>
    </VisualPicker>
</template>
