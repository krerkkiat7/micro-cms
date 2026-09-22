<script setup lang="ts">
import draggable from 'vuedraggable';
import { GripVertical } from 'lucide-vue-next';
import { defaultMenuName, FrontMenuType } from '@/utils/frontMenu';
import type { FrontMenuNode } from '@/types';

/**
 * แถวเมนู 1 รายการใน dialog เรียงลำดับ (recursive) — เฉพาะเมนูประเภท "เมนูหัวข้อ" เท่านั้นที่มีกล่องลูก
 * ให้ลากมาวางได้ (ประเภทอื่นไม่มี <draggable> ของลูกเลย จึงวางไม่ได้โดยธรรมชาติ ไม่ต้องเช็ก :move เพิ่ม)
 * `:move` กันกรณีลากเมนูหัวข้อไปวางใต้เมนูลูกของตัวเอง (จะเกิด cycle)
 */
const props = defineProps<{
    node: FrontMenuNode;
    depth: number;
}>();

function checkMove(evt: { to: HTMLElement; draggedContext: { element: FrontMenuNode } }): boolean {
    const ownerId = evt.to.dataset.ownerId ? Number(evt.to.dataset.ownerId) : null;
    if (ownerId === null) return true;

    return !isDescendant(evt.draggedContext.element, ownerId);
}

function isDescendant(node: FrontMenuNode, targetId: number): boolean {
    return node.children.some((child) => child.id === targetId || isDescendant(child, targetId));
}
</script>

<template>
    <div class="rounded-md border border-gray-200 bg-white">
        <div class="flex items-center gap-2 px-3 py-2 text-sm" :style="{ paddingLeft: `${depth * 16 + 12}px` }">
            <button type="button" class="reorder-drag-handle cursor-grab text-gray-400 hover:text-gray-600">
                <GripVertical class="size-4" />
            </button>
            <span class="truncate font-medium text-gray-700">{{ defaultMenuName(node) }}</span>
        </div>

        <draggable
            v-if="node.menu_type === FrontMenuType.HEADING"
            :list="node.children"
            :data-owner-id="node.id"
            group="menu-reorder"
            item-key="id"
            handle=".reorder-drag-handle"
            ghost-class="reorder-drag-ghost"
            :animation="150"
            :move="checkMove"
            class="space-y-1.5 border-t border-dashed border-gray-200 p-2"
        >
            <template #item="{ element }">
                <MenuReorderNode :node="element" :depth="depth + 1" />
            </template>
        </draggable>
    </div>
</template>
