<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { FileItem, LanguageOption } from '@/types';

interface DetailFields {
    title: string;
    intro_text: string;
    detail: string;
    slug: string;
    meta_title: string;
    meta_description: string;
    meta_keywords: string;
    og_title: string;
    og_description: string;
}

interface EditCategory {
    id: number;
    sort_order: number;
    status: string;
    intro_image: FileItem | null;
}

const props = defineProps<{
    category: EditCategory;
    details: Record<string, DetailFields>;
    languages: LanguageOption[];
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm({
    intro_image_id: props.category.intro_image?.id ?? null,
    sort_order: String(props.category.sort_order ?? 0),
    status: props.category.status,
    detail: { ...props.details } as Record<string, DetailFields>,
});

// FilePickerField ทำงานกับ array ของไฟล์เสมอ (เลือกได้ 1 รูป) — เลือกใหม่ = แทนที่ของเดิมทั้งหมด
const introImage = ref<FileItem[]>(props.category.intro_image ? [props.category.intro_image] : []);
watch(introImage, (files) => {
    form.intro_image_id = files[0]?.id ?? null;
});

function detailError(lang: string, field: string): string | undefined {
    return (form.errors as Record<string, string>)[`detail.${lang}.${field}`];
}

function submit() {
    form.put(route('admin.article.category.update', props.category.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.article.category.destroy', props.category.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const defaultTitle = computed(() => {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;
    return (defaultLang && props.details[defaultLang]?.title) || `หมวดหมู่ #${props.category.id}`;
});

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'หมวดหมู่บทความ', href: route('admin.article.category.index') },
    { label: defaultTitle.value },
]);
</script>

<template>
    <Head :title="`แก้ไขหมวดหมู่บทความ: ${defaultTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="defaultTitle" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลทั่วไป</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <InputLabel value="ลำดับ" />
                        <TextInput v-model="form.sort_order" type="number" min="0" step="1" />
                        <InputError :message="form.errors.sort_order" />
                    </div>
                    <div class="sm:col-span-3">
                        <InputLabel value="สถานะ" required />
                        <SelectInput v-model="form.status">
                            <option value="Y">ใช้งาน</option>
                            <option value="N">ไม่ใช้งาน</option>
                        </SelectInput>
                        <InputError :message="form.errors.status" />
                    </div>
                    <div class="sm:col-span-6">
                        <InputLabel value="รูปภาพหน้าปก" />
                        <FilePickerField v-model="introImage" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                        <InputError :message="form.errors.intro_image_id" />
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <LangFieldGroup label="ชื่อ" :languages="languages">
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

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">SEO / AEO / GEO</h2>

                <div class="mt-5 space-y-4">
                    <LangFieldGroup label="Slug" :languages="languages">
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].slug" type="text" />
                            <InputError :message="detailError(lang.code, 'slug')" />
                        </template>
                    </LangFieldGroup>

                    <LangFieldGroup label="Meta Title" :languages="languages">
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].meta_title" type="text" />
                            <InputError :message="detailError(lang.code, 'meta_title')" />
                        </template>
                    </LangFieldGroup>

                    <LangFieldGroup label="Meta Description" :languages="languages">
                        <template #default="{ lang }">
                            <Textarea v-model="form.detail[lang.code].meta_description" rows="2" />
                            <InputError :message="detailError(lang.code, 'meta_description')" />
                        </template>
                    </LangFieldGroup>

                    <LangFieldGroup label="Meta Keywords" :languages="languages">
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].meta_keywords" type="text" />
                            <InputError :message="detailError(lang.code, 'meta_keywords')" />
                        </template>
                    </LangFieldGroup>

                    <LangFieldGroup label="OG Title" :languages="languages">
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].og_title" type="text" />
                            <InputError :message="detailError(lang.code, 'og_title')" />
                        </template>
                    </LangFieldGroup>

                    <LangFieldGroup label="OG Description" :languages="languages">
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
                <Link
                    :href="route('admin.article.category.index')"
                    class="text-sm font-medium text-gray-500 hover:text-gray-700"
                >
                    กลับไปหน้ารายการ
                </Link>
            </div>
        </form>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบหมวดหมู่"
            confirm-text="ลบหมวดหมู่"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบหมวดหมู่ "{{ defaultTitle }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
