<script setup lang="ts">
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import type { ArticleSeoFields } from '@/utils/articleForm';
import type { LanguageOption } from '@/types';

/**
 * การ์ด SEO / AEO / GEO ของหมวดหมู่และบทความ — แบ่งหัวข้อย่อยแบบเดียวกับฟอร์มหน้าเพจ
 * (ลิงก์ของหน้า / การแสดงผลในผลการค้นหา / การแชร์ไปโซเชียลมีเดีย)
 * `subject` = คำเรียกข้อมูลในคำอธิบาย เช่น "หมวดหมู่" / "บทความ"
 */
defineProps<{
    detail: Record<string, ArticleSeoFields>;
    languages: LanguageOption[];
    subject: string;
    detailError: (lang: string, field: string) => string | undefined;
}>();
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
        <h2 class="text-base font-semibold text-gray-800">SEO / AEO / GEO</h2>
        <p class="mt-1 text-sm text-gray-500">ช่วยให้{{ subject }}นี้ถูกค้นพบได้ง่ายบน Google / ระบบ AI และแสดงผลสวยงามเมื่อแชร์ลิงก์ (ไม่บังคับกรอก)</p>

        <div class="mt-6 space-y-8">
            <section class="space-y-4">
                <h3 class="border-b border-gray-100 pb-2 text-sm font-semibold text-gray-700">ลิงก์ของหน้า</h3>
                <LangFieldGroup
                    label="Slug"
                    :description="`ส่วนของ URL ที่ใช้แทน${subject}นี้ ควรใช้ตัวอักษรอังกฤษพิมพ์เล็ก ตัวเลข และเครื่องหมายขีด (-) แทนการเว้นวรรค`"
                    :languages="languages"
                >
                    <template #default="{ lang }">
                        <TextInput v-model="detail[lang.code].slug" type="text" />
                        <InputError :message="detailError(lang.code, 'slug')" />
                    </template>
                </LangFieldGroup>
            </section>

            <section class="space-y-4">
                <h3 class="border-b border-gray-100 pb-2 text-sm font-semibold text-gray-700">การแสดงผลในผลการค้นหา</h3>
                <LangFieldGroup
                    label="Meta Title"
                    :description="`หัวข้อที่แสดงบนแท็บเบราว์เซอร์และหัวข้อผลการค้นหา (SEO) ถ้าไม่กรอกจะใช้ชื่อ${subject}แทน`"
                    :languages="languages"
                >
                    <template #default="{ lang }">
                        <TextInput v-model="detail[lang.code].meta_title" type="text" />
                        <InputError :message="detailError(lang.code, 'meta_title')" />
                    </template>
                </LangFieldGroup>

                <LangFieldGroup
                    label="Meta Description"
                    description="คำอธิบายสั้น ๆ ที่แสดงใต้หัวข้อในผลการค้นหา (SEO) ถ้าไม่กรอกจะใช้ข้อความเกริ่นนำแทน"
                    :languages="languages"
                >
                    <template #default="{ lang }">
                        <Textarea v-model="detail[lang.code].meta_description" rows="2" />
                        <InputError :message="detailError(lang.code, 'meta_description')" />
                    </template>
                </LangFieldGroup>

                <LangFieldGroup
                    label="Meta Keywords"
                    :description="`คำสำคัญที่เกี่ยวข้องกับ${subject}นี้ คั่นด้วยเครื่องหมายจุลภาค (,)`"
                    :languages="languages"
                >
                    <template #default="{ lang }">
                        <TextInput v-model="detail[lang.code].meta_keywords" type="text" />
                        <InputError :message="detailError(lang.code, 'meta_keywords')" />
                    </template>
                </LangFieldGroup>
            </section>

            <section class="space-y-4">
                <h3 class="border-b border-gray-100 pb-2 text-sm font-semibold text-gray-700">การแชร์ไปโซเชียลมีเดีย</h3>
                <LangFieldGroup
                    label="OG Title"
                    description="หัวข้อที่แสดงเมื่อแชร์ลิงก์ไปยังโซเชียลมีเดีย (Facebook, LINE ฯลฯ) ถ้าไม่กรอกจะใช้ Meta Title แทน"
                    :languages="languages"
                >
                    <template #default="{ lang }">
                        <TextInput v-model="detail[lang.code].og_title" type="text" />
                        <InputError :message="detailError(lang.code, 'og_title')" />
                    </template>
                </LangFieldGroup>

                <LangFieldGroup
                    label="OG Description"
                    description="คำอธิบายที่แสดงเมื่อแชร์ลิงก์ไปยังโซเชียลมีเดีย ถ้าไม่กรอกจะใช้ Meta Description แทน"
                    :languages="languages"
                >
                    <template #default="{ lang }">
                        <Textarea v-model="detail[lang.code].og_description" rows="2" />
                        <InputError :message="detailError(lang.code, 'og_description')" />
                    </template>
                </LangFieldGroup>
            </section>
        </div>
    </div>
</template>
