<script setup lang="ts">
import { computed } from 'vue';
import { Globe, Menu as MenuIcon, Pin } from 'lucide-vue-next';
import HeaderMenu from '@/Components/Admin/Template/Preview/HeaderMenu.vue';
import HeaderTools from '@/Components/Admin/Template/Preview/HeaderTools.vue';
import { JUSTIFY, zoneBackgroundStyle } from '@/utils/template';
import type { Align, AsideZone, HeaderZone, TemplatePreviewData, Width } from '@/utils/template';

/**
 * ตัวอย่าง header ตามค่าตั้งค่า — ใช้โลโก้/ชื่อเว็บ/เมนู/ภาษา/social จริงของระบบ (กดไม่ได้)
 * แถบหลักแบ่ง 3 ช่อง ซ้าย/กลาง/ขวา: โลโก้อยู่ช่องตาม logo_align, เมนู (ถ้าไม่มีแถวเมนูแยก) อยู่ช่องตาม menu_align,
 * เครื่องมือ (ถ้าไม่มีแถบบน) ต่อท้ายช่องขวา และไอคอนเปิด aside อยู่ขอบฝั่งตาม toggle_position
 */
const props = defineProps<{
    zone: HeaderZone;
    aside: AsideZone;
    preview: TemplatePreviewData;
}>();

type Slot = 'logo' | 'menu' | 'tools' | 'toggle';

const slots = computed(() => {
    const result: Record<Align, Slot[]> = { left: [], center: [], right: [] };
    const z = props.zone;

    if (props.aside.status === 'Y' && props.aside.toggle_position === 'left') result.left.push('toggle');
    if (z.logo_status === 'Y') result[z.logo_align].push('logo');
    if (z.layout_type !== 'main_menubar') result[z.menu_align].push('menu');
    if (z.layout_type !== 'topbar_main') result.right.push('tools');
    if (props.aside.status === 'Y' && props.aside.toggle_position === 'right') result.right.push('toggle');

    return result;
});

/** เต็มหน้าจอ = แถบกว้างเต็มพื้นที่ / ตาม container = แถบกว้างเท่า container กึ่งกลาง (ในตัวอย่างใช้ 88% ของพื้นที่) */
function barClass(width: Width): string {
    return width === 'container' ? 'mx-auto w-[88%]' : 'w-full';
}

const showLogoImage = computed(() => props.zone.logo_display !== 'name');
const showSiteName = computed(() => props.zone.logo_display !== 'image');
</script>

<template>
    <div>
        <!-- แถบบน -->
        <div
            v-if="zone.layout_type === 'topbar_main'"
            :class="barClass(zone.topbar_width)"
            :style="{ backgroundColor: zone.topbar_background_color, color: zone.topbar_text_color }"
        >
            <div class="flex min-h-8 items-center justify-between gap-3 px-4 py-1">
                <HeaderTools :zone="zone" :preview="preview" only="social" />
                <HeaderTools :zone="zone" :preview="preview" only="tools" />
            </div>
        </div>

        <!-- แถบหลัก -->
        <div :class="barClass(zone.main_width)" :style="{ ...zoneBackgroundStyle(zone), color: zone.main_text_color }">
            <div class="relative grid min-h-16 grid-cols-[1fr_auto_1fr] items-center gap-3 px-4 py-3">
                <div
                    v-for="align in (['left', 'center', 'right'] as Align[])"
                    :key="align"
                    class="flex min-w-0 flex-wrap items-center gap-3"
                    :class="JUSTIFY[align]"
                >
                    <template v-for="item in slots[align]" :key="item">
                        <span v-if="item === 'toggle'" class="rounded p-1"><MenuIcon class="size-5" /></span>

                        <span v-else-if="item === 'logo'" class="flex items-center gap-2">
                            <template v-if="showLogoImage">
                                <img v-if="preview.logoUrl" :src="preview.logoUrl" alt="" class="h-10 w-auto object-contain" />
                                <span v-else class="flex size-10 items-center justify-center rounded-lg bg-brand-500 text-white"><Globe class="size-5" /></span>
                            </template>
                            <span v-if="showSiteName" class="text-lg font-semibold whitespace-nowrap">{{ preview.siteName }}</span>
                        </span>

                        <HeaderMenu
                            v-else-if="item === 'menu'"
                            :items="preview.menu"
                            :menu-style="zone.menu_style"
                            :text-color="zone.menu_text_color"
                            :active-color="zone.menu_active_color"
                        />

                        <HeaderTools v-else-if="item === 'tools'" :zone="zone" :preview="preview" only="all" />
                    </template>
                </div>

                <span
                    v-if="zone.sticky === 'Y'"
                    class="absolute right-1 top-1 inline-flex items-center gap-0.5 rounded bg-black/50 px-1.5 py-0.5 text-[10px] text-white"
                    title="แสดง header เสมอเมื่อเลื่อนลง"
                >
                    <Pin class="size-3" /> ติดด้านบน
                </span>
            </div>
        </div>

        <!-- แถวเมนู -->
        <div
            v-if="zone.layout_type === 'main_menubar'"
            :class="barClass(zone.menubar_width)"
            :style="{ backgroundColor: zone.menubar_background_color }"
        >
            <div class="flex px-4 py-1" :class="JUSTIFY[zone.menu_align]">
                <HeaderMenu :items="preview.menu" :menu-style="zone.menu_style" :text-color="zone.menu_text_color" :active-color="zone.menu_active_color" />
            </div>
        </div>
    </div>
</template>
