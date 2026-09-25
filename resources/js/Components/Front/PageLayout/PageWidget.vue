<script setup lang="ts">
import { computed } from 'vue';
import BlockTexts from '@/Components/Front/PageLayout/BlockTexts.vue';
import PartList from '@/Components/Front/ContentPart/PartList.vue';
import WidgetGrid from '@/Components/Front/PageLayout/widgets/WidgetGrid.vue';
import WidgetSlideset from '@/Components/Front/PageLayout/widgets/WidgetSlideset.vue';
import WidgetSlideshow from '@/Components/Front/PageLayout/widgets/WidgetSlideshow.vue';
import { backgroundCss } from '@/utils/front';
import { useFront } from '@/composables/useFront';
import { headingTag, paddingCss, trackBannerClick } from '@/utils/frontPage';
import type { FrontWidgetData } from '@/utils/frontPage';
import { gridConfig, slidesetConfig, slideshowConfig } from '@/utils/pageWidget';
import { isCustomTextWidget } from '@/utils/pageWidgetCustomText';

/**
 * widget 1 ตัวของหน้าเพจ — หัวเรื่อง + เนื้อหาตามประเภท (Slideshow / Slideset / Grid / Custom Text)
 * `level` = ระดับหัวเรื่องที่ได้รับจากคอลัมน์ (ปกติ h4 — เลื่อนขึ้นเมื่อแถว/คอลัมน์ไม่มีหัวเรื่อง); หัวข้อของรายการข้างในใช้ระดับถัดไป
 * ถ้า widget มีหัวเรื่อง ไม่งั้นใช้ระดับเดียวกัน
 * widget จาก banner: คลิกลิงก์ของรายการ (อยู่ในกล่องที่มี data-item-id — Slideshow/Slideset/Grid) = นับการคลิกของ banner นั้น
 * (ดักที่นี่ที่เดียวแบบ event delegation — ไม่ต้องแก้ลิงก์ทุกจุด และคลิกกลาง/เปิดแท็บใหม่ก็นับด้วย)
 * widget ที่ไม่มีอะไรให้แสดง (ไม่มีรายการที่เผยแพร่ และไม่มีหัวเรื่อง) ไม่ render เลย
 */
const props = defineProps<{ widget: FrontWidgetData; level: number }>();

const slideset = computed(() => slidesetConfig(props.widget.widget_type));
const grid = computed(() => gridConfig(props.widget.widget_type));
const isSlideshow = computed(() => !!slideshowConfig(props.widget.widget_type));
const isCustomText = computed(() => isCustomTextWidget(props.widget.widget_type));
const hasContent = computed(() => props.widget.items.length > 0 || props.widget.parts.length > 0);
const hasTitle = computed(() => !!(props.widget.title || props.widget.subtitle || props.widget.intro_text));
const { front } = useFront();
const tracksClicks = computed(() => props.widget.widget_type.endsWith('banner'));

function onLinkClick(event: MouseEvent) {
    if (!tracksClicks.value || (event.type === 'auxclick' && event.button !== 1)) return;

    const link = (event.target as Element | null)?.closest('a');
    const id = Number(link?.closest('[data-item-id]')?.getAttribute('data-item-id'));

    if (link && id > 0) trackBannerClick(id, front.value.lang);
}

const itemHeadingTag = computed(() => headingTag(props.widget.title ? props.level + 1 : props.level));
const headingId = computed(() => (props.widget.title ? `widget-${props.widget.id}-title` : undefined));
</script>

<template>
    <section v-if="hasContent || hasTitle" class="space-y-4" :style="{ ...backgroundCss(widget.background), ...paddingCss(widget.padding) }" :aria-labelledby="headingId" @click.capture="onLinkClick" @auxclick.capture="onLinkClick">
        <BlockTexts :block="widget" :tag="headingTag(level)" :heading-id="headingId" />

        <WidgetSlideshow v-if="isSlideshow" :setting="widget.setting" :items="widget.items" :label="widget.title" />
        <WidgetSlideset
            v-else-if="slideset"
            :setting="widget.setting"
            :items="widget.items"
            :has-meta="slideset.hasMeta"
            :label="widget.title"
            :heading-tag="itemHeadingTag"
        />
        <WidgetGrid v-else-if="grid" :setting="widget.setting" :items="widget.items" :has-meta="grid.hasMeta" :heading-tag="itemHeadingTag" />
        <PartList v-else-if="isCustomText" :parts="widget.parts" :heading-tag="itemHeadingTag" />
    </section>
</template>
