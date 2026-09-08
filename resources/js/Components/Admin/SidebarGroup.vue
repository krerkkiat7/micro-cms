<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronDown, Folder } from 'lucide-vue-next';
import type { MenuGroup } from '@/types';
import { useSidebar } from '@/composables/useSidebar';

const props = defineProps<{ group: MenuGroup }>();

const { closeMobileSidebar } = useSidebar();

const STORAGE_PREFIX = 'admin.sidebar.group.';

function currentMatches(name: string | null): boolean {
    if (!name) return false;
    try {
        return route().current(name) || route().current(`${name}.*`);
    } catch {
        return false;
    }
}

// route ปัจจุบันอยู่ในกลุ่มนี้ไหม (ใช้บังคับให้กางไว้)
const containsActive = computed(() =>
    props.group.items.some((item) => currentMatches(item.routeName)),
);

function readStored(): boolean {
    try {
        const v = window.localStorage.getItem(STORAGE_PREFIX + props.group.id);
        return v === null ? true : v === '1';
    } catch {
        return true;
    }
}

const open = ref(readStored() || containsActive.value);

watch(open, (value) => {
    try {
        window.localStorage.setItem(STORAGE_PREFIX + props.group.id, value ? '1' : '0');
    } catch {
        /* private mode / storage ปิด — ข้ามได้ */
    }
});

watch(containsActive, (value) => {
    if (value) open.value = true;
});
</script>

<template>
    <div>
        <button
            type="button"
            @click="open = !open"
            class="group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-400 transition-colors hover:bg-white/5 hover:text-white"
        >
            <Folder class="size-5 shrink-0 text-gray-500 group-hover:text-gray-300" />
            <span class="flex-1 truncate text-left">{{ group.name }}</span>
            <ChevronDown
                class="size-4 shrink-0 transition-transform duration-200"
                :class="open ? '' : '-rotate-90'"
            />
        </button>

        <div v-show="open" class="mt-1 space-y-0.5 border-l border-white/10 pl-4">
            <template v-for="item in group.items" :key="item.id">
                <Link
                    v-if="item.href"
                    :href="item.href"
                    @click="closeMobileSidebar"
                    class="block rounded-lg px-3 py-2 text-sm transition-colors"
                    :class="
                        currentMatches(item.routeName)
                            ? 'bg-brand-500/15 text-white'
                            : 'text-gray-400 hover:bg-white/5 hover:text-white'
                    "
                >
                    {{ item.name }}
                </Link>
                <span
                    v-else
                    class="block cursor-default rounded-lg px-3 py-2 text-sm text-gray-600"
                    :title="`ยังไม่มี route: ${item.routeName ?? '-'}`"
                >
                    {{ item.name }}
                </span>
            </template>
        </div>
    </div>
</template>
