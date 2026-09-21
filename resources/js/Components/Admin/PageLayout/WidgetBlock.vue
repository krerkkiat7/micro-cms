<script setup lang="ts">
import { computed } from 'vue';
import LayoutToolbar from './LayoutToolbar.vue';
import LayoutTexts from './LayoutTexts.vue';
import SlideshowPreview from './widgets/SlideshowPreview.vue';
import SlidesetPreview from './widgets/SlidesetPreview.vue';
import { usePageLayoutEditor } from '@/composables/usePageLayoutEditor';
import { backgroundStyle, displayTitle } from '@/utils/pageLayout';
import { slideshowConfig, slidesetConfig, widgetTypeLabel } from '@/utils/pageWidget';
import type { SlideshowCommonSetting, SlidesetSetting } from '@/utils/pageWidget';
import type { ColumnData, WidgetData } from '@/utils/pageLayout';

/**
 * widget ในคอลัมน์ — การ์ดจำลองการแสดงผล: ชื่อประเภท + หัวเรื่อง (h4) / หัวเรื่องรอง / ข้อความเกริ่นนำเมื่อเปิด "แสดงหัวเรื่อง"
 * แล้วตามด้วยตัวอย่างการแสดงผลของ widget ตามประเภท/ค่าตั้งค่า (ข้อมูลจริง แต่ลิงก์กดไม่ได้) พร้อมพื้นหลังที่ตั้งไว้และแถบจัดการมุมซ้ายบน
 * ประเภทเดิม (placeholder) ไม่มีตัวอย่าง แสดงแค่ชื่อประเภท
 */
const props = defineProps<{
    widget: WidgetData;
    column: ColumnData;
}>();

const editor = usePageLayoutEditor();

const title = computed(() => displayTitle(props.widget.detail, editor.languages, '(ไม่มีชื่อ)'));
const slideshowSetting = computed(() => props.widget.setting as unknown as SlideshowCommonSetting);
const slidesetSetting = computed(() => props.widget.setting as unknown as SlidesetSetting);
</script>

<template>
    <div
        class="relative rounded-md border border-amber-300 bg-white px-3 pb-3 pt-9 shadow-xs"
        :class="widget.status === 'N' ? 'opacity-50' : ''"
        :style="backgroundStyle(widget)"
    >
        <LayoutToolbar
            kind="widget"
            :title="title"
            :hidden="widget.status === 'N'"
            :readonly="editor.readonly"
            @settings="editor.editWidget(column, widget)"
            @toggle="editor.toggleStatus(widget)"
            @remove="editor.removeWidget(column, widget)"
        />

        <p class="text-xs font-medium text-amber-700">{{ widgetTypeLabel(widget.widget_type) }}</p>

        <LayoutTexts
            level="widget"
            :show="widget.show_title === 'Y'"
            :detail="widget.detail"
            :styles="widget"
            :languages="editor.languages"
            class="mt-2"
        />

        <SlideshowPreview
            v-if="slideshowConfig(widget.widget_type)"
            :widget-type="widget.widget_type"
            :setting="slideshowSetting"
            class="mt-3"
        />
        <SlidesetPreview
            v-else-if="slidesetConfig(widget.widget_type)"
            :widget-type="widget.widget_type"
            :setting="slidesetSetting"
            :languages="editor.languages"
            class="mt-3"
        />
    </div>
</template>
