<script setup lang="ts">
import { Menu, PanelLeft } from 'lucide-vue-next';
import UserMenu from '@/Components/Admin/UserMenu.vue';
import { useSidebar } from '@/composables/useSidebar';

const { toggleSidebar, toggleMobileSidebar } = useSidebar();
</script>

<template>
    <header
        class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-white/10 bg-admin-900 px-4 text-gray-200 sm:px-6"
    >
        <!-- toggle: มือถือเปิด drawer / desktop ย่อ-ขยาย -->
        <button
            type="button"
            class="flex size-9 items-center justify-center rounded-lg text-gray-300 transition-colors hover:bg-white/10 lg:hidden"
            aria-label="เปิดเมนู"
            @click="toggleMobileSidebar"
        >
            <Menu class="size-5" />
        </button>
        <button
            type="button"
            class="hidden size-9 items-center justify-center rounded-lg text-gray-300 transition-colors hover:bg-white/10 lg:flex"
            aria-label="ย่อ/ขยายเมนู"
            @click="toggleSidebar"
        >
            <PanelLeft class="size-5" />
        </button>

        <div class="flex-1"></div>

        <UserMenu />
    </header>
</template>
