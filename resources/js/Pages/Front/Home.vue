<script setup>
import FrontLayout from '@/Layouts/FrontLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
  currentLang: String,
  title: String,
});
</script>

<template>
  <FrontLayout>

    <template #header>
        <h2
            class="text-xl font-semibold leading-tight text-gray-800"
        >
            {{ title }}
        </h2>
    </template>

    <div class="max-w-7xl mx-auto p-6">
      <h1 class="text-3xl font-bold">{{ title }}</h1>
      <p class="mt-2">Current Language: {{ currentLang.toUpperCase() }}</p>

      <!-- ปุ่มสลับภาษา -->
      <div class="mt-4 space-x-2">
        <Link href="/th" class="px-3 py-1 bg-blue-500 text-white rounded">TH</Link>
        <Link href="/en" class="px-3 py-1 bg-gray-500 text-white rounded">EN</Link>
      </div>
    </div>
  </FrontLayout>
</template>