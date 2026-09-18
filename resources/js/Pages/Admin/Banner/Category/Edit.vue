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
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { STATUS_OPTIONS } from '@/utils/options';
import type { LanguageOption } from '@/types';

interface DetailFields {
    title: string;
    intro_text: string;
}

interface EditCategory {
    id: number;
    status: string;
}

const props = defineProps<{
    category: EditCategory;
    details: Record<string, DetailFields>;
    languages: LanguageOption[];
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm({
    status: props.category.status,
    detail: { ...props.details } as Record<string, DetailFields>,
});

function detailError(lang: string, field: string): string | undefined {
    return (form.errors as Record<string, string>)[`detail.${lang}.${field}`];
}

function submit() {
    form.put(route('admin.banner.category.update', props.category.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.banner.category.destroy', props.category.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const defaultTitle = computed(() => {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;
    return (defaultLang && props.details[defaultLang]?.title) || `หมวดหมู่ #${props.category.id}`;
});

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'หมวดหมู่ป้ายโฆษณา', href: route('admin.banner.category.index') },
    { label: defaultTitle.value },
]);
</script>

<template>
    <Head :title="`แก้ไขหมวดหมู่ป้ายโฆษณา: ${defaultTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="defaultTitle" :breadcrumbs="breadcrumbs" />
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

            <div v-if="can.manage || can.delete" class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                    <Trash2 class="mr-1.5 size-4" /> ลบ
                </DangerButton>
                <Link
                    :href="route('admin.banner.category.index')"
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
