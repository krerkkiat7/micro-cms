<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import DateTimeInput from '@/Components/Admin/DateTimeInput.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import IntropageDisplayTypePicker from '@/Components/Admin/IntropageDisplayTypePicker.vue';
import IntropageDisplaySizePicker from '@/Components/Admin/IntropageDisplaySizePicker.vue';
import BackgroundRepeatPicker from '@/Components/Admin/IntropageBackground/RepeatPicker.vue';
import BackgroundSizePicker from '@/Components/Admin/IntropageBackground/SizePicker.vue';
import BackgroundAttachmentPicker from '@/Components/Admin/IntropageBackground/AttachmentPicker.vue';
import BackgroundPositionPicker from '@/Components/Admin/IntropageBackground/PositionPicker.vue';
import ButtonList from '@/Components/Admin/IntropageButton/ButtonList.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { STATUS_OPTIONS } from '@/utils/options';
import { buttonsToPayload, createHomeButton } from '@/utils/intropageButtons';
import type { ButtonData } from '@/utils/intropageButtons';
import type { FileItem, LanguageOption } from '@/types';

interface DetailFields {
    title: string;
    detail: string;
}

function emptyDetail(): DetailFields {
    return { title: '', detail: '' };
}

const props = defineProps<{
    languages: LanguageOption[];
}>();

const initialDetail: Record<string, DetailFields> = {};
props.languages.forEach((lang) => {
    initialDetail[lang.code] = emptyDetail();
});

function nowDateTime(): string {
    const d = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
}

const form = useForm({
    background_color: '#ffffff',
    background_image_id: null as number | null,
    background_repeat: '',
    background_size: '',
    background_attachment: '',
    background_position: '',
    display_type: 'image',
    display_size: 'screen_100',
    image_file_id: null as number | null,
    vdo_file_id: null as number | null,
    vdo_url: '',
    show_button: 'Y',
    publish_date: nowDateTime() as string | null,
    publish_down: null as string | null,
    status: 'Y',
    detail: initialDetail,
    buttons: [createHomeButton(props.languages)] as ButtonData[],
});

const backgroundImage = ref<FileItem[]>([]);
watch(backgroundImage, (files) => {
    form.background_image_id = files[0]?.id ?? null;
});

const imageFile = ref<FileItem[]>([]);
watch(imageFile, (files) => {
    form.image_file_id = files[0]?.id ?? null;
});

const vdoFile = ref<FileItem[]>([]);
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
    })).post(route('admin.intropage.item.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'Intropage', href: route('admin.intropage.item.index') },
    { label: 'เพิ่ม Intropage' },
];
</script>

<template>
    <Head title="เพิ่ม Intropage" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่ม Intropage" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลทั่วไป</h2>

                <div class="mt-5 space-y-5">
                    <div>
                        <InputLabel value="ประเภทการแสดงผล" required />
                        <IntropageDisplayTypePicker v-model="form.display_type" />
                        <InputError :message="form.errors.display_type" />
                    </div>

                    <div v-if="form.display_type === 'image'">
                        <InputLabel value="รูปภาพ" required />
                        <FilePickerField v-model="imageFile" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                        <InputError :message="form.errors.image_file_id" />
                    </div>
                    <div v-else-if="form.display_type === 'vdo'">
                        <InputLabel value="ไฟล์วิดีโอ" required />
                        <FilePickerField v-model="vdoFile" :accept="['mp4']" />
                        <InputError :message="form.errors.vdo_file_id" />
                    </div>
                    <div v-else>
                        <InputLabel :value="form.display_type === 'youtubeurl' ? 'YouTube URL' : 'URL วิดีโอ'" required />
                        <TextInput
                            v-model="form.vdo_url"
                            type="text"
                            :placeholder="form.display_type === 'youtubeurl' ? 'https://www.youtube.com/watch?v=...' : 'https://...'"
                        />
                        <InputError :message="form.errors.vdo_url" />
                    </div>

                    <div>
                        <InputLabel value="ขนาดการแสดงผล" required />
                        <IntropageDisplaySizePicker v-model="form.display_size" />
                        <InputError :message="form.errors.display_size" />
                    </div>

                    <div class="space-y-5 border-t border-gray-100 pt-5">
                        <h3 class="text-sm font-medium text-gray-600">พื้นหลัง</h3>

                        <div class="grid gap-4 sm:grid-cols-6">
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
                        </div>

                        <div>
                            <InputLabel value="การเรียงซ้ำ (Background Repeat)" />
                            <BackgroundRepeatPicker v-model="form.background_repeat" />
                            <InputError :message="form.errors.background_repeat" />
                        </div>

                        <div>
                            <InputLabel value="ขนาด (Background Size)" />
                            <BackgroundSizePicker v-model="form.background_size" />
                            <InputError :message="form.errors.background_size" />
                        </div>

                        <div>
                            <InputLabel value="การเลื่อน (Background Attachment)" />
                            <BackgroundAttachmentPicker v-model="form.background_attachment" />
                            <InputError :message="form.errors.background_attachment" />
                        </div>

                        <div>
                            <InputLabel value="ตำแหน่ง (Background Position)" />
                            <BackgroundPositionPicker v-model="form.background_position" />
                            <InputError :message="form.errors.background_position" />
                        </div>
                    </div>

                    <div class="grid gap-4 border-t border-gray-100 pt-5 sm:grid-cols-6">
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
                        <div class="sm:col-span-2">
                            <InputLabel value="สถานะ" required />
                            <SearchableSelect v-model="form.status" :options="STATUS_OPTIONS" />
                            <InputError :message="form.errors.status" />
                        </div>
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

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <Link :href="route('admin.intropage.item.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    ยกเลิก
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
