import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { Ref } from 'vue';

/**
 * สถานะของ carousel/slideshow หน้าบ้าน (ใช้ร่วมกันทุกแบบ: Slideshow/Slideset ของหน้าเพจ, กลุ่มรูปภาพของบทความ)
 *
 * WCAG 2.2.2 (Pause, Stop, Hide): เลื่อนอัตโนมัติต้องหยุดได้ — มีสถานะ `playing` ให้ปุ่มหยุด/เล่น, หยุดชั่วคราวเมื่อเมาส์ชี้/โฟกัสอยู่ใน carousel
 * และไม่เลื่อนอัตโนมัติเลยถ้าผู้ใช้ตั้ง prefers-reduced-motion
 *
 * @param total จำนวน "หน้า" ที่เลื่อนได้
 */
export function useCarousel(total: Ref<number>, options: { autoplay: () => boolean; intervalMs: () => number }) {
    const index = ref(0);
    const playing = ref(false);
    const hovering = ref(false);
    const focusWithin = ref(false);
    const reducedMotion = ref(false);
    let timer: ReturnType<typeof setInterval> | null = null;

    const canAutoplay = computed(() => options.autoplay() && total.value > 1 && !reducedMotion.value);

    function go(target: number): void {
        if (total.value > 0) index.value = ((target % total.value) + total.value) % total.value;
    }

    const next = () => go(index.value + 1);
    const prev = () => go(index.value - 1);

    function stop(): void {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }
    }

    function restart(): void {
        stop();

        if (playing.value && canAutoplay.value && !hovering.value && !focusWithin.value) {
            timer = setInterval(next, Math.max(1000, options.intervalMs()));
        }
    }

    function togglePlay(): void {
        playing.value = !playing.value;
    }

    watch(total, () => {
        if (index.value >= total.value) index.value = 0;
    });

    watch([playing, hovering, focusWithin, canAutoplay, () => options.intervalMs()], restart);

    onMounted(() => {
        reducedMotion.value = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
        playing.value = canAutoplay.value;
        restart();
    });

    onBeforeUnmount(stop);

    /** ผูกกับ element ครอบ carousel — หยุดชั่วคราวเมื่อเมาส์ชี้/โฟกัสอยู่ข้างใน */
    const pauseHandlers = {
        onMouseenter: () => (hovering.value = true),
        onMouseleave: () => (hovering.value = false),
        onFocusin: () => (focusWithin.value = true),
        onFocusout: (event: FocusEvent) => {
            const nextTarget = event.relatedTarget as Node | null;
            if (!nextTarget || !(event.currentTarget as HTMLElement).contains(nextTarget)) focusWithin.value = false;
        },
    };

    return { index, playing, canAutoplay, reducedMotion, go, next, prev, togglePlay, pauseHandlers };
}
