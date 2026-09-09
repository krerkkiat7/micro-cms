<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

interface Crumb {
    label: string;
    href?: string;
}

defineProps<{
    items: Crumb[];
}>();
</script>

<template>
    <nav class="flex flex-wrap items-center gap-1 text-xs text-gray-500">
        <template v-for="(item, i) in items" :key="i">
            <ChevronRight v-if="i > 0" class="size-3 shrink-0 text-gray-300" />
            <Link
                v-if="item.href && i < items.length - 1"
                :href="item.href"
                class="text-brand-600 hover:text-brand-700"
            >
                {{ item.label }}
            </Link>
            <span v-else class="text-gray-500">{{ item.label }}</span>
        </template>
    </nav>
</template>
