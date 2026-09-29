<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import type { MenuGroup, MenuItem } from '@/types';
import { useSidebar } from '@/composables/useSidebar';
import { menuIcon } from '@/Components/Admin/menuIcons';

const props = defineProps<{ group: MenuGroup }>();

const { isExpanded, isMobileOpen, toggleSidebar, closeMobileSidebar } = useSidebar();

const STORAGE_PREFIX = 'admin.sidebar.group.';

const showText = computed(() => isExpanded.value || isMobileOpen.value);
const groupIcon = computed(() => menuIcon(props.group.icon));

// เมนูย่อยตัวนี้ active ไหม — เทียบ route ปัจจุบันกับ activePattern (server คำนวณจาก route_name)
function itemIsActive(item: MenuItem): boolean {
    if (!item.activePattern) return false;
    try {
        return route().current(item.activePattern);
    } catch {
        return false;
    }
}

// route ปัจจุบันอยู่ในกลุ่มนี้ไหม (ใช้ทั้งไฮไลต์หัวข้อกลุ่ม และบังคับให้กางไว้)
const containsActive = computed(() => props.group.items.some(itemIsActive));

// ค่าเริ่มต้น = ปิดทุกกลุ่ม (ยังไม่เคยกดเปิด/ปิดเอง) — กลุ่มที่มีหน้าปัจจุบันจะกางให้อัตโนมัติ (containsActive)
function readStored(): boolean {
    try {
        return window.localStorage.getItem(STORAGE_PREFIX + props.group.id) === '1';
    } catch {
        return false;
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

// โหมด sidebar ย่อ (rail): กดไอคอนกลุ่ม → กาง sidebar แล้วเปิดกลุ่มนั้น
function expandAndOpen() {
    if (!isExpanded.value) toggleSidebar();
    open.value = true;
}
</script>

<template>
    <!-- โหมดย่อ: แสดงเฉพาะไอคอนกลุ่ม -->
    <button
        v-if="!showText"
        type="button"
        :title="group.name"
        @click="expandAndOpen"
        class="group flex w-full items-center justify-center rounded-lg px-3 py-2.5 text-gray-400 transition-colors hover:bg-white/5 hover:text-white"
        :class="containsActive ? 'bg-brand-500/15 text-white' : ''"
    >
        <component
            :is="groupIcon"
            class="size-5 shrink-0"
            :class="containsActive ? 'text-brand-400' : 'text-gray-500 group-hover:text-gray-300'"
        />
    </button>

    <!-- โหมดกางเต็ม: หัวข้อกลุ่ม (toggle) + เมนูย่อย -->
    <div v-else>
        <button
            type="button"
            @click="open = !open"
            class="group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
            :class="
                containsActive
                    ? 'bg-brand-500/15 text-white'
                    : 'text-gray-400 hover:bg-white/5 hover:text-white'
            "
        >
            <component
                :is="groupIcon"
                class="size-5 shrink-0"
                :class="
                    containsActive
                        ? 'text-brand-400'
                        : 'text-gray-500 group-hover:text-gray-300'
                "
            />
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
                    class="group flex items-center gap-2.5 rounded-lg border border-transparent px-3 py-2 text-sm transition-colors"
                    :class="
                        itemIsActive(item)
                            ? 'border-brand-500/25 bg-brand-500/15 text-white'
                            : 'text-white hover:border-white/15 hover:bg-white/5'
                    "
                >
                    <component
                        :is="menuIcon(item.icon)"
                        class="size-4 shrink-0"
                        :class="
                            itemIsActive(item)
                                ? 'text-brand-400'
                                : 'text-gray-300 group-hover:text-white'
                        "
                    />
                    <span class="truncate">{{ item.name }}</span>
                </Link>
                <span
                    v-else
                    :title="`ยังไม่มี route: ${item.routeName ?? '-'}`"
                    class="flex cursor-default items-center gap-2.5 rounded-lg border border-transparent px-3 py-2 text-sm text-gray-500"
                >
                    <component :is="menuIcon(item.icon)" class="size-4 shrink-0 text-gray-600" />
                    <span class="truncate">{{ item.name }}</span>
                </span>
            </template>
        </div>
    </div>
</template>
