<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import SystemInfoCard from '@/Components/Admin/SystemInfoCard.vue';
import type { SystemAudit } from '@/Components/Admin/SystemInfoCard.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Textarea from '@/Components/Textarea.vue';
import { CONTACTUS_PROCESS_STATUS_OPTIONS } from '@/utils/contactus';
import { formatDateTime } from '@/utils/date';
import { languageLabel } from '@/utils/languages';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed } from 'vue';

/**
 * รายละเอียดข้อมูลติดต่อเรา — แสดงทุกฟิลด์ของแบบฟอร์มเสมอ (แม้ตั้งค่าซ่อนไว้) + บันทึกสถานะ/หมายเหตุ
 * ข้อความจากผู้ชมแสดงเป็น text ธรรมดาเท่านั้น (ห้ามใช้ v-html)
 */
const props = defineProps<{
    item: {
        id: number;
        fullname: string;
        position: string | null;
        company: string | null;
        phone: string | null;
        email: string | null;
        subject: string | null;
        detail: string | null;
        process_status: string;
        note: string | null;
    };
    systemInfo: SystemAudit;
    meta: { remote_ip: string | null; lang: string };
    can: { manage: boolean };
}>();

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'ติดต่อเรา', href: route('admin.contactus.item.index') },
    { label: 'รายละเอียด' },
]);

const fields = computed(() => [
    { label: 'ชื่อ - นามสกุล', value: props.item.fullname },
    { label: 'ตำแหน่ง', value: props.item.position },
    { label: 'บริษัท', value: props.item.company },
    { label: 'เบอร์ติดต่อ', value: props.item.phone },
    { label: 'อีเมล', value: props.item.email },
    { label: 'หัวข้อ', value: props.item.subject },
]);

const form = useForm({
    process_status: props.item.process_status,
    note: props.item.note ?? '',
});

function submit() {
    form.put(route('admin.contactus.item.update', props.item.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="รายละเอียดติดต่อเรา" />

    <AdminLayout>
        <template #header>
            <PageHeader title="รายละเอียดติดต่อเรา" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <!-- ข้อมูลการติดต่อ -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลการติดต่อ</h2>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div v-for="field in fields" :key="field.label">
                        <dt class="text-sm text-gray-500">{{ field.label }}</dt>
                        <dd class="mt-0.5 break-words text-sm font-medium text-gray-800">
                            <a v-if="field.label === 'อีเมล' && field.value" :href="`mailto:${field.value}`" class="text-brand-600 hover:underline">
                                {{ field.value }}
                            </a>
                            <template v-else>{{ field.value || '-' }}</template>
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm text-gray-500">รายละเอียด</dt>
                        <dd class="mt-1 whitespace-pre-line break-words rounded-lg bg-gray-50 p-4 text-sm text-gray-800">{{ item.detail || '-' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- บันทึกความเห็น / หมายเหตุ -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                <h2 class="text-base font-semibold text-gray-800">บันทึกความเห็น / หมายเหตุ</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <InputLabel value="สถานะ" required />
                        <SearchableSelect v-model="form.process_status" :options="CONTACTUS_PROCESS_STATUS_OPTIONS" :disabled="!can.manage" />
                        <InputError :message="form.errors.process_status" />
                    </div>
                    <div class="sm:col-span-6">
                        <InputLabel for="contactus_note" value="หมายเหตุ" />
                        <Textarea id="contactus_note" v-model="form.note" rows="5" :disabled="!can.manage" />
                        <InputError :message="form.errors.note" />
                    </div>
                </div>
            </div>

            <!-- ข้อมูลระบบ -->
            <SystemInfoCard
                :audit="systemInfo"
                hide-created
                :prepend="[{ label: 'วันเวลาที่ส่ง', value: formatDateTime(systemInfo.created_at) }]"
                :append="[
                    { label: 'IP Address', value: meta.remote_ip },
                    { label: 'กรอกจากภาษา', value: languageLabel(meta.lang) },
                ]"
            />

            <div class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <BackToListButton :href="route('admin.contactus.item.index')" />
            </div>
        </form>
    </AdminLayout>
</template>
