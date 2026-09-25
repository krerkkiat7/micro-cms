<script setup lang="ts">
import { computed } from 'vue';
import BlockTexts from '@/Components/Front/PageLayout/BlockTexts.vue';
import PartList from '@/Components/Front/ContentPart/PartList.vue';
import WidgetGrid from '@/Components/Front/PageLayout/widgets/WidgetGrid.vue';
import WidgetSlideset from '@/Components/Front/PageLayout/widgets/WidgetSlideset.vue';
import WidgetSlideshow from '@/Components/Front/PageLayout/widgets/WidgetSlideshow.vue';
import { backgroundCss } from '@/utils/front';
import { paddingCss } from '@/utils/frontPage';
import type { FrontWidgetData } from '@/utils/frontPage';
import { gridConfig, slidesetConfig, slideshowConfig } from '@/utils/pageWidget';
import { isCustomTextWidget } from '@/utils/pageWidgetCustomText';

/**
 * widget 1 ตัวของหน้าเพจ — หัวเรื่อง (h4) + เนื้อหาตามประเภท (Slideshow / Slideset / Grid / Custom Text)
 * widget ที่ไม่มีอะไรให้แสดง (ไม่มีรายการที่เผยแพร่ และไม่มีหัวเรื่อง) ไม่ render เลย
 */
const props = defineProps<{ widget: FrontWidgetData }>();

const slideset = computed(() => slidesetConfig(props.widget.widget_type));
const grid = computed(() => gridConfig(props.widget.widget_type));
const isSlideshow = computed(() => !!slideshowConfig(props.widget.widget_type));
const isCustomText = computed(() => isCustomTextWidget(props.widget.widget_type));
const hasContent = computed(() => props.widget.items.length > 0 || props.widget.parts.length > 0);
const hasTitle = computed(() => !!(props.widget.title || props.widget.subtitle || props.widget.intro_text));
const headingId = computed(() => (props.widget.title ? `widget-${props.widget.id}-title` : undefined));
</script>

<template>
    <section v-if="hasContent || hasTitle" class="space-y-4" :style="{ ...backgroundCss(widget.background), ...paddingCss(widget.padding) }" :aria-labelledby="headingId">
        <BlockTexts :block="widget" tag="h4" :heading-id="headingId" />

        <WidgetSlideshow v-if="isSlideshow" :setting="widget.setting" :items="widget.items" :label="widget.title" />
        <WidgetSlideset
            v-else-if="slideset"
            :setting="widget.setting"
            :items="widget.items"
            :has-meta="slideset.hasMeta"
            :label="widget.title"
            :heading-tag="widget.title ? 'h5' : 'h4'"
        />
        <WidgetGrid v-else-if="grid" :setting="widget.setting" :items="widget.items" :has-meta="grid.hasMeta" :heading-tag="widget.title ? 'h5' : 'h4'" />
        <PartList v-else-if="isCustomText" :parts="widget.parts" :heading-tag="widget.title ? 'h5' : 'h4'" />
    </section>
</template>
