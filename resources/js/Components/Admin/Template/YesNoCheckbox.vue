<script setup lang="ts">
import { computed } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';

/**
 * checkbox ที่ผูกกับค่า 'Y'/'N' (คอลัมน์ char(1)) — ใช้กับตัวเลือก แสดง/ซ่อน ทุกจุดของ template
 */
const model = defineModel<string>({ required: true });

defineProps<{
    label: string;
    description?: string;
}>();

const checked = computed({
    get: () => model.value === 'Y',
    set: (value: boolean) => (model.value = value ? 'Y' : 'N'),
});
</script>

<template>
    <label class="flex cursor-pointer items-start gap-2">
        <Checkbox v-model:checked="checked" class="mt-0.5" />
        <span>
            <span class="text-sm font-medium text-gray-700">{{ label }}</span>
            <span v-if="description" class="block text-xs text-gray-500">{{ description }}</span>
        </span>
    </label>
</template>
