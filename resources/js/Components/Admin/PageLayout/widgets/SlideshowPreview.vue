<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import type { CSSProperties } from 'vue';
import { ChevronLeft, ChevronRight, Link2 } from 'lucide-vue-next';
import { useWidgetPreview } from '@/composables/useWidgetPreview';
import { slideshowConfig } from '@/utils/pageWidget';
import type { SlideshowCommonSetting } from '@/utils/pageWidget';

/**
 * ตัวอย่างการแสดงผลของ widget กลุ่ม Slideshow (จาก banner / จาก article) ในหน้าโครงสร้าง — ข้อมูลจริงจากหมวดหมู่ที่เลือก
 * (ผ่าน endpoint ตัวอย่าง: เฉพาะที่เผยแพร่อยู่และมีรูป ตามลำดับที่ตั้งไว้) แสดงตามค่าตั้งค่าทั้งหมด (สัดส่วนภาพ ลูกศร จุด
 * ข้อความบนภาพ effect และเลื่อนอัตโนมัติ) ลูกศร/จุดกดเลื่อนดูได้ แต่ลิงก์เป็นแค่ป้ายบอกว่ามีลิงก์ — กดไม่ได้และไม่มี URL
 */
const props = defineProps<{
    widgetType: string;
    setting: SlideshowCommonSetting;
}>();

// คีย์หมวดหมู่ใน setting ต่างกันตามประเภท (banner_category_info_id / article_category_info_id)
const config = slideshowConfig(props.widgetType)!;
const categoryId = () => (props.setting as unknown as Record<string, number | null>)[config.categoryKey];

const { items, loading, failed } = useWidgetPreview(props.widgetType, () =>
    categoryId() ? { [config.categoryKey]: categoryId(), sort_by: props.setting.sort_by } : null,
);

const index = ref(0);
const count = computed(() => items.value.length);

watch(items, () => {
    index.value = 0;
});

function go(target: number) {
    if (count.value > 0) {
        index.value = (target + count.value) % count.value;
    }
}

// ---- เลื่อนอัตโนมัติ (รีสตาร์ทเมื่อค่าที่เกี่ยวข้องเปลี่ยน) ----
let timer: ReturnType<typeof setInterval> | null = null;

function stopTimer() {
    if (timer !== null) {
        clearInterval(timer);
        timer = null;
    }
}

watch(
    () => [props.setting.autoplay, props.setting.autoplay_interval, count.value] as const,
    ([autoplay, interval, total]) => {
        stopTimer();

        if (autoplay === 'Y' && total > 1 && interval > 0) {
            timer = setInterval(() => go(index.value + 1), interval * 1000);
        }
    },
    { immediate: true },
);

onBeforeUnmount(stopTimer);

// ---- รูปแบบการแสดงผล ----
const frameStyle = computed<CSSProperties>(() => ({ aspectRatio: props.setting.aspect_ratio.replace(':', ' / ') }));

/** ตำแหน่งสัมพัทธ์ของสไลด์ i ต่อสไลด์ปัจจุบัน แบบวนรอบ (ทำให้เลื่อนไปข้างหน้าเสมอแม้ข้ามจากภาพสุดท้ายไปภาพแรก) */
function offset(i: number): number {
    const total = count.value;
    let d = (((i - index.value) % total) + total) % total;

    if (d > total / 2) d -= total;

    return d;
}

function slideStyle(i: number): CSSProperties {
    const active = i === index.value;
    const transition = `${props.setting.transition_speed}ms ease`;

    switch (props.setting.transition_effect) {
        case 'fade':
            return { opacity: active ? 1 : 0, transition: `opacity ${transition}` };
        case 'zoom':
            return { opacity: active ? 1 : 0, transform: `scale(${active ? 1 : 1.15})`, transition: `opacity ${transition}, transform ${transition}` };
        default:
            return { transform: `translateX(${offset(i) * 100}%)`, transition: `transform ${transition}` };
    }
}

const textBoxClass = computed(() => [
    props.setting.text_width === 'container' ? 'mx-auto max-w-5xl' : '',
    { left: 'text-left', center: 'text-center', right: 'text-right' }[props.setting.text_align],
]);

const showText = (i: number) =>
    (props.setting.show_title === 'Y' && items.value[i].title !== '') || (props.setting.show_intro_text === 'Y' && items.value[i].intro_text !== '');
</script>

<template>
    <div v-if="!categoryId()" class="rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 py-6 text-center text-xs text-gray-500">
        ยังไม่ได้เลือก{{ config.categoryLabel }}
    </div>
    <div v-else-if="loading" class="flex items-center justify-center rounded-md bg-gray-100 text-xs text-gray-500" :style="frameStyle">กำลังโหลดตัวอย่าง…</div>
    <div v-else-if="failed" class="rounded-md border border-dashed border-red-200 bg-red-50 px-3 py-6 text-center text-xs text-red-600">
        โหลดตัวอย่างไม่สำเร็จ (ตรวจสอบหมวดหมู่ที่เลือก)
    </div>
    <div v-else-if="count === 0" class="flex items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 text-center text-xs text-gray-500" :style="frameStyle">
        ไม่มี{{ config.itemLabel }}ที่เผยแพร่อยู่ในหมวดหมู่นี้
    </div>

    <div v-else class="relative select-none overflow-hidden rounded-md bg-gray-200" :style="frameStyle">
        <div v-for="(item, i) in items" :key="item.id" class="absolute inset-0" :style="slideStyle(i)" :aria-hidden="i !== index">
            <img
                :src="route('admin.system.file.get.thumbnail.size', { size: 960, hashname: item.image })"
                :alt="item.title"
                class="size-full object-cover"
                draggable="false"
            />

            <!-- ข้อความของ banner ซ้อนบนภาพ (div ธรรมดา ไม่ใช้ h1/h2) -->
            <div
                v-if="showText(i)"
                class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-4 pt-10 text-white"
                :class="setting.show_dots === 'Y' ? 'pb-8' : 'pb-4'"
            >
                <div :class="textBoxClass">
                    <div v-if="setting.show_title === 'Y' && item.title" class="text-base font-semibold leading-snug">{{ item.title }}</div>
                    <div v-if="setting.show_intro_text === 'Y' && item.intro_text" class="mt-0.5 line-clamp-2 text-sm leading-snug text-white/90">
                        {{ item.intro_text }}
                    </div>
                </div>
            </div>

            <!-- ตัวอย่างแค่บอกว่า banner นี้มีลิงก์ — กดไม่ได้ ไม่มี URL -->
            <span
                v-if="setting.is_clickable === 'Y' && item.has_link"
                class="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full bg-black/55 px-2 py-0.5 text-[11px] text-white"
            >
                <Link2 class="size-3" /> ลิงก์
            </span>
        </div>

        <template v-if="count > 1">
            <template v-if="setting.show_arrows === 'Y'">
                <button
                    type="button"
                    class="absolute left-2 top-1/2 flex size-8 -translate-y-1/2 items-center justify-center rounded-full bg-black/40 text-white hover:bg-black/60"
                    aria-label="ก่อนหน้า"
                    @click="go(index - 1)"
                >
                    <ChevronLeft class="size-5" />
                </button>
                <button
                    type="button"
                    class="absolute right-2 top-1/2 flex size-8 -translate-y-1/2 items-center justify-center rounded-full bg-black/40 text-white hover:bg-black/60"
                    aria-label="ถัดไป"
                    @click="go(index + 1)"
                >
                    <ChevronRight class="size-5" />
                </button>
            </template>

            <div v-if="setting.show_dots === 'Y'" class="absolute inset-x-0 bottom-2 flex justify-center gap-1.5">
                <button
                    v-for="(item, i) in items"
                    :key="item.id"
                    type="button"
                    class="size-2 rounded-full transition-colors"
                    :class="i === index ? 'bg-white' : 'bg-white/50 hover:bg-white/80'"
                    :aria-label="`ภาพที่ ${i + 1}`"
                    @click="go(i)"
                />
            </div>
        </template>
    </div>
</template>
