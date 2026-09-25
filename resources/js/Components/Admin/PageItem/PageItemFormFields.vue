<script setup lang="ts">
import BackgroundFields from '@/Components/Admin/PageLayout/BackgroundFields.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { STATUS_OPTIONS } from '@/utils/options';
import type { InertiaForm } from '@inertiajs/vue3';
import type { PageItemFormData } from '@/utils/pageItem';
import type { LanguageOption } from '@/types';

/**
 * การ์ดฟอร์มข้อมูลทั่วไปของหน้าเพจ ใช้ร่วมกันระหว่างหน้าเพิ่มและแก้ไข — เรียงตามลำดับที่ผู้ใช้กรอกจริง:
 * ข้อมูลหน้าเพจ (ชื่อ + ข้อความเกริ่นนำ) → SEO / AEO / GEO (แบ่งหัวข้อย่อย: ลิงก์ของหน้า / ผลการค้นหา / การแชร์ไปโซเชียลมีเดีย
 * ซึ่งรวมรูปแทนหน้าไว้ด้วย เพราะใช้เป็นรูปตอนแชร์) → พื้นหลังของทั้งหน้า → สถานะ
 * ฟอร์ม (`useForm`) เป็นของหน้าที่เรียกใช้ ที่นี่แค่ผูกฟิลด์และแสดง error
 */
const props = defineProps<{
    form: InertiaForm<PageItemFormData>;
    languages: LanguageOption[];
}>();

const errors = () => props.form.errors as Record<string, string | undefined>;

function detailError(lang: string, field: string): string | undefined {
    return errors()[`detail.${lang}.${field}`];
}
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">ข้อมูลหน้าเพจ</h2>
            <p class="mt-1 text-sm text-gray-500">ชื่อและคำอธิบายสั้น ๆ ของหน้านี้ แยกตามภาษา</p>

            <div class="mt-5 space-y-4">
                <LangFieldGroup label="ชื่อ" :languages="languages" required>
                    <template #default="{ lang }">
                        <TextInput v-model="form.detail[lang.code].title" type="text" />
                        <InputError :message="detailError(lang.code, 'title')" />
                    </template>
                </LangFieldGroup>

                <LangFieldGroup label="ข้อความเกริ่นนำ" :languages="languages">
                    <template #default="{ lang }">
                        <Textarea v-model="form.detail[lang.code].intro_text" rows="3" />
                        <InputError :message="detailError(lang.code, 'intro_text')" />
                    </template>
                </LangFieldGroup>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">SEO / AEO / GEO</h2>
            <p class="mt-1 text-sm text-gray-500">ช่วยให้หน้านี้ถูกค้นพบได้ง่ายบน Google / ระบบ AI และแสดงผลสวยงามเมื่อแชร์ลิงก์ (ไม่บังคับกรอก)</p>

            <div class="mt-6 space-y-8">
                <section class="space-y-4">
                    <h3 class="border-b border-gray-100 pb-2 text-sm font-semibold text-gray-700">ลิงก์ของหน้า</h3>
                    <LangFieldGroup
                        label="Slug"
                        description="ส่วนของ URL ที่ใช้แทนหน้านี้ (เช่น example.com/th/slug-ที่ตั้งไว้) ควรใช้ตัวอักษรอังกฤษพิมพ์เล็ก ตัวเลข และเครื่องหมายขีด (-) แทนการเว้นวรรค"
                        :languages="languages"
                    >
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].slug" type="text" />
                            <InputError :message="detailError(lang.code, 'slug')" />
                        </template>
                    </LangFieldGroup>
                </section>

                <section class="space-y-4">
                    <h3 class="border-b border-gray-100 pb-2 text-sm font-semibold text-gray-700">การแสดงผลในผลการค้นหา</h3>
                    <LangFieldGroup
                        label="Meta Title"
                        description="หัวข้อที่แสดงบนแท็บเบราว์เซอร์และหัวข้อผลการค้นหา (SEO) ถ้าไม่กรอกจะใช้ชื่อหน้าแทน"
                        :languages="languages"
                    >
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].meta_title" type="text" />
                            <InputError :message="detailError(lang.code, 'meta_title')" />
                        </template>
                    </LangFieldGroup>

                    <LangFieldGroup
                        label="Meta Description"
                        description="คำอธิบายสั้น ๆ ที่แสดงใต้หัวข้อในผลการค้นหา (SEO) ควรกระชับและดึงดูดให้คนอยากคลิกเข้ามาอ่าน"
                        :languages="languages"
                    >
                        <template #default="{ lang }">
                            <Textarea v-model="form.detail[lang.code].meta_description" rows="2" />
                            <InputError :message="detailError(lang.code, 'meta_description')" />
                        </template>
                    </LangFieldGroup>

                    <LangFieldGroup
                        label="Meta Keywords"
                        description="คำสำคัญของหน้านี้ คั่นด้วยเครื่องหมายจุลภาค (,) เช่น ข่าว, กิจกรรม, ประชาสัมพันธ์"
                        :languages="languages"
                    >
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].meta_keywords" type="text" />
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
                            <TextInput v-model="form.detail[lang.code].og_title" type="text" />
                            <InputError :message="detailError(lang.code, 'og_title')" />
                        </template>
                    </LangFieldGroup>

                    <LangFieldGroup
                        label="OG Description"
                        description="คำอธิบายที่แสดงเมื่อแชร์ลิงก์ไปยังโซเชียลมีเดีย ถ้าไม่กรอกจะใช้ Meta Description แทน"
                        :languages="languages"
                    >
                        <template #default="{ lang }">
                            <Textarea v-model="form.detail[lang.code].og_description" rows="2" />
                            <InputError :message="detailError(lang.code, 'og_description')" />
                        </template>
                    </LangFieldGroup>

                    <div>
                        <InputLabel value="รูปแทนหน้า" />
                        <p class="mb-1.5 text-xs text-gray-500">รูปที่แสดงคู่กับลิงก์เมื่อแชร์หน้านี้ไปยังโซเชียลมีเดีย และใช้แทนหน้านี้ตอนทำ SEO (ไม่บังคับ)</p>
                        <FilePickerField v-model="form.intro_image" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                        <InputError :message="errors().intro_image_id" />
                    </div>
                </section>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">พื้นหลังของทั้งหน้า</h2>
            <p class="mt-1 text-sm text-gray-500">สีหรือรูปภาพที่อยู่ด้านหลังเนื้อหาทั้งหน้า (แต่ละแถว/คอลัมน์/Widget ตั้งพื้นหลังแยกได้อีกที่แท็บโครงสร้าง)</p>

            <div class="mt-5 space-y-4">
                <BackgroundFields :fields="form" :errors="errors()" />
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">สถานะ</h2>

            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <InputLabel value="สถานะ" required />
                    <SearchableSelect v-model="form.status" :options="STATUS_OPTIONS" />
                    <p class="mt-1 text-xs text-gray-500">ปิดใช้งานแล้วหน้านี้จะไม่แสดงที่หน้าเว็บไซต์</p>
                    <InputError :message="form.errors.status" />
                </div>
            </div>
        </div>
    </div>
</template>
