<script setup lang="ts">
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { STATUS_OPTIONS } from '@/utils/options';
import type { InertiaForm } from '@inertiajs/vue3';
import type { BannerCategoryFormData } from '@/utils/bannerForm';
import type { LanguageOption } from '@/types';

/**
 * การ์ดฟอร์มหมวดหมู่ป้ายโฆษณา ใช้ร่วมกันระหว่างหน้าเพิ่มและแก้ไข — ข้อมูลหมวดหมู่ (ชื่อ + ข้อความเกริ่นนำ) → สถานะ
 */
const props = defineProps<{
    form: InertiaForm<BannerCategoryFormData>;
    languages: LanguageOption[];
}>();

function detailError(lang: string, field: string): string | undefined {
    return (props.form.errors as Record<string, string | undefined>)[`detail.${lang}.${field}`];
}
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">ข้อมูลหมวดหมู่</h2>
            <p class="mt-1 text-sm text-gray-500">ชื่อและคำอธิบายสั้น ๆ ของหมวดหมู่ แยกตามภาษา</p>

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
            <h2 class="text-base font-semibold text-gray-800">สถานะ</h2>

            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <InputLabel value="สถานะ" required />
                    <SearchableSelect v-model="form.status" :options="STATUS_OPTIONS" />
                    <p class="mt-1 text-xs text-gray-500">ปิดใช้งานแล้วป้ายโฆษณาในหมวดหมู่นี้จะไม่แสดงที่หน้าเว็บไซต์</p>
                    <InputError :message="form.errors.status" />
                </div>
            </div>
        </div>
    </div>
</template>
