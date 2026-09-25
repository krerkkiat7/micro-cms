<script setup lang="ts">
import { computed } from 'vue';
import type { CSSProperties } from 'vue';
import { Globe, Mail, MapPin, Phone, Printer, Smartphone } from 'lucide-vue-next';
import SocialIcon from '@/Components/Template/SocialIcon.vue';
import { LINK_MENU_TYPES, SOCIAL_LABELS, zoneBackgroundStyle } from '@/utils/template';
import type { FooterZone, PreviewMenu, TemplatePreviewData, Width } from '@/utils/template';

/**
 * ตัวอย่าง footer + แถบลิขสิทธิ์
 * - ข้อมูลไซต์ = โลโก้ + ชื่อเว็บ (+ social) · ข้อมูลติดต่อ = ชื่อเจ้าของไซต์ (ถ้าตั้งค่าไว้) + ที่อยู่/เบอร์/อีเมลตามที่เลือกแสดง
 * - เมนู = เมนูระดับแรก พร้อมเมนูย่อยระดับที่สองเฉพาะที่เป็นลิงก์ (ของในระบบ / ลิงก์ภายนอก — LINK_MENU_TYPES) ระดับลึกกว่านั้นไม่แสดง
 * - ขอบเขตความกว้างมีผลกับข้อมูลเท่านั้น พื้นหลังเต็มหน้าจอเสมอ
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

/** เมนูระดับแรก + เมนูย่อยระดับที่สองที่เป็นลิงก์จริงเท่านั้น (ตัดเมนูหัวข้อ/ไม่มีลิงก์ และระดับที่ 3 ขึ้นไป) */
const footerMenus = computed(() =>
    props.preview.menu.map((menu) => ({
        ...menu,
        children: menu.children.filter((child: PreviewMenu) => LINK_MENU_TYPES.includes(child.menu_type)),
    })),
);

const copyrightText = computed(() => {
    const { year, owner } = props.preview.copyright;

    return props.zone.copyright_show_owner === 'Y' ? `© ${year} ${owner} สงวนลิขสิทธิ์` : `© ${year} สงวนลิขสิทธิ์`;
});

/** ขอบเขตความกว้างมีผลกับข้อมูลเท่านั้น — ตาม container = กรอบกึ่งกลาง (ในตัวอย่างใช้ 88% ของพื้นที่) */
function contentClass(width: Width): string {
    return width === 'container' ? 'mx-auto w-[88%]' : 'w-full';
}
</script>

<template>
    <div>
        <div :style="zoneBackgroundStyle(zone)">
            <div :class="contentClass(zone.width)">
                <!-- ข้อมูลไซต์, ข้อมูลติดต่อ และเมนู -->
                <div v-if="zone.layout_type === 'site_contact_menu'" class="grid gap-6 px-6 py-8 sm:grid-cols-3">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <img v-if="preview.logoUrl" :src="preview.logoUrl" alt="" class="h-10 w-auto object-contain" />
                            <span v-else class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-white"><Globe class="size-5" /></span>
                            <div :style="headingStyle">{{ preview.siteName }}</div>
                        </div>
                        <div v-if="zone.show_social === 'Y'" class="flex gap-3" :style="textStyle">
                            <span v-for="key in socials" :key="key" :title="SOCIAL_LABELS[key]"><SocialIcon :name="key" /></span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div :style="headingStyle">ติดต่อเรา</div>
                        <div v-if="preview.contact.owner" class="font-semibold" :style="textStyle">{{ preview.contact.owner }}</div>
                        <div v-for="line in contactLines" :key="line.key" class="flex items-start gap-2" :style="textStyle">
                            <component :is="line.icon" class="mt-0.5 size-4 shrink-0 opacity-70" />
                            <span :class="line.value ? '' : 'italic opacity-50'">{{ line.value || `${line.placeholder} (ยังไม่ได้ตั้งค่า)` }}</span>
                        </div>
                    </div>
                    <div v-if="zone.show_menu === 'Y'" class="space-y-2">
                        <div :style="headingStyle">เมนู</div>
                        <div v-for="menu in footerMenus" :key="menu.id" :style="textStyle">
                            <div>{{ menu.name }}</div>
                            <ul v-if="menu.children.length" class="mt-1 mb-1.5 space-y-0.5 border-l border-current/20 pl-3 text-[0.9em] opacity-80">
                                <li v-for="child in menu.children" :key="child.id">{{ child.name }}</li>
                            </ul>
                        </div>
                        <div v-if="footerMenus.length === 0" class="italic opacity-50" :style="textStyle">(ยังไม่มีเมนูหน้าบ้าน)</div>
                    </div>
                </div>

                <!-- ข้อมูลไซต์, ข้อมูลติดต่อ (จัดกึ่งกลาง) -->
                <div v-else-if="zone.layout_type === 'site_contact_center'" class="space-y-3 px-6 py-8 text-center">
                    <div class="flex flex-col items-center gap-2">
                        <img v-if="preview.logoUrl" :src="preview.logoUrl" alt="" class="h-12 w-auto object-contain" />
                        <span v-else class="flex size-12 items-center justify-center rounded-lg bg-brand-500 text-white"><Globe class="size-6" /></span>
                        <div :style="headingStyle">{{ preview.siteName }}</div>
                    </div>
                    <div v-if="preview.contact.owner" class="font-semibold" :style="textStyle">{{ preview.contact.owner }}</div>
                    <div class="flex flex-wrap justify-center gap-x-4 gap-y-1" :style="textStyle">
                        <span v-for="line in contactLines" :key="line.key" class="inline-flex items-center gap-1.5">
                            <component :is="line.icon" class="size-4 opacity-70" />
                            <span :class="line.value ? '' : 'italic opacity-50'">{{ line.value || line.placeholder }}</span>
                        </span>
                    </div>
                    <div v-if="zone.show_social === 'Y'" class="flex justify-center gap-3" :style="textStyle">
                        <span v-for="key in socials" :key="key" :title="SOCIAL_LABELS[key]"><SocialIcon :name="key" /></span>
                    </div>
                </div>

                <!-- ข้อมูลไซต์, ข้อมูลติดต่อ (จัดเป็นบล็อก) -->
                <div v-else class="grid gap-4 px-6 py-8 sm:grid-cols-2">
                    <div class="space-y-3 rounded-xl bg-white/5 p-5 ring-1 ring-black/5">
                        <div class="flex items-center gap-2">
                            <img v-if="preview.logoUrl" :src="preview.logoUrl" alt="" class="h-10 w-auto object-contain" />
                            <span v-else class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-white"><Globe class="size-5" /></span>
                            <div :style="headingStyle">{{ preview.siteName }}</div>
                        </div>
                        <div v-if="zone.show_social === 'Y'" class="flex gap-3" :style="textStyle">
                            <span v-for="key in socials" :key="key" :title="SOCIAL_LABELS[key]"><SocialIcon :name="key" /></span>
                        </div>
                    </div>
                    <div class="space-y-2 rounded-xl bg-white/5 p-5 ring-1 ring-black/5">
                        <div :style="headingStyle">ติดต่อเรา</div>
                        <div v-if="preview.contact.owner" class="font-semibold" :style="textStyle">{{ preview.contact.owner }}</div>
                        <div v-for="line in contactLines" :key="line.key" class="flex items-start gap-2" :style="textStyle">
                            <component :is="line.icon" class="mt-0.5 size-4 shrink-0 opacity-70" />
                            <span :class="line.value ? '' : 'italic opacity-50'">{{ line.value || `${line.placeholder} (ยังไม่ได้ตั้งค่า)` }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- แถบลิขสิทธิ์ -->
        <div v-if="zone.copyright_status === 'Y'" :style="{ backgroundColor: zone.copyright_background_color }">
            <div class="px-6 py-3" :class="contentClass(zone.copyright_width)" :style="copyrightStyle">
                {{ copyrightText }}
            </div>
        </div>
    </div>
</template>
