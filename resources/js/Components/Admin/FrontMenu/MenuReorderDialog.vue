<script setup lang="ts">
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';
import LayoutDialog from '@/Components/Admin/PageLayout/LayoutDialog.vue';
import MenuReorderNode from './MenuReorderNode.vue';
import { cloneDeep } from '@/utils/pageLayout';
import { flattenForReorder } from '@/utils/frontMenu';
import type { ReorderItem } from '@/utils/frontMenu';
import type { FrontMenuNode } from '@/types';

/**
 * เรียงลำดับ/ย้ายเมนูทั้ง tree ผ่าน dialog เดียว — ทุกรายการลากได้ แต่วางได้เฉพาะใต้เมนูประเภท "เมนูหัวข้อ"
 * (MenuReorderNode ไม่ render กล่องลูกให้ประเภทอื่น) แก้ไขบนสำเนา (workingTree) เท่านั้น ยืนยันแล้วค่อย emit
 * รายการแบบแบน {id, parent_id, sort_order} กลับไปให้หน้าเรียกบันทึกจริง
 */
const props = defineProps<{
    show: boolean;
    tree: FrontMenuNode[];
}>();

const emit = defineEmits<{
    close: [];
    confirm: [order: ReorderItem[]];
}>();

const workingTree = ref<FrontMenuNode[]>([]);

watch(
    () => props.show,
    (show) => {
        if (show) {
            workingTree.value = cloneDeep(props.tree);
        }
    },
);

function confirm() {
    emit('confirm', flattenForReorder(workingTree.value));
}
</script>

<template>
    <LayoutDialog
        :show="show"
        size="md"
        title="เรียงลำดับเมนู"
        description='ลากเพื่อสลับลำดับหรือย้ายเข้า/ออกจากเมนูหัวข้อ แล้วกด "ยืนยันลำดับ" เพื่อใช้ลำดับใหม่'
        confirm-text="ยืนยันลำดับ"
        @close="emit('close')"
        @confirm="confirm"
    >
        <draggable
            :list="workingTree"
            group="menu-reorder"
            item-key="id"
            handle=".reorder-drag-handle"
            ghost-class="reorder-drag-ghost"
            :animation="150"
            class="space-y-1.5"
        >
            <template #item="{ element }">
                <MenuReorderNode :node="element" :depth="0" />
            </template>
        </draggable>

        <p v-if="workingTree.length === 0" class="py-6 text-center text-sm text-gray-400">ยังไม่มีเมนู</p>
    </LayoutDialog>
</template>

<style scoped>
.reorder-drag-ghost {
    opacity: 0.4;
    background-color: #eff6ff;
    border: 2px dashed #93c5fd;
}
</style>
