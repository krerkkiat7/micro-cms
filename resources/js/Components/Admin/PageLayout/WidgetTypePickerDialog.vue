<script setup lang="ts">
import { ref, watch } from 'vue';
import LayoutDialog from './LayoutDialog.vue';
import { WIDGET_TYPE_DEFS } from '@/utils/pageWidget';
import type { WidgetSource } from '@/utils/pageWidget';

/**
 * ขั้นที่ 1 ของการเพิ่ม widget — เลือกประเภท แสดงเป็นการ์ดพร้อมภาพประกอบ (SVG อย่างง่ายวาดเอง เหมือน ArticlePart/ImagesDisplayTypePicker)
 * ประเภทที่ยังไม่พร้อม (`available = false`) แสดงเป็น "เร็ว ๆ นี้" เลือกไม่ได้ กด "ต่อไป" แล้วส่ง `next` พร้อมประเภทที่เลือก
 * (widget จะถูกเพิ่มลงหน้าเมื่อยืนยันใน dialog ตั้งค่าถัดไปเท่านั้น)
 */
const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
    next: [widgetType: string];
}>();

const selected = ref('');

// เปิด dialog ใหม่ทุกครั้งเริ่มจากยังไม่เลือก
watch(
    () => props.show,
    (show) => {
        if (show) selected.value = '';
    },
);

const SOURCE_LABELS: Record<WidgetSource, string> = { banner: 'banner', article: 'article', custom: 'กำหนดเอง' };

function next() {
    if (selected.value !== '') {
        emit('next', selected.value);
    }
}
</script>

<template>
    <LayoutDialog
        :show="show"
        title="เพิ่ม Widget — เลือกประเภท"
        description="เลือกรูปแบบการแสดงผลและแหล่งข้อมูลของ Widget แล้วกด &quot;ต่อไป&quot; เพื่อตั้งค่า (ประเภทเปลี่ยนไม่ได้หลังเพิ่มแล้ว)"
        confirm-text="ต่อไป"
        :confirm-disabled="selected === ''"
        @close="emit('close')"
        @confirm="next"
    >
        <div class="grid gap-3 sm:grid-cols-2">
            <label
                v-for="def in WIDGET_TYPE_DEFS"
                :key="def.value"
                class="relative flex flex-col rounded-xl border p-3 transition-colors"
                :class="[
                    !def.available ? 'cursor-not-allowed border-gray-200 bg-gray-50 opacity-60' : 'cursor-pointer',
                    def.available && selected === def.value ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500' : '',
                    def.available && selected !== def.value ? 'border-gray-200 bg-white hover:border-gray-300' : '',
                ]"
            >
                <input v-model="selected" type="radio" name="widget_type" :value="def.value" :disabled="!def.available" class="sr-only" />

                <span
                    v-if="!def.available"
                    class="absolute right-2 top-2 rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-medium text-gray-600"
                >
                    เร็ว ๆ นี้
                </span>

                <svg viewBox="0 0 120 80" class="h-20 w-full text-gray-300">
                    <!-- slideshow: ภาพเต็มกรอบเดียว + ลูกศรซ้าย/ขวา + จุดด้านล่างในกรอบ + ข้อความบนภาพ -->
                    <template v-if="def.layout === 'slideshow'">
                        <rect x="8" y="6" width="104" height="68" rx="4" class="fill-brand-200" />
                        <circle cx="18" cy="40" r="5" class="fill-white/80" />
                        <circle cx="102" cy="40" r="5" class="fill-white/80" />
                        <rect x="24" y="50" width="48" height="5" rx="2" class="fill-brand-500" />
                        <rect x="24" y="58" width="32" height="3" rx="1.5" class="fill-brand-400" />
                        <circle cx="52" cy="68" r="2" class="fill-white" />
                        <circle cx="60" cy="68" r="2" class="fill-white/60" />
                        <circle cx="68" cy="68" r="2" class="fill-white/60" />
                    </template>

                    <!-- slideset: การ์ดหลายใบ เศษการ์ดโผล่ขอบซ้าย/ขวาบอกว่าเลื่อนได้ -->
                    <template v-else-if="def.layout === 'slideset'">
                        <rect x="-8" y="14" width="18" height="52" rx="3" class="fill-gray-200" />
                        <rect x="14" y="14" width="28" height="52" rx="3" class="fill-brand-300" />
                        <rect x="46" y="14" width="28" height="52" rx="3" class="fill-brand-400" />
                        <rect x="78" y="14" width="28" height="52" rx="3" class="fill-brand-300" />
                        <rect x="110" y="14" width="18" height="52" rx="3" class="fill-gray-200" />
                        <rect x="17" y="56" width="18" height="3" rx="1.5" class="fill-white/80" />
                        <rect x="49" y="56" width="18" height="3" rx="1.5" class="fill-white/80" />
                        <rect x="81" y="56" width="18" height="3" rx="1.5" class="fill-white/80" />
                    </template>

                    <!-- grid: กล่องเรียงต่อเนื่อง 3 × 2 -->
                    <template v-else-if="def.layout === 'grid'">
                        <rect x="8" y="8" width="32" height="30" rx="3" class="fill-brand-300" />
                        <rect x="44" y="8" width="32" height="30" rx="3" class="fill-brand-400" />
                        <rect x="80" y="8" width="32" height="30" rx="3" class="fill-brand-300" />
                        <rect x="8" y="42" width="32" height="30" rx="3" class="fill-brand-400" />
                        <rect x="44" y="42" width="32" height="30" rx="3" class="fill-brand-300" />
                        <rect x="80" y="42" width="32" height="30" rx="3" class="fill-brand-400" />
                    </template>

                    <!-- text: หัวเรื่อง + ย่อหน้า -->
                    <template v-else>
                        <rect x="16" y="12" width="60" height="8" rx="2" class="fill-brand-400" />
                        <rect x="16" y="28" width="88" height="4" rx="2" class="fill-gray-300" />
                        <rect x="16" y="37" width="80" height="4" rx="2" class="fill-gray-300" />
                        <rect x="16" y="46" width="88" height="4" rx="2" class="fill-gray-300" />
                        <rect x="16" y="55" width="52" height="4" rx="2" class="fill-gray-300" />
                        <rect x="16" y="64" width="70" height="4" rx="2" class="fill-gray-200" />
                    </template>
                </svg>

                <div class="mt-2 flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-800">{{ def.label }}</span>
                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[11px] text-gray-500">{{ SOURCE_LABELS[def.source] }}</span>
                </div>
                <p class="mt-0.5 text-xs text-gray-500">{{ def.description }}</p>
            </label>
        </div>
    </LayoutDialog>
</template>
