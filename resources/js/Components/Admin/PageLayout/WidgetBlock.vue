<script setup lang="ts">
import { computed } from 'vue';
import LayoutToolbar from './LayoutToolbar.vue';
import { usePageLayoutEditor } from '@/composables/usePageLayoutEditor';
import { displayIntro, displayTitle, widgetTypeLabel } from '@/utils/pageLayout';
import type { ColumnData, WidgetData } from '@/utils/pageLayout';

/**
 * widget ในคอลัมน์ — การ์ดจำลองการแสดงผล (ชนิด + ข้อความเกริ่นนำย่อ) พร้อมแถบจัดการมุมซ้ายบน
 * ประเภท widget จริงยังรอกำหนด ตอนนี้แสดงเป็นการ์ดตัวแทนเท่านั้น
 */
const props = defineProps<{
    widget: WidgetData;
    column: ColumnData;
}>();

const editor = usePageLayoutEditor();

const title = computed(() => displayTitle(props.widget.detail, editor.languages, '(ไม่มีชื่อ)'));
const intro = computed(() => displayIntro(props.widget.detail, editor.languages));
</script>

<template>
    <div
        class="relative rounded-md border border-amber-300 bg-white px-3 pb-3 pt-9 shadow-xs"
        :class="widget.status === 'N' ? 'opacity-50' : ''"
    >
        <LayoutToolbar
            kind="widget"
            :title="title"
            :hidden="widget.status === 'N'"
            :readonly="editor.readonly"
            @settings="editor.editWidget(column, widget)"
            @toggle="editor.toggleStatus(widget)"
        />

        <p class="text-xs font-medium text-amber-700">{{ widgetTypeLabel(widget.widget_type) }}</p>
        <p v-if="intro" class="mt-1 line-clamp-2 text-xs text-gray-500">{{ intro }}</p>
    </div>
</template>
