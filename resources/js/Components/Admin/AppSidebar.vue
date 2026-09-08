<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, UserCircle } from 'lucide-vue-next';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import SidebarItem from '@/Components/Admin/SidebarItem.vue';
import SidebarGroup from '@/Components/Admin/SidebarGroup.vue';
import type { MenuGroup } from '@/types';
import { useSidebar } from '@/composables/useSidebar';

const { isExpanded, isMobileOpen, closeMobileSidebar } = useSidebar();

const page = usePage();

// เมนูจาก DB (sys_menu_group + sys_menu) กรองตามสิทธิ์แล้วจากฝั่ง server
const menuGroups = computed<MenuGroup[]>(() => page.props.menu ?? []);

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
            <!-- เมนูหลัก: Dashboard + กลุ่มเมนูจาก DB (ระดับเดียวกัน) -->
            <div class="mb-6">
                <p
                    v-show="showText"
                    class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-600"
                >
                    เมนู
                </p>
                <div v-show="!showText" class="mb-2 lg:mx-3 lg:border-t lg:border-white/10"></div>
                <div class="space-y-1">
                    <SidebarItem
                        label="Dashboard"
                        :href="route('admin.dashboard')"
                        active="admin.dashboard"
                        :icon="LayoutGrid"
                    />
                    <SidebarGroup
                        v-for="group in menuGroups"
                        :key="group.id"
                        :group="group"
                    />
                </div>
            </div>

            <!-- บัญชี -->
            <div>
                <p
                    v-show="showText"
                    class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-600"
                >
                    บัญชี
                </p>
                <div v-show="!showText" class="mb-2 lg:mx-3 lg:border-t lg:border-white/10"></div>
                <div class="space-y-1">
                    <SidebarItem
                        label="โปรไฟล์"
                        :href="route('admin.profile.edit')"
                        active="admin.profile"
                        :icon="UserCircle"
                    />
                </div>
            </div>
        </nav>
    </aside>
</template>
