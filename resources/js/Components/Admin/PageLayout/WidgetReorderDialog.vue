<script setup lang="ts">
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { GripVertical } from 'lucide-vue-next';
import LayoutDialog from './LayoutDialog.vue';
import { displayTitle } from '@/utils/pageLayout';
import type { ColumnData, RowData, WidgetData } from '@/utils/pageLayout';
import { widgetTypeLabel } from '@/utils/pageWidget';
import type { LanguageOption } from '@/types';

interface ColumnEntry {
    column: ColumnData;
    widgets: WidgetData[];
}

/**
 * เรียงลำดับ "Widget" ข้ามคอลัมน์ (และข้ามแถว) ผ่าน dialog เดียว (widget แสดงตัวอย่างจริงที่สูง/ซับซ้อนขึ้นเรื่อย ๆ ลากสลับตรงในหน้าจอยาก) —
 * แสดงกล่องของทุกแถวและทุกคอลัมน์แบบ fix ไว้ (ลำดับแถว/คอลัมน์เปลี่ยนที่นี่ไม่ได้ ใช้ dialog เรียงลำดับของชั้นนั้นแยกต่างหาก) แล้วลากกล่อง widget
 * สลับลำดับหรือย้ายข้ามคอลัมน์/แถวได้ (ทุกคอลัมน์ใช้ vuedraggable group เดียวกัน) ลำดับจริงเปลี่ยนก็ต่อเมื่อกด "ยืนยันลำดับ"
 */
const props = defineProps<{
    show: boolean;
    rows: RowData[];
    languages: LanguageOption[];
}>();

const emit = defineEmits<{
    close: [];
    confirm: [order: { row: RowData; columns: ColumnEntry[] }[]];
}>();

const workingRows = ref<{ row: RowData; columns: ColumnEntry[] }[]>([]);

watch(
    () => props.show,
    (show) => {
        if (show) {
            workingRows.value = props.rows.map((row) => ({
                row,
                columns: row.columns.map((column) => ({ column, widgets: [...column.widgets] })),
            }));
        }
    },
);

function rowTitle(row: RowData): string {
    return displayTitle(row.detail, props.languages, `แถวที่ ${props.rows.indexOf(row) + 1}`);
}

function columnTitle(row: RowData, column: ColumnData): string {
    return displayTitle(column.detail, props.languages, `คอลัมน์ที่ ${row.columns.indexOf(column) + 1}`);
}

function widgetTitle(widgets: WidgetData[], widget: WidgetData): string {
    return displayTitle(widget.detail, props.languages, `Widget #${widgets.indexOf(widget) + 1}`);
}
</script>

<template>
    <LayoutDialog
        :show="show"
        title="เรียงลำดับ Widget"
        description='ลากกล่อง Widget เพื่อสลับลำดับหรือย้ายไปคอลัมน์/แถวอื่นได้ (ลำดับของแถวและคอลัมน์เองไม่เปลี่ยน) แล้วกด "ยืนยันลำดับ" เพื่อใช้ลำดับใหม่'
        confirm-text="ยืนยันลำดับ"
        @close="emit('close')"
        @confirm="emit('confirm', workingRows)"
    >
        <div class="space-y-3">
            <div v-for="rowEntry in workingRows" :key="rowEntry.row._key" class="rounded-lg border-2 border-dashed border-brand-300 bg-brand-50/30 p-3">
                <p class="mb-2 truncate text-xs font-semibold text-brand-700">{{ rowTitle(rowEntry.row) }}</p>

                <div class="grid gap-2 sm:grid-cols-2">
                    <div v-for="colEntry in rowEntry.columns" :key="colEntry.column._key" class="rounded-md border-2 border-dashed border-emerald-400 bg-white/70 p-2">
                        <p class="mb-1.5 truncate text-[11px] font-semibold text-emerald-700">{{ columnTitle(rowEntry.row, colEntry.column) }}</p>

                        <draggable
                            :list="colEntry.widgets"
                            item-key="_key"
                            group="reorder-widgets"
                            handle=".reorder-drag-handle"
                            ghost-class="reorder-drag-ghost"
                            :animation="150"
                            class="min-h-10 space-y-1.5"
                        >
                            <template #item="{ element }">
                                <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-2.5 py-1.5 text-sm">
                                    <button type="button" class="reorder-drag-handle cursor-grab text-gray-400 hover:text-gray-600">
                                        <GripVertical class="size-4" />
                                    </button>
                                    <span class="truncate font-medium text-gray-700">{{ widgetTitle(colEntry.widgets, element) }}</span>
                                    <span class="ml-auto shrink-0 text-xs text-gray-400">{{ widgetTypeLabel(element.widget_type) }}</span>
                                </div>
                            </template>
                            <template #footer>
                                <p v-if="colEntry.widgets.length === 0" class="py-2 text-center text-xs text-gray-400">ไม่มี Widget (ลากมาวางที่นี่ได้)</p>
                            </template>
                        </draggable>
                    </div>

                    <p v-if="rowEntry.columns.length === 0" class="col-span-full py-2 text-center text-xs text-gray-400">ไม่มีคอลัมน์ในแถวนี้</p>
                </div>
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
