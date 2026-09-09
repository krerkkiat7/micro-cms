<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import { CheckCircle2 } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        show?: boolean;
        message?: string;
        duration?: number;
    }>(),
    {
        show: false,
        message: '',
        duration: 5,
    },
);

const emit = defineEmits<{
    close: [];
}>();

const remaining = ref(props.duration);
let timer: ReturnType<typeof setInterval> | null = null;

function stop() {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
}

function start() {
    stop();
    remaining.value = props.duration;
    timer = setInterval(() => {
        remaining.value -= 1;
        if (remaining.value <= 0) {
            stop();
            emit('close');
        }
    }, 1000);
}

watch(
    () => props.show,
    (value) => {
        if (value) {
            start();
        } else {
            stop();
        }
    },
    { immediate: true },
);

onBeforeUnmount(stop);
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
                    @click="emit('close')"
                />

                <div
                    class="relative w-full max-w-sm rounded-lg bg-white p-6 text-center shadow-xl"
                >
                    <div
                        class="mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-100"
                    >
                        <CheckCircle2 class="size-7 text-emerald-600" />
                    </div>

                    <h2 class="mt-4 text-base font-semibold text-gray-800">
                        {{ message || 'ดำเนินการเรียบร้อยแล้ว' }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        หน้าต่างนี้จะปิดอัตโนมัติใน {{ remaining }} วินาที
                    </p>

                    <button
                        type="button"
                        class="mt-4 text-sm font-medium text-brand-600 hover:text-brand-700"
                        @click="emit('close')"
                    >
                        ปิดตอนนี้
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
