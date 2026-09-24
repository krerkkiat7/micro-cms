<script setup lang="ts">
import { computed } from 'vue';
import type { CSSProperties } from 'vue';
import FrontLink from '@/Components/Front/FrontLink.vue';
import { useFront } from '@/composables/useFront';
import { readAllIconComponent } from '@/utils/readAllButton';

/**
 * ปุ่ม/ลิงก์ "อ่านทั้งหมด" ของ widget กลุ่ม article (Slideset / Grid) — หน้าตาเดียวกับตัวอย่างในหลังบ้าน (widgets/ReadAllButton.vue)
 * แต่เป็นลิงก์จริง (read_all_url) ไม่มี URL ที่ใช้ได้ = ไม่แสดง
 */
const props = defineProps<{
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setting: Record<string, any>;
}>();

const { t } = useFront();

const text = computed(() => String(props.setting.read_all_text || '').trim() || t('read_all'));
const icon = computed(() => readAllIconComponent(props.setting.read_all_icon ?? 'none'));
const align = computed(() => ({ left: 'justify-start', center: 'justify-center', right: 'justify-end' })[String(props.setting.read_all_position ?? 'bottom_center').split('_')[1] as 'left'] ?? 'justify-center');

const classes = computed(() => {
    switch (props.setting.read_all_style) {
        case 'link':
            return 'font-medium underline underline-offset-2';
        case 'pill':
            return 'rounded-full px-7 py-2.5 font-medium';
        default:
            return 'rounded-md px-4 py-2 font-medium';
    }
});

const style = computed<CSSProperties>(() => ({
    fontSize: `${props.setting.read_all_font_size ?? 14}px`,
    fontFamily: `'${props.setting.read_all_font_family ?? 'Sarabun'}', sans-serif`,
    color: props.setting.read_all_color,
    backgroundColor: props.setting.read_all_style === 'link' ? undefined : props.setting.read_all_background,
}));
</script>

<template>
    <div v-if="setting.show_read_all === 'Y' && setting.read_all_url" class="flex" :class="align">
        <FrontLink :href="setting.read_all_url" :target="setting.read_all_link_target" class="inline-flex items-center gap-1.5 whitespace-nowrap hover:opacity-90" :class="classes" :style="style">
            <component :is="icon" v-if="icon && setting.read_all_icon_position === 'before'" class="size-[1.2em] shrink-0" aria-hidden="true" />
            {{ text }}
            <component :is="icon" v-if="icon && setting.read_all_icon_position !== 'before'" class="size-[1.2em] shrink-0" aria-hidden="true" />
        </FrontLink>
    </div>
</template>
