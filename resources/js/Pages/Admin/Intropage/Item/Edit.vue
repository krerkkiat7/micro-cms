<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import DateTimeInput from '@/Components/Admin/DateTimeInput.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import ButtonList from '@/Components/Admin/IntropageButton/ButtonList.vue';
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
import {
    BACKGROUND_ATTACHMENT_OPTIONS,
    BACKGROUND_POSITION_OPTIONS,
    BACKGROUND_REPEAT_OPTIONS,
    BACKGROUND_SIZE_OPTIONS,
    INTROPAGE_DISPLAY_SIZE_OPTIONS,
    INTROPAGE_DISPLAY_TYPE_OPTIONS,
    STATUS_OPTIONS,
} from '@/utils/options';
import { buttonsFromServer, buttonsToPayload } from '@/utils/intropageButtons';
import type { ButtonData, ButtonType, ButtonDisplayType } from '@/utils/intropageButtons';
import type { FileItem, LanguageOption } from '@/types';

interface DetailFields {
    title: string;
    detail: string;
}

interface EditItem {
    id: number;
    background_color: string | null;
    background_image: FileItem | null;
    background_repeat: string | null;
    background_size: string | null;
    background_attachment: string | null;
    background_position: string | null;
    display_type: string;
    display_size: string;
    image_file: FileItem | null;
    vdo_file: FileItem | null;
    vdo_url: string | null;
    show_button: string;
    publish_date: string | null;
    publish_down: string | null;
    status: string;
}

interface ServerButton {
    button_type: ButtonType;
    button_display_type: ButtonDisplayType;
    background_color: string | null;
    text_color: string | null;
    button_image: FileItem | null;
    url: string | null;
    link_target: string | null;
    texts: Record<string, string> | null;
}

const props = defineProps<{
    item: EditItem;
    details: Record<string, DetailFields>;
    buttons: ServerButton[];
    languages: LanguageOption[];
    can: { manage: boolean; delete: boolean };
}>();

const optionalOption = { value: '', label: 'ไม่ระบุ' };
const backgroundRepeatOptions = computed(() => [optionalOption, ...BACKGROUND_REPEAT_OPTIONS]);
const backgroundSizeOptions = computed(() => [optionalOption, ...BACKGROUND_SIZE_OPTIONS]);
const backgroundAttachmentOptions = computed(() => [optionalOption, ...BACKGROUND_ATTACHMENT_OPTIONS]);
const backgroundPositionOptions = computed(() => [optionalOption, ...BACKGROUND_POSITION_OPTIONS]);

const form = useForm({
    background_color: props.item.background_color ?? '',
    background_image_id: props.item.background_image?.id ?? null,
    background_repeat: props.item.background_repeat ?? '',
    background_size: props.item.background_size ?? '',
    background_attachment: props.item.background_attachment ?? '',
    background_position: props.item.background_position ?? '',
    display_type: props.item.display_type,
    display_size: props.item.display_size,
    image_file_id: props.item.image_file?.id ?? null,
    vdo_file_id: props.item.vdo_file?.id ?? null,
    vdo_url: props.item.vdo_url ?? '',
    show_button: props.item.show_button,
    publish_date: props.item.publish_date,
    publish_down: props.item.publish_down,
    status: props.item.status,
    detail: { ...props.details } as Record<string, DetailFields>,
    buttons: buttonsFromServer(props.buttons, props.languages) as ButtonData[],
});

const backgroundImage = ref<FileItem[]>(props.item.background_image ? [props.item.background_image] : []);
watch(backgroundImage, (files) => {
    form.background_image_id = files[0]?.id ?? null;
});

const imageFile = ref<FileItem[]>(props.item.image_file ? [props.item.image_file] : []);
watch(imageFile, (files) => {
    form.image_file_id = files[0]?.id ?? null;
});

const vdoFile = ref<FileItem[]>(props.item.vdo_file ? [props.item.vdo_file] : []);
watch(vdoFile, (files) => {
    form.vdo_file_id = files[0]?.id ?? null;
});

function detailError(lang: string, field: string): string | undefined {
    return (form.errors as Record<string, string>)[`detail.${lang}.${field}`];
}

function submit() {
    form.transform((data) => ({
        ...data,
        buttons: buttonsToPayload(data.buttons),
    })).put(route('admin.intropage.item.update', props.item.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.intropage.item.destroy', props.item.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const defaultTitle = computed(() => {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;
    return (defaultLang && props.details[defaultLang]?.title) || `Intropage #${props.item.id}`;
});

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'Intropage', href: route('admin.intropage.item.index') },
    { label: defaultTitle.value },
]);
</script>

<template>
    <Head :title="`แก้ไข Intropage: ${defaultTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="defaultTitle" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลทั่วไป</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-2">
                        <InputLabel value="สีพื้นหลัง" />
                        <ColorPickerInput v-model="form.background_color" />
                        <InputError :message="form.errors.background_color" />
                    </div>
                    <div class="sm:col-span-4">
                        <InputLabel value="รูปภาพพื้นหลัง" />
                        <FilePickerField v-model="backgroundImage" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                        <InputError :message="form.errors.background_image_id" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel value="Background Repeat" />
                        <SearchableSelect v-model="form.background_repeat" :options="backgroundRepeatOptions" />
                        <InputError :message="form.errors.background_repeat" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Background Size" />
                        <SearchableSelect v-model="form.background_size" :options="backgroundSizeOptions" />
                        <InputError :message="form.errors.background_size" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Background Attachment" />
                        <SearchableSelect v-model="form.background_attachment" :options="backgroundAttachmentOptions" />
                        <InputError :message="form.errors.background_attachment" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Background Position" />
                        <SearchableSelect v-model="form.background_position" :options="backgroundPositionOptions" />
                        <InputError :message="form.errors.background_position" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel value="ประเภทการแสดงผล" required />
                        <SearchableSelect v-model="form.display_type" :options="INTROPAGE_DISPLAY_TYPE_OPTIONS" />
                        <InputError :message="form.errors.display_type" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="ขนาดการแสดงผล" required />
                        <SearchableSelect v-model="form.display_size" :options="INTROPAGE_DISPLAY_SIZE_OPTIONS" />
                        <InputError :message="form.errors.display_size" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="สถานะ" required />
                        <SearchableSelect v-model="form.status" :options="STATUS_OPTIONS" />
                        <InputError :message="form.errors.status" />
                    </div>

                    <div v-if="form.display_type === 'image'" class="sm:col-span-6">
                        <InputLabel value="รูปภาพ" required />
                        <FilePickerField v-model="imageFile" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                        <InputError :message="form.errors.image_file_id" />
                    </div>
                    <div v-else-if="form.display_type === 'vdo'" class="sm:col-span-6">
                        <InputLabel value="ไฟล์วิดีโอ" required />
                        <FilePickerField v-model="vdoFile" :accept="['mp4']" />
                        <InputError :message="form.errors.vdo_file_id" />
                    </div>
                    <div v-else class="sm:col-span-6">
                        <InputLabel :value="form.display_type === 'youtubeurl' ? 'YouTube URL' : 'URL วิดีโอ'" required />
                        <TextInput
                            v-model="form.vdo_url"
                            type="text"
                            :placeholder="form.display_type === 'youtubeurl' ? 'https://www.youtube.com/watch?v=...' : 'https://...'"
                        />
                        <InputError :message="form.errors.vdo_url" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel value="วันที่ประกาศ" required />
                        <DateTimeInput v-model="form.publish_date" />
                        <InputError :message="form.errors.publish_date" />
                    </div>
                    <div class="sm:col-span-3">
                        <InputLabel value="วันที่ปิดประกาศ" required />
                        <DateTimeInput v-model="form.publish_down" />
                        <InputError :message="form.errors.publish_down" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลหน้า Intropage</h2>

                <div class="mt-5 space-y-4">
                    <LangFieldGroup label="ชื่อ" :languages="languages" required>
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].title" type="text" />
                            <InputError :message="detailError(lang.code, 'title')" />
                        </template>
                    </LangFieldGroup>

                    <LangFieldGroup label="ข้อความต้อนรับ" :languages="languages">
                        <template #default="{ lang }">
                            <Textarea v-model="form.detail[lang.code].detail" rows="3" />
                            <InputError :message="detailError(lang.code, 'detail')" />
                        </template>
                    </LangFieldGroup>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">การจัดการปุ่ม</h2>
                <p class="mt-0.5 text-xs text-gray-500">ปุ่ม "เข้าหน้าแรก" มีอยู่เสมอ 1 ปุ่ม (ลบไม่ได้ แต่เรียงลำดับได้)</p>

                <div class="mt-5">
                    <InputLabel value="แสดงโซนปุ่ม" />
                    <SearchableSelect v-model="form.show_button" :options="STATUS_OPTIONS" class="max-w-xs" />
                    <InputError :message="form.errors.show_button" />
                </div>

                <div class="mt-5">
                    <ButtonList v-model="form.buttons" :languages="languages" :form-errors="(form.errors as Record<string, string>)" />
                    <InputError :message="form.errors.buttons" />
                </div>
            </div>

            <div v-if="can.manage || can.delete" class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                    <Trash2 class="mr-1.5 size-4" /> ลบ
                </DangerButton>
                <Link :href="route('admin.intropage.item.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    กลับไปหน้ารายการ
                </Link>
            </div>
        </form>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบ Intropage"
            confirm-text="ลบ Intropage"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบ Intropage "{{ defaultTitle }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
