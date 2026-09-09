<script setup lang="ts">
import AppSidebar from '@/Components/Admin/AppSidebar.vue';
import AppHeader from '@/Components/Admin/AppHeader.vue';
import SuccessDialog from '@/Components/SuccessDialog.vue';
import { provideSidebar } from '@/composables/useSidebar';
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const { isExpanded } = provideSidebar();

const page = usePage();
const flashSuccess = ref<string | null>(null);

// successId เปลี่ยนทุกครั้งที่มี flash ใหม่ (แม้ข้อความเดิม) → เด้ง dialog
watch(
    () => page.props.flash?.successId,
    () => {
        const message = page.props.flash?.success;
        if (message) flashSuccess.value = message;
    },
    { immediate: true },
);
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

        <SuccessDialog
            :show="!!flashSuccess"
            :message="flashSuccess ?? ''"
            @close="flashSuccess = null"
        />
    </div>
</template>
