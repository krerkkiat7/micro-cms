<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import RadioGroup from '@/Components/RadioGroup.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import TestSmtpDialog from '@/Components/Admin/TestSmtpDialog.vue';
import SocialIcon from '@/Components/Admin/SocialIcon.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Send } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { FileItem } from '@/types';

interface Props {
    settings: {
        site: Record<string, string>;
        social: Record<string, string>;
        smtp: Record<string, string>;
        turnstile: Record<string, string>;
        login_back: Record<string, string>;
    };
    logoFile: FileItem | null;
    faviconFile: FileItem | null;
    /** รายชื่อ timezone identifier ทั้งหมดที่ PHP รู้จัก — ใช้กับ SearchableSelect ของฟิลด์ "โซนเวลา" */
    timezoneOptions: { value: string; label: string }[];
}

const props = defineProps<Props>();

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'ตั้งค่าระบบ' },
]);

const tabs = computed(() => [
    { label: 'ตั้งค่าระบบ', href: route('admin.system.setting.index'), active: true },
    { label: 'ล้างแคช', href: route('admin.system.setting.clearcache'), active: false },
]);

// ---------- กลุ่ม "ข้อมูลระบบ" ----------

/** ภาษาที่ระบบรองรับให้เลือกได้ตอนนี้ — ต้องตรงกับ UpdateSiteSettingRequest::AVAILABLE_LANGUAGES ฝั่ง backend */
const LANGUAGE_OPTIONS = [
    { code: 'th', label: 'ภาษาไทย' },
    { code: 'en', label: 'ภาษาอังกฤษ' },
];

const siteForm = useForm({
    site_name: props.settings.site?.site_name ?? '',
    site_email: props.settings.site?.site_email ?? '',
    site_description: props.settings.site?.site_description ?? '',
    logo_id: props.logoFile?.id ?? null,
    favicon_id: props.faviconFile?.id ?? null,
    copyright_year: props.settings.site?.copyright_year ?? '',
    copyright_owner: props.settings.site?.copyright_owner ?? '',
    // ว่าง = ไม่ระบุ (cascade ไปใช้ .env APP_TIMEZONE หรือ php.ini ต่อไป — ดู config/app.php)
    timezone: props.settings.site?.timezone ?? '',
    // เก็บรวมเป็น 1 record คั่นด้วย , ในฐานข้อมูล (ดู App\Support\Setting::selectedLanguages()) — ฝั่งฟอร์มแยกเป็น array
    lang_selected: (props.settings.site?.lang_selected ?? 'th,en').split(',').filter(Boolean),
    lang_default: props.settings.site?.lang_default ?? 'th',
});

/** ตัวเลือกของ dropdown "ภาษาหลัก" — จำกัดเฉพาะภาษาที่ติ๊กเลือกไว้ใน "ภาษาในระบบ" เท่านั้น */
const availableDefaultLanguages = computed(() =>
    LANGUAGE_OPTIONS.filter((lang) => siteForm.lang_selected.includes(lang.code)),
);

const availableDefaultLanguageOptions = computed(() =>
    availableDefaultLanguages.value.map((lang) => ({ value: lang.code, label: `${lang.label} (${lang.code})` })),
);

// เพิ่มตัวเลือก "ไม่ระบุ" ไว้ตัวแรกให้เลือกกลับไปว่างได้ (ว่าง = cascade ไปใช้ .env/php.ini แทน — ดู config/app.php)
const timezoneSelectOptions = computed(() => [{ value: '', label: 'ไม่ระบุ (ใช้การตั้งค่าของเซิร์ฟเวอร์)' }, ...props.timezoneOptions]);

function toggleLang(code: string) {
    const idx = siteForm.lang_selected.indexOf(code);

    if (idx !== -1) {
        if (siteForm.lang_selected.length <= 1) {
            return; // ต้องเลือกไว้อย่างน้อย 1 ภาษาเสมอ
        }
        siteForm.lang_selected.splice(idx, 1);
    } else {
        siteForm.lang_selected.push(code);
    }
}

// เอาภาษาที่เลือกไว้เป็น "ภาษาหลัก" ออกจาก "ภาษาในระบบ" ไปแล้ว — เปลี่ยนภาษาหลักไปที่ตัวแรกที่เหลือให้อัตโนมัติ
watch(
    () => siteForm.lang_selected.slice(),
    (selected) => {
        if (!selected.includes(siteForm.lang_default)) {
            siteForm.lang_default = selected[0] ?? '';
        }
    },
);

// FilePickerField ทำงานกับ array ของไฟล์เสมอ (เลือกได้ 1 ไฟล์) — เลือกใหม่/เอาออก = แทนที่/ล้าง logo_id เดิม
const logoFile = ref<FileItem[]>(props.logoFile ? [props.logoFile] : []);
watch(logoFile, (files) => {
    siteForm.logo_id = files[0]?.id ?? null;
});

const faviconFile = ref<FileItem[]>(props.faviconFile ? [props.faviconFile] : []);
watch(faviconFile, (files) => {
    siteForm.favicon_id = files[0]?.id ?? null;
});

function submitSite() {
    siteForm.put(route('admin.system.setting.update.site'), { preserveScroll: true });
}

// ---------- กลุ่ม "Social Media" ----------
const SOCIAL_FIELDS: { key: 'facebook' | 'youtube' | 'x' | 'instagram' | 'tiktok' | 'line'; label: string }[] = [
    { key: 'facebook', label: 'Facebook' },
    { key: 'youtube', label: 'YouTube' },
    { key: 'x', label: 'X' },
    { key: 'instagram', label: 'Instagram' },
    { key: 'tiktok', label: 'TikTok' },
    { key: 'line', label: 'LINE' },
];

const socialForm = useForm({
    facebook: props.settings.social?.facebook ?? '',
    youtube: props.settings.social?.youtube ?? '',
    x: props.settings.social?.x ?? '',
    instagram: props.settings.social?.instagram ?? '',
    tiktok: props.settings.social?.tiktok ?? '',
    line: props.settings.social?.line ?? '',
});

function submitSocial() {
    socialForm.put(route('admin.system.setting.update.social'), { preserveScroll: true });
}

// ---------- กลุ่ม "SMTP" ----------
const smtpForm = useForm({
    host: props.settings.smtp?.host ?? '',
    port: props.settings.smtp?.port ?? '',
    use_auth: props.settings.smtp?.use_auth ?? 'N',
    username: props.settings.smtp?.username ?? '',
    password: props.settings.smtp?.password ?? '',
    ssl_type: props.settings.smtp?.ssl_type ?? 'none',
    from_name: props.settings.smtp?.from_name ?? '',
    from_email: props.settings.smtp?.from_email ?? '',
});

watch(
    () => smtpForm.use_auth,
    (value) => {
        if (value !== 'Y') {
            smtpForm.username = '';
            smtpForm.password = '';
        }
    },
);

function submitSmtp() {
    smtpForm.put(route('admin.system.setting.update.smtp'), { preserveScroll: true });
}

// ปุ่ม "ทดสอบส่งอีเมล" ใช้ค่า SMTP ที่บันทึกไว้แล้วเท่านั้น (props.settings.smtp มาจาก DB จริง ไม่ใช่
// ค่าที่พิมพ์ค้างใน smtpForm) — ยังไม่เคยบันทึก host เลยก็ปิดปุ่มไว้ก่อน กันกดแล้วงงว่าทำไมส่งไม่ได้
const canTestSmtp = computed(() => !!props.settings.smtp?.host);
const showTestSmtpDialog = ref(false);

// ---------- กลุ่ม "Turnstile CAPTCHA" ----------
const turnstileForm = useForm({
    site_key: props.settings.turnstile?.site_key ?? '',
    key_secret: props.settings.turnstile?.key_secret ?? '',
});

function submitTurnstile() {
    turnstileForm.put(route('admin.system.setting.update.turnstile'), { preserveScroll: true });
}

// ---------- กลุ่ม "การเข้าสู่ระบบหลังบ้าน" ----------
const loginBackForm = useForm({
    captcha_enabled: props.settings.login_back?.captcha_enabled ?? 'N',
    lockout_enabled: props.settings.login_back?.lockout_enabled ?? 'N',
    lockout_count: props.settings.login_back?.lockout_count ?? '',
});

watch(
    () => loginBackForm.lockout_enabled,
    (value) => {
        if (value !== 'Y') {
            loginBackForm.lockout_count = '';
        }
    },
);

function submitLoginBack() {
    loginBackForm.put(route('admin.system.setting.update.login_back'), { preserveScroll: true });
}
</script>

<template>
    <Head title="ตั้งค่าระบบ" />

    <AdminLayout>
        <template #header>
            <PageHeader title="ตั้งค่าระบบ" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <TabNav :tabs="tabs" />

            <!-- ข้อมูลระบบ -->
            <form
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
                @submit.prevent="submitSite"
            >
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลระบบ</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <InputLabel for="site_name" value="ชื่อไซต์" required />
                        <TextInput id="site_name" v-model="siteForm.site_name" type="text" />
                        <InputError :message="siteForm.errors.site_name" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="site_email" value="อีเมลไซต์" />
                        <TextInput id="site_email" v-model="siteForm.site_email" type="email" />
                        <InputError :message="siteForm.errors.site_email" />
                    </div>

                    <div class="sm:col-span-6">
                        <InputLabel for="site_description" value="รายละเอียด" />
                        <Textarea id="site_description" v-model="siteForm.site_description" rows="3" />
                        <InputError :message="siteForm.errors.site_description" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel value="รูปโลโก้ (.png)" />
                        <FilePickerField v-model="logoFile" :accept="['png']" />
                        <InputError :message="siteForm.errors.logo_id" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel value="Favicon (.ico)" />
                        <FilePickerField v-model="faviconFile" :accept="['ico']" />
                        <InputError :message="siteForm.errors.favicon_id" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="copyright_year" value="ปีเริ่มต้นลิขสิทธิ์" />
                        <TextInput id="copyright_year" v-model="siteForm.copyright_year" type="text" inputmode="numeric" />
                        <InputError :message="siteForm.errors.copyright_year" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="copyright_owner" value="ชื่อเจ้าของลิขสิทธิ์" />
                        <TextInput id="copyright_owner" v-model="siteForm.copyright_owner" type="text" />
                        <InputError :message="siteForm.errors.copyright_owner" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel value="ภาษาในระบบ" required />
                        <div class="mt-1 flex flex-wrap gap-4">
                            <label
                                v-for="lang in LANGUAGE_OPTIONS"
                                :key="lang.code"
                                class="flex cursor-pointer items-center gap-2 text-sm text-gray-700"
                            >
                                <Checkbox
                                    :checked="siteForm.lang_selected.includes(lang.code)"
                                    @update:checked="toggleLang(lang.code)"
                                />
                                <span>{{ lang.label }} ({{ lang.code }})</span>
                            </label>
                        </div>
                        <InputError :message="siteForm.errors.lang_selected" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="lang_default" value="ภาษาหลัก" required />
                        <SearchableSelect id="lang_default" v-model="siteForm.lang_default" :options="availableDefaultLanguageOptions" />
                        <InputError :message="siteForm.errors.lang_default" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="timezone" value="โซนเวลา" />
                        <SearchableSelect id="timezone" v-model="siteForm.timezone" :options="timezoneSelectOptions" />
                        <p class="mt-1 text-xs text-gray-500">ใช้กำหนดโซนเวลาของระบบ</p>
                        <InputError :message="siteForm.errors.timezone" />
                    </div>
                </div>

                <div class="mt-6">
                    <PrimaryButton type="submit" :disabled="siteForm.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                </div>
            </form>

            <!-- Social Media -->
            <form
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
                @submit.prevent="submitSocial"
            >
                <h2 class="text-base font-semibold text-gray-800">Social Media</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div v-for="field in SOCIAL_FIELDS" :key="field.key" class="sm:col-span-3">
                        <InputLabel :for="`social_${field.key}`">
                            <span class="inline-flex items-center gap-1.5">
                                <SocialIcon :platform="field.key" class="size-4 shrink-0 text-gray-500" />
                                {{ field.label }}
                            </span>
                        </InputLabel>
                        <TextInput :id="`social_${field.key}`" v-model="socialForm[field.key]" type="text" />
                        <InputError :message="socialForm.errors[field.key]" />
                    </div>
                </div>

                <div class="mt-6">
                    <PrimaryButton type="submit" :disabled="socialForm.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                </div>
            </form>

            <!-- SMTP -->
            <form
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
                @submit.prevent="submitSmtp"
            >
                <h2 class="text-base font-semibold text-gray-800">SMTP</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <InputLabel for="smtp_host" value="Host" required />
                        <TextInput id="smtp_host" v-model="smtpForm.host" type="text" />
                        <InputError :message="smtpForm.errors.host" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="smtp_port" value="Port" required />
                        <TextInput id="smtp_port" v-model="smtpForm.port" type="text" inputmode="numeric" />
                        <InputError :message="smtpForm.errors.port" />
                    </div>

                    <div class="sm:col-span-6">
                        <InputLabel value="มีการ Auth" required />
                        <RadioGroup
                            v-model="smtpForm.use_auth"
                            name="smtp_use_auth"
                            :options="[
                                { label: 'มี', value: 'Y' },
                                { label: 'ไม่มี', value: 'N' },
                            ]"
                        />
                        <InputError :message="smtpForm.errors.use_auth" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="smtp_username" value="Username" :required="smtpForm.use_auth === 'Y'" />
                        <TextInput
                            id="smtp_username"
                            v-model="smtpForm.username"
                            type="text"
                            :disabled="smtpForm.use_auth !== 'Y'"
                        />
                        <InputError :message="smtpForm.errors.username" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="smtp_password" value="Password" :required="smtpForm.use_auth === 'Y'" />
                        <TextInput
                            id="smtp_password"
                            v-model="smtpForm.password"
                            type="password"
                            autocomplete="new-password"
                            :disabled="smtpForm.use_auth !== 'Y'"
                        />
                        <InputError :message="smtpForm.errors.password" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="smtp_ssl_type" value="ประเภท SSL" required />
                        <SearchableSelect
                            id="smtp_ssl_type"
                            v-model="smtpForm.ssl_type"
                            :options="[
                                { value: 'none', label: 'ไม่มี' },
                                { value: 'ssl', label: 'SSL' },
                                { value: 'tls', label: 'TLS' },
                            ]"
                        />
                        <InputError :message="smtpForm.errors.ssl_type" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="smtp_from_name" value="ชื่อผู้ส่ง" />
                        <TextInput id="smtp_from_name" v-model="smtpForm.from_name" type="text" />
                        <InputError :message="smtpForm.errors.from_name" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="smtp_from_email" value="ส่งจากอีเมล" />
                        <TextInput id="smtp_from_email" v-model="smtpForm.from_email" type="email" />
                        <InputError :message="smtpForm.errors.from_email" />
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <PrimaryButton type="submit" :disabled="smtpForm.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                    <SecondaryButton
                        type="button"
                        :disabled="!canTestSmtp"
                        :title="canTestSmtp ? '' : 'กรุณาบันทึกการตั้งค่า SMTP ก่อน'"
                        @click="showTestSmtpDialog = true"
                    >
                        <Send class="mr-1.5 size-4" /> ทดสอบส่งอีเมล
                    </SecondaryButton>
                </div>
            </form>

            <TestSmtpDialog :show="showTestSmtpDialog" @close="showTestSmtpDialog = false" />

            <!-- Turnstile CAPTCHA -->
            <form
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
                @submit.prevent="submitTurnstile"
            >
                <h2 class="text-base font-semibold text-gray-800">Turnstile CAPTCHA</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <InputLabel for="turnstile_site_key" value="Site Key" />
                        <TextInput id="turnstile_site_key" v-model="turnstileForm.site_key" type="text" />
                        <InputError :message="turnstileForm.errors.site_key" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="turnstile_key_secret" value="Key Secret" />
                        <TextInput id="turnstile_key_secret" v-model="turnstileForm.key_secret" type="text" />
                        <InputError :message="turnstileForm.errors.key_secret" />
                    </div>
                </div>

                <div class="mt-6">
                    <PrimaryButton type="submit" :disabled="turnstileForm.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                </div>
            </form>

            <!-- การเข้าสู่ระบบหลังบ้าน -->
            <form
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
                @submit.prevent="submitLoginBack"
            >
                <h2 class="text-base font-semibold text-gray-800">การเข้าสู่ระบบหลังบ้าน</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-6">
                        <InputLabel value="เปิดใช้งาน Turnstile CAPTCHA" required />
                        <RadioGroup
                            v-model="loginBackForm.captcha_enabled"
                            name="login_back_captcha_enabled"
                            :options="[
                                { label: 'ใช่', value: 'Y' },
                                { label: 'ไม่', value: 'N' },
                            ]"
                        />
                        <InputError :message="loginBackForm.errors.captcha_enabled" />
                    </div>

                    <div class="sm:col-span-6">
                        <InputLabel value="กำหนดจำนวนครั้งที่ผิดพลาด" required />
                        <RadioGroup
                            v-model="loginBackForm.lockout_enabled"
                            name="login_back_lockout_enabled"
                            :options="[
                                { label: 'ใช่', value: 'Y' },
                                { label: 'ไม่', value: 'N' },
                            ]"
                        />
                        <InputError :message="loginBackForm.errors.lockout_enabled" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel
                            for="login_back_lockout_count"
                            value="จำนวนครั้งที่ผิดพลาด"
                            :required="loginBackForm.lockout_enabled === 'Y'"
                        />
                        <TextInput
                            id="login_back_lockout_count"
                            v-model="loginBackForm.lockout_count"
                            type="number"
                            min="1"
                            :disabled="loginBackForm.lockout_enabled !== 'Y'"
                        />
                        <InputError :message="loginBackForm.errors.lockout_count" />
                    </div>
                </div>

                <div class="mt-6">
                    <PrimaryButton type="submit" :disabled="loginBackForm.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
