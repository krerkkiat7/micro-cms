<script setup lang="ts">
import { computed } from 'vue';
import { ChevronDown } from 'lucide-vue-next';
import type { HeaderZone, PreviewMenu } from '@/utils/template';

/**
 * ตัวอย่างเมนูแนวนอนใน header ตามรูปแบบ (menu_style) — เมนูแรกถือเป็นเมนูที่เลือกอยู่ (active), เมนูที่มีเมนูย่อยมีลูกศรลง
 * ไม่มีเมนูในระบบ = แสดงเมนูสมมติให้เห็นรูปแบบ
 */
const props = defineProps<{
    items: PreviewMenu[];
    menuStyle: HeaderZone['menu_style'];
    textColor: string;
    activeColor: string;
}>();

const SAMPLE: PreviewMenu[] = ['หน้าแรก', 'เกี่ยวกับเรา', 'ข่าวสาร', 'ติดต่อเรา'].map((name, i) => ({ id: -i - 1, name, children: [] }));

const menus = computed(() => (props.items.length ? props.items : SAMPLE));

function itemStyle(index: number) {
    const active = index === 0;

    switch (props.menuStyle) {
        case 'underline':
            return { color: active ? props.activeColor : props.textColor, borderBottom: `2px solid ${active ? props.activeColor : 'transparent'}` };
        case 'pill':
            return {
                color: active ? props.activeColor : props.textColor,
                backgroundColor: active ? `color-mix(in srgb, ${props.activeColor} 22%, transparent)` : undefined,
            };
        default:
            return { color: active ? props.activeColor : props.textColor };
    }
}
</script>

<template>
    <nav class="flex flex-wrap items-center" :class="menuStyle === 'pill' ? 'gap-1' : 'gap-0'">
        <template v-for="(menu, index) in menus" :key="menu.id">
            <span v-if="menuStyle === 'divider' && index > 0" class="h-4 w-px opacity-30" :style="{ backgroundColor: textColor }" />
            <span
                class="inline-flex items-center gap-0.5 whitespace-nowrap px-3 py-1.5 text-sm font-medium"
                :class="menuStyle === 'pill' ? 'rounded-full' : ''"
                :style="itemStyle(index)"
            >
                {{ menu.name || '(ไม่มีชื่อ)' }}
                <ChevronDown v-if="menu.children.length" class="size-3.5 opacity-70" />
            </span>
        </template>
    </nav>
</template>
