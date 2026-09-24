<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { ChevronLeft, ChevronRight, X } from 'lucide-vue-next';
import { useFront } from '@/composables/useFront';

/**
 * ดูรูปขยาย (modal dialog) — ปุ่มปิด/ก่อนหน้า/ถัดไป, คีย์บอร์ด Esc/←/→, โฟกัสวนอยู่ใน dialog แล้วคืนโฟกัสให้รูปที่กดเปิด
 */
export interface LightboxImage {
    src: string;
    alt: string;
}

const props = defineProps<{ images: LightboxImage[] }>();

const index = defineModel<number | null>({ required: true });

const { t } = useFront();

const dialog = ref<HTMLElement | null>(null);
let returnFocus: HTMLElement | null = null;

const current = computed(() => (index.value !== null ? props.images[index.value] : null));

function close(): void {
    index.value = null;
}

function step(delta: number): void {
    if (index.value === null || props.images.length < 2) return;
    index.value = (index.value + delta + props.images.length) % props.images.length;
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        event.stopPropagation();
        close();
    } else if (event.key === 'ArrowLeft') {
        step(-1);
    } else if (event.key === 'ArrowRight') {
        step(1);
    } else if (event.key === 'Tab') {
        const items = Array.from(dialog.value?.querySelectorAll<HTMLElement>('button') ?? []);
        if (!items.length) return;

        const first = items[0];
        const last = items[items.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
}

watch(
    () => index.value !== null,
    (open) => {
        if (open) {
            returnFocus = document.activeElement as HTMLElement | null;
            document.body.style.overflow = 'hidden';
            void nextTick(() => dialog.value?.querySelector<HTMLElement>('[data-close]')?.focus());
        } else {
            document.body.style.overflow = '';
            returnFocus?.focus();
        }
    },
);

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="current"
            ref="dialog"
            class="fixed inset-0 z-[70] flex items-center justify-center bg-black/90 p-4"
            role="dialog"
            aria-modal="true"
            :aria-label="current.alt || t('image', { number: (index ?? 0) + 1 })"
            @keydown="onKeydown"
            @click.self="close"
        >
            <figure class="flex max-h-full max-w-full flex-col items-center gap-3">
                <img :src="current.src" :alt="current.alt" class="max-h-[80vh] max-w-full object-contain" />
                <figcaption class="text-center text-sm text-white/90">
                    <span v-if="current.alt">{{ current.alt }} · </span>{{ (index ?? 0) + 1 }} / {{ images.length }}
                </figcaption>
            </figure>

            <button type="button" data-close class="absolute right-3 top-3 flex size-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" :aria-label="t('close')" @click="close">
                <X class="size-6" aria-hidden="true" />
            </button>
            <template v-if="images.length > 1">
                <button type="button" class="absolute left-3 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" :aria-label="t('previous')" @click="step(-1)">
                    <ChevronLeft class="size-6" aria-hidden="true" />
                </button>
                <button type="button" class="absolute right-3 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" :aria-label="t('next')" @click="step(1)">
                    <ChevronRight class="size-6" aria-hidden="true" />
                </button>
            </template>
        </div>
    </Teleport>
</template>
