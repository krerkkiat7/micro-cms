<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import type { CSSProperties } from 'vue';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';
import FrontLink from '@/Components/Front/FrontLink.vue';
import { useFront } from '@/composables/useFront';
import type { FrontHeaderZone, FrontMenuItem } from '@/utils/front';

defineOptions({ name: 'HeaderNavItem' });

/**
 * เมนู 1 รายการใน header (recursive) — ระดับแรกเรียงแนวนอน เมนูย่อยกางลงด้านล่าง, ระดับที่ 2 ขึ้นไปเรียงแนวตั้ง
 * เมนูย่อยของระดับนั้นกางออกด้านข้าง (flyout) ตาม docs/PRD-system-frontmenu.md
 *
 * Accessibility (รูปแบบ disclosure navigation): เมนูที่มีเมนูย่อยเป็น <button aria-expanded> เปิด/ปิดด้วยคลิก/Enter/Space,
 * Esc ปิดแล้วคืนโฟกัสที่ปุ่ม, ออกจากกลุ่มด้วย Tab ปิดเอง; เมาส์ชี้เปิดได้ด้วย (desktop)
 */
const props = defineProps<{
    item: FrontMenuItem;
    level: number;
    activeIds: number[];
    zone: FrontHeaderZone;
    /** มีเส้นคั่นหน้าเมนูนี้ (menu_style = divider, ระดับแรก ไม่ใช่รายการแรก) */
    divider?: boolean;
}>();

const { t } = useFront();

const open = ref(false);
const button = ref<HTMLButtonElement | null>(null);
const hasChildren = computed(() => props.item.children.length > 0);
const active = computed(() => props.activeIds.includes(props.item.id));
const submenuId = `front-submenu-${props.item.id}`;

function toggle(): void {
    open.value = !open.value;
}

function close(focusButton = false): void {
    if (!open.value) return;
    open.value = false;
    if (focusButton) void nextTick(() => button.value?.focus());
}

function onFocusOut(event: FocusEvent): void {
    const next = event.relatedTarget as Node | null;
    if (!next || !(event.currentTarget as HTMLElement).contains(next)) close();
}

/** สไตล์ของเมนูระดับแรกตามรูปแบบที่ตั้งไว้ (plain / underline / pill / divider) — ระดับลึกกว่าใช้สไตล์ของกล่องเมนูย่อย */
const topStyle = computed<CSSProperties>(() => {
    const color = active.value ? props.zone.menu_active_color : props.zone.menu_text_color;

    switch (props.zone.menu_style) {
        case 'underline':
            return { color, borderBottom: `2px solid ${active.value ? props.zone.menu_active_color : 'transparent'}` };
        case 'pill':
            return { color, backgroundColor: active.value ? `color-mix(in srgb, ${props.zone.menu_active_color} 18%, transparent)` : undefined };
        default:
            return { color };
    }
});

const itemClass = computed(() =>
    props.level === 0
        ? [
              'inline-flex items-center gap-1 whitespace-nowrap px-3 py-2 text-[15px] font-medium transition-colors hover:opacity-80',
              props.zone.menu_style === 'pill' ? 'rounded-full' : '',
              props.zone.menu_style === 'underline' ? 'border-b-2' : '',
          ]
        : ['flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left text-sm hover:bg-gray-100', active.value ? 'font-semibold text-brand-700' : 'text-gray-800'],
);
</script>

<template>
    <li
        class="relative"
        :class="level === 0 ? 'flex items-center' : ''"
        @mouseenter="hasChildren && (open = true)"
        @mouseleave="hasChildren && close()"
        @focusout="onFocusOut"
        @keydown.esc.stop="close(true)"
    >
        <span v-if="divider" class="mx-1 h-4 w-px opacity-30" :style="{ backgroundColor: zone.menu_text_color }" aria-hidden="true" />

        <button
            v-if="hasChildren"
            ref="button"
            type="button"
            :class="itemClass"
            :style="level === 0 ? topStyle : undefined"
            :aria-expanded="open"
            :aria-controls="submenuId"
            @click="toggle"
        >
            {{ item.name }}
            <component :is="level === 0 ? ChevronDown : ChevronRight" class="size-4 shrink-0 opacity-70 transition-transform" :class="level === 0 && open ? 'rotate-180' : ''" aria-hidden="true" />
        </button>

        <FrontLink
            v-else-if="item.url"
            :href="item.url"
            :target="item.target"
            :class="itemClass"
            :style="level === 0 ? topStyle : undefined"
            :aria-current="activeIds[activeIds.length - 1] === item.id ? 'page' : undefined"
        >
            {{ item.name }}
        </FrontLink>

        <span v-else :class="itemClass" :style="level === 0 ? topStyle : undefined">{{ item.name }}</span>

        <ul
            v-if="hasChildren"
            v-show="open"
            :id="submenuId"
            class="absolute z-40 min-w-56 rounded-lg border border-gray-200 bg-white py-1.5 shadow-lg"
            :class="level === 0 ? 'left-0 top-full' : 'left-full -top-1.5'"
            :aria-label="t('submenu_of', { name: item.name })"
        >
            <HeaderNavItem v-for="child in item.children" :key="child.id" :item="child" :level="level + 1" :active-ids="activeIds" :zone="zone" />
        </ul>
    </li>
</template>
