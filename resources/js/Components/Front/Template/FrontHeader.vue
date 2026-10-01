<script setup lang="ts">
import { computed } from 'vue';
import { Globe, Menu as MenuIcon } from 'lucide-vue-next';
import FrontLink from '@/Components/Front/FrontLink.vue';
import A11yTools from '@/Components/Front/Template/A11yTools.vue';
import HeaderNavItem from '@/Components/Front/Template/HeaderNavItem.vue';
import LanguageSwitcher from '@/Components/Front/Template/LanguageSwitcher.vue';
import SocialLinks from '@/Components/Front/Template/SocialLinks.vue';
import { useFront } from '@/composables/useFront';
import { zoneBackgroundCss } from '@/utils/front';
import type { FrontAsideZone, FrontHeaderZone, SeoData } from '@/utils/front';
import { JUSTIFY } from '@/utils/template';
import type { Align, Width } from '@/utils/template';

/**
 * header หน้าบ้านตาม template ที่เปิดใช้งาน (sys_template_header) — โครงเดียวกับตัวอย่างในหลังบ้าน (Admin/Template/Preview/HeaderPreview.vue)
 * แถบบน (social/เครื่องมือ) / แถบหลัก (โลโก้ เมนู เครื่องมือ ปุ่มเมนูข้าง แบ่ง 3 ช่องซ้าย-กลาง-ขวา) / แถวเมนู
 * จอเล็ก (< lg) เมนูแนวนอนซ่อน ใช้ปุ่มเปิดเมนูข้างแทนเสมอ (แม้ปิด aside ไว้ในตั้งค่า)
 */
const props = defineProps<{
    zone: FrontHeaderZone;
    aside: FrontAsideZone;
    activeIds: number[];
    seo?: SeoData | null;
    asideOpen: boolean;
}>();

const emit = defineEmits<{ openAside: [] }>();

const { front, t } = useFront();

type Slot = 'logo' | 'menu' | 'tools' | 'toggle';

const togglePosition = computed<Align>(() => (props.aside.toggle_position === 'left' ? 'left' : 'right'));

const slots = computed(() => {
    const result: Record<Align, Slot[]> = { left: [], center: [], right: [] };
    const z = props.zone;

    if (togglePosition.value === 'left') result.left.push('toggle');
    if (z.logo_status === 'Y') result[z.logo_align].push('logo');
    if (z.layout_type !== 'main_menubar') result[z.menu_align].push('menu');
    if (z.layout_type !== 'topbar_main') result.right.push('tools');
    if (togglePosition.value === 'right') result.right.push('toggle');

    return result;
});

function contentClass(width: Width): string {
    return width === 'container' ? 'mx-auto w-full max-w-7xl' : 'w-full';
}

const showTools = computed(() => [props.zone.fontsize_status, props.zone.contrast_status, props.zone.lang_status].includes('Y'));
const showLogoImage = computed(() => props.zone.logo_display !== 'name');
const showSiteName = computed(() => props.zone.logo_display !== 'image');
</script>

<template>
    <header :class="zone.sticky === 'Y' ? 'sticky top-0 z-30' : 'relative z-30'">
        <!-- แถบบน: social ซ้าย / เครื่องมือขวา -->
        <div
            v-if="zone.layout_type === 'topbar_main'"
            class="hidden sm:block"
            :style="{ backgroundColor: zone.topbar_background_color, color: zone.topbar_text_color }"
        >
            <div class="flex min-h-10 items-center justify-between gap-3 px-4 py-1" :class="contentClass(zone.topbar_width)">
                <SocialLinks v-if="zone.social_status === 'Y'" />
                <span v-else />
                <div v-if="showTools" class="flex flex-wrap items-center gap-3">
                    <A11yTools
                        :show-font-size="zone.fontsize_status === 'Y'"
                        :font-size-display="zone.fontsize_display"
                        :show-contrast="zone.contrast_status === 'Y'"
                        :contrast-display="zone.contrast_display"
                    />
                    <LanguageSwitcher v-if="zone.lang_status === 'Y'" :display="zone.lang_display" :select="zone.lang_select" :seo="seo" />
                </div>
            </div>
        </div>

        <!-- แถบหลัก -->
        <div :style="{ ...zoneBackgroundCss(zone), color: zone.main_text_color }" class="shadow-sm">
            <div class="grid min-h-16 grid-cols-[1fr_auto_1fr] items-center gap-3 px-4 py-2" :class="contentClass(zone.main_width)">
                <div v-for="align in (['left', 'center', 'right'] as Align[])" :key="align" class="flex min-w-0 items-center gap-3" :class="JUSTIFY[align]">
                    <template v-for="item in slots[align]" :key="item">
                        <button
                            v-if="item === 'toggle'"
                            type="button"
                            class="inline-flex size-10 items-center justify-center rounded-md hover:bg-current/10"
                            :class="aside.status === 'Y' ? '' : 'lg:hidden'"
                            :aria-label="t('open_menu')"
                            :aria-expanded="asideOpen"
                            aria-controls="front-aside"
                            @click="emit('openAside')"
                        >
                            <MenuIcon class="size-6" aria-hidden="true" />
                        </button>

                        <component
                            :is="zone.logo_action === 'home' ? FrontLink : 'div'"
                            v-else-if="item === 'logo'"
                            v-bind="zone.logo_action === 'home' ? { href: front.homeUrl } : {}"
                            class="flex min-w-0 items-center gap-2"
                        >
                            <template v-if="showLogoImage">
                                <img v-if="front.site.logoUrl" :src="front.site.logoUrl" :alt="showSiteName ? '' : front.site.name" class="h-10 w-auto max-w-[45vw] object-contain" />
                                <span v-else class="flex size-10 items-center justify-center rounded-lg bg-brand-500 text-white" aria-hidden="true"><Globe class="size-5" /></span>
                            </template>
                            <span v-if="showSiteName" class="truncate text-lg font-semibold">{{ front.site.name }}</span>
                            <span v-else-if="!front.site.logoUrl" class="sr-only">{{ front.site.name }}</span>
                        </component>

                        <nav v-else-if="item === 'menu'" class="hidden lg:block" :aria-label="t('main_menu')">
                            <ul class="flex flex-wrap items-center" :class="zone.menu_style === 'pill' ? 'gap-1' : ''">
                                <HeaderNavItem
                                    v-for="(menu, index) in front.menu"
                                    :key="menu.id"
                                    :item="menu"
                                    :level="0"
                                    :active-ids="activeIds"
                                    :zone="zone"
                                    :divider="zone.menu_style === 'divider' && index > 0"
                                />
                            </ul>
                        </nav>

                        <div v-else-if="item === 'tools'" class="hidden flex-wrap items-center gap-3 sm:flex">
                            <SocialLinks v-if="zone.social_status === 'Y'" />
                            <A11yTools
                                :show-font-size="zone.fontsize_status === 'Y'"
                                :font-size-display="zone.fontsize_display"
                                :show-contrast="zone.contrast_status === 'Y'"
                                :contrast-display="zone.contrast_display"
                            />
                            <LanguageSwitcher v-if="zone.lang_status === 'Y'" :display="zone.lang_display" :select="zone.lang_select" :seo="seo" />
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- แถวเมนู -->
        <div v-if="zone.layout_type === 'main_menubar'" class="hidden lg:block" :style="{ backgroundColor: zone.menubar_background_color }">
            <nav class="flex px-4" :class="[contentClass(zone.menubar_width), JUSTIFY[zone.menu_align]]" :aria-label="t('main_menu')">
                <ul class="flex flex-wrap items-center" :class="zone.menu_style === 'pill' ? 'gap-1 py-1' : ''">
                    <HeaderNavItem
                        v-for="(menu, index) in front.menu"
                        :key="menu.id"
                        :item="menu"
                        :level="0"
                        :active-ids="activeIds"
                        :zone="zone"
                        :divider="zone.menu_style === 'divider' && index > 0"
                    />
                </ul>
            </nav>
        </div>
    </header>
</template>
