<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import DateTimeInput from '@/Components/Admin/DateTimeInput.vue';
import PartList from '@/Components/Admin/ArticlePart/PartList.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SelectInput from '@/Components/SelectInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import TagPicker from '@/Components/Admin/ArticleTag/TagPicker.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { createPart, partsToPayload } from '@/utils/articleParts';
import type { PartData } from '@/utils/articleParts';
import type { FileItem, LanguageOption } from '@/types';

interface DetailFields {
    title: string;
    intro_text: string;
    slug: string;
    meta_title: string;
    meta_description: string;
    meta_keywords: string;
    og_title: string;
    og_description: string;
}

function emptyDetail(): DetailFields {
    return {
        title: '',
        intro_text: '',
        slug: '',
        meta_title: '',
        meta_description: '',
        meta_keywords: '',
        og_title: '',
        og_description: '',
    };
}

interface CategoryOption {
    id: number;
    title: string | null;
}

const props = defineProps<{
    languages: LanguageOption[];
    categories: CategoryOption[];
}>();

const initialDetail: Record<string, DetailFields> = {};
props.languages.forEach((lang) => {
    initialDetail[lang.code] = emptyDetail();
});

const categoryOptions = computed(() => props.categories.map((cat) => ({ value: String(cat.id), label: cat.title ?? '(ไม่มีชื่อ)' })));

function nowDateTime(): string {
    const d = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
}

const form = useForm({
    article_category_info_id: '' as string,
    intro_image_id: null as number | null,
    publish_date: nowDateTime() as string | null,
    publish_down: null as string | null,
    status: 'Y',
    tags: [] as number[],
    detail: initialDetail,
    parts: [createPart('text', props.languages)] as PartData[],
});

const introImage = ref<FileItem[]>([]);
watch(introImage, (files) => {
    form.intro_image_id = files[0]?.id ?? null;
});

function detailError(lang: string, field: string): string | undefined {
    return (form.errors as Record<string, string>)[`detail.${lang}.${field}`];
}

function submit() {
    form.transform((data) => ({
        ...data,
        article_category_info_id: data.article_category_info_id !== '' ? Number(data.article_category_info_id) : null,
        parts: partsToPayload(data.parts),
    })).post(route('admin.article.item.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'บทความ', href: route('admin.article.item.index') },
    { label: 'เพิ่มบทความ' },
];
</script>

<template>
    <Head title="เพิ่มบทความ" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่มบทความ" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลทั่วไป</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <InputLabel value="หมวดหมู่" required />
                        <SearchableSelect v-model="form.article_category_info_id" :options="categoryOptions" placeholder="เลือกหมวดหมู่" />
                        <InputError :message="form.errors.article_category_info_id" />
                    </div>
                    <div class="sm:col-span-3">
                        <InputLabel value="สถานะ" required />
                        <SelectInput v-model="form.status">
                            <option value="Y">ใช้งาน</option>
                            <option value="N">ไม่ใช้งาน</option>
                        </SelectInput>
                        <InputError :message="form.errors.status" />
                    </div>
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
                    <div class="sm:col-span-6">
                        <InputLabel value="รูปภาพหน้าปก" />
                        <FilePickerField v-model="introImage" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                        <InputError :message="form.errors.intro_image_id" />
                    </div>
                    <div class="sm:col-span-6">
                        <InputLabel value="แท็ก" />
                        <TagPicker v-model="form.tags" :languages="languages" />
                        <InputError :message="form.errors.tags" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลบทความ</h2>

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
                <h2 class="text-base font-semibold text-gray-800">เนื้อหา</h2>
                <p class="mt-0.5 text-xs text-gray-500">กดไอคอนจัดลำดับที่แต่ละส่วนเพื่อเปิดหน้าต่างสลับลำดับ</p>

                <div class="mt-5">
                    <PartList v-model="form.parts" :languages="languages" :form-errors="(form.errors as Record<string, string>)" />
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">SEO / AEO / GEO</h2>

                <div class="mt-5 space-y-4">
                    <LangFieldGroup
                        label="Slug"
                        description="ส่วนของ URL ที่ใช้แทนบทความนี้ (เช่น example.com/article/slug-ที่ตั้งไว้) ควรใช้ตัวอักษรอังกฤษพิมพ์เล็ก ตัวเลข และเครื่องหมายขีด (-) แทนการเว้นวรรค"
                        :languages="languages"
                    >
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].slug" type="text" />
                            <InputError :message="detailError(lang.code, 'slug')" />
                        </template>
                    </LangFieldGroup>

                    <LangFieldGroup
                        label="Meta Title"
                        description="หัวข้อที่แสดงบนแท็บเบราว์เซอร์และหัวข้อผลการค้นหา (SEO) ถ้าไม่กรอกจะใช้ชื่อบทความแทน"
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
                        description="คำสำคัญที่เกี่ยวข้องกับบทความนี้ คั่นด้วยเครื่องหมายจุลภาค (,) — search engine ส่วนใหญ่ในปัจจุบันไม่ได้ใช้จัดอันดับแล้ว แต่ยังกรอกไว้เพื่ออ้างอิงได้"
                        :languages="languages"
                    >
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].meta_keywords" type="text" />
                            <InputError :message="detailError(lang.code, 'meta_keywords')" />
                        </template>
                    </LangFieldGroup>

                    <LangFieldGroup
                        label="OG Title"
                        description="หัวข้อที่แสดงตอนแชร์ลิงก์นี้ไปยังโซเชียลมีเดีย เช่น Facebook, Line (Open Graph) ถ้าไม่กรอกจะใช้ Meta Title หรือชื่อบทความแทน"
                        :languages="languages"
                    >
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].og_title" type="text" />
                            <InputError :message="detailError(lang.code, 'og_title')" />
                        </template>
                    </LangFieldGroup>

                    <LangFieldGroup
                        label="OG Description"
                        description="คำอธิบายที่แสดงตอนแชร์ลิงก์นี้ไปยังโซเชียลมีเดีย ถ้าไม่กรอกจะใช้ Meta Description แทน"
                        :languages="languages"
                    >
                        <template #default="{ lang }">
                            <Textarea v-model="form.detail[lang.code].og_description" rows="2" />
                            <InputError :message="detailError(lang.code, 'og_description')" />
                        </template>
                    </LangFieldGroup>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <Link :href="route('admin.article.item.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    ยกเลิก
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
