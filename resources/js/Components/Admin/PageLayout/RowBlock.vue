<script setup lang="ts">
import { computed } from 'vue';
import draggable from 'vuedraggable';
import LayoutToolbar from './LayoutToolbar.vue';
import ColumnBlock from './ColumnBlock.vue';
import { usePageLayoutEditor } from '@/composables/usePageLayoutEditor';
import { backgroundStyle, displayTitle } from '@/utils/pageLayout';
import type { RowData } from '@/utils/pageLayout';

/**
 * แถวในหน้าเพจ — กรอบเส้นปะให้เห็นขอบเขต + พื้นหลังตามการตั้งค่า; คอลัมน์ข้างในเรียงเป็น grid 12 ตามความกว้างของแต่ละคอลัมน์
 * และลากสลับลำดับได้ในแถวนี้เลย (เมื่อ "ใช้ container" จะจำกัดความกว้างเนื้อหาไว้ตรงกลางเหมือนที่หน้าบ้านจะแสดง)
 */
const props = defineProps<{
    row: RowData;
    index: number;
}>();

const editor = usePageLayoutEditor();

const title = computed(() => displayTitle(props.row.detail, editor.languages, `แถวที่ ${props.index + 1}`));
const columnTotal = computed(() => props.row.columns.reduce((sum, c) => sum + c.column_size, 0));

function span(size: number): { gridColumn: string } {
    return { gridColumn: `span ${size} / span ${size}` };
}
</script>

<template>
    <section
        class="relative rounded-lg border-2 border-dashed border-brand-400 bg-white pt-9"
        :class="row.status === 'N' ? 'opacity-50' : ''"
        :style="backgroundStyle(row)"
    >
        <LayoutToolbar
            kind="row"
            :title="title"
            :hidden="row.status === 'N'"
            add-label="เพิ่มคอลัมน์"
            :readonly="editor.readonly"
            @reorder="editor.reorderRows()"
            @settings="editor.editRow(row)"
            @toggle="editor.toggleStatus(row)"
            @add="editor.addColumn(row)"
        />

        <span
            v-if="columnTotal > 12"
            class="absolute right-2 top-2 rounded bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-800"
            title="ผลรวมความกว้างคอลัมน์เกิน 12 คอลัมน์ที่เกินจะตกลงบรรทัดใหม่"
        >
            ผลรวมความกว้าง {{ columnTotal }}/12 — คอลัมน์ที่เกินจะขึ้นบรรทัดใหม่
        </span>

        <div class="px-2 pb-2">
            <div :class="row.use_container === 'Y' ? 'mx-auto max-w-5xl' : ''">
                <draggable
                    v-model="row.columns"
                    item-key="_key"
                    handle=".layout-drag-handle-column"
                    ghost-class="layout-drag-ghost"
                    :animation="150"
                    :disabled="editor.readonly"
                    class="grid grid-cols-12 gap-3"
                >
                    <template #item="{ element, index: columnIndex }">
                        <div class="min-w-0" :style="span(element.column_size)">
                            <ColumnBlock :column="element" :row="row" :index="columnIndex" />
                        </div>
                    </template>
                    <template #footer>
                        <p v-if="row.columns.length === 0" class="col-span-12 py-4 text-center text-xs text-gray-400">
                            ยังไม่มีคอลัมน์ — กด "เพิ่มคอลัมน์" ที่แถบด้านบน
                        </p>
                    </template>
                </draggable>
            </div>
        </div>
    </section>
</template>
