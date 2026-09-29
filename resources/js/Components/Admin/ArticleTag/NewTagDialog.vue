<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { languageLabel } from '@/utils/languages';
import type { LanguageOption } from '@/types';

/**
 * dialog สร้างแท็กใหม่ — เปิดจาก TagPicker.vue ตอนพิมพ์ชื่อแท็กที่ยังไม่มีในระบบแล้วกด "เพิ่ม"
 * ต้องกรอกชื่อให้ครบทุกภาษาที่ระบบเปิดใช้ (เพื่อให้แท็กใหม่ใช้งานได้ในทุกภาษาตั้งแต่สร้าง)
 */
const props = defineProps<{
    show: boolean;
    languages: LanguageOption[];
    defaultName?: string;
}>();

const emit = defineEmits<{
    close: [];
    created: [tag: { id: number; name: string }];
}>();

const names = reactive<Record<string, string>>({});
const errors = reactive<Record<string, string>>({});
const processing = ref(false);

watch(
    () => props.show,
    (show) => {
        if (!show) {
            return;
        }

        props.languages.forEach((lang) => {
            names[lang.code] = lang.is_default ? (props.defaultName ?? '') : '';
        });
        Object.keys(errors).forEach((key) => delete errors[key]);
    },
);

async function save() {
    processing.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        const { data } = await axios.post<{ data: { id: number; name: string } }>(route('admin.article.tag.quickStore'), {
            name: names,
        });
        emit('created', data.data);
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
    } catch (e: any) {
        const serverErrors = e.response?.data?.errors as Record<string, string[]> | undefined;
        if (serverErrors) {
            Object.entries(serverErrors).forEach(([key, messages]) => {
                errors[key] = messages[0];
            });
        }
    } finally {
        processing.value = false;
    }
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.show && !processing.value) {
        emit('close');
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
                <div class="absolute inset-0 bg-gray-500/75" @click="!processing && emit('close')" />

                <div class="relative w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                    <h2 class="text-base font-semibold text-gray-800">แท็กใหม่</h2>
                    <p class="mt-1 text-sm text-gray-500">ยังไม่มีแท็กนี้ในระบบ กรุณากรอกชื่อแท็กให้ครบทุกภาษาเพื่อสร้างแท็กใหม่</p>

                    <div class="mt-4 space-y-3">
                        <div v-for="lang in languages" :key="lang.code">
                            <InputLabel :value="`ชื่อแท็ก (${languageLabel(lang.code)})`" required />
                            <TextInput v-model="names[lang.code]" type="text" />
                            <InputError :message="errors[`name.${lang.code}`]" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <PrimaryButton type="button" :disabled="processing" @click="save">บันทึก</PrimaryButton>
                        <SecondaryButton type="button" :disabled="processing" @click="emit('close')">ยกเลิก</SecondaryButton>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
