<script setup lang="ts">
import { computed, nextTick, onMounted, ref, useId } from 'vue';
import type { CSSProperties } from 'vue';
import { X } from 'lucide-vue-next';
import CarouselControls from '@/Components/Front/PageLayout/CarouselControls.vue';
import PopupPart from '@/Components/Front/Popup/PopupPart.vue';
import { useCarousel } from '@/composables/useCarousel';
import { useFront } from '@/composables/useFront';
import type { FrontPopup } from '@/utils/front';

/**
 * popup 1 รายการ — part แต่ละรายการเป็น 1 สไลด์ (ลูกศร/จุด/เลื่อนอัตโนมัติตามตั้งค่า, ปุ่มหยุด/เล่นตาม WCAG 2.2.2)
 * - modal: role="dialog" aria-modal, กักโฟกัสไว้ข้างในเมื่อเป็นรายการบนสุด (`active`), ปุ่ม "ปิด และไม่แสดงวันนี้อีก" + "ปิด" ด้านล่าง
 * - floating: dialog แบบไม่ modal ลอยกลางจอไม่มีพื้นหลัง ไม่ดึงโฟกัส, ปุ่มปิด (X) + ลิงก์ "ไม่แสดงวันนี้อีก"
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
        <div
            :id="id"
            ref="panel"
            role="dialog"
            :aria-modal="isModal ? 'true' : undefined"
            :aria-label="t('popup')"
            tabindex="-1"
            class="pointer-events-auto relative flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-xl bg-white text-gray-900 shadow-2xl outline-none"
            :class="isModal ? '' : 'ring-1 ring-black/10'"
        >
            <button
                v-if="!isModal"
                type="button"
                class="absolute right-2 top-2 z-20 flex size-8 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow hover:bg-gray-100"
                :aria-label="t('close')"
                @click="emit('close')"
            >
                <X class="size-4" aria-hidden="true" />
            </button>

            <div class="overflow-y-auto" :class="isModal ? 'p-5' : 'px-5 pb-4 pt-12'">
                <section
                    :aria-roledescription="count > 1 ? 'carousel' : undefined"
                    :aria-label="count > 1 ? t('popup') : undefined"
                    v-on="carousel.pauseHandlers"
                >
                    <div class="relative">
                        <div class="grid overflow-hidden">
                            <div
                                v-for="(part, i) in popup.parts"
                                :key="part.id"
                                :role="count > 1 ? 'group' : undefined"
                                :aria-roledescription="count > 1 ? 'slide' : undefined"
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

            <div v-if="isModal" class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 px-5 py-3">
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
            <div v-else-if="popup.show_dismiss_today" class="border-t border-gray-100 px-5 py-2 text-center">
                <button type="button" class="cursor-pointer text-sm text-gray-600 underline hover:text-gray-900" @click="emit('dismissToday')">
                    {{ t('dont_show_today') }}
                </button>
            </div>
        </div>
    </div>
</template>
