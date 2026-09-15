<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Send, X } from 'lucide-vue-next';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

/**
 * dialog "ทดสอบส่งอีเมล" ของหน้าตั้งค่า SMTP — กรอกอีเมลปลายทาง/ชื่อเรื่อง/เนื้อหา แล้วส่งด้วยค่า SMTP
 * ที่ "บันทึกไว้แล้ว" ใน sys_setting (ไม่ใช่ค่าที่พิมพ์ค้างในฟอร์ม SMTP ที่ยังไม่กดบันทึก) —
 * ดู SettingController::testSmtp(). สำเร็จแล้วปิด dialog นี้ทันที ปล่อยให้ SuccessDialog กลาง
 * (ผูกกับ flash.success ใน AdminLayout.vue) ขึ้นแจ้งผลแทน
 */
const props = withDefaults(defineProps<{ show?: boolean }>(), { show: false });

const emit = defineEmits<{ close: [] }>();

const form = useForm({
    to: '',
    subject: 'ทดสอบการส่งอีเมลจากระบบ',
    body: 'นี่คืออีเมลทดสอบการตั้งค่า SMTP ของระบบ',
});

// error ทั่วไปที่ไม่ผูกกับฟิลด์ไหนโดยเฉพาะ (เช่น ยังไม่บันทึก host / ส่งไม่สำเร็จจาก SMTP) — backend ส่งมาใน
// key 'send' ซึ่งไม่ใช่ฟิลด์ของฟอร์มนี้ จึงอ่านผ่าน error bag แบบ any แทน form.errors.send ตรง ๆ
const sendError = computed(() => (form.errors as Record<string, string>).send);

// เคลียร์ error ทุกครั้งที่เปิด dialog ใหม่ (ไม่ล้างค่าฟอร์ม — เผื่อแก้แล้วส่งซ้ำ)
watch(
    () => props.show,
    (show) => {
        if (show) {
            form.clearErrors();
        }
    },
);

function submit() {
    form.post(route('admin.system.setting.smtp.test'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
}

function close() {
    form.clearErrors();
    emit('close');
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.show && !form.processing) {
        close();
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-500/75" @click="close" />

                <div class="relative flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3.5">
                        <h2 class="text-base font-semibold text-gray-800">ทดสอบส่งอีเมล</h2>
                        <button
                            type="button"
                            class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                            aria-label="ปิด"
                            @click="close"
                        >
                            <X class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="space-y-4 overflow-y-auto px-5 py-4">
                            <InputError :message="sendError" />

                            <div>
                                <InputLabel for="test_smtp_to" value="อีเมลปลายทาง" required />
                                <TextInput id="test_smtp_to" v-model="form.to" type="email" placeholder="name@example.com" />
                                <InputError :message="form.errors.to" />
                            </div>

                            <div>
                                <InputLabel for="test_smtp_subject" value="ชื่อเรื่อง" required />
                                <TextInput id="test_smtp_subject" v-model="form.subject" type="text" />
                                <InputError :message="form.errors.subject" />
                            </div>

                            <div>
                                <InputLabel for="test_smtp_body" value="เนื้อหา" required />
                                <Textarea id="test_smtp_body" v-model="form.body" rows="4" />
                                <InputError :message="form.errors.body" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-5 py-3">
                            <SecondaryButton type="button" :disabled="form.processing" @click="close">
                                ยกเลิก
                            </SecondaryButton>
                            <PrimaryButton type="submit" :disabled="form.processing">
                                <Send class="mr-1.5 size-4" /> ส่ง
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
