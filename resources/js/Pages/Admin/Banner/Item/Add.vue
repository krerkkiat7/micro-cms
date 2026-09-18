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
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { LINK_TARGET_OPTIONS, STATUS_OPTIONS } from '@/utils/options';
import type { FileItem, LanguageOption } from '@/types';

interface DetailFields {
    title: string;
    intro_text: string;
}

function emptyDetail(): DetailFields {
    return {
        title: '',
        intro_text: '',
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
    banner_category_info_id: '' as string,
    intro_image_id: null as number | null,
    url: '',
    link_target: '_self',
    publish_date: nowDateTime() as string | null,
    publish_down: null as string | null,
    sort_order: '0',
    status: 'Y',
    detail: initialDetail,
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
        banner_category_info_id: data.banner_category_info_id !== '' ? Number(data.banner_category_info_id) : null,
    })).post(route('admin.banner.item.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'ป้ายโฆษณา', href: route('admin.banner.item.index') },
    { label: 'เพิ่มป้ายโฆษณา' },
];
</script>

<template>
    <Head title="เพิ่มป้ายโฆษณา" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่มป้ายโฆษณา" :breadcrumbs="breadcrumbs" />
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

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <Link :href="route('admin.banner.item.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    ยกเลิก
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
