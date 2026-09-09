<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types';

defineProps<{
    links: PaginationLink[];
}>();
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center gap-1">
        <template v-for="(link, i) in links" :key="i">
            <span
                v-if="link.url === null"
                class="rounded-lg px-3 py-2 text-sm text-gray-400"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                preserve-scroll
                preserve-state
                class="rounded-lg border px-3 py-2 text-sm transition-colors"
                :class="
                    link.active
                        ? 'border-brand-500 bg-brand-500 text-white'
                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                "
                v-html="link.label"
            />
        </template>
    </nav>
</template>
