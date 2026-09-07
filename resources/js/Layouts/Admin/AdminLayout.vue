<script setup lang="ts">
import AppSidebar from '@/Components/Admin/AppSidebar.vue';
import AppHeader from '@/Components/Admin/AppHeader.vue';
import { provideSidebar } from '@/composables/useSidebar';

const { isExpanded } = provideSidebar();
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <AppSidebar />

        <div
            class="flex min-h-screen flex-col transition-all duration-300 ease-in-out"
            :class="isExpanded ? 'lg:ml-[290px]' : 'lg:ml-[90px]'"
        >
            <AppHeader />

            <main class="flex-1">
                <div v-if="$slots.header" class="border-b border-gray-200 bg-white">
                    <div class="mx-auto max-w-[1536px] px-4 py-5 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </div>

                <div class="mx-auto max-w-[1536px] px-4 py-6 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
