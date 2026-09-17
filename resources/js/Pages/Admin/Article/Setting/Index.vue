<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import RadioGroup from '@/Components/RadioGroup.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    settings: Record<string, string>;
}>();

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'ตั้งค่าบทความ' },
]);

const tabs = computed(() => [
    { label: 'ตั้งค่า', href: route('admin.article.setting.index'), active: true },
    { label: 'ล้างแคช', href: route('admin.article.setting.clearcache'), active: false },
]);

// ฟอร์มเดียวรวมทุกกลุ่ม (ตอนนี้มีกลุ่ม "รายการบทความ" กลุ่มเดียว) — ต่างจากตั้งค่าระบบที่แยกฟอร์ม/ปุ่มบันทึกต่อกลุ่ม
const form = useForm({
    list_per_page: props.settings.list_per_page ?? '10',
    list_display_mode: props.settings.list_display_mode ?? 'card',
});

function submit() {
    form.put(route('admin.article.setting.update'), { preserveScroll: true });
}
</script>

<template>
    <Head title="ตั้งค่าโมดูลบทความ" />

    <AdminLayout>
        <template #header>
            <PageHeader title="ตั้งค่าบทความ" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <TabNav :tabs="tabs" />

            <form class="space-y-6" @submit.prevent="submit">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                    <h2 class="text-base font-semibold text-gray-800">รายการบทความ</h2>
                    <p class="mt-1 text-sm text-gray-500">การตั้งค่าที่มีผลกับหน้ารายการบทความฝั่งหน้าบ้าน</p>

                    <div class="mt-5 grid gap-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <InputLabel for="list_per_page" value="จำนวนรายการที่แสดง" required />
                            <TextInput id="list_per_page" v-model="form.list_per_page" type="number" min="1" max="100" step="1" />
                            <InputError :message="form.errors.list_per_page" />
                        </div>

                        <div class="sm:col-span-6">
                            <InputLabel value="รูปแบบการแสดงผล" required />
                            <RadioGroup
                                v-model="form.list_display_mode"
                                name="list_display_mode"
                                class="mt-1"
                                :options="[
                                    { label: 'การ์ด', value: 'card' },
                                    { label: 'แถว', value: 'row' },
                                ]"
                            />
                            <InputError :message="form.errors.list_display_mode" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <PrimaryButton type="submit" :disabled="form.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
