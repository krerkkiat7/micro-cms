<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import IntropageFormFields from '@/Components/Admin/IntropageForm/IntropageFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { buttonsToPayload, createHomeButton } from '@/utils/intropageButtons';
import type { ButtonData } from '@/utils/intropageButtons';
import type { LanguageOption } from '@/types';

interface DetailFields {
    title: string;
    detail: string;
}

const props = defineProps<{
    languages: LanguageOption[];
    fonts: string[];
    fontsUrl: string;
}>();

const initialDetail: Record<string, DetailFields> = {};
props.languages.forEach((lang) => {
    initialDetail[lang.code] = { title: '', detail: '' };
});

function nowDateTime(): string {
    const d = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
}

// ค่าเริ่มต้นตรงกับ default ของคอลัมน์ใน migration
const form = useForm({
    detail: initialDetail,
    detail_font_family: 'Sarabun',
    detail_font_size: 18,
    detail_color: '#1F2937',
    display_type: 'image',
    display_size: 'screen_100',
    image_file_id: null as number | null,
    vdo_file_id: null as number | null,
    vdo_url: '',
    background_color: '#ffffff',
    background_image_id: null as number | null,
    background_repeat: '',
    background_size: '',
    background_attachment: '',
    background_position: '',
    show_button: 'Y',
    button_font_size: 16,
    button_font_family: 'Sarabun',
    buttons: [createHomeButton(props.languages)] as ButtonData[],
    publish_date: nowDateTime() as string | null,
    publish_down: null as string | null,
    status: 'Y',
});

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
    <Head title="เพิ่ม Intropage">
        <link rel="stylesheet" :href="fontsUrl" />
    </Head>

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่ม Intropage" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <IntropageFormFields :form="form" :languages="languages" :fonts="fonts" />

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
