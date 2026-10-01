<script setup lang="ts">
import { computed, nextTick, onMounted, ref, useId } from 'vue';
import type { CSSProperties } from 'vue';
import { X } from 'lucide-vue-next';
import CarouselControls from '@/Components/Front/PageLayout/CarouselControls.vue';
import FrontLink from '@/Components/Front/FrontLink.vue';
import PopupPart from '@/Components/Front/Popup/PopupPart.vue';
import { useCarousel } from '@/composables/useCarousel';
import { useFront } from '@/composables/useFront';
import type { FrontPopup } from '@/utils/front';

/**
 * popup 1 รายการ — part แต่ละรายการเป็น 1 สไลด์ (ลูกศร/จุด/เลื่อนอัตโนมัติตามตั้งค่า, ปุ่มหยุด/เล่นตาม WCAG 2.2.2)
 * - modal: role="dialog" aria-modal, กักโฟกัสไว้ข้างในเมื่อเป็นรายการบนสุด (`active`), ปุ่ม "ปิด และไม่แสดงวันนี้อีก" + "ปิด" ด้านล่าง
 * - floating: dialog แบบไม่ modal ไม่ดึงโฟกัส — รูปภาพลอยกลางจออย่างเดียว ไม่มีกล่อง/พื้นหลัง รูปสูงไม่เกินหน้าจอ,
 *   ใต้รูปมี "ไม่แสดงวันนี้อีก" + ปุ่มปิด
 * สไลด์ทุกอันซ้อนในช่อง grid เดียวกัน → ความสูงกล่อง = สไลด์ที่สูงที่สุด (ไม่กระโดดตอนเปลี่ยนสไลด์)
 */
const props = defineProps<{
    popup: FrontPopup;
    /** เป็นรายการบนสุดของกอง — Esc/กักโฟกัสทำงานเฉพาะรายการนี้ */
    active: boolean;
    zIndex: number;
}>();

const emit = defineEmits<{ close: []; dismissToday: [] }>();

const { t } = useFront();
const id = useId();
const panel = ref<HTMLElement | null>(null);

/** ความกว้างรูปของ floating ตามขนาดที่ตั้งไว้ (เทียบกับความกว้างสูงสุดของ popup) */
const FLOATING_WIDTH: Record<string, string> = { full: '100%', large: '75%', medium: '50%', small: '33.333%' };

const isModal = computed(() => props.popup.display_type === 'modal');
const count = computed(() => props.popup.parts.length);

const carousel = useCarousel(count, {
    autoplay: () => props.popup.autoplay,
    // เวลาที่ค้าง + เวลาที่ใช้เลื่อน เพื่อให้แต่ละสไลด์ค้างเต็มเวลาที่ตั้งไว้หลังเลื่อนเสร็จ
    intervalMs: () => props.popup.slide_interval * 1000 + props.popup.slide_speed,
});

function offset(i: number): number {
    const total = count.value;
    let d = (((i - carousel.index.value) % total) + total) % total;

    if (d > total / 2) d -= total;

    return d;
}

function slideStyle(i: number): CSSProperties {
    const speed = carousel.reducedMotion.value ? 0 : props.popup.slide_speed;

    return { gridArea: '1 / 1', transform: `translateX(${offset(i) * 100}%)`, transition: `transform ${speed}ms ease` };
}

function focusables(): HTMLElement[] {
    return Array.from(
        panel.value?.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])') ?? [],
    ).filter((el) => el.offsetParent !== null && !el.closest('[inert]'));
}

function onKeydown(event: KeyboardEvent): void {
    if (!props.active) return;

    if (event.key === 'Escape') {
        event.stopPropagation();
        emit('close');

        return;
    }

    if (event.key !== 'Tab' || !isModal.value) return;

    const items = focusables();
    if (!items.length) return;

    const first = items[0];
    const last = items[items.length - 1];

    if (event.shiftKey && (document.activeElement === first || document.activeElement === panel.value)) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

defineExpose({
    focus: () => panel.value?.focus(),
});

onMounted(() => {
    if (props.active && isModal.value) {
        void nextTick(() => panel.value?.focus());
    }
});
</script>

<template>
    <div
        class="pointer-events-none fixed inset-0 flex items-center justify-center p-4"
        :style="{ zIndex }"
        @keydown="onKeydown"
    >
        <!-- modal: กล่องพื้นขาว เนื้อหา (รูป/ข้อความ) + ปุ่มด้านล่าง -->
        <div
            v-if="isModal"
            :id="id"
            ref="panel"
            role="dialog"
            aria-modal="true"
            :aria-label="t('popup')"
            tabindex="-1"
            class="pointer-events-auto relative flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-xl bg-white text-gray-900 shadow-2xl outline-none"
        >
            <div class="overflow-y-auto p-5">
                <section
                    :aria-roledescription="count > 1 ? t('carousel_role') : undefined"
                    :aria-label="count > 1 ? t('popup') : undefined"
                    v-on="carousel.pauseHandlers"
                >
                    <div class="relative">
                        <div class="grid overflow-hidden">
                            <div
                                v-for="(part, i) in popup.parts"
                                :key="part.id"
                                :role="count > 1 ? 'group' : undefined"
                                :aria-roledescription="count > 1 ? t('slide_role') : undefined"
                                :aria-label="count > 1 ? t('slide', { current: i + 1, total: count }) : undefined"
                                :aria-hidden="i !== carousel.index.value ? 'true' : undefined"
                                :inert="i !== carousel.index.value || undefined"
                                :style="slideStyle(i)"
                                class="min-w-0"
                            >
                                <PopupPart :part="part" @navigate="emit('close')" />
                            </div>
                        </div>

                        <CarouselControls
                            :total="count"
                            :index="carousel.index.value"
                            :show-arrows="popup.show_arrows"
                            :show-dots="false"
                            :can-autoplay="false"
                            :playing="carousel.playing.value"
                            variant="overlay"
                            @prev="carousel.prev"
                            @next="carousel.next"
                        />
                    </div>

                    <CarouselControls
                        :total="count"
                        :index="carousel.index.value"
                        :show-arrows="false"
                        :show-dots="popup.show_dots"
                        :can-autoplay="carousel.canAutoplay.value"
                        :playing="carousel.playing.value"
                        variant="below"
                        @go="carousel.go"
                        @toggle-play="carousel.togglePlay"
                    />
                </section>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 px-5 py-3">
                <button
                    v-if="popup.show_dismiss_today"
                    type="button"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    @click="emit('dismissToday')"
                >
                    {{ t('close_and_dont_show_today') }}
                </button>
                <button type="button" class="rounded-md bg-brand-700 px-4 py-2 text-sm font-medium text-white hover:bg-brand-800" @click="emit('close')">
                    {{ t('close') }}
                </button>
            </div>
        </div>

        <!--
            floating: รูปภาพลอยอย่างเดียว ไม่มีกล่อง/พื้นหลัง (ลูกศร จุด ปุ่มหยุด ซ้อนบนรูป) รูปสูงไม่เกินหน้าจอ (เผื่อพื้นที่แถบใต้รูป)
            ใต้รูป: "ไม่แสดงวันนี้อีก" + ปุ่มปิด — ทึบเล็กน้อยให้อ่านออกบนพื้นหลังหน้าเว็บทุกสี
        -->
        <div
            v-else
            :id="id"
            ref="panel"
            role="dialog"
            :aria-label="t('popup')"
            tabindex="-1"
            class="pointer-events-auto flex w-[min(92vw,40rem)] flex-col items-center gap-3 outline-none"
        >
            <section
                class="relative w-full"
                :aria-roledescription="count > 1 ? t('carousel_role') : undefined"
                :aria-label="count > 1 ? t('popup') : undefined"
                v-on="carousel.pauseHandlers"
            >
                <div class="grid overflow-hidden">
                    <div
                        v-for="(part, i) in popup.parts"
                        :key="part.id"
                        :role="count > 1 ? 'group' : undefined"
                        :aria-roledescription="count > 1 ? t('slide_role') : undefined"
                        :aria-label="count > 1 ? t('slide', { current: i + 1, total: count }) : undefined"
                        :aria-hidden="i !== carousel.index.value ? 'true' : undefined"
                        :inert="i !== carousel.index.value || undefined"
                        :style="slideStyle(i)"
                        class="flex min-w-0 items-center justify-center"
                    >
                        <div v-if="part.image" class="flex justify-center" :style="{ width: FLOATING_WIDTH[part.image_size] ?? '100%' }">
                            <FrontLink v-if="part.url" :href="part.url" :target="part.link_target" class="inline-block max-w-full" @click="emit('close')">
                                <img :src="part.image.thumb_url ?? part.image.url" :alt="t('popup_image')" class="floating-image" />
                            </FrontLink>
                            <img v-else :src="part.image.thumb_url ?? part.image.url" :alt="t('popup_image')" class="floating-image" />
                        </div>
                    </div>
                </div>

                <CarouselControls
                    :total="count"
                    :index="carousel.index.value"
                    :show-arrows="popup.show_arrows"
                    :show-dots="popup.show_dots"
                    :can-autoplay="carousel.canAutoplay.value"
                    :playing="carousel.playing.value"
                    variant="overlay"
                    @prev="carousel.prev"
                    @next="carousel.next"
                    @go="carousel.go"
                    @toggle-play="carousel.togglePlay"
                />
            </section>

            <div class="flex items-center gap-2">
                <button
                    v-if="popup.show_dismiss_today"
                    type="button"
                    class="cursor-pointer rounded-full bg-black/60 px-4 py-1.5 text-sm text-white shadow hover:bg-black/75"
                    @click="emit('dismissToday')"
                >
                    {{ t('dont_show_today') }}
                </button>
                <button
                    type="button"
                    class="flex size-9 cursor-pointer items-center justify-center rounded-full bg-black/60 text-white shadow hover:bg-black/75"
                    :aria-label="t('close')"
                    @click="emit('close')"
                >
                    <X class="size-5" aria-hidden="true" />
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* รูปของ floating — ย่อให้กว้างไม่เกินกรอบและสูงไม่เกินหน้าจอ (หักขอบ + แถบใต้รูป) คงสัดส่วนเดิม ไม่ขยายเกินขนาดจริง */
.floating-image {
    display: block;
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: calc(100dvh - 7rem);
    border-radius: 0.5rem;
    box-shadow: 0 20px 40px rgb(0 0 0 / 0.35);
}
</style>
