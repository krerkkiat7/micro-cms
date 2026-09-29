<script setup lang="ts">
import { computed } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import type { PopupMenuNode } from '@/utils/popupForm';

/**
 * แถวเมนูหน้าบ้าน 1 รายการแบบ tree (recursive) สำหรับเลือกเมนูที่แสดง popup — checkbox แสดงเฉพาะเมนูที่ผูกกับ
 * โมดูลเนื้อหา (`selectable`) เมนูประเภทอื่นแสดงเป็นชื่อเฉย ๆ ให้เห็นโครง tree
 */
const props = defineProps<{
    node: PopupMenuNode;
    depth: number;
}>();

const selected = defineModel<number[]>({ required: true });

const TYPE_LABELS: Record<string, string> = {
    heading: 'เมนูหัวข้อ',
    none: 'ไม่กำหนด',
    external: 'ลิงค์ภายนอก',
    article_category: 'หมวดหมู่บทความ',
    article_item: 'บทความ',
    page: 'หน้าเพจ',
    contactus: 'ติดต่อเรา',
};

const checked = computed({
    get: () => selected.value.includes(props.node.id),
    set: (value: boolean) => {
        const others = selected.value.filter((id) => id !== props.node.id);
        selected.value = value ? [...others, props.node.id] : others;
    },
});
</script>

<template>
    <div>
        <component
            :is="node.selectable ? 'label' : 'div'"
            class="flex items-center gap-2 rounded-lg py-1.5 pr-2 text-sm"
            :class="node.selectable ? 'cursor-pointer hover:bg-gray-50' : ''"
            :style="{ paddingLeft: `${depth * 20 + 8}px` }"
        >
            <Checkbox v-if="node.selectable" v-model:checked="checked" />
            <span v-else class="inline-block size-4 shrink-0" aria-hidden="true" />
            <span class="min-w-0 truncate" :class="node.selectable ? 'text-gray-700' : 'font-medium text-gray-500'">
                {{ node.name || '(ไม่มีชื่อ)' }}
            </span>
            <span class="shrink-0 rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ TYPE_LABELS[node.menu_type] ?? node.menu_type }}</span>
            <span v-if="node.status === 'N'" class="shrink-0 rounded bg-amber-50 px-2 py-0.5 text-xs text-amber-700">ปิดใช้งาน</span>
        </component>

        <PopupMenuTreeNode v-for="child in node.children" :key="child.id" v-model="selected" :node="child" :depth="depth + 1" />
    </div>
</template>
