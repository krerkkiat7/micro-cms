<script setup lang="ts">
import { computed } from 'vue';
import ContactForm from '@/Components/Front/Contactus/ContactForm.vue';
import ContactInfo from '@/Components/Front/Contactus/ContactInfo.vue';
import ContactMap from '@/Components/Front/Contactus/ContactMap.vue';
import FrontLayout from '@/Layouts/Front/FrontLayout.vue';
import type { PageHeaderData, SeoData } from '@/utils/front';
import type { FrontContactusData, FrontContactusForm } from '@/utils/frontContactus';

/**
 * หน้าติดต่อเรา (front.contactus.item) — จัดวางตามรูปแบบที่ตั้งค่า
 * stacked = ข้อมูลติดต่อ (กึ่งกลาง) → รูปแผนที่ → Google Map → แบบฟอร์ม
 * split_info = [ข้อมูลติดต่อ | แผนที่ + Google Map] แล้วแบบฟอร์มด้านล่าง
 * half = [ข้อมูลติดต่อ + แผนที่ + Google Map | แบบฟอร์ม]
 * ส่วนที่ไม่มี (ไม่มีแผนที่/ไม่มีแบบฟอร์ม) ยุบเหลือคอลัมน์เดียว — ชื่อหน้าเป็น h1 ที่ซ่อนไว้ (ส่วนหัวของเมนูแสดงหัวเรื่องให้แล้ว)
 */
const props = defineProps<{
    contactus: FrontContactusData;
    form: FrontContactusForm | null;
    sent: boolean;
    header: PageHeaderData;
    seo: SeoData;
}>();

const hasMap = computed(() => props.contactus.mapImage !== null || props.contactus.googleMap !== null);
</script>

<template>
    <FrontLayout :seo="seo" :header="header" :fonts-url="contactus.fontsUrl">
        <h1 class="sr-only">{{ contactus.title }}</h1>

        <!-- แบบเรียงลงมา -->
        <div v-if="contactus.displayType === 'stacked'" class="mx-auto max-w-4xl space-y-10">
            <ContactInfo :contactus="contactus" centered />
            <ContactMap v-if="hasMap" :contactus="contactus" />
            <ContactForm v-if="form" :form="form" :sent="sent" />
        </div>

        <!-- แบบแบ่งข้อมูลติดต่อ -->
        <div v-else-if="contactus.displayType === 'split_info'" class="space-y-10">
            <div class="grid gap-8" :class="hasMap ? 'lg:grid-cols-2' : ''">
                <ContactInfo :contactus="contactus" />
                <ContactMap v-if="hasMap" :contactus="contactus" />
            </div>
            <ContactForm v-if="form" :form="form" :sent="sent" />
        </div>

        <!-- แบบครึ่ง -->
        <div v-else class="grid gap-10" :class="form ? 'lg:grid-cols-2' : ''">
            <div class="space-y-8">
                <ContactInfo :contactus="contactus" />
                <ContactMap v-if="hasMap" :contactus="contactus" />
            </div>
            <ContactForm v-if="form" :form="form" :sent="sent" />
        </div>
    </FrontLayout>
</template>
