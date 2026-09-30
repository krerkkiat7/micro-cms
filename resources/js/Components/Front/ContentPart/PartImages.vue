<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, useId } from 'vue';
import type { CSSProperties } from 'vue';
import { Maximize2 } from 'lucide-vue-next';
import CarouselControls from '@/Components/Front/PageLayout/CarouselControls.vue';
import Lightbox from '@/Components/Front/ContentPart/Lightbox.vue';
import type { LightboxImage } from '@/Components/Front/ContentPart/Lightbox.vue';
import { useCarousel } from '@/composables/useCarousel';
import { useFront } from '@/composables/useFront';
import type { FrontPart } from '@/utils/front';

/**
 * part "กลุ่มรูปภาพ" — 7 รูปแบบตาม images_display_type (ดู utils/articleParts.ts IMAGES_DISPLAY_TYPES):
 * thumbnail_carousel / multi_carousel / full_width_slider (carousel — เลื่อนอัตโนมัติได้ตามตั้งค่า + ปุ่มหยุด),
 * grid_lightbox / masonry_grid / justified_grid (จำนวนคอลัมน์ตามตั้งค่า), stacked_cards (การ์ดซ้อน) — ทุกแบบกดดูรูปขยายได้ (Lightbox)
 * ไม่ใช้ library เพิ่ม; รูปทุกใบมี alt จากข้อความแทนภาพที่กรอกไว้ (ไม่มี = รูปลำดับที่ n)
 */
const props = defineProps<{ part: FrontPart }>();

const { t } = useFront();
const id = useId();

const images = computed<(LightboxImage & { thumb: string })[]>(() =>
    props.part.files
        .filter((row) => row.file?.is_image)
        .map((row, i) => ({ src: row.file!.url, thumb: row.file!.thumb_url ?? row.file!.url, alt: row.alt || t('image', { number: i + 1 }) })),
);

const type = computed(() => props.part.images_display_type);
const columns = computed(() => props.part.setting.columns || 3);
const lightbox = ref<number | null>(null);

// ---- จำนวนต่อหน้าของ multi_carousel ตามความกว้างจอ ----
const width = ref(1280);
const onResize = () => (width.value = window.innerWidth);
onMounted(() => {
    onResize();
    window.addEventListener('resize', onResize, { passive: true });
});
onBeforeUnmount(() => window.removeEventListener('resize', onResize));

const perView = computed(() => {
    if (type.value !== 'multi_carousel') return 1;
    const max = width.value < 640 ? 1 : width.value < 1024 ? 2 : columns.value;

    return Math.max(1, Math.min(max, images.value.length));
});
const pageCount = computed(() => Math.max(1, Math.ceil(images.value.length / perView.value)));

const carousel = useCarousel(pageCount, {
    autoplay: () => props.part.setting.autoplay && ['thumbnail_carousel', 'multi_carousel', 'full_width_slider'].includes(type.value),
    intervalMs: () => props.part.setting.interval_ms,
});

const offset = computed(() => Math.min(carousel.index.value * perView.value, Math.max(images.value.length - perView.value, 0)));
const trackStyle = computed<CSSProperties>(() => ({
    transform: `translateX(-${offset.value * (100 / perView.value)}%)`,
    transition: carousel.reducedMotion.value ? 'none' : 'transform 500ms ease',
}));

const gridStyle = computed<CSSProperties>(() => ({ '--part-cols': columns.value }) as CSSProperties);

// ---- justified: สัดส่วนจริงของรูป (ได้หลังโหลด) ใช้เป็น flex-grow ให้แต่ละแถวเต็มความกว้าง ----
const ratios = ref<Record<number, number>>({});
function onJustifiedLoad(event: Event, i: number): void {
    const img = event.target as HTMLImageElement;
    if (img.naturalWidth && img.naturalHeight) ratios.value = { ...ratios.value, [i]: img.naturalWidth / img.naturalHeight };
}

// ---- stacked cards: หมุนการ์ดใบบนสุดไปท้ายกอง ----
const stackOrder = computed(() => images.value.map((_, i) => (i + carousel.index.value) % images.value.length));
</script>

<template>
    <div v-if="images.length">
        <!-- Thumbnail Carousel: รูปหลัก + แถวรูปย่อย -->
        <section v-if="type === 'thumbnail_carousel'" :aria-roledescription="t('carousel_role')" :aria-label="part.title || undefined" v-on="carousel.pauseHandlers">
            <div class="relative overflow-hidden rounded-lg bg-gray-100">
                <button type="button" class="block w-full" :aria-label="`${t('view_image')}: ${images[carousel.index.value].alt}`" @click="lightbox = carousel.index.value">
                    <img :src="images[carousel.index.value].src" :alt="images[carousel.index.value].alt" class="mx-auto max-h-[70vh] w-full object-contain" />
                </button>
                <CarouselControls
                    :total="images.length"
                    :index="carousel.index.value"
                    :show-arrows="true"
                    :show-dots="false"
                    :can-autoplay="carousel.canAutoplay.value"
                    :playing="carousel.playing.value"
                    variant="overlay"
                    @prev="carousel.prev()"
                    @next="carousel.next()"
                    @go="carousel.go($event)"
                    @toggle-play="carousel.togglePlay()"
                />
            </div>
            <ul class="mt-2 flex gap-2 overflow-x-auto pb-1">
                <li v-for="(image, i) in images" :key="i" class="shrink-0">
                    <button
                        type="button"
                        class="block size-16 overflow-hidden rounded-md ring-2 sm:size-20"
                        :class="i === carousel.index.value ? 'ring-brand-600' : 'ring-transparent opacity-70 hover:opacity-100'"
                        :aria-label="t('go_to_slide', { number: i + 1 })"
                        :aria-current="i === carousel.index.value ? 'true' : undefined"
                        @click="carousel.go(i)"
                    >
                        <img :src="image.thumb" alt="" class="size-full object-cover" loading="lazy" />
                    </button>
                </li>
            </ul>
        </section>

        <!-- Multi-item Carousel / Full-width Slider -->
        <section
            v-else-if="type === 'multi_carousel' || type === 'full_width_slider'"
            class="relative"
            :aria-roledescription="t('carousel_role')"
            :aria-label="part.title || undefined"
            v-on="carousel.pauseHandlers"
        >
            <div class="overflow-hidden" :class="type === 'full_width_slider' ? 'rounded-lg' : ''">
                <ul :id="`${id}-track`" class="flex" :style="trackStyle">
                    <li
                        v-for="(image, i) in images"
                        :key="i"
                        :class="type === 'multi_carousel' ? 'px-1.5' : ''"
                        :style="{ flex: `0 0 ${100 / perView}%`, maxWidth: `${100 / perView}%` }"
                        :aria-hidden="i < offset || i >= offset + perView ? 'true' : undefined"
                        :inert="i < offset || i >= offset + perView"
                    >
                        <button type="button" class="block w-full overflow-hidden" :class="type === 'multi_carousel' ? 'aspect-[4/3] rounded-md' : 'aspect-video'" :aria-label="`${t('view_image')}: ${image.alt}`" @click="lightbox = i">
                            <img :src="type === 'multi_carousel' ? image.thumb : image.src" :alt="image.alt" class="size-full object-cover" :loading="i < perView ? 'eager' : 'lazy'" />
                        </button>
                    </li>
                </ul>
            </div>
            <CarouselControls
                :total="pageCount"
                :index="carousel.index.value"
                :show-arrows="true"
                :show-dots="true"
                :can-autoplay="carousel.canAutoplay.value"
                :playing="carousel.playing.value"
                :variant="type === 'full_width_slider' ? 'overlay' : 'below'"
                @prev="carousel.prev()"
                @next="carousel.next()"
                @go="carousel.go($event)"
                @toggle-play="carousel.togglePlay()"
            />
        </section>

        <!-- Masonry Grid -->
        <ul v-else-if="type === 'masonry_grid'" class="front-part-masonry gap-3" :style="gridStyle">
            <li v-for="(image, i) in images" :key="i" class="mb-3 break-inside-avoid">
                <button type="button" class="group relative block w-full overflow-hidden rounded-md" :aria-label="`${t('view_image')}: ${image.alt}`" @click="lightbox = i">
                    <img :src="image.thumb" :alt="image.alt" class="h-auto w-full transition-transform group-hover:scale-105" loading="lazy" />
                </button>
            </li>
        </ul>

        <!-- Justified Grid -->
        <ul v-else-if="type === 'justified_grid'" class="flex flex-wrap gap-2 after:grow-[10] after:content-['']">
            <li v-for="(image, i) in images" :key="i" class="h-40 sm:h-52" :style="{ flexGrow: ratios[i] ?? 1.5, flexBasis: `${(ratios[i] ?? 1.5) * 10}rem` }">
                <button type="button" class="block size-full overflow-hidden rounded-md" :aria-label="`${t('view_image')}: ${image.alt}`" @click="lightbox = i">
                    <img :src="image.thumb" :alt="image.alt" class="size-full object-cover" loading="lazy" @load="onJustifiedLoad($event, i)" />
                </button>
            </li>
        </ul>

        <!-- Stacked / Overlapping Cards -->
        <section v-else-if="type === 'stacked_cards'" :aria-roledescription="t('carousel_role')" :aria-label="part.title || undefined" class="mx-auto max-w-xl">
            <div class="relative aspect-[4/3]">
                <component
                    :is="depth === 0 ? 'button' : 'div'"
                    v-for="(imageIndex, depth) in stackOrder.slice(0, 4)"
                    :key="imageIndex"
                    v-bind="depth === 0 ? { type: 'button', 'aria-label': `${t('view_image')}: ${images[imageIndex].alt}` } : { 'aria-hidden': 'true' }"
                    class="absolute inset-0 overflow-hidden rounded-xl border-4 border-white bg-gray-100 shadow-xl transition-transform duration-500"
                    :style="{ transform: `translate(${depth * 14}px, ${depth * 10}px) rotate(${depth * 3}deg)`, zIndex: 10 - depth }"
                    @click="depth === 0 && (lightbox = imageIndex)"
                >
                    <img :src="images[imageIndex].thumb" :alt="depth === 0 ? images[imageIndex].alt : ''" class="size-full object-cover" loading="lazy" />
                </component>
            </div>
            <CarouselControls
                :total="images.length"
                :index="carousel.index.value"
                :show-arrows="false"
                :show-dots="true"
                :can-autoplay="false"
                :playing="false"
                variant="below"
                class="mt-6"
                @go="carousel.go($event)"
            />
        </section>

        <!-- Grid Gallery with Lightbox (ค่าเริ่มต้น) -->
        <ul v-else class="front-part-grid grid gap-2" :style="gridStyle">
            <li v-for="(image, i) in images" :key="i">
                <button type="button" class="group relative block aspect-square w-full overflow-hidden rounded-md" :aria-label="`${t('view_image')}: ${image.alt}`" @click="lightbox = i">
                    <img :src="image.thumb" :alt="image.alt" class="size-full object-cover transition-transform group-hover:scale-105" loading="lazy" />
                    <span class="absolute right-1.5 top-1.5 rounded bg-black/50 p-1 text-white opacity-0 transition-opacity group-hover:opacity-100" aria-hidden="true">
                        <Maximize2 class="size-3.5" />
                    </span>
                </button>
            </li>
        </ul>

        <Lightbox v-model="lightbox" :images="images" />
    </div>
</template>
