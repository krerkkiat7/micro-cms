<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SelectInput from '@/Components/SelectInput.vue';
import RadioGroup from '@/Components/RadioGroup.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Props {
    settings: {
        site: Record<string, string>;
        smtp: Record<string, string>;
        recaptcha: Record<string, string>;
        login_back: Record<string, string>;
    };
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
const siteForm = useForm({
    site_name: props.settings.site?.site_name ?? '',
    site_email: props.settings.site?.site_email ?? '',
    site_description: props.settings.site?.site_description ?? '',
    copyright_year: props.settings.site?.copyright_year ?? '',
    copyright_owner: props.settings.site?.copyright_owner ?? '',
});

function submitSite() {
    siteForm.put(route('admin.system.setting.update.site'), { preserveScroll: true });
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

// ---------- กลุ่ม "reCAPTCHA" ----------
const recaptchaForm = useForm({
    site_key: props.settings.recaptcha?.site_key ?? '',
    key_secret: props.settings.recaptcha?.key_secret ?? '',
});

function submitRecaptcha() {
    recaptchaForm.put(route('admin.system.setting.update.recaptcha'), { preserveScroll: true });
}

// ---------- กลุ่ม "การเข้าสู่ระบบหลังบ้าน" ----------
const loginBackForm = useForm({
    recaptcha_enabled: props.settings.login_back?.recaptcha_enabled ?? 'N',
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
                        <InputLabel for="copyright_year" value="ปีเริ่มต้นลิขสิทธิ์" />
                        <TextInput id="copyright_year" v-model="siteForm.copyright_year" type="text" inputmode="numeric" />
                        <InputError :message="siteForm.errors.copyright_year" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="copyright_owner" value="ชื่อเจ้าของลิขสิทธิ์" />
                        <TextInput id="copyright_owner" v-model="siteForm.copyright_owner" type="text" />
                        <InputError :message="siteForm.errors.copyright_owner" />
                    </div>
                </div>

                <div class="mt-6">
                    <PrimaryButton type="submit" :disabled="siteForm.processing">
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
                        <SelectInput id="smtp_ssl_type" v-model="smtpForm.ssl_type">
                            <option value="none">ไม่มี</option>
                            <option value="ssl">SSL</option>
                            <option value="tls">TLS</option>
                        </SelectInput>
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

                <div class="mt-6">
                    <PrimaryButton type="submit" :disabled="smtpForm.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                </div>
            </form>

            <!-- reCAPTCHA -->
            <form
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
                @submit.prevent="submitRecaptcha"
            >
                <h2 class="text-base font-semibold text-gray-800">reCAPTCHA</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <InputLabel for="recaptcha_site_key" value="Site Key" />
                        <TextInput id="recaptcha_site_key" v-model="recaptchaForm.site_key" type="text" />
                        <InputError :message="recaptchaForm.errors.site_key" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="recaptcha_key_secret" value="Key Secret" />
                        <TextInput id="recaptcha_key_secret" v-model="recaptchaForm.key_secret" type="text" />
                        <InputError :message="recaptchaForm.errors.key_secret" />
                    </div>
                </div>

                <div class="mt-6">
                    <PrimaryButton type="submit" :disabled="recaptchaForm.processing">
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
                        <InputLabel value="เปิดใช้งาน reCAPTCHA" required />
                        <RadioGroup
                            v-model="loginBackForm.recaptcha_enabled"
                            name="login_back_recaptcha_enabled"
                            :options="[
                                { label: 'ใช่', value: 'Y' },
                                { label: 'ไม่', value: 'N' },
                            ]"
                        />
                        <InputError :message="loginBackForm.errors.recaptcha_enabled" />
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
