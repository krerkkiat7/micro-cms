<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { ChevronLeft, ChevronRight, X } from 'lucide-vue-next';
import FrontLink from '@/Components/Front/FrontLink.vue';
import A11yTools from '@/Components/Front/Template/A11yTools.vue';
import AsideMenuList from '@/Components/Front/Template/AsideMenuList.vue';
import LanguageSwitcher from '@/Components/Front/Template/LanguageSwitcher.vue';
import SocialLinks from '@/Components/Front/Template/SocialLinks.vue';
import { useFront } from '@/composables/useFront';
import { zoneBackgroundCss } from '@/utils/front';
import type { FrontAsideZone, FrontHeaderZone, FrontMenuItem, SeoData } from '@/utils/front';

/**
 * แผงเมนูข้าง (sys_template_aside) — แถบข้าง (drawer) หรือเต็มจอ, รูปแบบเมนู list / accordion / drilldown / large
 * เป็น modal dialog (role="dialog" aria-modal) — โฟกัสวนอยู่ในแผง, Esc/ปุ่มปิด/คลิกพื้นหลังเพื่อปิด แล้วคืนโฟกัสให้ปุ่มที่เปิด
 * ด้านล่างเป็นเครื่องมือตามตั้งค่า header (social / ภาษา / ขนาดตัวอักษร / การแสดงสี — ไม่มีค้นหา)
 */
const props = defineProps<{
    zone: FrontAsideZone;
    header: FrontHeaderZone;
    activeIds: number[];
    seo?: SeoData | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const { front, t } = useFront();

const panel = ref<HTMLElement | null>(null);
let returnFocus: HTMLElement | null = null;

const isFullscreen = computed(() => props.zone.display_type === 'fullscreen');
const fromLeft = computed(() => props.zone.toggle_position === 'left');

// ---- drilldown: เส้นทางของเมนูย่อยที่เปิดเข้าไป ----
const drillPath = ref<FrontMenuItem[]>([]);
const drillItems = computed(() => (drillPath.value.length ? drillPath.value[drillPath.value.length - 1].children : front.value.menu));

function drillInto(item: FrontMenuItem): void {
    drillPath.value = [...drillPath.value, item];
    void nextTick(() => panel.value?.querySelector<HTMLElement>('[data-drill-back]')?.focus());
}

function drillBack(): void {
    drillPath.value = drillPath.value.slice(0, -1);
}

function close(): void {
    open.value = false;
}

function focusables(): HTMLElement[] {
    return Array.from(
        panel.value?.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])') ?? [],
    ).filter((el) => el.offsetParent !== null);
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        event.stopPropagation();
        close();

        return;
    }

    if (event.key !== 'Tab') return;

    const items = focusables();
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

watch(open, (value) => {
    if (value) {
        returnFocus = document.activeElement as HTMLElement | null;
        drillPath.value = [];
        document.body.style.overflow = 'hidden';
        void nextTick(() => panel.value?.focus());
    } else {
        document.body.style.overflow = '';
        returnFocus?.focus();
    }
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});

const hasTools = computed(() => [props.header.social_status, props.header.lang_status, props.header.fontsize_status, props.header.contrast_status].includes('Y'));
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-50" @keydown="onKeydown">
                <div class="absolute inset-0 bg-black/50" aria-hidden="true" @click="close" />

                <div
                    id="front-aside"
                    ref="panel"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="t('menu')"
                    tabindex="-1"
                    class="absolute inset-y-0 flex flex-col shadow-2xl outline-none"
                    :class="[isFullscreen ? 'inset-x-0' : 'w-80 max-w-[85vw]', !isFullscreen && fromLeft ? 'left-0' : '', !isFullscreen && !fromLeft ? 'right-0' : '']"
                    :style="{ backgroundColor: '#FFFFFF', ...zoneBackgroundCss(zone), color: zone.text_color }"
                >
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <span class="truncate font-semibold">{{ front.site.name }}</span>
                        <button type="button" class="inline-flex size-10 items-center justify-center rounded-md hover:bg-current/10" :aria-label="t('close_menu')" @click="close">
                            <X class="size-6" aria-hidden="true" />
                        </button>
                    </div>

                    <nav class="min-h-0 flex-1 overflow-y-auto px-4 pb-4" :class="isFullscreen ? 'mx-auto w-full max-w-2xl' : ''" :aria-label="t('main_menu')">
                        <template v-if="zone.menu_style === 'drilldown'">
                            <button v-if="drillPath.length" type="button" data-drill-back class="mb-2 inline-flex items-center gap-1 py-2 text-sm opacity-80 hover:opacity-100" @click="drillBack">
                                <ChevronLeft class="size-4" aria-hidden="true" /> {{ t('back') }}
                            </button>
                            <p v-if="drillPath.length" class="mb-1 text-sm font-semibold opacity-70">{{ drillPath[drillPath.length - 1].name }}</p>
                            <ul>
                                <li v-for="item in drillItems" :key="item.id" class="border-b border-current/10">
                                    <button
                                        v-if="item.children.length"
                                        type="button"
                                        class="flex w-full items-center justify-between py-2.5 text-left font-medium"
                                        @click="drillInto(item)"
                                    >
                                        {{ item.name }}
                                        <ChevronRight class="size-4 opacity-70" aria-hidden="true" />
                                    </button>
                                    <FrontLink
                                        v-else-if="item.url"
                                        :href="item.url"
                                        :target="item.target"
                                        class="block py-2.5 font-medium"
                                        :class="activeIds.includes(item.id) ? 'underline underline-offset-4' : ''"
                                        :aria-current="activeIds[activeIds.length - 1] === item.id ? 'page' : undefined"
                                        @click="close"
                                    >
                                        {{ item.name }}
                                    </FrontLink>
                                    <span v-else class="block py-2.5 font-medium">{{ item.name }}</span>
                                </li>
                            </ul>
                        </template>

                        <AsideMenuList v-else :items="front.menu" :mode="zone.menu_style" :level="0" :active-ids="activeIds" @navigate="close" />
                    </nav>

                    <div v-if="hasTools" class="shrink-0 space-y-3 border-t border-current/15 px-4 py-3">
                        <SocialLinks v-if="header.social_status === 'Y'" />
                        <LanguageSwitcher v-if="header.lang_status === 'Y'" :display="header.lang_display" select="all" inline :seo="seo" />
                        <A11yTools
                            :show-font-size="header.fontsize_status === 'Y'"
                            :font-size-display="header.fontsize_display"
                            :show-contrast="header.contrast_status === 'Y'"
                            :contrast-display="header.contrast_display"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
