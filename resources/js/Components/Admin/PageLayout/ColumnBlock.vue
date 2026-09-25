<script setup lang="ts">
import { computed } from 'vue';
import LayoutToolbar from './LayoutToolbar.vue';
import LayoutTexts from './LayoutTexts.vue';
import WidgetBlock from './WidgetBlock.vue';
import { usePageLayoutEditor } from '@/composables/usePageLayoutEditor';
import { backgroundStyle, displayTitle, paddingStyle } from '@/utils/pageLayout';
import type { ColumnData, RowData } from '@/utils/pageLayout';

/**
 * คอลัมน์ในแถว — กรอบเส้นปะ + พื้นหลังตามการตั้งค่า; widget ข้างในเรียงลำดับ/ย้ายข้ามคอลัมน์ผ่าน dialog "เรียงลำดับ Widget" เท่านั้น
 * (ดู WidgetReorderDialog.vue — ไม่มีการลากสลับตรงในหน้าจอนี้อีกแล้ว เพราะ widget แสดงตัวอย่างจริงที่สูง/ซับซ้อนขึ้นเรื่อย ๆ ลากยาก);
 * เมื่อเปิด "แสดงหัวเรื่อง" จะแสดงหัวเรื่อง (h3) / หัวเรื่องรอง / ข้อความเกริ่นนำเหนือ widget ความกว้างของคอลัมน์กำหนดโดย RowBlock ผ่าน grid 12
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
            @reorder="editor.reorderColumns()"
            @settings="editor.editColumn(row, column)"
            @toggle="editor.toggleStatus(column)"
            @remove="editor.removeColumn(row, column)"
            @add="editor.addWidget(column)"
        />

        <span class="absolute right-2 top-2 rounded bg-emerald-600/90 px-1.5 py-0.5 text-[10px] font-semibold text-white">
            {{ column.column_size }}/12
        </span>

        <!-- ระยะขอบด้านในตามที่ตั้งค่า (เว้นเพิ่มจากกรอบของหน้าจอแก้ไข) -->
        <div class="flex flex-1 flex-col" :style="paddingStyle(column)">
            <LayoutTexts
                level="column"
                :show="column.show_title === 'Y'"
                :detail="column.detail"
                :styles="column"
                :languages="editor.languages"
                class="px-3 pb-2 pt-1"
            />

            <div class="min-h-16 flex-1 space-y-2 px-2 pb-2">
                <WidgetBlock v-for="widget in column.widgets" :key="widget._key" :widget="widget" :column="column" />
                <p v-if="column.widgets.length === 0" class="py-3 text-center text-xs text-gray-400">ยังไม่มี Widget</p>
            </div>
        </div>
    </div>
</template>
