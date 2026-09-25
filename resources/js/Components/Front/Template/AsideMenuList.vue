<script setup lang="ts">
import { ref } from 'vue';
import { ChevronDown } from 'lucide-vue-next';
import FrontLink from '@/Components/Front/FrontLink.vue';
import type { FrontMenuItem } from '@/utils/front';

defineOptions({ name: 'AsideMenuList' });

/**
 * รายการเมนูในแผงเมนูข้าง (recursive) — รูปแบบ list (แสดงทุกระดับเยื้อง), accordion (กดขยายเมนูย่อย — disclosure button),
 * large (ระดับแรกตัวใหญ่ เมนูย่อยตัวเล็กใต้แต่ละรายการ) — drilldown แยกไปอยู่ใน FrontAside.vue
 */
const props = defineProps<{
    items: FrontMenuItem[];
    mode: 'list' | 'accordion' | 'large';
    level: number;
    activeIds: number[];
}>();

const emit = defineEmits<{ navigate: [] }>();

// accordion: เปิดกลุ่มที่มีเมนูของหน้าปัจจุบันไว้ตั้งแต่แรก
const expanded = ref<number[]>(props.items.filter((item) => props.activeIds.includes(item.id) && item.children.length).map((item) => item.id));

function toggle(id: number): void {
    expanded.value = expanded.value.includes(id) ? expanded.value.filter((x) => x !== id) : [...expanded.value, id];
}

function itemClass(item: FrontMenuItem): string {
    const active = props.activeIds.includes(item.id) ? 'font-semibold underline underline-offset-4' : '';

    if (props.mode === 'large' && props.level === 0) {
        return `block py-2 text-2xl font-semibold ${active}`;
    }

    return `flex w-full items-center justify-between gap-2 py-2.5 text-left ${props.level === 0 ? 'font-medium' : 'text-[0.95em]'} ${active}`;
}
</script>

<template>
    <ul :class="[mode === 'large' && level === 0 ? 'flex flex-col items-center gap-2 text-center' : '', level > 0 ? 'ml-4 border-l border-current/15 pl-3' : '']">
        <li v-for="item in items" :key="item.id" :class="mode !== 'large' && level === 0 ? 'border-b border-current/10' : ''">
            <!-- accordion: เมนูที่มีเมนูย่อยเป็นปุ่มขยาย -->
            <template v-if="mode === 'accordion' && item.children.length">
                <button
                    type="button"
                    :class="itemClass(item)"
                    :aria-expanded="expanded.includes(item.id)"
                    :aria-controls="`front-aside-sub-${item.id}`"
                    @click="toggle(item.id)"
                >
                    {{ item.name }}
                    <ChevronDown class="size-4 shrink-0 transition-transform" :class="expanded.includes(item.id) ? 'rotate-180' : ''" aria-hidden="true" />
                </button>
                <div v-show="expanded.includes(item.id)" :id="`front-aside-sub-${item.id}`" class="pb-2">
                    <AsideMenuList :items="item.children" :mode="mode" :level="level + 1" :active-ids="activeIds" @navigate="emit('navigate')" />
                </div>
            </template>

            <template v-else>
                <FrontLink
                    v-if="item.url"
                    :href="item.url"
                    :target="item.target"
                    :class="itemClass(item)"
                    :aria-current="activeIds[activeIds.length - 1] === item.id ? 'page' : undefined"
                    @click="emit('navigate')"
                >
                    {{ item.name }}
                </FrontLink>
                <span v-else :class="itemClass(item)">{{ item.name }}</span>

                <AsideMenuList
                    v-if="item.children.length"
                    :items="item.children"
                    :mode="mode"
                    :level="level + 1"
                    :active-ids="activeIds"
                    :class="mode === 'large' ? 'mb-2 !ml-0 !border-0 !pl-0 text-base opacity-90' : ''"
                    @navigate="emit('navigate')"
                />
            </template>
        </li>
    </ul>
</template>
