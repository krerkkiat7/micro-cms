<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, UserCircle } from '@lucide/vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import SidebarItem from '@/Components/Admin/SidebarItem.vue';
import { useSidebar } from '@/composables/useSidebar';

const { isExpanded, isMobileOpen, closeMobileSidebar } = useSidebar();

const navGroups = [
    {
        title: 'เมนู',
        items: [
            {
                label: 'Dashboard',
                href: route('admin.dashboard'),
                active: 'admin.dashboard',
                icon: LayoutGrid,
            },
        ],
    },
    {
        title: 'บัญชี',
        items: [
            {
                label: 'โปรไฟล์',
                href: route('admin.profile.edit'),
                active: 'admin.profile',
                icon: UserCircle,
            },
        ],
    },
    // { title: 'เนื้อหา', items: [ /* โมดูล CMS: บทความ / เพจ / หมวดหมู่ (ยังไม่ทำ) */ ] },
];

const showText = computed(() => isExpanded.value || isMobileOpen.value);
</script>

<template>
    <!-- Backdrop (มือถือ) -->
    <div
        v-show="isMobileOpen"
        class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"
        @click="closeMobileSidebar"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-[290px] flex-col border-r border-white/10 bg-admin-900 transition-all duration-300 ease-in-out"
        :class="[
            isMobileOpen ? 'translate-x-0' : '-translate-x-full',
            'lg:translate-x-0',
            isExpanded ? 'lg:w-[290px]' : 'lg:w-[90px]',
        ]"
    >
        <!-- โลโก้ -->
        <div
            class="flex h-16 items-center border-b border-white/10 px-5"
            :class="showText ? 'justify-start' : 'lg:justify-center'"
        >
            <Link :href="route('admin.dashboard')" class="flex items-center gap-2.5 text-white">
                <ApplicationLogo class="size-8 shrink-0 fill-current" />
                <span v-show="showText" class="text-lg font-semibold tracking-tight">My CMS</span>
            </Link>
        </div>

        <!-- เมนู -->
        <nav class="flex-1 overflow-y-auto px-4 py-5">
            <div v-for="group in navGroups" :key="group.title" class="mb-6 last:mb-0">
                <p
                    v-show="showText"
                    class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-600"
                >
                    {{ group.title }}
                </p>
                <div v-show="!showText" class="mb-2 lg:mx-3 lg:border-t lg:border-white/10"></div>
                <div class="space-y-1">
                    <SidebarItem
                        v-for="item in group.items"
                        :key="item.label"
                        :label="item.label"
                        :href="item.href"
                        :active="item.active"
                        :icon="item.icon"
                    />
                </div>
            </div>
        </nav>
    </aside>
</template>
