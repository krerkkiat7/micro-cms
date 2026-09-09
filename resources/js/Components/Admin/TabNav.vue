<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Tab {
    label: string;
    href: string;
    active: boolean;
}

defineProps<{
    tabs: Tab[];
}>();
</script>

<template>
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex gap-6">
            <Link
                v-for="tab in tabs"
                :key="tab.href"
                :href="tab.href"
                class="border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                :class="
                    tab.active
                        ? 'border-brand-500 text-brand-600'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                "
            >
                {{ tab.label }}
            </Link>
        </nav>
    </div>
</template>
