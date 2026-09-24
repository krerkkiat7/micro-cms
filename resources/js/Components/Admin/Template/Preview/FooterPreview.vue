<script setup lang="ts">
import { computed } from 'vue';
import type { CSSProperties } from 'vue';
import { Mail, MapPin, Phone, Printer, Smartphone } from 'lucide-vue-next';
import { SOCIAL_LABELS, zoneBackgroundStyle } from '@/utils/template';
import type { FooterZone, TemplatePreviewData, Width } from '@/utils/template';

/**
 * ตัวอย่าง footer + แถบลิขสิทธิ์ — ข้อมูลติดต่อ / social / ลิขสิทธิ์จากตั้งค่าระบบ, เมนูระดับแรกจากเมนูหน้าบ้าน
 * ข้อมูลที่ยังไม่ได้ตั้งค่าในระบบแสดงเป็นข้อความจาง ๆ ให้รู้ว่าตำแหน่งนั้นจะมีอะไร
 */
const props = defineProps<{
    zone: FooterZone;
    preview: TemplatePreviewData;
}>();

function font(family: string, size: number, bold: string, color: string): CSSProperties {
    return { fontFamily: `'${family}', sans-serif`, fontSize: `${size}px`, fontWeight: bold === 'Y' ? 700 : 400, color };
}

const headingStyle = computed(() => font(props.zone.heading_font_family, props.zone.heading_font_size, props.zone.heading_bold, props.zone.heading_color));
const textStyle = computed(() => font(props.zone.text_font_family, props.zone.text_font_size, props.zone.text_bold, props.zone.text_color));
const copyrightStyle = computed<CSSProperties>(() => ({
    ...font(props.zone.copyright_font_family, props.zone.copyright_font_size, 'N', props.zone.copyright_text_color),
    backgroundColor: props.zone.copyright_background_color,
    textAlign: props.zone.copyright_align,
}));

const contactLines = computed(() => {
    const c = props.preview.contact;
    const z = props.zone;

    return [
        { key: 'address', show: z.show_address, icon: MapPin, value: c.address, placeholder: 'ที่อยู่' },
        { key: 'phone', show: z.show_phone, icon: Phone, value: c.phone, placeholder: 'เบอร์ติดต่อ' },
        { key: 'fax', show: z.show_fax, icon: Printer, value: c.fax, placeholder: 'เบอร์แฟกซ์' },
        { key: 'mobile', show: z.show_mobile, icon: Smartphone, value: c.mobile, placeholder: 'เบอร์มือถือ' },
        { key: 'email', show: z.show_email, icon: Mail, value: c.email, placeholder: 'อีเมล' },
    ].filter((line) => line.show === 'Y');
});

const socials = computed(() => (props.preview.social.length ? props.preview.social : ['facebook', 'youtube', 'line']));

const copyrightText = computed(() => {
    const { year, owner } = props.preview.copyright;

    return props.zone.copyright_show_owner === 'Y' ? `© ${year} ${owner} สงวนลิขสิทธิ์` : `© ${year} สงวนลิขสิทธิ์`;
});

function barClass(width: Width): string {
    return width === 'container' ? 'mx-auto w-[88%]' : 'w-full';
}
</script>

<template>
    <div>
        <div :class="barClass(zone.width)" :style="zoneBackgroundStyle(zone)">
            <!-- ข้อมูลไซต์, ข้อมูลติดต่อ และเมนู -->
            <div v-if="zone.layout_type === 'site_contact_menu'" class="grid gap-6 px-6 py-8 sm:grid-cols-3">
                <div class="space-y-2">
                    <div :style="headingStyle">{{ preview.siteName }}</div>
                    <div v-if="zone.show_social === 'Y'" class="flex gap-1.5" :style="textStyle">
                        <span v-for="key in socials" :key="key" class="flex size-6 items-center justify-center rounded-full border border-current text-[10px]">
                            {{ (SOCIAL_LABELS[key] ?? key).charAt(0) }}
                        </span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div :style="headingStyle">ติดต่อเรา</div>
                    <div v-for="line in contactLines" :key="line.key" class="flex items-start gap-2" :style="textStyle">
                        <component :is="line.icon" class="mt-0.5 size-4 shrink-0 opacity-70" />
                        <span :class="line.value ? '' : 'italic opacity-50'">{{ line.value || `${line.placeholder} (ยังไม่ได้ตั้งค่า)` }}</span>
                    </div>
                </div>
                <div v-if="zone.show_menu === 'Y'" class="space-y-2">
                    <div :style="headingStyle">เมนู</div>
                    <div v-for="menu in preview.menu" :key="menu.id" :style="textStyle">{{ menu.name }}</div>
                    <div v-if="preview.menu.length === 0" class="italic opacity-50" :style="textStyle">(ยังไม่มีเมนูหน้าบ้าน)</div>
                </div>
            </div>

            <!-- ข้อมูลไซต์, ข้อมูลติดต่อ (จัดกึ่งกลาง) -->
            <div v-else-if="zone.layout_type === 'site_contact_center'" class="space-y-3 px-6 py-8 text-center">
                <div :style="headingStyle">{{ preview.siteName }}</div>
                <div class="flex flex-wrap justify-center gap-x-4 gap-y-1" :style="textStyle">
                    <span v-for="line in contactLines" :key="line.key" class="inline-flex items-center gap-1.5">
                        <component :is="line.icon" class="size-4 opacity-70" />
                        <span :class="line.value ? '' : 'italic opacity-50'">{{ line.value || line.placeholder }}</span>
                    </span>
                </div>
                <div v-if="zone.show_social === 'Y'" class="flex justify-center gap-1.5" :style="textStyle">
                    <span v-for="key in socials" :key="key" class="flex size-6 items-center justify-center rounded-full border border-current text-[10px]">
                        {{ (SOCIAL_LABELS[key] ?? key).charAt(0) }}
                    </span>
                </div>
            </div>

            <!-- ข้อมูลไซต์, ข้อมูลติดต่อ (จัดเป็นบล็อก) -->
            <div v-else class="grid gap-4 px-6 py-8 sm:grid-cols-2">
                <div class="space-y-2 rounded-xl bg-white/5 p-5 ring-1 ring-black/5">
                    <div :style="headingStyle">{{ preview.siteName }}</div>
                    <div v-if="zone.show_social === 'Y'" class="flex gap-1.5" :style="textStyle">
                        <span v-for="key in socials" :key="key" class="flex size-6 items-center justify-center rounded-full border border-current text-[10px]">
                            {{ (SOCIAL_LABELS[key] ?? key).charAt(0) }}
                        </span>
                    </div>
                </div>
                <div class="space-y-2 rounded-xl bg-white/5 p-5 ring-1 ring-black/5">
                    <div :style="headingStyle">ติดต่อเรา</div>
                    <div v-for="line in contactLines" :key="line.key" class="flex items-start gap-2" :style="textStyle">
                        <component :is="line.icon" class="mt-0.5 size-4 shrink-0 opacity-70" />
                        <span :class="line.value ? '' : 'italic opacity-50'">{{ line.value || `${line.placeholder} (ยังไม่ได้ตั้งค่า)` }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- แถบลิขสิทธิ์ -->
        <div v-if="zone.copyright_status === 'Y'" :class="barClass(zone.copyright_width)" class="px-6 py-3" :style="copyrightStyle">
            {{ copyrightText }}
        </div>
    </div>
</template>
