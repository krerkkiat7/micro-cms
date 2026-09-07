<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, LogOut, UserCircle } from 'lucide-vue-next';
import Dropdown from '@/Components/Dropdown.vue';

const page = usePage();
</script>

<template>
    <Dropdown align="right" width="48" content-classes="py-1 bg-white">
        <template #trigger>
            <button
                type="button"
                class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-200 transition-colors hover:bg-white/10"
            >
                <span
                    class="flex size-8 items-center justify-center rounded-full bg-brand-500/20 text-brand-200"
                >
                    <UserCircle class="size-5" />
                </span>
                <span class="hidden font-medium sm:block">{{ page.props.auth.user.name }}</span>
                <ChevronDown class="size-4 text-gray-400" />
            </button>
        </template>

        <template #content>
            <div class="border-b border-gray-100 px-4 py-3">
                <p class="text-sm font-medium text-gray-900">{{ page.props.auth.user.name }}</p>
                <p class="truncate text-xs text-gray-500">{{ page.props.auth.user.email }}</p>
            </div>
            <Link
                :href="route('admin.profile.edit')"
                class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50"
            >
                <UserCircle class="size-4 text-gray-400" />
                โปรไฟล์
            </Link>
            <Link
                :href="route('admin.logout')"
                method="post"
                as="button"
                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 transition-colors hover:bg-gray-50"
            >
                <LogOut class="size-4 text-gray-400" />
                ออกจากระบบ
            </Link>
        </template>
    </Dropdown>
</template>
