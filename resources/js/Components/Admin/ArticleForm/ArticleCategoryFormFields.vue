<script setup lang="ts">
import ArticleSeoFields from '@/Components/Admin/ArticleForm/ArticleSeoFields.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { STATUS_OPTIONS } from '@/utils/options';
import type { InertiaForm } from '@inertiajs/vue3';
import type { ArticleCategoryFormData } from '@/utils/articleForm';
import type { FileItem, LanguageOption } from '@/types';

/**
 * การ์ดฟอร์มหมวดหมู่บทความ ใช้ร่วมกันระหว่างหน้าเพิ่มและแก้ไข — เรียงตามลำดับ:
 * ข้อมูลหมวดหมู่ (ชื่อ + ข้อความเกริ่นนำ + รายละเอียด) → รูปภาพหน้าปกและลำดับ → SEO / AEO / GEO → สถานะ
 * ฟอร์ม (`useForm`) เป็นของหน้าที่เรียกใช้; รูปหน้าปกผูกผ่าน v-model:introImage (FilePickerField ใช้ array เสมอ)
 */
const props = defineProps<{
    form: InertiaForm<ArticleCategoryFormData>;
    languages: LanguageOption[];
}>();

const introImage = defineModel<FileItem[]>('introImage', { required: true });

function detailError(lang: string, field: string): string | undefined {
    return (props.form.errors as Record<string, string | undefined>)[`detail.${lang}.${field}`];
}
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">ข้อมูลหมวดหมู่</h2>
            <p class="mt-1 text-sm text-gray-500">ชื่อ ข้อความเกริ่นนำ และรายละเอียดของหมวดหมู่ แยกตามภาษา</p>

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

                <LangFieldGroup label="รายละเอียด" :languages="languages">
                    <template #default="{ lang }">
                        <RichTextEditor v-model="form.detail[lang.code].detail" />
                        <InputError :message="detailError(lang.code, 'detail')" />
                    </template>
                </LangFieldGroup>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">รูปภาพหน้าปกและลำดับ</h2>
            <p class="mt-1 text-sm text-gray-500">รูปที่ใช้แทนหมวดหมู่ (รวมถึงตอนแชร์ลิงก์) และลำดับการแสดงเทียบกับหมวดหมู่อื่น</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-6">
                    <InputLabel value="รูปภาพหน้าปก" />
                    <FilePickerField v-model="introImage" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                    <InputError :message="form.errors.intro_image_id" />
                </div>
                <div class="sm:col-span-2">
                    <InputLabel value="ลำดับ" />
                    <TextInput v-model="form.sort_order" type="number" min="0" step="1" />
                    <p class="mt-1 text-xs text-gray-500">ตัวเลขน้อยแสดงก่อน</p>
                    <InputError :message="form.errors.sort_order" />
                </div>
            </div>
        </div>

        <ArticleSeoFields :detail="form.detail" :languages="languages" subject="หมวดหมู่" :detail-error="detailError" />

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">สถานะ</h2>

            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <InputLabel value="สถานะ" required />
                    <SearchableSelect v-model="form.status" :options="STATUS_OPTIONS" />
                    <p class="mt-1 text-xs text-gray-500">ปิดใช้งานแล้วหมวดหมู่นี้และบทความในหมวดหมู่จะไม่แสดงที่หน้าเว็บไซต์</p>
                    <InputError :message="form.errors.status" />
                </div>
            </div>
        </div>
    </div>
</template>
