<script setup lang="ts">
import { computed } from 'vue';
import DateTimeInput from '@/Components/Admin/DateTimeInput.vue';
import SegmentedChoice from '@/Components/Admin/Template/SegmentedChoice.vue';
import YesNoCheckbox from '@/Components/Admin/Template/YesNoCheckbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import PopupDisplayTypePicker from './PopupDisplayTypePicker.vue';
import PopupMenuTreeNode from './PopupMenuTreeNode.vue';
import PopupPartList from './PopupPartList.vue';
import { STATUS_OPTIONS } from '@/utils/options';
import { POPUP_MENU_MODE_OPTIONS } from '@/utils/popupForm';
import type { InertiaForm } from '@inertiajs/vue3';
import type { LanguageOption } from '@/types';
import type { PopupItemFormData, PopupMenuNode } from '@/utils/popupForm';

/**
 * การ์ดฟอร์ม popup ใช้ร่วมหน้าเพิ่มและแก้ไข — ข้อมูล Popup (ชื่อ/รูปแบบ/การสไลด์) → ข้อมูลที่แสดง (part)
 * → การแสดงผล (ลำดับ + เมนูที่แสดง) → การเผยแพร่ → สถานะ; ฟอร์ม (`useForm`) เป็นของหน้าที่เรียกใช้
 */
const props = defineProps<{
    form: InertiaForm<PopupItemFormData>;
    languages: LanguageOption[];
    menuTree: PopupMenuNode[];
}>();

const errors = computed(() => props.form.errors as Record<string, string | undefined>);

const menuIdsError = computed(() => errors.value.menu_ids ?? Object.entries(errors.value).find(([key]) => key.startsWith('menu_ids.'))?.[1]);

const hasSelectable = computed(() => {
    const walk = (nodes: PopupMenuNode[]): boolean => nodes.some((node) => node.selectable || walk(node.children));
    return walk(props.menuTree);
});
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">ข้อมูล Popup</h2>
            <p class="mt-1 text-sm text-gray-500">ชื่อ (ใช้ในหลังบ้าน) รูปแบบการแสดง และการสไลด์เมื่อมีข้อมูลมากกว่า 1 รายการ</p>

            <div class="mt-5 space-y-5">
                <div class="grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <InputLabel value="ชื่อ" required />
                        <TextInput v-model="form.name" type="text" maxlength="250" />
                        <InputError :message="form.errors.name" />
                    </div>
                </div>

                <div>
                    <InputLabel value="รูปแบบการแสดงของ Popup" required />
                    <div class="max-w-xl">
                        <PopupDisplayTypePicker v-model="form.display_type" />
                    </div>
                    <InputError :message="form.errors.display_type" />
                </div>

                <YesNoCheckbox
                    v-model="form.show_dismiss_today"
                    label='แสดงปุ่ม "ไม่แสดงวันนี้อีก"'
                    :description="
                        form.display_type === 'modal'
                            ? 'แบบ Modal: มีปุ่ม “ปิด และไม่แสดงวันนี้อีก” อยู่ก่อนปุ่ม “ปิด”'
                            : 'แบบ Floating: มีลิงก์ “ไม่แสดงวันนี้อีก” ให้กดปิดและไม่แสดงอีกในวันนี้'
                    "
                />

                <section class="space-y-4">
                    <h3 class="border-b border-gray-100 pb-2 text-sm font-semibold text-gray-700">การสไลด์</h3>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <YesNoCheckbox v-model="form.show_arrows" label="แสดงลูกศร" />
                        <YesNoCheckbox v-model="form.show_dots" label="แสดงจุดด้านล่าง" />
                        <YesNoCheckbox v-model="form.autoplay" label="ให้สไลด์อัตโนมัติ" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-6">
                        <div class="sm:col-span-2">
                            <InputLabel value="เวลาที่ค้าง (วินาที)" required />
                            <TextInput v-model="form.slide_interval" type="number" min="1" max="120" step="1" :disabled="form.autoplay !== 'Y'" />
                            <InputError :message="form.errors.slide_interval" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel value="ความเร็วการสไลด์ (มิลลิวินาที)" required />
                            <TextInput v-model="form.slide_speed" type="number" min="100" max="10000" step="100" />
                            <InputError :message="form.errors.slide_speed" />
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">ข้อมูลที่แสดง</h2>
            <p class="mt-1 text-sm text-gray-500">
                แต่ละรายการแสดงเป็น 1 สไลด์ใน Popup — ต้องมีรายการที่แสดง (ไม่ได้ซ่อน) อย่างน้อย 1 รายการ
            </p>

            <div class="mt-5">
                <PopupPartList v-model="form.parts" :languages="languages" :form-errors="errors" />
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">การแสดงผล</h2>
            <p class="mt-1 text-sm text-gray-500">ลำดับใช้เมื่อตั้งค่าโมดูลให้เรียง Popup ตามลำดับ และหน้าที่แสดง Popup</p>

            <div class="mt-5 space-y-5">
                <div class="grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-1">
                        <InputLabel value="ลำดับ" />
                        <TextInput v-model="form.sort_order" type="number" min="0" step="1" />
                        <InputError :message="form.errors.sort_order" />
                    </div>
                </div>

                <div>
                    <InputLabel value="เมนูที่แสดง" required />
                    <SegmentedChoice v-model="form.menu_mode" :options="POPUP_MENU_MODE_OPTIONS" />
                    <p class="mt-1 text-xs text-gray-500">
                        <template v-if="form.menu_mode === 'all'">แสดงทุกหน้าของเว็บไซต์ (ยกเว้นหน้า Intropage)</template>
                        <template v-else-if="form.menu_mode === 'selected'">แสดงเฉพาะหน้าของเมนูที่เลือก (เลือกได้เฉพาะเมนูที่ผูกกับโมดูลเนื้อหา)</template>
                        <template v-else>ยังไม่แสดงที่หน้าเว็บไซต์</template>
                    </p>
                    <InputError :message="form.errors.menu_mode" />
                </div>

                <div v-if="form.menu_mode === 'selected'">
                    <div class="max-h-96 overflow-y-auto rounded-xl border border-gray-200 p-2">
                        <PopupMenuTreeNode v-for="node in menuTree" :key="node.id" v-model="form.menu_ids" :node="node" :depth="0" />
                        <p v-if="!hasSelectable" class="py-6 text-center text-sm text-gray-400">ยังไม่มีเมนูที่ผูกกับโมดูลเนื้อหา</p>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">เลือกแล้ว {{ form.menu_ids.length }} เมนู</p>
                    <InputError :message="menuIdsError" />
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
            <h2 class="text-base font-semibold text-gray-800">การเผยแพร่</h2>
            <p class="mt-1 text-sm text-gray-500">Popup แสดงที่หน้าเว็บไซต์ตั้งแต่วันที่เผยแพร่ จนถึงวันที่ปิดการเผยแพร่ (ถ้ากำหนด)</p>

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
                    <p class="mt-1 text-xs text-gray-500">ปิดใช้งานแล้ว Popup นี้จะไม่แสดงที่หน้าเว็บไซต์</p>
                    <InputError :message="form.errors.status" />
                </div>
            </div>
        </div>
    </div>
</template>
