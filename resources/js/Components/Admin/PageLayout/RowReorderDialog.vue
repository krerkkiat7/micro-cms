<script setup lang="ts">
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { GripVertical } from 'lucide-vue-next';
import LayoutDialog from './LayoutDialog.vue';
import { displayTitle } from '@/utils/pageLayout';
import type { RowData } from '@/utils/pageLayout';
import type { LanguageOption } from '@/types';

/**
 * เรียงลำดับ "แถว" ผ่าน dialog (แถวสูงและมีเนื้อหาเยอะ ลากสลับตรงในหน้าจอยาก) — เทียบเคียง ArticlePart/PartReorderDialog:
 * แสดงรายการแถวแบบย่อ (ลำดับ + ชื่อ + ความกว้างคอลัมน์ข้างใน) ลากสลับได้ ลำดับจริงเปลี่ยนก็ต่อเมื่อกด "ยืนยันลำดับ"
 */
const props = defineProps<{
    show: boolean;
    rows: RowData[];
    languages: LanguageOption[];
}>();

const emit = defineEmits<{
    close: [];
    confirm: [order: RowData[]];
}>();

const workingOrder = ref<RowData[]>([]);

watch(
    () => props.show,
    (show) => {
        if (show) {
            workingOrder.value = [...props.rows];
        }
    },
);

function summary(row: RowData): string {
    return row.columns.length > 0 ? row.columns.map((c) => c.column_size).join(' + ') : 'ไม่มีคอลัมน์';
}

function title(row: RowData): string {
    return displayTitle(row.detail, props.languages, `แถวที่ ${props.rows.indexOf(row) + 1}`);
}
</script>

<template>
    <LayoutDialog
        :show="show"
        size="md"
        title="เรียงลำดับแถว"
        description='ลากเพื่อสลับลำดับ แล้วกด "ยืนยันลำดับ" เพื่อใช้ลำดับใหม่'
        confirm-text="ยืนยันลำดับ"
        @close="emit('close')"
        @confirm="emit('confirm', workingOrder)"
    >
        <draggable
            :list="workingOrder"
            item-key="_key"
            handle=".reorder-drag-handle"
            ghost-class="reorder-drag-ghost"
            :animation="150"
            class="space-y-1.5"
        >
            <template #item="{ element }">
                <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm">
                    <button type="button" class="reorder-drag-handle cursor-grab text-gray-400 hover:text-gray-600">
                        <GripVertical class="size-4" />
                    </button>
                    <span class="truncate font-medium text-gray-700">{{ title(element) }}</span>
                    <span class="ml-auto shrink-0 text-xs text-gray-400">{{ summary(element) }}</span>
                </div>
            </template>
        </draggable>

        <p v-if="workingOrder.length === 0" class="py-6 text-center text-sm text-gray-400">ยังไม่มีแถว</p>
    </LayoutDialog>
</template>

<style scoped>
/* placeholder ที่ตำแหน่งที่จะวาง ให้เห็นขอบเขตชัดเจนระหว่างลาก แยกจากรายการที่กำลังถูกลากอยู่ */
.reorder-drag-ghost {
    opacity: 0.4;
    background-color: #eff6ff;
    border: 2px dashed #93c5fd;
}
</style>
