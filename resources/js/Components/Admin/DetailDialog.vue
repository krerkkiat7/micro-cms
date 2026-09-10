<script setup lang="ts">
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { X } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted } from 'vue';

const props = withDefaults(
    defineProps<{
        show?: boolean;
        title?: string;
    }>(),
    {
        show: false,
        title: 'รายละเอียด',
    },
);

const emit = defineEmits<{
    close: [];
}>();

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.show) emit('close');
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
                    @click="emit('close')"
                />

                <div
                    class="relative flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-white shadow-xl"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-200 px-5 py-3.5"
                    >
                        <h2 class="text-base font-semibold text-gray-800">
                            {{ title }}
                        </h2>
                        <button
                            type="button"
                            class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                            aria-label="ปิด"
                            @click="emit('close')"
                        >
                            <X class="size-5" />
                        </button>
                    </div>

                    <div class="overflow-y-auto px-5 py-4">
                        <slot />
                    </div>

                    <div class="border-t border-gray-200 px-5 py-3 text-right">
                        <SecondaryButton @click="emit('close')">
                            ปิด
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
