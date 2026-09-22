<script setup lang="ts">
import { Eye, EyeOff, Home, Pencil, Trash2 } from 'lucide-vue-next';
import { defaultMenuName } from '@/utils/frontMenu';
import type { FrontMenuNode } from '@/types';

/**
 * แถวเมนู 1 รายการแบบ tree (recursive) — เยื้องตามระดับด้วยเส้นคั่นซ้าย เทียบเคียง PermissionTreeNode.vue
 * ไม่มีลากจัดเรียงในรายการนี้ (การเรียงลำดับ/ย้าย parent ทำผ่าน MenuReorderDialog เท่านั้น ตามสเปก)
 */
const props = defineProps<{
    node: FrontMenuNode;
    depth: number;
    menuTypes: Record<string, string>;
    canManage: boolean;
    canDelete: boolean;
}>();

const emit = defineEmits<{
    edit: [node: FrontMenuNode];
    toggleStatus: [node: FrontMenuNode];
    remove: [node: FrontMenuNode];
}>();
</script>

<template>
    <div>
        <div
            class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm hover:bg-gray-50"
            :class="node.status === 'N' ? 'opacity-50' : ''"
            :style="{ paddingLeft: `${depth * 20 + 8}px` }"
        >
            <Home v-if="node.is_home === 'Y'" class="size-4 shrink-0 text-amber-500" title="หน้าหลัก" />

            <button
                type="button"
                class="min-w-0 flex-1 truncate text-left font-medium text-gray-700 hover:text-brand-600"
                @click="emit('edit', node)"
            >
                {{ defaultMenuName(node) }}
            </button>

            <span class="shrink-0 rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ menuTypes[node.menu_type] ?? node.menu_type }}</span>
            <span v-if="node.target_label" class="hidden shrink-0 truncate text-xs text-gray-400 sm:inline">→ {{ node.target_label }}</span>

            <div class="ml-auto flex shrink-0 items-center gap-1">
                <button
                    v-if="canManage"
                    type="button"
                    class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    title="แก้ไข"
                    @click="emit('edit', node)"
                >
                    <Pencil class="size-4" />
                </button>
                <button
                    v-if="canManage"
                    type="button"
                    class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    :title="node.status === 'Y' ? 'ซ่อนเมนู' : 'แสดงเมนู'"
                    @click="emit('toggleStatus', node)"
                >
                    <Eye v-if="node.status === 'Y'" class="size-4" />
                    <EyeOff v-else class="size-4" />
                </button>
                <button
                    v-if="canDelete"
                    type="button"
                    class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-30"
                    :title="node.is_home === 'Y' ? 'ลบไม่ได้ — เมนูนี้ตั้งเป็นหน้าหลักอยู่' : 'ลบ (ลบได้เฉพาะเมนูที่ไม่มีเมนูลูก)'"
                    :disabled="node.children.length > 0 || node.is_home === 'Y'"
                    @click="emit('remove', node)"
                >
                    <Trash2 class="size-4" />
                </button>
            </div>
        </div>

        <MenuTreeNode
            v-for="child in node.children"
            :key="child.id"
            :node="child"
            :depth="depth + 1"
            :menu-types="menuTypes"
            :can-manage="canManage"
            :can-delete="canDelete"
            @edit="(n) => emit('edit', n)"
            @toggle-status="(n) => emit('toggleStatus', n)"
            @remove="(n) => emit('remove', n)"
        />
    </div>
</template>
