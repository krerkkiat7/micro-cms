<script setup lang="ts">
import { computed, useId } from 'vue';
import type { CSSProperties } from 'vue';
import FrontLink from '@/Components/Front/FrontLink.vue';
import CarouselControls from '@/Components/Front/PageLayout/CarouselControls.vue';
import { useCarousel } from '@/composables/useCarousel';
import { useFront } from '@/composables/useFront';
import { positionClasses, slideshowOverlayClass } from '@/utils/front';
import { itemTarget } from '@/utils/frontPage';
import type { FrontWidgetItem } from '@/utils/frontPage';

/**
 * widget Slideshow (จาก banner / จาก article) ที่หน้าบ้าน — ภาพเต็มกรอบตามอัตราส่วน, ข้อความซ้อนบนภาพ, effect slide/fade/zoom
 * ตามตั้งค่า (เหมือนตัวอย่างในหลังบ้าน widgets/SlideshowPreview.vue) แต่ลิงก์กดได้จริง
 * ARIA carousel pattern: region + aria-roledescription, สไลด์ที่ไม่แสดงถูกซ่อนจาก AT (inert), ปุ่มหยุด/เล่นเมื่อเลื่อนอัตโนมัติ
 */
const props = defineProps<{
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setting: Record<string, any>;
    items: FrontWidgetItem[];
    /** ชื่อ widget (ใช้เป็น aria-label ของ carousel) */
    label: string;
}>();

const { t } = useFront();
const id = useId();

const count = computed(() => props.items.length);
const carousel = useCarousel(count, {
    autoplay: () => props.setting.autoplay === 'Y',
    intervalMs: () => Number(props.setting.autoplay_interval ?? 5) * 1000,
});

const frameStyle = computed<CSSProperties>(() => ({ aspectRatio: String(props.setting.aspect_ratio ?? '16:9').replace(':', ' / ') }));

function offset(i: number): number {
    const total = count.value;
    let d = (((i - carousel.index.value) % total) + total) % total;

    if (d > total / 2) d -= total;

    return d;
}

function slideStyle(i: number): CSSProperties {
    const active = i === carousel.index.value;
    const speed = carousel.reducedMotion.value ? 0 : Number(props.setting.transition_speed ?? 500);
    const transition = `${speed}ms ease`;

    switch (props.setting.transition_effect) {
        case 'fade':
            return { opacity: active ? 1 : 0, transition: `opacity ${transition}` };
        case 'zoom':
            return { opacity: active ? 1 : 0, transform: `scale(${active ? 1 : 1.15})`, transition: `opacity ${transition}, transform ${transition}` };
        default:
            return { transform: `translateX(${offset(i) * 100}%)`, transition: `transform ${transition}` };
    }
}

// ข้อความวางตาม 9 ตำแหน่ง (text_align เป็นค่าแบบ background-position) ภายในกรอบเต็มภาพหรือ container ตาม text_width
const textBoxClass = computed(() => [positionClasses(String(props.setting.text_align ?? 'center')), props.setting.text_width === 'container' ? 'mx-auto w-full max-w-7xl' : 'w-full']);
const overlayClass = computed(() => slideshowOverlayClass(String(props.setting.text_align ?? 'center')));

const titleCss = computed<CSSProperties>(() => ({
    fontSize: `${props.setting.title_font_size}px`,
    fontFamily: `'${props.setting.title_font_family}', sans-serif`,
    color: props.setting.title_color,
    fontWeight: props.setting.title_bold === 'N' ? 400 : 700,
}));
const introCss = computed<CSSProperties>(() => ({
    fontSize: `${props.setting.intro_text_font_size}px`,
    fontFamily: `'${props.setting.intro_text_font_family}', sans-serif`,
    color: props.setting.intro_text_color,
    fontWeight: props.setting.intro_text_bold === 'Y' ? 700 : 400,
}));

const showText = (item: FrontWidgetItem) => (props.setting.show_title === 'Y' && item.title !== '') || (props.setting.show_intro_text === 'Y' && item.intro_text !== '');
const linkOf = (item: FrontWidgetItem) => (props.setting.is_clickable === 'Y' && item.url ? item.url : null);
</script>

<template>
    <section
        v-if="items.length"
        class="relative select-none overflow-hidden bg-gray-200"
        :style="frameStyle"
        aria-roledescription="carousel"
        :aria-label="label || undefined"
        v-on="carousel.pauseHandlers"
    >
        <div :id="`${id}-slides`" aria-live="off" class="absolute inset-0">
            <div
                v-for="(item, i) in items"
                :key="item.id"
                :data-item-id="item.id"
                class="absolute inset-0"
                :style="slideStyle(i)"
                role="group"
                aria-roledescription="slide"
                :aria-label="t('slide', { current: i + 1, total: items.length })"
                :aria-hidden="i !== carousel.index.value"
                :inert="i !== carousel.index.value"
            >
                <component :is="linkOf(item) ? FrontLink : 'div'" v-bind="linkOf(item) ? { href: linkOf(item), target: itemTarget(setting, item) } : {}" class="block size-full">
                    <img
                        v-if="item.image_url"
                        :src="item.image_url"
                        :alt="showText(item) ? '' : item.title"
                        class="size-full object-cover"
                        :loading="i === 0 ? 'eager' : 'lazy'"
                        draggable="false"
                    />

                    <div
                        v-if="showText(item)"
                        class="absolute inset-0 flex px-4 pt-5 text-white sm:px-8"
                        :class="[overlayClass, setting.show_dots === 'Y' || carousel.canAutoplay.value ? 'pb-10' : 'pb-5']"
                    >
                        <div class="flex" :class="textBoxClass">
                            <div class="max-w-full">
                                <p v-if="setting.show_title === 'Y' && item.title" class="leading-snug" :style="titleCss">{{ item.title }}</p>
                                <p v-if="setting.show_intro_text === 'Y' && item.intro_text" class="mt-0.5 whitespace-pre-line leading-snug" :style="introCss">{{ item.intro_text }}</p>
                            </div>
                        </div>
                    </div>
                </component>
            </div>
        </div>

        <CarouselControls
            :total="items.length"
            :index="carousel.index.value"
            :show-arrows="setting.show_arrows === 'Y'"
            :show-dots="setting.show_dots === 'Y'"
            :can-autoplay="carousel.canAutoplay.value"
            :playing="carousel.playing.value"
            variant="overlay"
            @prev="carousel.prev()"
            @next="carousel.next()"
            @go="carousel.go($event)"
            @toggle-play="carousel.togglePlay()"
        />
    </section>
</template>
