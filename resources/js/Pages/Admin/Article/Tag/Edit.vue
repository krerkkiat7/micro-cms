<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import SystemInfoCard from '@/Components/Admin/SystemInfoCard.vue';
import type { SystemAudit } from '@/Components/Admin/SystemInfoCard.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
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
    name: string;
}

interface EditTag {
    id: number;
    status: string;
    article_count: number;
}

const props = defineProps<{
    tag: EditTag;
    details: Record<string, DetailFields>;
    languages: LanguageOption[];
    systemInfo: SystemAudit;
    articleCount: number;
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm({
    status: props.tag.status,
    detail: { ...props.details } as Record<string, DetailFields>,
});

function detailError(lang: string, field: string): string | undefined {
    return (form.errors as Record<string, string>)[`detail.${lang}.${field}`];
}

function submit() {
    form.put(route('admin.article.tag.update', props.tag.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.article.tag.destroy', props.tag.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const defaultName = computed(() => {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;
    return (defaultLang && props.details[defaultLang]?.name) || `แท็ก #${props.tag.id}`;
});

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'แท็กบทความ', href: route('admin.article.tag.index') },
    { label: defaultName.value },
]);
</script>

<template>
    <Head :title="`แก้ไขแท็กบทความ: ${defaultName}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="defaultName" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลแท็ก</h2>
                <p class="mt-1 text-sm text-gray-500">ใช้งานอยู่ใน {{ tag.article_count }} บทความ</p>

                <div class="mt-5 space-y-4">
                    <LangFieldGroup label="ชื่อ" :languages="languages" required>
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].name" type="text" />
                            <InputError :message="detailError(lang.code, 'name')" />
                        </template>
                    </LangFieldGroup>

                    <div class="sm:w-1/3">
                        <InputLabel value="สถานะ" required />
                        <SearchableSelect v-model="form.status" :options="STATUS_OPTIONS" />
                        <InputError :message="form.errors.status" />
                    </div>
                </div>
            </div>

            <SystemInfoCard :audit="systemInfo" :append="[{ label: 'จำนวนบทความ', value: articleCount }]" />

            <div v-if="can.manage || can.delete" class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                    <Trash2 class="mr-1.5 size-4" /> ลบ
                </DangerButton>
                <Link
                    :href="route('admin.article.tag.index')"
                    class="text-sm font-medium text-gray-500 hover:text-gray-700"
                >
                    กลับไปหน้ารายการ
                </Link>
            </div>
        </form>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบแท็ก"
            confirm-text="ลบแท็ก"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบแท็ก "{{ defaultName }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
