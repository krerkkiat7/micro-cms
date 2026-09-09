<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import type { ActionNode } from '@/types';

const props = defineProps<{
    node: ActionNode;
    /** parent ของ node นี้ถูกเลือกอยู่ไหม (root = true เสมอ) */
    parentSelected: boolean;
    readOnly: boolean;
    isSelected: (id: string) => boolean;
}>();

const emit = defineEmits<{
    toggle: [node: ActionNode, checked: boolean];
}>();

function disabled(): boolean {
    return props.readOnly || !props.parentSelected;
}
</script>

<template>
    <div>
        <label
            class="flex items-center gap-2 py-1 text-sm"
            :class="disabled() ? 'text-gray-400' : 'text-gray-700'"
        >
            <Checkbox
                :checked="isSelected(node.id)"
                :disabled="disabled()"
                @update:checked="(v: boolean) => emit('toggle', node, v)"
            />
            <span>{{ node.name }}</span>
            <code class="text-xs text-gray-400">{{ node.code }}</code>
        </label>

        <div
            v-if="node.children.length"
            class="ml-5 border-l border-gray-200 pl-3"
        >
            <PermissionTreeNode
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :parent-selected="isSelected(node.id)"
                :read-only="readOnly"
                :is-selected="isSelected"
                @toggle="(n: ActionNode, v: boolean) => emit('toggle', n, v)"
            />
        </div>
    </div>
</template>
