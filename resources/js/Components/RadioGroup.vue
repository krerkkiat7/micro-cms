<script setup lang="ts">
interface Option {
    label: string;
    value: string;
}

defineProps<{
    name: string;
    options: Option[];
    disabled?: boolean;
}>();

const model = defineModel<string>({ required: true });
</script>

<template>
    <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
        <label
            v-for="option in options"
            :key="option.value"
            class="flex items-center gap-2 text-sm text-gray-700"
            :class="disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'"
        >
            <input
                type="radio"
                :name="name"
                :value="option.value"
                v-model="model"
                :disabled="disabled"
                class="size-4 cursor-pointer border-gray-400 text-brand-500 shadow-xs transition-colors checked:border-brand-500 focus:ring-2 focus:ring-brand-500/30 disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-100 disabled:text-gray-300"
            />
            {{ option.label }}
        </label>
    </div>
</template>
