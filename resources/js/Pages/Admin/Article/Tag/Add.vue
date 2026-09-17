<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import type { LanguageOption } from '@/types';

const props = defineProps<{
    languages: LanguageOption[];
}>();

interface DetailFields {
    name: string;
}

const initialDetail: Record<string, DetailFields> = {};
props.languages.forEach((lang) => {
    initialDetail[lang.code] = { name: '' };
});

const form = useForm({
    status: 'Y',
    detail: initialDetail,
});

function detailError(lang: string, field: string): string | undefined {
    return (form.errors as Record<string, string>)[`detail.${lang}.${field}`];
}

function submit() {
    form.post(route('admin.article.tag.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'แท็กบทความ', href: route('admin.article.tag.index') },
    { label: 'เพิ่มแท็ก' },
];
</script>

<template>
    <Head title="เพิ่มแท็กบทความ" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่มแท็กบทความ" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลแท็ก</h2>

                <div class="mt-5 space-y-4">
                    <LangFieldGroup label="ชื่อ" :languages="languages" required>
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].name" type="text" />
                            <InputError :message="detailError(lang.code, 'name')" />
                        </template>
                    </LangFieldGroup>

                    <div class="sm:w-1/3">
                        <InputLabel value="สถานะ" required />
                        <SelectInput v-model="form.status">
                            <option value="Y">ใช้งาน</option>
                            <option value="N">ไม่ใช้งาน</option>
                        </SelectInput>
                        <InputError :message="form.errors.status" />
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <Link
                    :href="route('admin.article.tag.index')"
                    class="text-sm font-medium text-gray-500 hover:text-gray-700"
                >
                    ยกเลิก
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
