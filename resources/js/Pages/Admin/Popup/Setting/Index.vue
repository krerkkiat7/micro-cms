<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed } from 'vue';
import { POPUP_DISPLAY_ORDER_OPTIONS } from '@/utils/popupForm';

/**
 * ตั้งค่าโมดูล popup — คีย์/ค่าเริ่มต้นมาจาก App\Support\PopupSetting
 */
const props = defineProps<{
    settings: Record<string, string>;
}>();

const breadcrumbs = computed(() => [{ label: 'Dashboard', href: route('admin.dashboard') }, { label: 'ตั้งค่า Popup' }]);

const tabs = computed(() => [
    { label: 'ตั้งค่า', href: route('admin.popup.setting.index'), active: true },
    { label: 'ล้างแคช', href: route('admin.popup.setting.clearcache'), active: false },
]);

const form = useForm<Record<string, string>>({ ...props.settings });

function submit() {
    form.put(route('admin.popup.setting.update'), { preserveScroll: true });
}
</script>

<template>
    <Head title="ตั้งค่าโมดูล Popup" />

    <AdminLayout>
        <template #header>
            <PageHeader title="ตั้งค่า Popup" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <TabNav :tabs="tabs" />

            <form class="space-y-6" @submit.prevent="submit">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                    <h2 class="text-base font-semibold text-gray-800">การแสดงผล</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        เมื่อหน้าเดียวมีหลาย Popup จะแสดงซ้อนกัน — Popup รายการแรกตามลำดับนี้จะอยู่บนสุด
                    </p>

                    <div class="mt-5 grid gap-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <InputLabel value="ลำดับการแสดงผล" required />
                            <SearchableSelect v-model="form.display_order" :options="POPUP_DISPLAY_ORDER_OPTIONS" />
                            <InputError :message="form.errors.display_order" />
                        </div>
                    </div>
                </div>

                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
            </form>
        </div>
    </AdminLayout>
</template>
