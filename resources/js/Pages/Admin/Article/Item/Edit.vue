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
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { partsFromServer, partsToPayload } from '@/utils/articleParts';
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

interface EditItem {
    id: number;
    article_category_info_id: number | null;
    status: string;
    publish_date: string | null;
    publish_down: string | null;
    intro_image: FileItem | null;
}

interface CategoryOption {
    id: number;
    title: string | null;
}

interface TagOption {
    id: number;
    name: string | null;
}

interface ServerPart {
    part_type: PartData['part_type'];
    images_display_type: string | null;
    setting: Record<string, unknown> | null;
    detail: Record<string, { title: string; detail: string }>;
    files: Array<{
        file: FileItem | null;
        cover_image: FileItem | null;
        video_type: string | null;
        youtube_url: string | null;
        description: Record<string, unknown> | null;
    }>;
}

const props = defineProps<{
    item: EditItem;
    details: Record<string, DetailFields>;
    parts: ServerPart[];
    tagIds: number[];
    languages: LanguageOption[];
    categories: CategoryOption[];
    tags: TagOption[];
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm({
    article_category_info_id: props.item.article_category_info_id ? String(props.item.article_category_info_id) : '',
    intro_image_id: props.item.intro_image?.id ?? null,
    publish_date: props.item.publish_date,
    publish_down: props.item.publish_down,
    status: props.item.status,
    tags: [...props.tagIds],
    detail: { ...props.details } as Record<string, DetailFields>,
    parts: partsFromServer(props.parts, props.languages) as PartData[],
});

const introImage = ref<FileItem[]>(props.item.intro_image ? [props.item.intro_image] : []);
watch(introImage, (files) => {
    form.intro_image_id = files[0]?.id ?? null;
});

function detailError(lang: string, field: string): string | undefined {
    return (form.errors as Record<string, string>)[`detail.${lang}.${field}`];
}

function toggleTag(tagId: number, checked: boolean) {
    form.tags = checked ? [...form.tags, tagId] : form.tags.filter((id) => id !== tagId);
}

function submit() {
    form.transform((data) => ({
        ...data,
        article_category_info_id: data.article_category_info_id !== '' ? Number(data.article_category_info_id) : null,
        parts: partsToPayload(data.parts),
    })).put(route('admin.article.item.update', props.item.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.article.item.destroy', props.item.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const defaultTitle = computed(() => {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;
    return (defaultLang && props.details[defaultLang]?.title) || `บทความ #${props.item.id}`;
});

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'บทความ', href: route('admin.article.item.index') },
    { label: defaultTitle.value },
]);
</script>

<template>
    <Head :title="`แก้ไขบทความ: ${defaultTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="defaultTitle" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลทั่วไป</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <InputLabel value="หมวดหมู่" required />
                        <SelectInput v-model="form.article_category_info_id">
                            <option value="" disabled>เลือกหมวดหมู่</option>
                            <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">{{ cat.title }}</option>
                        </SelectInput>
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
                    <div v-if="tags.length" class="sm:col-span-6">
                        <InputLabel value="แท็ก" />
                        <div class="flex flex-wrap gap-x-5 gap-y-2">
                            <label v-for="tag in tags" :key="tag.id" class="flex items-center gap-2 text-sm text-gray-700">
                                <Checkbox :checked="form.tags.includes(tag.id)" @update:checked="(v) => toggleTag(tag.id, v)" />
                                {{ tag.name }}
                            </label>
                        </div>
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
                <p class="mt-0.5 text-xs text-gray-500">ลากที่ไอคอนซ้ายของแต่ละส่วนเพื่อสลับลำดับ</p>

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

            <div v-if="can.manage || can.delete" class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                    <Trash2 class="mr-1.5 size-4" /> ลบ
                </DangerButton>
                <Link :href="route('admin.article.item.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    กลับไปหน้ารายการ
                </Link>
            </div>
        </form>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบบทความ"
            confirm-text="ลบบทความ"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบบทความ "{{ defaultTitle }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
