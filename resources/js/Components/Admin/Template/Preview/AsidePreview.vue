<script setup lang="ts">
import { computed } from 'vue';
import { ChevronDown, ChevronLeft, ChevronRight, X } from 'lucide-vue-next';
import HeaderTools from '@/Components/Admin/Template/Preview/HeaderTools.vue';
import { zoneBackgroundStyle } from '@/utils/template';
import type { AsideZone, HeaderZone, PreviewMenu, TemplatePreviewData } from '@/utils/template';

/**
 * ตัวอย่างแผงเมนูข้าง (สภาพตอนเปิด) ตามรูปแบบเมนู — list: แสดงทุกระดับเยื้อง / accordion: ขยายกลุ่มแรกที่มีเมนูย่อย /
 * drilldown: แสดงเฉพาะระดับแรกพร้อมลูกศรเข้าเมนูย่อย / large: ระดับแรกตัวใหญ่กึ่งกลาง
 * ด้านล่างเป็นพื้นที่ fix (ไม่เลื่อนตามเมนู) แสดง social / ภาษา / ปรับขนาดตัวอักษร / การแสดงสี ตามที่เปิดไว้ในตั้งค่า header
 * (ไม่มีค้นหา) — เมนูที่ยาวเกินพื้นที่เลื่อนอยู่ในส่วนบน
 */
const props = defineProps<{
    zone: AsideZone;
    header: HeaderZone;
    preview: TemplatePreviewData;
}>();

const hasTools = computed(() =>
    [props.header.social_status, props.header.lang_status, props.header.fontsize_status, props.header.contrast_status].includes('Y'),
);

const SAMPLE: PreviewMenu[] = [
    { id: -1, name: 'หน้าแรก', menu_type: 'page', children: [] },
    { id: -2, name: 'เกี่ยวกับเรา', menu_type: 'page', children: [{ id: -21, name: 'ประวัติ', menu_type: 'page', children: [] }, { id: -22, name: 'วิสัยทัศน์', menu_type: 'page', children: [] }] },
    { id: -3, name: 'ข่าวสาร', menu_type: 'page', children: [] },
    { id: -4, name: 'ติดต่อเรา', menu_type: 'page', children: [] },
];

const items = computed(() => (props.preview.menu.length ? props.preview.menu : SAMPLE));

const expandedId = computed(() => items.value.find((m) => m.children.length)?.id ?? null);

/** list: แปลง tree เป็นรายการแบนพร้อมระดับ */
const flat = computed(() => {
    const result: { id: number; name: string; depth: number }[] = [];
    const walk = (nodes: PreviewMenu[], depth: number) => {
        nodes.forEach((node) => {
            result.push({ id: node.id, name: node.name, depth });
            walk(node.children, depth + 1);
        });
    };
    walk(items.value, 0);

    return result;
});
</script>

<template>
    <div class="flex h-full flex-col" :style="{ ...zoneBackgroundStyle(zone), color: zone.text_color }">
        <div class="flex items-center justify-end px-4 py-3">
            <X class="size-5 opacity-70" />
        </div>

        <div class="min-h-0 flex-1 overflow-hidden px-4 pb-4 text-sm">
            <template v-if="zone.menu_style === 'list'">
                <div
                    v-for="item in flat"
                    :key="item.id"
                    class="border-b border-current/10 py-2"
                    :class="item.depth === 0 ? 'font-medium' : 'opacity-80'"
                    :style="{ paddingLeft: `${item.depth * 16}px` }"
                >
                    {{ item.name }}
                </div>
            </template>

            <template v-else-if="zone.menu_style === 'accordion'">
                <div v-for="item in items" :key="item.id" class="border-b border-current/10">
                    <div class="flex items-center justify-between py-2 font-medium">
                        {{ item.name }}
                        <ChevronDown v-if="item.children.length" class="size-4 transition-transform" :class="item.id === expandedId ? 'rotate-180' : ''" />
                    </div>
                    <div v-if="item.id === expandedId" class="mb-2 space-y-1 rounded-md bg-current/5 px-3 py-2">
                        <div v-for="child in item.children" :key="child.id" class="opacity-80">{{ child.name }}</div>
                    </div>
                </div>
            </template>

            <template v-else-if="zone.menu_style === 'drilldown'">
                <div class="mb-2 flex items-center gap-1 text-xs opacity-60"><ChevronLeft class="size-3.5" /> ย้อนกลับ (เมื่ออยู่ในเมนูย่อย)</div>
                <div v-for="item in items" :key="item.id" class="flex items-center justify-between border-b border-current/10 py-2 font-medium">
                    {{ item.name }}
                    <ChevronRight v-if="item.children.length" class="size-4 opacity-70" />
                </div>
            </template>

            <template v-else>
                <div class="flex flex-col items-center gap-3 py-4">
                    <div v-for="item in items" :key="item.id" class="text-2xl font-semibold">{{ item.name }}</div>
                </div>
            </template>
        </div>

        <!-- เครื่องมือตามตั้งค่า header — fix ไว้ด้านล่าง -->
        <div v-if="hasTools" class="shrink-0 border-t border-current/15 px-4 py-3">
            <HeaderTools :zone="header" :preview="preview" only="aside" />
        </div>
    </div>
</template>
