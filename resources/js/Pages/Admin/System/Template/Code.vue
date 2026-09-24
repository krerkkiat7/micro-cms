<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import YesNoCheckbox from '@/Components/Admin/Template/YesNoCheckbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Textarea from '@/Components/Textarea.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed } from 'vue';
import { templateTabs } from '@/utils/template';

/**
 * แท็บ Custom CSS/JS ของ template — โค้ดที่จะแทรกในหน้าบ้านเมื่อ template นี้ใช้งานอยู่ (เปิด/ปิดแยกกันได้
 * โดยไม่ต้องลบโค้ดทิ้ง) CSS แทรกท้าย <head>, JS แทรกท้าย <body>
 */
const props = defineProps<{
    template: {
        id: number;
        name: string;
        custom_css_status: string;
        custom_css: string;
        custom_js_status: string;
        custom_js: string;
    };
    can: { manage: boolean };
}>();

const form = useForm({
    custom_css_status: props.template.custom_css_status,
    custom_css: props.template.custom_css,
    custom_js_status: props.template.custom_js_status,
    custom_js: props.template.custom_js,
});

function submit() {
    form.put(route('admin.system.template.code.update', props.template.id), { preserveScroll: true });
}

const tabs = computed(() => templateTabs(props.template.id, 'code'));

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการ Template', href: route('admin.system.template.index') },
    { label: props.template.name },
]);
</script>

<template>
    <Head :title="`Custom CSS/JS: ${template.name}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="template.name" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-4">
            <TabNav :tabs="tabs" />

            <form class="space-y-6" @submit.prevent="submit">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                    <h2 class="text-base font-semibold text-gray-800">Custom CSS</h2>
                    <p class="mt-1 text-sm text-gray-500">แทรกท้าย &lt;head&gt; ของทุกหน้าบ้าน — ไม่ต้องใส่แท็ก &lt;style&gt;</p>

                    <div class="mt-5 space-y-4">
                        <YesNoCheckbox v-model="form.custom_css_status" label="ใช้งาน Custom CSS" />
                        <Textarea
                            v-model="form.custom_css"
                            rows="14"
                            class="font-mono text-xs"
                            spellcheck="false"
                            placeholder=".site-header { box-shadow: 0 1px 4px rgba(0, 0, 0, .1); }"
                            :disabled="!can.manage"
                        />
                        <InputError :message="form.errors.custom_css" />
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                    <h2 class="text-base font-semibold text-gray-800">Custom JS</h2>
                    <p class="mt-1 text-sm text-gray-500">แทรกท้าย &lt;body&gt; ของทุกหน้าบ้าน — ไม่ต้องใส่แท็ก &lt;script&gt; (ระวัง: โค้ดทำงานกับผู้เข้าชมทุกคน)</p>

                    <div class="mt-5 space-y-4">
                        <YesNoCheckbox v-model="form.custom_js_status" label="ใช้งาน Custom JS" />
                        <Textarea
                            v-model="form.custom_js"
                            rows="14"
                            class="font-mono text-xs"
                            spellcheck="false"
                            placeholder="console.log('hello');"
                            :disabled="!can.manage"
                        />
                        <InputError :message="form.errors.custom_js" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                    <Link :href="route('admin.system.template.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                        กลับไปหน้ารายการ
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
