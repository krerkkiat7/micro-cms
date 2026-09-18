<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { STATUS_OPTIONS } from '@/utils/options';
import type { LanguageOption } from '@/types';

const props = defineProps<{
    languages: LanguageOption[];
}>();

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

const initialDetail: Record<string, DetailFields> = {};
props.languages.forEach((lang) => {
    initialDetail[lang.code] = emptyDetail();
});

const form = useForm({
    status: 'Y',
    detail: initialDetail,
});

function detailError(lang: string, field: string): string | undefined {
    return (form.errors as Record<string, string>)[`detail.${lang}.${field}`];
}

function submit() {
    form.post(route('admin.banner.category.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'หมวดหมู่ป้ายโฆษณา', href: route('admin.banner.category.index') },
    { label: 'เพิ่มหมวดหมู่' },
];
</script>

<template>
    <Head title="เพิ่มหมวดหมู่ป้ายโฆษณา" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่มหมวดหมู่ป้ายโฆษณา" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลทั่วไป</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <InputLabel value="สถานะ" required />
                        <SearchableSelect v-model="form.status" :options="STATUS_OPTIONS" />
                        <InputError :message="form.errors.status" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลหมวดหมู่</h2>

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
                <Link
                    :href="route('admin.banner.category.index')"
                    class="text-sm font-medium text-gray-500 hover:text-gray-700"
                >
                    ยกเลิก
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
