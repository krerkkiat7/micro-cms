<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p class="mt-1.5 text-sm text-red-600">
            {{ message }}
        </p>
    </div>
</template>
