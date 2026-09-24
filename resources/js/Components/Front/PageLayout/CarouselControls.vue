<script setup lang="ts">
import { ChevronLeft, ChevronRight, Pause, Play } from 'lucide-vue-next';
import { useFront } from '@/composables/useFront';

/**
 * ปุ่มควบคุม carousel ที่ใช้ร่วมกัน — ลูกศรก่อนหน้า/ถัดไป, จุดเลือกหน้า (aria-current), ปุ่มหยุด/เล่น (เฉพาะเมื่อเลื่อนอัตโนมัติ)
 * `variant`: overlay = ซ้อนบนภาพ (Slideshow), below = ใต้เนื้อหา (Slideset/กลุ่มรูป)
 */
defineProps<{
    total: number;
    index: number;
    showArrows: boolean;
    showDots: boolean;
    canAutoplay: boolean;
    playing: boolean;
    variant: 'overlay' | 'below';
    /** ข้อความของจุด (ค่าเริ่มต้น "ไปยังสไลด์ที่ n") */
    dotLabelKey?: string;
}>();

const emit = defineEmits<{ prev: []; next: []; go: [index: number]; togglePlay: [] }>();

const { t } = useFront();

const arrowClass = 'flex size-10 items-center justify-center rounded-full bg-black/50 text-white shadow transition-colors hover:bg-black/70';
</script>

<template>
    <template v-if="total > 1">
        <template v-if="showArrows">
            <button type="button" :class="[arrowClass, 'absolute left-2 top-1/2 z-10 -translate-y-1/2']" :aria-label="t('previous')" @click="emit('prev')">
                <ChevronLeft class="size-5" aria-hidden="true" />
            </button>
            <button type="button" :class="[arrowClass, 'absolute right-2 top-1/2 z-10 -translate-y-1/2']" :aria-label="t('next')" @click="emit('next')">
                <ChevronRight class="size-5" aria-hidden="true" />
            </button>
        </template>

        <div
            v-if="showDots || canAutoplay"
            class="z-10 flex items-center justify-center gap-2"
            :class="variant === 'overlay' ? 'absolute inset-x-0 bottom-2' : 'mt-3'"
        >
            <button
                v-if="canAutoplay"
                type="button"
                class="flex size-7 items-center justify-center rounded-full"
                :class="variant === 'overlay' ? 'bg-black/50 text-white hover:bg-black/70' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'"
                :aria-label="playing ? t('pause') : t('play')"
                @click="emit('togglePlay')"
            >
                <Pause v-if="playing" class="size-3.5" aria-hidden="true" />
                <Play v-else class="size-3.5" aria-hidden="true" />
            </button>
            <template v-if="showDots">
                <button
                    v-for="n in total"
                    :key="n"
                    type="button"
                    class="flex size-6 items-center justify-center"
                    :aria-label="t(dotLabelKey ?? 'go_to_slide', { number: n })"
                    :aria-current="n - 1 === index ? 'true' : undefined"
                    @click="emit('go', n - 1)"
                >
                    <span
                        class="size-2.5 rounded-full transition-colors"
                        :class="
                            variant === 'overlay'
                                ? n - 1 === index
                                    ? 'bg-white'
                                    : 'bg-white/50'
                                : n - 1 === index
                                  ? 'bg-brand-600'
                                  : 'bg-gray-300'
                        "
                        aria-hidden="true"
                    />
                </button>
            </template>
        </div>
    </template>
</template>
