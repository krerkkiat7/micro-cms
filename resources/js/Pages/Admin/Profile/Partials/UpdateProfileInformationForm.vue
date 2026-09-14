<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import type { FileItem } from '@/types';

const props = defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
    profileImage: FileItem | null;
}>();

const user = usePage().props.auth.user;

const form = useForm({
    titlename: user.titlename ?? '',
    firstname: user.firstname,
    lastname: user.lastname,
    mobile: user.mobile ?? '',
    phone: user.phone ?? '',
    line: user.line ?? '',
    facebook: user.facebook ?? '',
    email: user.email,
    profile_image_id: props.profileImage?.id ?? null,
});

// FilePickerField ทำงานกับ array ของไฟล์เสมอ (เลือกได้ 1 รูป) — เลือกใหม่ = แทนที่รูปเดิม
const profileImage = ref<FileItem[]>(props.profileImage ? [props.profileImage] : []);
watch(profileImage, (files) => {
    form.profile_image_id = files[0]?.id ?? null;
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-base font-semibold text-gray-800">ข้อมูลโปรไฟล์</h2>

            <p class="mt-1 text-sm text-gray-500">
                แก้ไขชื่อ ช่องทางติดต่อ และอีเมลของบัญชี
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('admin.profile.update'))"
            class="mt-6 space-y-6"
        >
            <div class="grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <InputLabel for="titlename" value="คำนำหน้า" required />

                    <TextInput
                        id="titlename"
                        type="text"
                        v-model="form.titlename"
                        autocomplete="honorific-prefix"
                    />

                    <InputError :message="form.errors.titlename" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel for="firstname" value="ชื่อ" required />

                    <TextInput
                        id="firstname"
                        type="text"
                        v-model="form.firstname"
                        autocomplete="given-name"
                    />

                    <InputError :message="form.errors.firstname" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel for="lastname" value="นามสกุล" required />

                    <TextInput
                        id="lastname"
                        type="text"
                        v-model="form.lastname"
                        autocomplete="family-name"
                    />

                    <InputError :message="form.errors.lastname" />
                </div>

                <div class="sm:col-span-3">
                    <InputLabel for="mobile" value="เบอร์มือถือ" />

                    <TextInput
                        id="mobile"
                        type="text"
                        v-model="form.mobile"
                        autocomplete="tel"
                    />

                    <InputError :message="form.errors.mobile" />
                </div>

                <div class="sm:col-span-3">
                    <InputLabel for="phone" value="เบอร์ติดต่อ" />

                    <TextInput
                        id="phone"
                        type="text"
                        v-model="form.phone"
                    />

                    <InputError :message="form.errors.phone" />
                </div>

                <div class="sm:col-span-3">
                    <InputLabel for="line" value="LINE" />

                    <TextInput
                        id="line"
                        type="text"
                        v-model="form.line"
                    />

                    <InputError :message="form.errors.line" />
                </div>

                <div class="sm:col-span-3">
                    <InputLabel for="facebook" value="Facebook" />

                    <TextInput
                        id="facebook"
                        type="text"
                        v-model="form.facebook"
                    />

                    <InputError :message="form.errors.facebook" />
                </div>

                <div class="sm:col-span-3">
                    <InputLabel for="email" value="อีเมล" required />

                    <TextInput
                        id="email"
                        type="email"
                        v-model="form.email"
                        autocomplete="username"
                    />

                    <InputError :message="form.errors.email" />
                </div>

                <div class="sm:col-span-6">
                    <InputLabel value="รูปโปรไฟล์" />
                    <FilePickerField v-model="profileImage" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                    <InputError :message="form.errors.profile_image_id" />
                </div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm text-gray-800">
                    อีเมลของคุณยังไม่ได้ยืนยัน
                    <Link
                        :href="route('admin.verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-brand-600 underline hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                    >
                        กดที่นี่เพื่อส่งอีเมลยืนยันอีกครั้ง
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-emerald-600"
                >
                    ส่งลิงก์ยืนยันใหม่ไปที่อีเมลของคุณแล้ว
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
            </div>
        </form>
    </section>
</template>
