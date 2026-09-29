<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import SegmentedChoice from '@/Components/Admin/Template/SegmentedChoice.vue';
import YesNoCheckbox from '@/Components/Admin/Template/YesNoCheckbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed } from 'vue';
import { LOADING_SPINNERS, LOADING_TYPES, templateTabs } from '@/utils/template';
import type { FileItem } from '@/types';

/**
 * แท็บหน้า Loading ของ template — หน้าจอคั่นระหว่างโหลดหน้าบ้าน (เปิด/ปิดได้) เป็นตัวหมุนของระบบหรือรูปจากไฟล์ (เช่น GIF)
 * ด้านขวาเป็นตัวอย่างตามค่าที่ตั้ง
 */
const props = defineProps<{
    template: {
        id: number;
        name: string;
        loading_status: string;
        loading_show_logo: string;
        loading_type: string;
        loading_spinner: string;
        loading_color: string;
        loading_background_color: string;
        loading_image: FileItem | null;
    };
    /** โลโก้จากตั้งค่าระบบ (null = ยังไม่ได้ตั้งค่า) */
    logoUrl: string | null;
    siteName: string;
    can: { manage: boolean };
}>();

const form = useForm({
    loading_status: props.template.loading_status,
    loading_show_logo: props.template.loading_show_logo,
    loading_type: props.template.loading_type,
    loading_spinner: props.template.loading_spinner,
    loading_color: props.template.loading_color,
    loading_background_color: props.template.loading_background_color,
    loading_image: props.template.loading_image ? [props.template.loading_image] : ([] as FileItem[]),
});

function submit() {
    form.transform(({ loading_image, ...data }) => ({ ...data, loading_image_id: loading_image[0]?.id ?? null })).put(
        route('admin.system.template.loading.update', props.template.id),
        { preserveScroll: true },
    );
}

const imageUrl = computed(() => (form.loading_image[0] ? route('admin.system.file.get', form.loading_image[0].hash_name) : null));

const tabs = computed(() => templateTabs(props.template.id, 'loading'));

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการ Template', href: route('admin.system.template.index') },
    { label: props.template.name },
]);
</script>

<template>
    <Head :title="`หน้า Loading: ${template.name}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="template.name" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-4">
            <TabNav :tabs="tabs" />

            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid gap-6 lg:grid-cols-5">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:col-span-3 lg:p-8">
                        <h2 class="text-base font-semibold text-gray-800">หน้า Loading</h2>
                        <p class="mt-1 text-sm text-gray-500">แสดงระหว่างโหลดหน้าบ้าน แล้วหายไปเมื่อหน้าโหลดเสร็จ</p>

                        <div class="mt-5 space-y-5">
                            <YesNoCheckbox v-model="form.loading_status" label="ใช้งานหน้า Loading" />

                            <div>
                                <YesNoCheckbox
                                    v-model="form.loading_show_logo"
                                    label="แสดงโลโก้"
                                    description="แสดงโลโก้ของไซต์ (จากหน้าตั้งค่าระบบ) เหนือตัวหมุน/รูป Loading"
                                />
                                <p v-if="form.loading_show_logo === 'Y' && !logoUrl" class="mt-1 text-xs text-amber-600">
                                    ยังไม่ได้ตั้งค่าโลโก้ในหน้าตั้งค่าระบบ — จะแสดงชื่อเว็บแทน
                                </p>
                            </div>

                            <div>
                                <InputLabel value="ประเภท" />
                                <SegmentedChoice v-model="form.loading_type" :options="LOADING_TYPES" />
                                <InputError :message="form.errors.loading_type" />
                            </div>

                            <div v-if="form.loading_type === 'spinner'" class="grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <InputLabel value="รูปแบบตัวหมุน" />
                                    <SegmentedChoice v-model="form.loading_spinner" :options="LOADING_SPINNERS" />
                                </div>
                                <div>
                                    <InputLabel value="สีตัวหมุน" />
                                    <ColorPickerInput v-model="form.loading_color" />
                                    <InputError :message="form.errors.loading_color" />
                                </div>
                            </div>

                            <div v-else>
                                <InputLabel value="รูปภาพ Loading" :required="form.loading_status === 'Y'" />
                                <FilePickerField v-model="form.loading_image" :accept="['gif', 'png', 'jpg', 'jpeg', 'webp', 'svg']" />
                                <InputError :message="(form.errors as Record<string, string>).loading_image_id" />
                            </div>

                            <div class="sm:w-1/2">
                                <InputLabel value="สีพื้นหลัง" />
                                <ColorPickerInput v-model="form.loading_background_color" transparent />
                                <InputError :message="form.errors.loading_background_color" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:col-span-2">
                        <h2 class="text-sm font-semibold text-gray-800">ตัวอย่าง</h2>
                        <div
                            class="mt-4 flex h-64 flex-col items-center justify-center gap-5 rounded-xl border border-gray-200"
                            :class="form.loading_status === 'Y' ? '' : 'opacity-50'"
                            :style="{ backgroundColor: form.loading_background_color }"
                        >
                            <template v-if="form.loading_show_logo === 'Y'">
                                <img v-if="logoUrl" :src="logoUrl" alt="" class="max-h-16 max-w-[60%] object-contain" />
                                <span v-else class="text-lg font-semibold text-gray-700">{{ siteName }}</span>
                            </template>
                            <template v-if="form.loading_type === 'image'">
                                <img v-if="imageUrl" :src="imageUrl" alt="" class="max-h-32 max-w-[70%] object-contain" />
                                <span v-else class="text-sm text-gray-400">ยังไม่ได้เลือกรูปภาพ</span>
                            </template>
                            <span
                                v-else-if="form.loading_spinner === 'ring'"
                                class="size-12 animate-spin rounded-full border-4 border-t-transparent"
                                :style="{ borderColor: form.loading_color, borderTopColor: 'transparent' }"
                            />
                            <span v-else-if="form.loading_spinner === 'dots'" class="flex gap-2">
                                <span
                                    v-for="i in 3"
                                    :key="i"
                                    class="size-3 animate-bounce rounded-full"
                                    :style="{ backgroundColor: form.loading_color, animationDelay: `${(i - 1) * 150}ms` }"
                                />
                            </span>
                            <span v-else class="relative h-1.5 w-40 overflow-hidden rounded-full bg-gray-200">
                                <span class="absolute inset-y-0 left-0 w-1/3 animate-pulse rounded-full" :style="{ backgroundColor: form.loading_color }" />
                            </span>
                        </div>
                        <p v-if="form.loading_status !== 'Y'" class="mt-2 text-center text-xs text-gray-500">ยังไม่ได้เปิดใช้งาน</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                    <BackToListButton :href="route('admin.system.template.index')" />
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
