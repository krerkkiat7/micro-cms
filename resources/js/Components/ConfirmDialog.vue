<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = withDefaults(
    defineProps<{
        show?: boolean;
        title?: string;
        message?: string;
        confirmText?: string;
        cancelText?: string;
        processing?: boolean;
    }>(),
    {
        show: false,
        title: 'ยืนยันการทำรายการ',
        message: '',
        confirmText: 'ยืนยัน',
        cancelText: 'ยกเลิก',
        processing: false,
    },
);

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.show && !props.processing) {
        emit('cancel');
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
            <div
                v-if="show"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4"
            >
                <div
                    class="absolute inset-0 bg-gray-500/75"
                    @click="emit('cancel')"
                />

                <div
                    class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl"
                >
                    <h2 class="text-base font-semibold text-gray-800">
                        {{ title }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        <slot>{{ message }}</slot>
                    </p>

                    <div class="mt-6 flex justify-end gap-3">
                        <DangerButton
                            :disabled="processing"
                            :class="{ 'opacity-50': processing }"
                            @click="emit('confirm')"
                        >
                            {{ confirmText }}
                        </DangerButton>
                        <SecondaryButton
                            :disabled="processing"
                            @click="emit('cancel')"
                        >
                            {{ cancelText }}
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
