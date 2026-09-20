<script setup lang="ts">
import { computed } from 'vue';
import draggable from 'vuedraggable';
import LayoutToolbar from './LayoutToolbar.vue';
import WidgetBlock from './WidgetBlock.vue';
import { usePageLayoutEditor } from '@/composables/usePageLayoutEditor';
import { backgroundStyle, displayTitle } from '@/utils/pageLayout';
import type { ColumnData, RowData } from '@/utils/pageLayout';

/**
 * คอลัมน์ในแถว — กรอบเส้นปะ + พื้นหลังตามการตั้งค่า; widget ข้างในลากสลับลำดับได้ตรงนี้เลย และลากย้ายข้ามคอลัมน์ได้
 * (ทุกคอลัมน์ใช้ group เดียวกัน "page-widgets") ความกว้างของคอลัมน์กำหนดโดย RowBlock ผ่าน grid 12
 */
const props = defineProps<{
    column: ColumnData;
    row: RowData;
    index: number;
}>();

const editor = usePageLayoutEditor();

const title = computed(() => displayTitle(props.column.detail, editor.languages, `คอลัมน์ที่ ${props.index + 1}`));
</script>

<template>
    <div
        class="relative flex h-full flex-col rounded-md border border-dashed border-emerald-500 pt-9"
        :class="column.status === 'N' ? 'opacity-50' : ''"
        :style="backgroundStyle(column)"
    >
        <LayoutToolbar
            kind="column"
            :title="title"
            :hidden="column.status === 'N'"
            add-label="เพิ่ม Widget"
            :readonly="editor.readonly"
            @settings="editor.editColumn(row, column)"
            @toggle="editor.toggleStatus(column)"
            @add="editor.addWidget(column)"
        />

        <span class="absolute right-2 top-2 rounded bg-emerald-600/90 px-1.5 py-0.5 text-[10px] font-semibold text-white">
            {{ column.column_size }}/12
        </span>

        <draggable
            v-model="column.widgets"
            item-key="_key"
            group="page-widgets"
            handle=".layout-drag-handle-widget"
            ghost-class="layout-drag-ghost"
            :animation="150"
            :disabled="editor.readonly"
            class="min-h-16 flex-1 space-y-2 px-2 pb-2"
        >
            <template #item="{ element }">
                <WidgetBlock :widget="element" :column="column" />
            </template>
            <template #footer>
                <p v-if="column.widgets.length === 0" class="py-3 text-center text-xs text-gray-400">ยังไม่มี Widget</p>
            </template>
        </draggable>
    </div>
</template>
