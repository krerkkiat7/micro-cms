<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import DateTimeInput from '@/Components/Admin/DateTimeInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { LINK_TARGET_OPTIONS, STATUS_OPTIONS } from '@/utils/options';
import type { FileItem, LanguageOption } from '@/types';

interface DetailFields {
    title: string;
    intro_text: string;
}

interface EditItem {
    id: number;
    banner_category_info_id: number | null;
    status: string;
    url: string | null;
    link_target: string | null;
    publish_date: string | null;
    publish_down: string | null;
    sort_order: number;
    intro_image: FileItem | null;
}

interface CategoryOption {
    id: number;
    title: string | null;
}

const props = defineProps<{
    item: EditItem;
    details: Record<string, DetailFields>;
    languages: LanguageOption[];
    categories: CategoryOption[];
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm({
    banner_category_info_id: props.item.banner_category_info_id ? String(props.item.banner_category_info_id) : '',
    intro_image_id: props.item.intro_image?.id ?? null,
    url: props.item.url ?? '',
    link_target: props.item.link_target ?? '_self',
    publish_date: props.item.publish_date,
    publish_down: props.item.publish_down,
    sort_order: String(props.item.sort_order ?? 0),
    status: props.item.status,
    detail: { ...props.details } as Record<string, DetailFields>,
});

const categoryOptions = computed(() => props.categories.map((cat) => ({ value: String(cat.id), label: cat.title ?? '(ไม่มีชื่อ)' })));

const introImage = ref<FileItem[]>(props.item.intro_image ? [props.item.intro_image] : []);
watch(introImage, (files) => {
    form.intro_image_id = files[0]?.id ?? null;
});

function detailError(lang: string, field: string): string | undefined {
    return (form.errors as Record<string, string>)[`detail.${lang}.${field}`];
}

function submit() {
    form.transform((data) => ({
        ...data,
        banner_category_info_id: data.banner_category_info_id !== '' ? Number(data.banner_category_info_id) : null,
    })).put(route('admin.banner.item.update', props.item.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.banner.item.destroy', props.item.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const defaultTitle = computed(() => {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;
    return (defaultLang && props.details[defaultLang]?.title) || `ป้ายโฆษณา #${props.item.id}`;
});

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'ป้ายโฆษณา', href: route('admin.banner.item.index') },
    { label: defaultTitle.value },
]);
</script>

<template>
    <Head :title="`แก้ไขป้ายโฆษณา: ${defaultTitle}`" />

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
                        <SearchableSelect v-model="form.banner_category_info_id" :options="categoryOptions" placeholder="เลือกหมวดหมู่" />
                        <InputError :message="form.errors.banner_category_info_id" />
                    </div>
                    <div class="sm:col-span-3">
                        <InputLabel value="สถานะ" required />
                        <SearchableSelect v-model="form.status" :options="STATUS_OPTIONS" />
                        <InputError :message="form.errors.status" />
                    </div>
                    <div class="sm:col-span-6">
                        <InputLabel value="รูปภาพ" required />
                        <FilePickerField v-model="introImage" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                        <InputError :message="form.errors.intro_image_id" />
                    </div>
                    <div class="sm:col-span-3">
                        <InputLabel value="ลิงก์" />
                        <TextInput v-model="form.url" type="text" placeholder="https://..." />
                        <InputError :message="form.errors.url" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="เปิดลิงก์แบบ" />
                        <SearchableSelect v-model="form.link_target" :options="LINK_TARGET_OPTIONS" />
                        <InputError :message="form.errors.link_target" />
                    </div>
                    <div class="sm:col-span-1">
                        <InputLabel value="ลำดับ" />
                        <TextInput v-model="form.sort_order" type="number" min="0" step="1" />
                        <InputError :message="form.errors.sort_order" />
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
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลป้ายโฆษณา</h2>

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

            <div v-if="can.manage || can.delete" class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                    <Trash2 class="mr-1.5 size-4" /> ลบ
                </DangerButton>
                <Link :href="route('admin.banner.item.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    กลับไปหน้ารายการ
                </Link>
            </div>
        </form>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบป้ายโฆษณา"
            confirm-text="ลบป้ายโฆษณา"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบป้ายโฆษณา "{{ defaultTitle }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
