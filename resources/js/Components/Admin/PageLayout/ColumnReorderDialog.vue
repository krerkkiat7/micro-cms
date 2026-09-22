<script setup lang="ts">
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { GripVertical } from 'lucide-vue-next';
import LayoutDialog from './LayoutDialog.vue';
import { displayTitle } from '@/utils/pageLayout';
import type { ColumnData, RowData } from '@/utils/pageLayout';
import type { LanguageOption } from '@/types';

/**
 * เรียงลำดับ "คอลัมน์" ข้ามแถวผ่าน dialog เดียว (widget แสดงตัวอย่างจริงทำให้คอลัมน์สูง/ซับซ้อนขึ้นเรื่อย ๆ ลากสลับตรงในหน้าจอยาก
 * โดยเฉพาะย้ายข้ามแถว) — แสดงกล่องของทุกแถวแบบ fix ไว้ (ลำดับแถวเปลี่ยนที่นี่ไม่ได้ ใช้ "เรียงลำดับแถว" แยกต่างหาก) แล้วลากกล่องคอลัมน์
 * สลับลำดับหรือย้ายข้ามแถวได้ (ทุกแถวใช้ vuedraggable group เดียวกัน) ลำดับจริงเปลี่ยนก็ต่อเมื่อกด "ยืนยันลำดับ"
 */
const props = defineProps<{
    show: boolean;
    rows: RowData[];
    languages: LanguageOption[];
}>();

const emit = defineEmits<{
    close: [];
    confirm: [order: { row: RowData; columns: ColumnData[] }[]];
}>();

const workingRows = ref<{ row: RowData; columns: ColumnData[] }[]>([]);

watch(
    () => props.show,
    (show) => {
        if (show) {
            workingRows.value = props.rows.map((row) => ({ row, columns: [...row.columns] }));
        }
    },
);

function rowTitle(row: RowData): string {
    return displayTitle(row.detail, props.languages, `แถวที่ ${props.rows.indexOf(row) + 1}`);
}

function columnTitle(columns: ColumnData[], column: ColumnData): string {
    return displayTitle(column.detail, props.languages, `คอลัมน์ที่ ${columns.indexOf(column) + 1}`);
}
</script>

<template>
    <LayoutDialog
        :show="show"
        title="เรียงลำดับคอลัมน์"
        description='ลากกล่องคอลัมน์เพื่อสลับลำดับหรือย้ายไปแถวอื่นได้ (ลำดับของแถวเองไม่เปลี่ยน) แล้วกด "ยืนยันลำดับ" เพื่อใช้ลำดับใหม่'
        confirm-text="ยืนยันลำดับ"
        @close="emit('close')"
        @confirm="emit('confirm', workingRows)"
    >
        <div class="space-y-3">
            <div v-for="entry in workingRows" :key="entry.row._key" class="rounded-lg border-2 border-dashed border-brand-300 bg-brand-50/30 p-3">
                <p class="mb-2 truncate text-xs font-semibold text-brand-700">{{ rowTitle(entry.row) }}</p>

                <draggable
                    :list="entry.columns"
                    item-key="_key"
                    group="reorder-columns"
                    handle=".reorder-drag-handle"
                    ghost-class="reorder-drag-ghost"
                    :animation="150"
                    class="grid min-h-12 gap-1.5 sm:grid-cols-2"
                >
                    <template #item="{ element }">
                        <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm">
                            <button type="button" class="reorder-drag-handle cursor-grab text-gray-400 hover:text-gray-600">
                                <GripVertical class="size-4" />
                            </button>
                            <span class="truncate font-medium text-gray-700">{{ columnTitle(entry.columns, element) }}</span>
                            <span class="ml-auto shrink-0 text-xs text-gray-400">{{ element.column_size }}/12</span>
                        </div>
                    </template>
                    <template #footer>
                        <p v-if="entry.columns.length === 0" class="col-span-full py-3 text-center text-xs text-gray-400">
                            ไม่มีคอลัมน์ (ลากมาวางที่นี่ได้)
                        </p>
                    </template>
                </draggable>
            </div>

            <p v-if="workingRows.length === 0" class="py-6 text-center text-sm text-gray-400">ยังไม่มีแถว</p>
        </div>
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
