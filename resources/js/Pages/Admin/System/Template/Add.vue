<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import PresetPicker from '@/Components/Admin/Template/PresetPicker.vue';
import YesNoCheckbox from '@/Components/Admin/Template/YesNoCheckbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';

/**
 * เพิ่ม template — กรอกชื่อ + เลือกแม่แบบตั้งต้น (ค่าเริ่มต้นของทุกโซน) แล้วไปปรับต่อที่แท็บโครงสร้าง
 * ยังไม่มีรายการที่ใช้งานอยู่ (hasActive = false) = รายการนี้จะถูกเปิดใช้งานเสมอ
 */
const props = defineProps<{
    hasActive: boolean;
}>();

const form = useForm({
    name: '',
    preset: 'classic',
    status: props.hasActive ? 'N' : 'Y',
});

function submit() {
    form.post(route('admin.system.template.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการ Template', href: route('admin.system.template.index') },
    { label: 'เพิ่ม Template' },
];
</script>

<template>
    <Head title="เพิ่ม Template" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่ม Template" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลทั่วไป</h2>

                <div class="mt-5 space-y-5">
                    <div class="sm:w-2/3">
                        <InputLabel value="ชื่อ Template" required />
                        <TextInput v-model="form.name" type="text" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <YesNoCheckbox
                            v-if="hasActive"
                            v-model="form.status"
                            label="เปิดใช้งานทันที"
                            description="Template ที่ใช้งานอยู่ในปัจจุบันจะถูกปิดใช้งานอัตโนมัติ"
                        />
                        <p v-else class="text-sm text-emerald-700">ยังไม่มี Template ที่ใช้งานอยู่ — รายการนี้จะถูกเปิดใช้งานอัตโนมัติ</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">เลือกแม่แบบ</h2>
                <p class="mt-1 text-sm text-gray-500">แม่แบบเป็นจุดเริ่มต้นของการตั้งค่า header / footer / เมนูข้าง — ปรับแก้ทุกอย่างต่อได้ที่แท็บ "โครงสร้าง"</p>

                <div class="mt-5">
                    <PresetPicker v-model="form.preset" />
                    <InputError :message="form.errors.preset" />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <BackToListButton :href="route('admin.system.template.index')" cancel />
            </div>
        </form>
    </AdminLayout>
</template>
