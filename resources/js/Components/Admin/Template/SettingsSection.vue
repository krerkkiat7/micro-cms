<script setup lang="ts">
/** หัวข้อย่อยใน dialog ตั้งค่าโซนของ template (เส้นคั่นด้านบน ยกเว้นหัวข้อแรก) */
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <section class="space-y-4 border-t border-gray-100 pt-5 first:border-t-0 first:pt-0">
        <div>
            <h3 class="text-sm font-semibold text-gray-800">{{ title }}</h3>
            <p v-if="description" class="mt-0.5 text-xs text-gray-500">{{ description }}</p>
        </div>
        <slot />
    </section>
</template>
