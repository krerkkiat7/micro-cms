<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('admin.profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => {
            form.reset();
        },
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-base font-semibold text-red-600">ลบบัญชี</h2>

            <p class="mt-1 text-sm text-gray-500">
                เมื่อลบบัญชีแล้ว ข้อมูลทั้งหมดจะถูกลบอย่างถาวร
                กรุณาสำรองข้อมูลที่ต้องการเก็บไว้ก่อนดำเนินการ
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">ลบบัญชี</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-base font-semibold text-gray-800">
                    ยืนยันการลบบัญชีของคุณ?
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    เมื่อลบบัญชีแล้ว ข้อมูลทั้งหมดจะถูกลบอย่างถาวร
                    กรุณากรอกรหัสผ่านเพื่อยืนยัน
                </p>

                <div class="mt-6">
                    <InputLabel for="password" value="รหัสผ่าน" class="sr-only" />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="w-3/4"
                        placeholder="รหัสผ่าน"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">ยกเลิก</SecondaryButton>

                    <DangerButton
                        :class="{ 'opacity-50': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        ลบบัญชี
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
