<script setup lang="ts">
import VisualPicker from '@/Components/Admin/Template/VisualPicker.vue';
import { POPUP_PART_TYPE_OPTIONS } from '@/utils/popupForm';

/** เลือกรูปแบบของ part — รูปภาพ + ข้อความ / รูปภาพ / ข้อความ */
const model = defineModel<string>({ required: true });

defineProps<{ name: string }>();
</script>

<template>
    <VisualPicker v-model="model" :options="POPUP_PART_TYPE_OPTIONS" :name="name">
        <template #default="{ value }">
            <svg viewBox="0 0 120 70" class="h-14 w-full">
                <rect width="120" height="70" rx="4" class="fill-gray-50" />
                <rect x="30" y="4" width="60" height="62" rx="3" class="fill-white stroke-gray-300" stroke-width="1" />
                <template v-if="value === 'image_text'">
                    <rect x="34" y="8" width="52" height="28" rx="2" class="fill-brand-300" />
                    <path d="M38 32 L50 20 L60 30 L68 24 L82 32 Z" class="fill-brand-500" />
                    <rect x="36" y="42" width="48" height="3" rx="1.5" class="fill-gray-400" />
                    <rect x="36" y="49" width="44" height="3" rx="1.5" class="fill-gray-300" />
                    <rect x="36" y="56" width="36" height="3" rx="1.5" class="fill-gray-300" />
                </template>
                <template v-else-if="value === 'image'">
                    <rect x="34" y="8" width="52" height="54" rx="2" class="fill-brand-300" />
                    <circle cx="44" cy="20" r="4" class="fill-white/80" />
                    <path d="M38 58 L54 36 L66 50 L74 42 L82 58 Z" class="fill-brand-500" />
                </template>
                <template v-else>
                    <rect x="36" y="12" width="48" height="4" rx="2" class="fill-gray-500" />
                    <rect x="36" y="22" width="48" height="3" rx="1.5" class="fill-gray-300" />
                    <rect x="36" y="29" width="44" height="3" rx="1.5" class="fill-gray-300" />
                    <rect x="36" y="36" width="48" height="3" rx="1.5" class="fill-gray-300" />
                    <rect x="36" y="43" width="40" height="3" rx="1.5" class="fill-gray-300" />
                    <rect x="36" y="50" width="30" height="3" rx="1.5" class="fill-gray-300" />
                </template>
            </svg>
        </template>
    </VisualPicker>
</template>
