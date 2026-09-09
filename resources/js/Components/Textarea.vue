<script setup lang="ts">
const model = defineModel<string>({ required: true });
</script>

<template>
    <textarea
        class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-xs transition-colors placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-3 focus:ring-brand-500/10 disabled:bg-gray-50 disabled:text-gray-500"
        v-model="model"
    />
</template>
