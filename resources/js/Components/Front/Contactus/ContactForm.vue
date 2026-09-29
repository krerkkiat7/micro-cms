<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { CheckCircle2, Send } from 'lucide-vue-next';
import { useFront } from '@/composables/useFront';
import type { ContactusFormField, FrontContactusForm } from '@/utils/frontContactus';

/**
 * แบบฟอร์มติดต่อ — ฟิลด์ตามตั้งค่า (fullname แสดง/บังคับเสมอ) + Cloudflare Turnstile + honeypot (`website`) + form_token (เวลาเปิดฟอร์ม)
 * ส่งแบบ Inertia (สำเร็จ = server redirect กลับมาพร้อม `sent` และ token ใหม่) ข้อความจากผู้ชมแสดงแบบ text เท่านั้น
 */
interface TurnstileApi {
    render: (
        container: HTMLElement,
        options: {
            sitekey: string;
            language?: string;
            callback?: (token: string) => void;
            'expired-callback'?: () => void;
            'error-callback'?: () => void;
        },
    ) => string;
    reset: (widgetId?: string) => void;
    remove: (widgetId: string) => void;
}

const props = defineProps<{
    form: FrontContactusForm;
    sent: boolean;
}>();

const { front, t } = useFront();

const ORDER: ContactusFormField[] = ['fullname', 'position', 'company', 'phone', 'email', 'subject', 'detail'];
const MAX: Record<ContactusFormField, number> = { fullname: 255, position: 255, company: 255, phone: 50, email: 255, subject: 255, detail: 5000 };

const fields = computed(() => ORDER.filter((field) => field in props.form.fields).map((field) => ({ field, required: !!props.form.fields[field] })));

const data = useForm({
    fullname: '',
    position: '',
    company: '',
    phone: '',
    email: '',
    subject: '',
    detail: '',
    website: '',
    form_token: props.form.token,
    'cf-turnstile-response': '',
});

const errors = computed(() => data.errors as Record<string, string | undefined>);
const turnstileContainer = ref<HTMLElement | null>(null);
let widgetId: string | null = null;

function turnstile(): TurnstileApi | undefined {
    return (window as unknown as { turnstile?: TurnstileApi }).turnstile;
}

function renderWidget() {
    const api = turnstile();

    if (!api || !turnstileContainer.value || widgetId !== null) {
        return;
    }

    widgetId = api.render(turnstileContainer.value, {
        sitekey: props.form.siteKey,
        language: front.value.lang,
        callback: (token) => {
            data['cf-turnstile-response'] = token;
        },
        'expired-callback': () => {
            data['cf-turnstile-response'] = '';
        },
        'error-callback': () => {
            data['cf-turnstile-response'] = '';
        },
    });
}

function resetWidget() {
    data['cf-turnstile-response'] = '';

    if (widgetId !== null) {
        turnstile()?.reset(widgetId);
    }
}

onMounted(() => {
    if (turnstile()) {
        renderWidget();
        return;
    }

    const id = 'cf-turnstile-script';

    if (!document.getElementById(id)) {
        const script = document.createElement('script');
        script.id = id;
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.onload = renderWidget;
        document.head.appendChild(script);
    } else {
        document.getElementById(id)!.addEventListener('load', renderWidget);
    }
});

onBeforeUnmount(() => {
    if (widgetId !== null) {
        turnstile()?.remove(widgetId);
        widgetId = null;
    }
});

// หลังส่งสำเร็จ server ส่ง token ใหม่มา — ใช้ต่อได้ทันทีถ้าจะส่งอีกครั้ง
watch(
    () => props.form.token,
    (token) => {
        data.form_token = token;
    },
);

const successBox = ref<HTMLElement | null>(null);

function submit() {
    data.post(props.form.action, {
        preserveScroll: true,
        onSuccess: () => {
            data.reset();
            data.form_token = props.form.token;
            resetWidget();
            nextTick(() => successBox.value?.focus());
        },
        onError: () => {
            resetWidget();
            nextTick(() => {
                const first = document.querySelector<HTMLElement>('[aria-invalid="true"]');
                first?.focus();
            });
        },
    });
}

function label(field: ContactusFormField): string {
    return t(`contactus_form.fields.${field}`);
}
</script>

<template>
    <section aria-labelledby="contactus-form-title">
        <h2 id="contactus-form-title" class="text-xl font-semibold text-gray-900">{{ t('contactus_form.title') }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ t('contactus_form.required_note') }}</p>

        <div
            v-if="sent"
            ref="successBox"
            tabindex="-1"
            role="status"
            class="mt-4 flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 focus:outline-none"
        >
            <CheckCircle2 class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
            {{ t('contactus_form.sent') }}
        </div>

        <p v-if="errors.form" role="alert" class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ errors.form }}</p>

        <form class="mt-5 grid gap-4 sm:grid-cols-2" novalidate @submit.prevent="submit">
            <!-- honeypot: ซ่อนจากผู้ใช้/screen reader — บอทที่กรอกทุกช่องจะถูกทิ้ง -->
            <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
                <label for="contactus_website">Website</label>
                <input id="contactus_website" v-model="data.website" type="text" name="website" tabindex="-1" autocomplete="off" />
            </div>

            <div v-for="item in fields" :key="item.field" :class="item.field === 'detail' || item.field === 'subject' ? 'sm:col-span-2' : ''">
                <label :for="`contactus_${item.field}`" class="mb-1 block text-sm font-medium text-gray-700">
                    {{ label(item.field) }}
                    <span v-if="item.required" class="text-red-600" aria-hidden="true">*</span>
                </label>
                <textarea
                    v-if="item.field === 'detail'"
                    :id="`contactus_${item.field}`"
                    v-model="data.detail"
                    rows="6"
                    :maxlength="MAX.detail"
                    :required="item.required"
                    :aria-required="item.required"
                    :aria-invalid="errors.detail ? 'true' : undefined"
                    :aria-describedby="errors.detail ? 'contactus_detail_error' : undefined"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-brand-500 focus:ring-brand-500"
                />
                <input
                    v-else
                    :id="`contactus_${item.field}`"
                    v-model="data[item.field]"
                    :type="item.field === 'email' ? 'email' : item.field === 'phone' ? 'tel' : 'text'"
                    :autocomplete="item.field === 'fullname' ? 'name' : item.field === 'email' ? 'email' : item.field === 'phone' ? 'tel' : item.field === 'company' ? 'organization' : item.field === 'position' ? 'organization-title' : 'off'"
                    :maxlength="MAX[item.field]"
                    :required="item.required"
                    :aria-required="item.required"
                    :aria-invalid="errors[item.field] ? 'true' : undefined"
                    :aria-describedby="errors[item.field] ? `contactus_${item.field}_error` : undefined"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-brand-500 focus:ring-brand-500"
                />
                <p v-if="errors[item.field]" :id="`contactus_${item.field}_error`" class="mt-1 text-sm text-red-600">{{ errors[item.field] }}</p>
            </div>

            <div class="sm:col-span-2">
                <div ref="turnstileContainer" />
                <p v-if="errors['cf-turnstile-response']" class="mt-1 text-sm text-red-600" role="alert">{{ errors['cf-turnstile-response'] }}</p>
            </div>

            <div class="sm:col-span-2">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 font-medium text-white transition-colors hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 disabled:opacity-60"
                    :disabled="data.processing"
                >
                    <Send class="size-4" aria-hidden="true" />
                    {{ data.processing ? t('contactus_form.sending') : t('contactus_form.submit') }}
                </button>
            </div>
        </form>
    </section>
</template>
