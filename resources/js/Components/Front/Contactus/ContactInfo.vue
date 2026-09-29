<script setup lang="ts">
import { computed } from 'vue';
import { Mail, MapPin, Phone, Printer, Smartphone } from 'lucide-vue-next';
import SocialLinks from '@/Components/Front/Template/SocialLinks.vue';
import { useFront } from '@/composables/useFront';
import { fontCss } from '@/utils/front';
import type { ContactusTextPart, FrontContactusData } from '@/utils/frontContactus';

/**
 * ข้อมูลติดต่อ (ชื่อเจ้าของ/ที่อยู่/เบอร์/อีเมล/social) — ค่าจากตั้งค่าระบบ (prop front.contact) รูปแบบตัวอักษรจากตั้งค่าติดต่อเรา
 * รายการที่ตั้งค่าซ่อนไว้หรือไม่มีข้อมูลไม่แสดง; `centered` = จัดกึ่งกลาง (แบบเรียงลงมา)
 */
const props = defineProps<{
    contactus: FrontContactusData;
    centered?: boolean;
}>();

const { front, t } = useFront();

const ICONS = { address: MapPin, phone: Phone, fax: Printer, mobile: Smartphone, email: Mail } as const;

const owner = computed(() => front.value.contact.owner || front.value.site.name);

const rows = computed(() =>
    (['address', 'phone', 'fax', 'mobile', 'email'] as const)
        .filter((part) => props.contactus.texts[part].show && front.value.contact[part])
        .map((part) => {
            const value = front.value.contact[part] as string;
            const href = part === 'email' ? `mailto:${value}` : part === 'phone' || part === 'mobile' ? `tel:${value.replace(/[^0-9+]/g, '')}` : null;

            return { part, value, href, icon: ICONS[part], label: t(part) };
        }),
);

function style(part: ContactusTextPart) {
    return fontCss(props.contactus.texts[part].style);
}
</script>

<template>
    <section :class="centered ? 'text-center' : ''" aria-labelledby="contactus-owner">
        <h2 id="contactus-owner" :style="style('owner')" class="leading-snug">{{ owner }}</h2>

        <dl class="mt-4 space-y-3">
            <div v-for="row in rows" :key="row.part" class="flex gap-3" :class="centered ? 'justify-center' : ''">
                <dt class="shrink-0 pt-0.5 text-gray-400">
                    <component :is="row.icon" class="size-5" aria-hidden="true" />
                    <span class="sr-only">{{ row.label }}</span>
                </dt>
                <dd :style="style(row.part)" class="whitespace-pre-line break-words" :class="centered ? '' : 'min-w-0'">
                    <a v-if="row.href" :href="row.href" class="hover:opacity-80">{{ row.value }}</a>
                    <template v-else>{{ row.value }}</template>
                </dd>
            </div>
        </dl>

        <div v-if="contactus.showSocial && front.social.length" class="mt-5 flex" :class="centered ? 'justify-center' : ''">
            <SocialLinks />
        </div>
    </section>
</template>
