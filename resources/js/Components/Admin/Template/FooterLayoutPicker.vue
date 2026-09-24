<script setup lang="ts">
import VisualPicker from '@/Components/Admin/Template/VisualPicker.vue';
import { FOOTER_LAYOUTS } from '@/utils/template';

/** เลือกรูปแบบการแสดง footer */
const model = defineModel<string>({ required: true });
</script>

<template>
    <VisualPicker v-model="model" :options="FOOTER_LAYOUTS" name="footer_layout_type">
        <template #default="{ value }">
            <svg viewBox="0 0 120 50" class="h-16 w-full">
                <rect width="120" height="50" rx="3" class="fill-gray-700" />
                <template v-if="value === 'site_contact_menu'">
                    <rect x="8" y="8" width="16" height="8" rx="1.5" class="fill-brand-400" />
                    <rect x="8" y="20" width="28" height="2" class="fill-gray-400" />
                    <rect x="8" y="25" width="22" height="2" class="fill-gray-400" />
                    <rect x="46" y="8" width="20" height="3" class="fill-white" />
                    <rect x="46" y="15" width="28" height="2" class="fill-gray-400" />
                    <rect x="46" y="20" width="24" height="2" class="fill-gray-400" />
                    <rect x="46" y="25" width="26" height="2" class="fill-gray-400" />
                    <rect x="86" y="8" width="16" height="3" class="fill-white" />
                    <rect x="86" y="15" width="20" height="2" class="fill-gray-400" />
                    <rect x="86" y="20" width="16" height="2" class="fill-gray-400" />
                    <rect x="86" y="25" width="22" height="2" class="fill-gray-400" />
                </template>
                <template v-else-if="value === 'site_contact_center'">
                    <rect x="52" y="6" width="16" height="8" rx="1.5" class="fill-brand-400" />
                    <rect x="40" y="18" width="40" height="2" class="fill-gray-400" />
                    <rect x="30" y="23" width="60" height="2" class="fill-gray-400" />
                    <rect x="44" y="28" width="32" height="2" class="fill-gray-400" />
                </template>
                <template v-else-if="value === 'site_contact_block'">
                    <rect x="6" y="6" width="50" height="28" rx="2" class="fill-gray-600" />
                    <rect x="11" y="11" width="16" height="7" rx="1.5" class="fill-brand-400" />
                    <rect x="11" y="23" width="36" height="2" class="fill-gray-400" />
                    <rect x="64" y="6" width="50" height="28" rx="2" class="fill-gray-600" />
                    <rect x="69" y="11" width="22" height="3" class="fill-white" />
                    <rect x="69" y="18" width="36" height="2" class="fill-gray-400" />
                    <rect x="69" y="23" width="30" height="2" class="fill-gray-400" />
                </template>
                <rect y="40" width="120" height="10" rx="0" class="fill-gray-900" />
                <rect x="42" y="44" width="36" height="2" class="fill-gray-500" />
            </svg>
        </template>
    </VisualPicker>
</template>
