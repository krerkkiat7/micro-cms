<script setup lang="ts">
import ArticleSeoFields from '@/Components/Admin/ArticleForm/ArticleSeoFields.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import DateTimeInput from '@/Components/Admin/DateTimeInput.vue';
import PartList from '@/Components/Admin/ArticlePart/PartList.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import TagPicker from '@/Components/Admin/ArticleTag/TagPicker.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { STATUS_OPTIONS } from '@/utils/options';
import type { InertiaForm } from '@inertiajs/vue3';
import type { ArticleItemFormData, ArticleTagChip } from '@/utils/articleForm';
import type { FileItem, LanguageOption } from '@/types';

/**
 * การ์ดฟอร์มบทความ ใช้ร่วมกันระหว่างหน้าเพิ่มและแก้ไข — เรียงตามลำดับ:
 * ข้อมูลบทความ (ชื่อ + ข้อความเกริ่นนำ) → การจัดกลุ่ม (แท็ก + หมวดหมู่) → รูปภาพหน้าปก → เนื้อหา
 * → SEO / AEO / GEO → การเผยแพร่ (วันที่เผยแพร่ + วันที่ปิดการเผยแพร่) → สถานะ
 */
const props = defineProps<{
    form: InertiaForm<ArticleItemFormData>;
    languages: LanguageOption[];
    categoryOptions: { value: string; label: string }[];
    initialTags?: ArticleTagChip[];
}>();

const introImage = defineModel<FileItem[]>('introImage', { required: true });

function detailError(lang: string, field: string): string | undefined {
    return (props.form.errors as Record<string, string | undefined>)[`detail.${lang}.${field}`];
}
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">ข้อมูลบทความ</h2>
            <p class="mt-1 text-sm text-gray-500">ชื่อและคำอธิบายสั้น ๆ ของบทความ แยกตามภาษา</p>

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
            <h2 class="text-base font-semibold text-gray-800">การจัดกลุ่ม</h2>
            <p class="mt-1 text-sm text-gray-500">แท็กช่วยให้ผู้อ่านค้นหาบทความเรื่องเดียวกันได้ ส่วนหมวดหมู่กำหนดว่าบทความแสดงอยู่ในรายการใด</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-6">
                    <InputLabel value="แท็ก" />
                    <TagPicker v-model="form.tags" :languages="languages" :initial-chips="initialTags" />
                    <InputError :message="form.errors.tags" />
                </div>
                <div class="sm:col-span-3">
                    <InputLabel value="หมวดหมู่" required />
                    <SearchableSelect v-model="form.article_category_info_id" :options="categoryOptions" placeholder="เลือกหมวดหมู่" />
                    <InputError :message="form.errors.article_category_info_id" />
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">รูปภาพหน้าปก</h2>
            <p class="mt-1 text-sm text-gray-500">แสดงในรายการบทความ และใช้เป็นรูปตอนแชร์ลิงก์ไปโซเชียลมีเดีย</p>

            <div class="mt-5">
                <FilePickerField v-model="introImage" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                <InputError :message="form.errors.intro_image_id" />
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">เนื้อหา</h2>
            <p class="mt-1 text-sm text-gray-500">กดไอคอนจัดลำดับที่แต่ละส่วนเพื่อเปิดหน้าต่างสลับลำดับ</p>

            <div class="mt-5">
                <PartList v-model="form.parts" :languages="languages" :form-errors="(form.errors as Record<string, string>)" />
            </div>
        </div>

        <ArticleSeoFields :detail="form.detail" :languages="languages" subject="บทความ" :detail-error="detailError" />

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">การเผยแพร่</h2>
            <p class="mt-1 text-sm text-gray-500">บทความแสดงที่หน้าเว็บไซต์ตั้งแต่วันที่เผยแพร่ จนถึงวันที่ปิดการเผยแพร่ (ถ้ากำหนด)</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <InputLabel value="วันที่เผยแพร่" required />
                    <DateTimeInput v-model="form.publish_date" />
                    <InputError :message="form.errors.publish_date" />
                </div>
                <div class="sm:col-span-3">
                    <InputLabel value="วันที่ปิดการเผยแพร่" />
                    <DateTimeInput v-model="form.publish_down" clearable />
                    <InputError :message="form.errors.publish_down" />
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">สถานะ</h2>

            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <InputLabel value="สถานะ" required />
                    <SearchableSelect v-model="form.status" :options="STATUS_OPTIONS" />
                    <p class="mt-1 text-xs text-gray-500">ปิดใช้งานแล้วบทความนี้จะไม่แสดงที่หน้าเว็บไซต์</p>
                    <InputError :message="form.errors.status" />
                </div>
            </div>
        </div>
    </div>
</template>
