<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import SegmentedChoice from '@/Components/Admin/Template/SegmentedChoice.vue';
import YesNoCheckbox from '@/Components/Admin/Template/YesNoCheckbox.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LayoutGrid, List, Save } from 'lucide-vue-next';
import { computed } from 'vue';
import { SLIDESET_IMAGE_FIT_OPTIONS, SLIDESET_LINES_OPTIONS } from '@/utils/pageWidget';
import { ARTICLE_SORT_OPTIONS, ARTICLE_SHARE_POSITION_OPTIONS, ARTICLE_ASPECT_OPTIONS, ARTICLE_ROW_ASPECT_OPTIONS } from '@/utils/articleSetting';

/**
 * ตั้งค่าโมดูลบทความ — ฟอร์มเดียวรวมทุกกลุ่ม (ต่างจากตั้งค่าระบบที่แยกฟอร์ม/ปุ่มบันทึกต่อกลุ่ม)
 * คีย์/ค่าเริ่มต้นทั้งหมดมาจาก App\Support\ArticleSetting (controller ส่งค่าที่ merge ค่าเริ่มต้นแล้วมาครบทุกคีย์)
 */
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

const form = useForm<Record<string, string>>({ ...props.settings });

const errors = computed(() => form.errors as Record<string, string | undefined>);

// กลุ่มย่อยการแสดงแบบการ์ด/แถว ใช้ฟิลด์ชุดเดียวกัน (prefix card_ / row_) — แถวมีตัวเลือกอัตราส่วน "ตามขนาดของรูป" เพิ่ม
const views = [
    { prefix: 'card', title: 'การแสดงแบบการ์ด', icon: LayoutGrid, aspectOptions: ARTICLE_ASPECT_OPTIONS },
    { prefix: 'row', title: 'การแสดงแบบแถว', icon: List, aspectOptions: ARTICLE_ROW_ASPECT_OPTIONS },
];

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
                    <p class="mt-1 text-sm text-gray-500">การตั้งค่าที่มีผลกับหน้ารายการบทความฝั่งหน้าบ้าน (รายการตามหมวดหมู่ และรายการตามแท็ก)</p>

                    <div class="mt-6 space-y-8">
                        <section class="space-y-4">
                            <h3 class="border-b border-gray-100 pb-2 text-sm font-semibold text-gray-700">ส่วนหัวของหมวดหมู่</h3>
                            <YesNoCheckbox
                                v-model="form.list_show_category_intro"
                                label="แสดงข้อความเกริ่นนำของหมวดหมู่"
                                description="แสดงใต้ชื่อหมวดหมู่ ด้านบนของรายการบทความ"
                            />
                            <YesNoCheckbox
                                v-model="form.list_show_category_detail"
                                label="แสดงรายละเอียดของหมวดหมู่"
                                description="แสดงต่อจากข้อความเกริ่นนำ ด้านบนของรายการบทความ"
                            />
                        </section>

                        <section class="space-y-4">
                            <h3 class="border-b border-gray-100 pb-2 text-sm font-semibold text-gray-700">รายการ</h3>
                            <div class="grid gap-4 sm:grid-cols-6">
                                <div class="sm:col-span-2">
                                    <InputLabel for="list_per_page" value="จำนวนรายการที่แสดงต่อหน้า" required />
                                    <TextInput id="list_per_page" v-model="form.list_per_page" type="number" min="1" max="100" step="1" />
                                    <InputError :message="errors.list_per_page" />
                                </div>
                                <div class="sm:col-span-2">
                                    <InputLabel value="การเรียงลำดับตั้งต้น" required />
                                    <SearchableSelect v-model="form.list_default_sort" :options="ARTICLE_SORT_OPTIONS" />
                                    <InputError :message="errors.list_default_sort" />
                                </div>
                                <div class="sm:col-span-2">
                                    <InputLabel value="รูปแบบการแสดงผลตั้งต้น" required />
                                    <SegmentedChoice
                                        v-model="form.list_display_mode"
                                        :options="[
                                            { value: 'card', label: 'การ์ด', icon: LayoutGrid },
                                            { value: 'row', label: 'แถว', icon: List },
                                        ]"
                                    />
                                    <InputError :message="errors.list_display_mode" />
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">ผู้ชมเปลี่ยนการเรียงลำดับและรูปแบบการแสดงผลเองได้ที่หน้ารายการ ค่านี้คือค่าที่แสดงเมื่อเข้าหน้าครั้งแรก</p>
                            <YesNoCheckbox v-model="form.list_show_date" label="แสดงวันที่เผยแพร่" />
                            <YesNoCheckbox v-model="form.list_show_views" label="แสดงจำนวนเข้าชม" />
                        </section>

                        <section v-for="view in views" :key="view.prefix" class="space-y-4">
                            <h3 class="flex items-center gap-2 border-b border-gray-100 pb-2 text-sm font-semibold text-gray-700">
                                <component :is="view.icon" class="size-4 text-gray-400" aria-hidden="true" />
                                {{ view.title }}
                            </h3>
                            <div class="grid gap-4 sm:grid-cols-6">
                                <div class="sm:col-span-3">
                                    <InputLabel value="อัตราส่วนรูปภาพ" required />
                                    <SearchableSelect v-model="form[`${view.prefix}_aspect_ratio`]" :options="view.aspectOptions" />
                                    <InputError :message="errors[`${view.prefix}_aspect_ratio`]" />
                                </div>
                                <template v-if="form[`${view.prefix}_aspect_ratio`] !== 'natural'">
                                    <div class="sm:col-span-3">
                                        <InputLabel value="ประเภทการแสดงรูปภาพ" required />
                                        <SearchableSelect v-model="form[`${view.prefix}_image_fit`]" :options="SLIDESET_IMAGE_FIT_OPTIONS" />
                                        <InputError :message="errors[`${view.prefix}_image_fit`]" />
                                    </div>
                                    <div v-if="form[`${view.prefix}_image_fit`] === 'contain'" class="sm:col-span-6">
                                        <InputLabel value="สีพื้นหลังของรูปภาพ" />
                                        <ColorPickerInput v-model="form[`${view.prefix}_image_background`]" transparent />
                                        <InputError :message="errors[`${view.prefix}_image_background`]" />
                                    </div>
                                </template>
                                <div class="sm:col-span-3">
                                    <InputLabel value="จำนวนแถวที่แสดงหัวเรื่อง" required />
                                    <SearchableSelect v-model="form[`${view.prefix}_title_lines`]" :options="SLIDESET_LINES_OPTIONS" />
                                    <InputError :message="errors[`${view.prefix}_title_lines`]" />
                                </div>
                                <div class="sm:col-span-3">
                                    <InputLabel value="จำนวนแถวที่แสดงข้อความเกริ่นนำ" required />
                                    <SearchableSelect v-model="form[`${view.prefix}_intro_lines`]" :options="SLIDESET_LINES_OPTIONS" />
                                    <InputError :message="errors[`${view.prefix}_intro_lines`]" />
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                    <h2 class="text-base font-semibold text-gray-800">รายละเอียดบทความ</h2>
                    <p class="mt-1 text-sm text-gray-500">การตั้งค่าที่มีผลกับหน้ารายละเอียดบทความฝั่งหน้าบ้าน</p>

                    <div class="mt-5 space-y-4">
                        <YesNoCheckbox
                            v-model="form.detail_show_cover"
                            label="แสดงรูปภาพหน้าปก"
                            description="แสดงรูปภาพหน้าปกของบทความก่อนเนื้อหา"
                        />
                        <YesNoCheckbox
                            v-model="form.detail_show_print"
                            label="แสดงปุ่มพิมพ์"
                            description="แสดงปุ่มพิมพ์บทความต่อจากวันที่เผยแพร่และจำนวนเข้าชม"
                        />
                        <div>
                            <InputLabel value="แชร์บทความ" required />
                            <SegmentedChoice v-model="form.detail_share_position" :options="ARTICLE_SHARE_POSITION_OPTIONS" />
                            <p class="mt-1 text-xs text-gray-500">ปุ่มแชร์ไปยัง Facebook / X / LINE และคัดลอกลิงก์ แสดงก่อนและ/หรือหลังเนื้อหา</p>
                            <InputError :message="errors.detail_share_position" />
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
