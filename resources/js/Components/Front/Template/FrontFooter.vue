<script setup lang="ts">
import { computed } from 'vue';
import type { CSSProperties } from 'vue';
import { Globe, Mail, MapPin, Phone, Printer, Smartphone } from 'lucide-vue-next';
import FrontLink from '@/Components/Front/FrontLink.vue';
import SocialLinks from '@/Components/Front/Template/SocialLinks.vue';
import { useFront } from '@/composables/useFront';
import { zoneBackgroundCss } from '@/utils/front';
import type { FrontFooterZone, FrontMenuItem } from '@/utils/front';
import { LINK_MENU_TYPES } from '@/utils/template';
import type { Width } from '@/utils/template';

/**
 * footer หน้าบ้านตาม template (sys_template_footer) — โครงเดียวกับตัวอย่างในหลังบ้าน (Admin/Template/Preview/FooterPreview.vue):
 * ข้อมูลไซต์ + social / ข้อมูลติดต่อ (เฉพาะที่เลือกแสดงและตั้งค่าไว้ — เบอร์/อีเมลเป็นลิงก์ tel:/mailto:) / เมนู (ระดับแรก + ระดับสองที่เป็นลิงก์)
 * + แถบลิขสิทธิ์ ข้อมูลติดต่อใช้ <address> (ความหมายเชิงโครงสร้าง — ช่วย SEO/GEO)
 */
const props = defineProps<{ zone: FrontFooterZone }>();

const { front, t } = useFront();

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
    const c = front.value.contact;
    const z = props.zone;

    return [
        { key: 'address', show: z.show_address, icon: MapPin, value: c.address, href: null },
        { key: 'phone', show: z.show_phone, icon: Phone, value: c.phone, href: c.phone ? `tel:${c.phone.replace(/[^\d+]/g, '')}` : null },
        { key: 'fax', show: z.show_fax, icon: Printer, value: c.fax, href: null },
        { key: 'mobile', show: z.show_mobile, icon: Smartphone, value: c.mobile, href: c.mobile ? `tel:${c.mobile.replace(/[^\d+]/g, '')}` : null },
        { key: 'email', show: z.show_email, icon: Mail, value: c.email, href: c.email ? `mailto:${c.email}` : null },
    ].filter((line) => line.show === 'Y' && line.value);
});

/** เมนูระดับแรก + เมนูย่อยระดับที่สองที่เป็นลิงก์จริงเท่านั้น */
const footerMenus = computed(() =>
    front.value.menu.map((menu) => ({
        ...menu,
        children: menu.children.filter((child: FrontMenuItem) => LINK_MENU_TYPES.includes(child.menu_type) && child.url),
    })),
);

const copyrightText = computed(() => {
    const { year, owner } = front.value.copyright;

    return props.zone.copyright_show_owner === 'Y' ? `© ${year} ${owner} ${t('all_rights_reserved')}` : `© ${year} ${t('all_rights_reserved')}`;
});

function contentClass(width: Width): string {
    return width === 'container' ? 'mx-auto w-full max-w-7xl' : 'w-full';
}
</script>

<template>
    <footer>
        <div :style="zoneBackgroundCss(zone)">
            <div :class="contentClass(zone.width)">
                <div
                    class="gap-8 px-6 py-10"
                    :class="{
                        'grid md:grid-cols-3': zone.layout_type === 'site_contact_menu',
                        'flex flex-col items-center gap-4 text-center': zone.layout_type === 'site_contact_center',
                        'grid md:grid-cols-2': zone.layout_type === 'site_contact_block',
                    }"
                >
                    <!-- ข้อมูลไซต์ -->
                    <div class="space-y-3" :class="zone.layout_type === 'site_contact_block' ? 'rounded-xl bg-white/5 p-5 ring-1 ring-black/5' : ''">
                        <div class="flex items-center gap-2" :class="zone.layout_type === 'site_contact_center' ? 'flex-col' : ''">
                            <img v-if="front.site.logoUrl" :src="front.site.logoUrl" alt="" class="h-12 w-auto object-contain" />
                            <span v-else class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-white" aria-hidden="true"><Globe class="size-5" /></span>
                            <p :style="headingStyle">{{ front.site.name }}</p>
                        </div>
                        <div v-if="zone.show_social === 'Y'" :style="textStyle">
                            <SocialLinks />
                        </div>
                    </div>

                    <!-- ข้อมูลติดต่อ -->
                    <div
                        v-if="contactLines.length || front.contact.owner"
                        class="space-y-2"
                        :class="zone.layout_type === 'site_contact_block' ? 'rounded-xl bg-white/5 p-5 ring-1 ring-black/5' : ''"
                    >
                        <h2 v-if="zone.layout_type !== 'site_contact_center'" :style="headingStyle">{{ t('contact_us') }}</h2>
                        <address class="space-y-2 not-italic" :style="textStyle">
                            <p v-if="front.contact.owner" class="font-semibold">{{ front.contact.owner }}</p>
                            <p
                                v-for="line in contactLines"
                                :key="line.key"
                                class="flex items-start gap-2"
                                :class="zone.layout_type === 'site_contact_center' ? 'justify-center' : ''"
                            >
                                <component :is="line.icon" class="mt-0.5 size-4 shrink-0 opacity-70" aria-hidden="true" />
                                <span class="sr-only">{{ t(line.key) }}:</span>
                                <a v-if="line.href" :href="line.href">{{ line.value }}</a>
                                <span v-else class="whitespace-pre-line">{{ line.value }}</span>
                            </p>
                        </address>
                    </div>

                    <!-- เมนู -->
                    <nav v-if="zone.layout_type === 'site_contact_menu' && zone.show_menu === 'Y' && footerMenus.length" class="space-y-2" :aria-label="t('footer_menu')">
                        <h2 :style="headingStyle">{{ t('menu') }}</h2>
                        <ul class="space-y-1.5" :style="textStyle">
                            <li v-for="menu in footerMenus" :key="menu.id">
                                <FrontLink v-if="menu.url" :href="menu.url" :target="menu.target">{{ menu.name }}</FrontLink>
                                <span v-else>{{ menu.name }}</span>
                                <ul v-if="menu.children.length" class="mt-1 mb-1.5 space-y-1 border-l border-current/20 pl-3 text-[0.9em] opacity-90">
                                    <li v-for="child in menu.children" :key="child.id">
                                        <FrontLink :href="child.url!" :target="child.target">{{ child.name }}</FrontLink>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- แถบลิขสิทธิ์ -->
        <div v-if="zone.copyright_status === 'Y'" :style="{ backgroundColor: zone.copyright_background_color }">
            <p class="px-6 py-3" :class="contentClass(zone.copyright_width)" :style="copyrightStyle">{{ copyrightText }}</p>
        </div>
    </footer>
</template>
