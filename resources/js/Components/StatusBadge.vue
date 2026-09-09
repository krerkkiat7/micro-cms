<script setup lang="ts">
const props = defineProps<{
    status: string;
}>();
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="
            props.status === 'Y'
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-gray-100 text-gray-600'
        "
    >
        <span
            class="size-1.5 rounded-full"
            :class="props.status === 'Y' ? 'bg-emerald-500' : 'bg-gray-400'"
        />
        {{ props.status === 'Y' ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
    </span>
</template>
