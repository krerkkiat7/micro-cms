<script setup lang="ts">
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import DateTimeInput from '@/Components/Admin/DateTimeInput.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import SegmentedChoice from '@/Components/Admin/Template/SegmentedChoice.vue';
import { Ban, Link2, ListTree } from 'lucide-vue-next';
import { computed } from 'vue';
import { LINK_TARGET_OPTIONS, STATUS_OPTIONS } from '@/utils/options';
import { BANNER_LINK_TYPES } from '@/utils/bannerForm';
import { frontMenuSelectOptions } from '@/utils/readAllButton';
import type { InertiaForm } from '@inertiajs/vue3';
import type { BannerItemFormData } from '@/utils/bannerForm';
import type { FrontMenuPickerOption } from '@/utils/readAllButton';
import type { FileItem, LanguageOption } from '@/types';

/**
 * การ์ดฟอร์มป้ายโฆษณา ใช้ร่วมกันระหว่างหน้าเพิ่มและแก้ไข — เรียงตามลำดับ:
 * ข้อมูลป้ายโฆษณา (ชื่อ + ข้อความเกริ่นนำ) → หมวดหมู่ → รูปภาพและลิงก์ (รูปภาพ + ลำดับ + ประเภทลิงก์ → เมนู หรือ URL + เปิดลิงก์แบบ)
 * → การเผยแพร่ (วันที่เผยแพร่ + วันที่ปิดการเผยแพร่) → สถานะ
 * ฟอร์ม (`useForm`) เป็นของหน้าที่เรียกใช้; รูปภาพผูกผ่าน v-model:introImage (FilePickerField ใช้ array เสมอ)
 */
const props = defineProps<{
    form: InertiaForm<BannerItemFormData>;
    languages: LanguageOption[];
    categoryOptions: { value: string; label: string }[];
    /** เมนูหน้าบ้านสำหรับลิงก์ประเภท "เมนู" (FrontMenuTree::pickerOptions) */
    frontMenus: FrontMenuPickerOption[];
}>();

// ลิงก์: เลือกประเภทก่อน (ไม่มีลิงก์ / เมนู / กำหนดเอง) แบบเดียวกับปุ่ม "อ่านทั้งหมด" ของ widget
const LINK_TYPE_ICONS = { none: Ban, menu: ListTree, custom: Link2 };
const linkTypeOptions = BANNER_LINK_TYPES.map((option) => ({ ...option, icon: LINK_TYPE_ICONS[option.value] }));
const menuOptions = computed(() => frontMenuSelectOptions(props.frontMenus));

const introImage = defineModel<FileItem[]>('introImage', { required: true });

function detailError(lang: string, field: string): string | undefined {
    return (props.form.errors as Record<string, string | undefined>)[`detail.${lang}.${field}`];
}
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">ข้อมูลป้ายโฆษณา</h2>
            <p class="mt-1 text-sm text-gray-500">ชื่อและคำอธิบายสั้น ๆ ของป้ายโฆษณา แยกตามภาษา</p>

            <div class="mt-5 space-y-4">
                <LangFieldGroup label="ชื่อ" :languages="languages" required>
                    <template #default="{ lang }">
                        <TextInput v-model="form.detail[lang.code].title" type="text" />
                        <InputError :message="detailError(lang.code, 'title')" />
                    </template>
                </LangFieldGroup>

                <LangFieldGroup label="ข้อความเกริ่นนำ" :languages="languages">
                    <template #default="{ lang }">
                        <Textarea v-model="form.detail[lang.code].intro_text" rows="3" />
                        <InputError :message="detailError(lang.code, 'intro_text')" />
                    </template>
                </LangFieldGroup>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">หมวดหมู่</h2>
            <p class="mt-1 text-sm text-gray-500">กำหนดว่าป้ายโฆษณานี้แสดงอยู่ในชุดใด (widget ของหน้าเพจดึงป้ายโฆษณาตามหมวดหมู่)</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <InputLabel value="หมวดหมู่" required />
                    <SearchableSelect v-model="form.banner_category_info_id" :options="categoryOptions" placeholder="เลือกหมวดหมู่" />
                    <InputError :message="form.errors.banner_category_info_id" />
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">รูปภาพและลิงก์</h2>
            <p class="mt-1 text-sm text-gray-500">รูปที่แสดง ปลายทางเมื่อคลิก และลำดับการแสดงเทียบกับป้ายโฆษณาอื่นในหมวดหมู่เดียวกัน</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-6">
                    <InputLabel value="รูปภาพ" required />
                    <FilePickerField v-model="introImage" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                    <InputError :message="form.errors.intro_image_id" />
                </div>
                <div class="sm:col-span-2">
                    <InputLabel value="ลำดับ" />
                    <TextInput v-model="form.sort_order" type="number" min="0" step="1" />
                    <InputError :message="form.errors.sort_order" />
                </div>
                <div class="sm:col-span-6">
                    <InputLabel value="ประเภทลิงก์ปลายทาง" />
                    <SegmentedChoice v-model="form.link_type" :options="linkTypeOptions" />
                    <p v-if="form.link_type === 'none'" class="mt-1 text-xs text-gray-500">แสดงเฉพาะรูปภาพ คลิกแล้วไม่ไปที่ใด</p>
                    <InputError :message="form.errors.link_type" />
                </div>
                <div v-if="form.link_type === 'menu'" class="sm:col-span-4">
                    <InputLabel value="เมนูปลายทาง" required />
                    <SearchableSelect v-model="form.front_menu_info_id" :options="menuOptions" placeholder="เลือกเมนู" />
                    <p class="mt-1 text-xs text-gray-500">
                        เลือกได้เฉพาะเมนูที่ลิงก์ไปยังบทความ หน้าเพจ ติดต่อเรา หรือลิงค์ภายนอก (เมนูหัวข้อเลือกไม่ได้) — เปิดลิงก์ตามที่ตั้งไว้ในเมนูนั้น
                        และถ้าเมนูถูกซ่อนภายหลัง ป้ายโฆษณาจะแสดงแบบไม่มีลิงก์
                    </p>
                    <p v-if="frontMenus.length === 0" class="mt-1 text-xs text-amber-600">ยังไม่มีเมนูหน้าบ้านที่เปิดใช้งาน — สร้างที่ "จัดการเมนูหน้าบ้าน" ก่อน</p>
                    <InputError :message="form.errors.front_menu_info_id" />
                </div>
                <template v-else-if="form.link_type === 'custom'">
                    <div class="sm:col-span-4">
                        <InputLabel value="ลิงก์ URL ปลายทาง" required />
                        <TextInput v-model="form.url" type="text" placeholder="https://example.com/promo หรือ /promo" maxlength="500" />
                        <p class="mt-1 text-xs text-gray-500">
                            ขึ้นต้นด้วย http://, https:// หรือ / — ลิงก์ภายในเว็บที่ไม่ได้ขึ้นต้นด้วยภาษา (เช่น /promo) จะเติมภาษาของหน้าที่เปิดอยู่ให้อัตโนมัติ
                        </p>
                        <InputError :message="form.errors.url" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="เปิดลิงก์แบบ" />
                        <SearchableSelect v-model="form.link_target" :options="LINK_TARGET_OPTIONS" />
                        <InputError :message="form.errors.link_target" />
                    </div>
                </template>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">การเผยแพร่</h2>
            <p class="mt-1 text-sm text-gray-500">ป้ายโฆษณาแสดงที่หน้าเว็บไซต์ตั้งแต่วันที่เผยแพร่ จนถึงวันที่ปิดการเผยแพร่ (ถ้ากำหนด)</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <InputLabel value="วันที่เผยแพร่" required />
                    <DateTimeInput v-model="form.publish_date" />
                    <InputError :message="form.errors.publish_date" />
                </div>
                <div class="sm:col-span-3">
                    <InputLabel value="วันที่ปิดการเผยแพร่" />
                    <DateTimeInput v-model="form.publish_down" clearable />
                    <InputError :message="form.errors.publish_down" />
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">สถานะ</h2>

            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <InputLabel value="สถานะ" required />
                    <SearchableSelect v-model="form.status" :options="STATUS_OPTIONS" />
                    <p class="mt-1 text-xs text-gray-500">ปิดใช้งานแล้วป้ายโฆษณานี้จะไม่แสดงที่หน้าเว็บไซต์</p>
                    <InputError :message="form.errors.status" />
                </div>
            </div>
        </div>
    </div>
</template>
