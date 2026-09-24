<script setup lang="ts">
import { computed } from 'vue';
import FrontLink from '@/Components/Front/FrontLink.vue';
import FrontLayout from '@/Layouts/Front/FrontLayout.vue';
import { useFront } from '@/composables/useFront';
import type { SeoData } from '@/utils/front';

/**
 * หน้า error ของหน้าบ้าน (404 / 403 / 419 / 429 / 500 / 503) ใน layout หน้าบ้าน — App\Support\Front\FrontErrorPage
 */
const props = defineProps<{
    status: number;
    seo: SeoData;
}>();

const { front, t } = useFront();

const title = computed(() => t(`error_title.${props.status}`));
const description = computed(() => t(`error_description.${props.status}`));
</script>

<template>
    <FrontLayout :seo="seo">
        <div class="mx-auto flex max-w-xl flex-col items-center gap-4 py-16 text-center">
            <p class="text-6xl font-bold text-brand-700" aria-hidden="true">{{ status }}</p>
            <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
            <p class="text-gray-700">{{ description }}</p>
            <FrontLink :href="front.homeUrl" class="mt-2 inline-flex items-center rounded-md bg-brand-700 px-5 py-2.5 font-medium text-white hover:bg-brand-800">
                {{ t('back_to_home') }}
            </FrontLink>
        </div>
    </FrontLayout>
</template>
