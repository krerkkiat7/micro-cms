<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import SettingSection from '@/Components/Admin/PageLayout/widgets/SettingSection.vue';
import FlagField from '@/Components/Admin/PageLayout/widgets/FlagField.vue';
import ContactusDisplayTypePicker from '@/Components/Admin/Contactus/ContactusDisplayTypePicker.vue';
import MapPickerDialog from '@/Components/Admin/Contactus/MapPickerDialog.vue';
import ContactusTextFields from '@/Components/Admin/Contactus/ContactusTextFields.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, MapPin, Save, ShieldAlert } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { CONTACTUS_FORM_FIELDS, CONTACTUS_TEXT_PARTS } from '@/utils/contactus';
import type { FileItem } from '@/types';

/**
 * ตั้งค่าโมดูลติดต่อเรา — คีย์/ค่าเริ่มต้นมาจาก App\Support\ContactusSetting
 * ข้อมูลติดต่อจริง (ชื่อเจ้าของ/ที่อยู่/เบอร์/อีเมล/social) อ่านจากตั้งค่าระบบ หน้านี้ตั้งค่าแค่การแสดงผล
 */
const props = defineProps<{
    settings: Record<string, string>;
    mapImageFile: FileItem | null;
    fonts: string[];
    fontsUrl: string;
    contact: Record<string, string | null>;
    turnstileConfigured: boolean;
    googleMapKeySet: boolean;
    canManageSystemSetting: boolean;
}>();

const breadcrumbs = computed(() => [{ label: 'Dashboard', href: route('admin.dashboard') }, { label: 'ตั้งค่าติดต่อเรา' }]);

const tabs = computed(() => [
    { label: 'ตั้งค่า', href: route('admin.contactus.setting.index'), active: true },
    { label: 'ล้างแคช', href: route('admin.contactus.setting.clearcache'), active: false },
]);

const form = useForm<Record<string, string | FileItem[]>>({
    ...props.settings,
    map_image: props.mapImageFile ? [props.mapImageFile] : [],
});

// ฟิลด์แบบ 'Y'/'N' และข้อความทั่วไปของฟอร์ม — เข้าถึงผ่านชื่อคีย์ที่ประกอบขึ้น (show_address, form_phone_show ฯลฯ)
const values = form as unknown as Record<string, string>;

function flag(name: string) {
    return computed({
        get: () => values[name] as 'Y' | 'N',
        set: (value: 'Y' | 'N') => {
            values[name] = value;
        },
    });
}

const partFlags = Object.fromEntries(CONTACTUS_TEXT_PARTS.map(({ part }) => [part, flag(`show_${part}`)]));
const formShowFlags = Object.fromEntries(CONTACTUS_FORM_FIELDS.map(({ field }) => [field, flag(`form_${field}_show`)]));
const formRequiredFlags = Object.fromEntries(CONTACTUS_FORM_FIELDS.map(({ field }) => [field, flag(`form_${field}_required`)]));
const showSocial = flag('show_social');
const showMapImage = flag('show_map_image');
const showGoogleMap = flag('show_google_map');
const showForm = flag('show_form');

const mapImage = computed({
    get: () => form.map_image as FileItem[],
    set: (value: FileItem[]) => {
        form.map_image = value;
    },
});

const errors = computed(() => form.errors as Record<string, string | undefined>);

const showMapPicker = ref(false);

function onPickCoordinates(value: { latitude: string; longitude: string }) {
    values.latitude = value.latitude;
    values.longitude = value.longitude;
    showMapPicker.value = false;
}

function submit() {
    form.transform((data) => {
        const { map_image, ...rest } = data as Record<string, unknown>;

        return { ...rest, map_image_id: (map_image as FileItem[])[0]?.id ?? null };
    }).put(route('admin.contactus.setting.update'), { preserveScroll: true });
}
</script>

<template>
    <Head title="ตั้งค่าโมดูลติดต่อเรา">
        <link rel="stylesheet" :href="fontsUrl" />
    </Head>

    <AdminLayout>
        <template #header>
            <PageHeader title="ตั้งค่าติดต่อเรา" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <TabNav :tabs="tabs" />

            <form class="space-y-6" @submit.prevent="submit">
                <!-- รูปแบบการแสดงผล -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                    <h2 class="text-base font-semibold text-gray-800">รูปแบบการแสดงผล</h2>
                    <p class="mt-1 text-sm text-gray-500">การจัดวางข้อมูลติดต่อ แผนที่ และแบบฟอร์มในหน้าติดต่อเราที่หน้าบ้าน</p>

                    <div class="mt-5">
                        <ContactusDisplayTypePicker v-model="values.display_type" />
                        <InputError :message="errors.display_type" />
                    </div>
                </div>

                <!-- ข้อมูลติดต่อ -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                    <h2 class="text-base font-semibold text-gray-800">ข้อมูลติดต่อ</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        ข้อความที่แสดงมาจาก
                        <Link v-if="canManageSystemSetting" :href="route('admin.system.setting.index')" class="text-brand-600 underline">ตั้งค่าระบบ</Link>
                        <template v-else>ตั้งค่าระบบ</template>
                        (ข้อมูลติดต่อ / Social Media) — ที่นี่กำหนดการแสดงและรูปแบบตัวอักษร รายการที่ไม่ได้กรอกไว้จะไม่แสดงที่หน้าบ้าน
                    </p>

                    <div class="mt-5 space-y-4">
                        <SettingSection
                            v-for="item in CONTACTUS_TEXT_PARTS"
                            :key="item.part"
                            v-model:enabled="partFlags[item.part].value"
                            :title="item.label + (item.toggleable ? '' : ' (แสดงเสมอ)')"
                            :description="contact[item.part] ? `ค่าปัจจุบัน: ${contact[item.part]}` : 'ยังไม่ได้กรอกในตั้งค่าระบบ'"
                            :toggleable="item.toggleable"
                        >
                            <ContactusTextFields :values="values" :part="item.part" :fonts="fonts" />
                        </SettingSection>

                        <SettingSection v-model:enabled="showSocial" title="Social Media" description="ลิงก์ Social Media ที่กรอกไว้ในตั้งค่าระบบ" toggleable>
                            <p class="text-sm text-gray-500">แสดงไอคอนลิงก์ Social Media ต่อจากข้อมูลติดต่อ</p>
                        </SettingSection>
                    </div>
                </div>

                <!-- แผนที่ -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                    <h2 class="text-base font-semibold text-gray-800">แผนที่</h2>

                    <div class="mt-5 space-y-4">
                        <SettingSection v-model:enabled="showMapImage" title="รูปแผนที่" description="รูปภาพแผนที่ที่จัดทำไว้เอง" toggleable>
                            <div>
                                <InputLabel value="รูปแผนที่" required />
                                <FilePickerField v-model="mapImage" :accept="['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']" />
                                <InputError :message="errors.map_image_id" />
                            </div>
                        </SettingSection>

                        <SettingSection v-model:enabled="showGoogleMap" title="Google Map" description="แผนที่ Google Map จากพิกัด" toggleable>
                            <div
                                v-if="!googleMapKeySet"
                                class="flex gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                                role="alert"
                            >
                                <AlertTriangle class="mt-0.5 size-5 shrink-0" />
                                <div>
                                    ยังไม่ได้ตั้งค่า API Key ของ Google Map — การแสดงแผนที่ที่หน้าบ้านจะดูไม่เรียบร้อย
                                    กรุณาตั้งค่าที่
                                    <Link v-if="canManageSystemSetting" :href="route('admin.system.setting.index')" class="font-medium underline">
                                        ตั้งค่าระบบ → Google Map
                                    </Link>
                                    <span v-else class="font-medium">ตั้งค่าระบบ → Google Map</span>
                                    หรือติดต่อผู้ดูแลระบบ
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-6">
                                <div class="sm:col-span-2">
                                    <InputLabel for="contactus_latitude" value="ละติจูด (Latitude)" required />
                                    <TextInput id="contactus_latitude" v-model="values.latitude" type="text" inputmode="decimal" placeholder="13.756331" />
                                    <InputError :message="errors.latitude" />
                                </div>
                                <div class="sm:col-span-2">
                                    <InputLabel for="contactus_longitude" value="ลองจิจูด (Longitude)" required />
                                    <TextInput id="contactus_longitude" v-model="values.longitude" type="text" inputmode="decimal" placeholder="100.501765" />
                                    <InputError :message="errors.longitude" />
                                </div>
                                <div class="flex items-end sm:col-span-2">
                                    <button
                                        type="button"
                                        class="inline-flex w-full items-center justify-center rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors hover:bg-sky-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
                                        @click="showMapPicker = true"
                                    >
                                        <MapPin class="mr-1.5 size-4" /> เลือกจากแผนที่
                                    </button>
                                </div>
                            </div>
                        </SettingSection>
                    </div>
                </div>

                <!-- แบบฟอร์มติดต่อ -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                    <h2 class="text-base font-semibold text-gray-800">แบบฟอร์มติดต่อ</h2>

                    <div class="mt-5">
                        <SettingSection v-model:enabled="showForm" title="แบบฟอร์มติดต่อ" description="ให้ผู้ชมกรอกข้อมูลส่งมาถึงผู้ดูแล" toggleable>
                            <div
                                v-if="!turnstileConfigured"
                                class="flex gap-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"
                                role="alert"
                            >
                                <ShieldAlert class="mt-0.5 size-5 shrink-0" />
                                <div>
                                    ยังไม่ได้ตั้งค่า Turnstile CAPTCHA — เพื่อความปลอดภัย <strong>หน้าบ้านจะไม่แสดงแบบฟอร์มติดต่อ</strong>
                                    จนกว่าจะตั้งค่า กรุณาตั้งค่าที่
                                    <Link v-if="canManageSystemSetting" :href="route('admin.system.setting.index')" class="font-medium underline">
                                        ตั้งค่าระบบ → Turnstile CAPTCHA
                                    </Link>
                                    <span v-else class="font-medium">ตั้งค่าระบบ → Turnstile CAPTCHA</span>
                                    หรือติดต่อผู้ดูแลระบบ
                                </div>
                            </div>

                            <p class="text-sm text-gray-600">
                                ฟิลด์ <strong>ชื่อ - นามสกุล</strong> แสดงเสมอและจำเป็นต้องกรอก — ฟิลด์อื่นกำหนดการแสดงและการบังคับกรอกได้ด้านล่าง
                            </p>

                            <div class="divide-y divide-gray-100 rounded-lg border border-gray-200">
                                <div
                                    v-for="item in CONTACTUS_FORM_FIELDS"
                                    :key="item.field"
                                    class="grid gap-2 px-4 py-3 sm:grid-cols-3 sm:items-center"
                                >
                                    <span class="text-sm font-medium text-gray-800">{{ item.label }}</span>
                                    <FlagField v-model="formShowFlags[item.field].value" label="แสดง" />
                                    <FlagField
                                        v-if="formShowFlags[item.field].value === 'Y'"
                                        v-model="formRequiredFlags[item.field].value"
                                        label="จำเป็นต้องกรอก"
                                    />
                                </div>
                            </div>
                        </SettingSection>
                    </div>
                </div>

                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
            </form>
        </div>

        <MapPickerDialog
            :show="showMapPicker"
            :latitude="values.latitude"
            :longitude="values.longitude"
            @close="showMapPicker = false"
            @select="onPickCoordinates"
        />
    </AdminLayout>
</template>
