<script setup lang="ts">
import { computed } from 'vue';
import LayoutToolbar from './LayoutToolbar.vue';
import ColumnBlock from './ColumnBlock.vue';
import LayoutTexts from './LayoutTexts.vue';
import { usePageLayoutEditor } from '@/composables/usePageLayoutEditor';
import { backgroundStyle, displayTitle } from '@/utils/pageLayout';
import type { RowData } from '@/utils/pageLayout';

/**
 * แถวในหน้าเพจ — กรอบเส้นปะให้เห็นขอบเขต + พื้นหลังตามการตั้งค่า; คอลัมน์ข้างในเรียงเป็น grid 12 ตามความกว้างของแต่ละคอลัมน์
 * (เรียงลำดับ/ย้ายคอลัมน์ข้ามแถวผ่าน dialog "เรียงลำดับคอลัมน์" เท่านั้น ไม่มีการลากสลับตรงในหน้าจอนี้อีกแล้ว — ดู ColumnReorderDialog.vue);
 * เมื่อเปิด "แสดงหัวเรื่อง" จะแสดงหัวเรื่อง (h2) / หัวเรื่องรอง / ข้อความเกริ่นนำเหนือคอลัมน์ตามที่ตั้งค่าไว้ (เมื่อ "ใช้ container" จะจำกัดความกว้างเนื้อหาไว้ตรงกลางเหมือนที่หน้าบ้านจะแสดง)
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
            @remove="editor.removeRow(row)"
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
                <LayoutTexts
                    level="row"
                    :show="row.show_title === 'Y'"
                    :detail="row.detail"
                    :styles="row"
                    :languages="editor.languages"
                    class="px-2 pb-3 pt-1"
                />

                <div class="grid grid-cols-12 gap-3">
                    <div v-for="(column, columnIndex) in row.columns" :key="column._key" class="min-w-0" :style="span(column.column_size)">
                        <ColumnBlock :column="column" :row="row" :index="columnIndex" />
                    </div>
                    <p v-if="row.columns.length === 0" class="col-span-12 py-4 text-center text-xs text-gray-400">
                        ยังไม่มีคอลัมน์ — กด "เพิ่มคอลัมน์" ที่แถบด้านบน
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
