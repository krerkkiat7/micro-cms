<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import type { CSSProperties } from 'vue';
import CarouselControls from '@/Components/Front/PageLayout/CarouselControls.vue';
import ReadAllLink from '@/Components/Front/PageLayout/widgets/ReadAllLink.vue';
import WidgetCard from '@/Components/Front/PageLayout/widgets/WidgetCard.vue';
import { useCarousel } from '@/composables/useCarousel';
import { useFront } from '@/composables/useFront';
import { currentDevice } from '@/utils/pageWidget';
import type { SlidesetDevice } from '@/utils/pageWidget';
import type { FrontWidgetItem } from '@/utils/frontPage';

/**
 * widget Slideset (จาก article / จาก banner) ที่หน้าบ้าน — การ์ดเรียงแนวนอนเลื่อนทีละ "หน้า" (ครั้งละเท่าจำนวนการ์ดต่อแถวของขนาดหน้าจอ)
 * ตามตั้งค่า (เหมือน widgets/SlidesetPreview.vue ในหลังบ้าน) — การ์ดที่อยู่นอกหน้าที่แสดงถูกซ่อนจาก AT/Tab (inert)
 */
const props = defineProps<{
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setting: Record<string, any>;
    items: FrontWidgetItem[];
    hasMeta: boolean;
    label: string;
    headingTag: string;
}>();

const { t } = useFront();

// จำนวนการ์ดต่อแถวตามความกว้างหน้าต่างปัจจุบัน (ขนาดเดียวกับตัวเลือกดูตัวอย่างในหลังบ้าน)
const device = ref<SlidesetDevice>('pc');
const onResize = () => (device.value = currentDevice());

onMounted(() => {
    onResize();
    window.addEventListener('resize', onResize, { passive: true });
});
onBeforeUnmount(() => window.removeEventListener('resize', onResize));

const perView = computed(() => Math.max(1, Number(props.setting[`per_row_${device.value}`]) || 1));
const count = computed(() => props.items.length);
const pageCount = computed(() => Math.max(1, Math.ceil(count.value / perView.value)));
const canSlide = computed(() => count.value > perView.value);

const carousel = useCarousel(pageCount, {
    autoplay: () => props.setting.autoplay === 'Y',
    intervalMs: () => Number(props.setting.autoplay_interval ?? 5) * 1000,
});

const offset = computed(() => Math.min(carousel.index.value * perView.value, Math.max(count.value - perView.value, 0)));

const trackStyle = computed<CSSProperties>(() => ({
    transform: `translateX(-${offset.value * (100 / perView.value)}%)`,
    transition: carousel.reducedMotion.value ? 'none' : `transform ${props.setting.transition_speed ?? 500}ms ease`,
}));
const slotStyle = computed<CSSProperties>(() => ({ flex: `0 0 ${100 / perView.value}%`, maxWidth: `${100 / perView.value}%` }));

const visible = (i: number) => i >= offset.value && i < offset.value + perView.value;
const readAllOnTop = computed(() => String(props.setting.read_all_position ?? '').startsWith('top'));
</script>

<template>
    <div v-if="items.length">
        <ReadAllLink v-if="readAllOnTop" :setting="setting" class="mb-3" />

        <section class="relative" aria-roledescription="carousel" :aria-label="label || undefined" v-on="carousel.pauseHandlers">
            <div class="overflow-hidden">
                <ul class="flex" :style="trackStyle">
                    <li
                        v-for="(item, i) in items"
                        :key="item.id"
                        class="px-2"
                        :style="slotStyle"
                        :aria-hidden="canSlide && !visible(i) ? 'true' : undefined"
                        :inert="canSlide && !visible(i)"
                    >
                        <WidgetCard :setting="setting" :item="item" :has-meta="hasMeta" :heading-tag="headingTag" />
                    </li>
                </ul>
            </div>

            <CarouselControls
                v-if="canSlide"
                :total="pageCount"
                :index="carousel.index.value"
                :show-arrows="setting.show_arrows === 'Y'"
                :show-dots="setting.show_dots === 'Y'"
                :can-autoplay="carousel.canAutoplay.value"
                :playing="carousel.playing.value"
                variant="below"
                dot-label-key="go_to_page"
                @prev="carousel.prev()"
                @next="carousel.next()"
                @go="carousel.go($event)"
                @toggle-play="carousel.togglePlay()"
            />
            <p class="sr-only" aria-live="polite">{{ canSlide ? t('page_of', { current: carousel.index.value + 1, total: pageCount }) : '' }}</p>
        </section>

        <ReadAllLink v-if="!readAllOnTop" :setting="setting" class="mt-4" />
    </div>
</template>
