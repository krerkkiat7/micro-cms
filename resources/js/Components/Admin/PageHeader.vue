<script setup lang="ts">
import Breadcrumbs from '@/Components/Admin/Breadcrumbs.vue';

interface Crumb {
    label: string;
    href?: string;
}

defineProps<{
    title: string;
    breadcrumbs?: Crumb[];
}>();
</script>

<template>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <Breadcrumbs
                v-if="breadcrumbs && breadcrumbs.length"
                :items="breadcrumbs"
                class="mb-2"
            />
            <h1 class="text-xl font-semibold text-gray-800">{{ title }}</h1>
        </div>

        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
