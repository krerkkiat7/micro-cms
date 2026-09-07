<script setup lang="ts">
import { computed, type FunctionalComponent } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useSidebar } from '@/composables/useSidebar';

const props = defineProps<{
    label: string;
    href: string;
    /** ชื่อ route สำหรับเช็ค active (รองรับ wildcard เช่น `admin.profile.*`) */
    active: string;
    icon: FunctionalComponent;
}>();

const { isExpanded, isMobileOpen, closeMobileSidebar } = useSidebar();

const isActive = computed(() => {
    try {
        return route().current(props.active) || route().current(`${props.active}.*`);
    } catch {
        return false;
    }
});

// แสดง label เมื่อ desktop กางเต็ม หรือกำลังเปิด drawer บนมือถือ
const showLabel = computed(() => isExpanded.value || isMobileOpen.value);
</script>

<template>
    <Link
        :href="href"
        @click="closeMobileSidebar"
        class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
        :class="[
            isActive
                ? 'bg-brand-500/15 text-white'
                : 'text-gray-400 hover:bg-white/5 hover:text-white',
            showLabel ? '' : 'lg:justify-center',
        ]"
    >
        <component
            :is="icon"
            class="size-5 shrink-0"
            :class="isActive ? 'text-brand-400' : 'text-gray-500 group-hover:text-gray-300'"
        />
        <span v-show="showLabel" class="truncate">{{ label }}</span>
    </Link>
</template>
