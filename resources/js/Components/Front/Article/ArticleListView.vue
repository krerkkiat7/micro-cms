<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { LayoutGrid, List, Search, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ArticleListItem from '@/Components/Front/Article/ArticleListItem.vue';
import type { ArticleListData } from '@/Components/Front/Article/ArticleListItem.vue';
import FrontPagination from '@/Components/Front/FrontPagination.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useFront } from '@/composables/useFront';
import { formatNumber } from '@/utils/front';
import { ARTICLE_SORTS } from '@/utils/articleSetting';
import type { ArticleListSetting, ArticleSort } from '@/utils/articleSetting';

/**
 * รายการบทความหน้าบ้าน ใช้ร่วมกันระหว่างหน้าหมวดหมู่และหน้าแท็ก:
 * แถวเครื่องมือเดียว (ช่องค้นหา [เฉพาะเมื่อ searchable] + เรียงลำดับ + สลับการ์ด/แถว) → รายการ → แถวล่าง (จำนวนทั้งหมด/หน้า + แบ่งหน้า)
 * สถานะทั้งหมดอยู่ใน query string (?q= ?sort= ?view= ?page=) — ค่าเท่ากับค่าเริ่มต้นของตั้งค่าไม่ใส่ใน URL
 */
const props = withDefaults(
    defineProps<{
        articles: { data: ArticleListData[]; current_page: number; last_page: number; per_page: number; total: number };
        listSetting: ArticleListSetting;
        view: 'card' | 'row';
        sort: ArticleSort;
        baseUrl: string;
        q?: string;
        searchable?: boolean;
        emptyText: string;
    }>(),
    { q: '', searchable: false },
);

const { front, t } = useFront();

function urlFor(page: number, change: { view?: string; sort?: string; q?: string } = {}): string {
    const view = change.view ?? props.view;
    const sort = change.sort ?? props.sort;
    const q = (change.q ?? props.q).trim();
    const params = new URLSearchParams();

    if (props.searchable && q !== '') params.set('q', q);
    if (sort !== props.listSetting.default_sort) params.set('sort', sort);
    if (view !== props.listSetting.display_mode) params.set('view', view);
    if (page > 1) params.set('page', String(page));

    const query = params.toString();

    return query ? `${props.baseUrl}?${query}` : props.baseUrl;
}

function visit(url: string): void {
    router.get(url, {}, { preserveScroll: true, preserveState: true });
}

function setView(view: 'card' | 'row'): void {
    if (view !== props.view) visit(urlFor(props.articles.current_page, { view }));
}

// เปลี่ยนการเรียงลำดับ = กลับไปหน้าแรก
const sortModel = ref<string>(props.sort);
watch(
    () => props.sort,
    (value) => (sortModel.value = value),
);
watch(sortModel, (value) => {
    if (value !== props.sort) visit(urlFor(1, { sort: value }));
});

const sortOptions = computed(() => ARTICLE_SORTS.map((value) => ({ value, label: t(`sort_options.${value}`) })));

const search = ref(props.q);
watch(
    () => props.q,
    (value) => (search.value = value),
);

function submitSearch(): void {
    visit(urlFor(1, { q: search.value }));
}

function clearSearch(): void {
    search.value = '';
    visit(urlFor(1, { q: '' }));
}

const views = [
    { value: 'card' as const, icon: LayoutGrid, key: 'view_card' },
    { value: 'row' as const, icon: List, key: 'view_row' },
];
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-3 border-y border-gray-200 py-3">
            <form v-if="searchable" role="search" class="flex min-w-60 flex-1 items-center gap-2" @submit.prevent="submitSearch">
                <label for="article-search" class="sr-only">{{ t('search_articles') }}</label>
                <div class="relative flex-1">
                    <input
                        id="article-search"
                        v-model="search"
                        type="search"
                        maxlength="100"
                        :placeholder="t('search_articles')"
                        class="w-full rounded-md border border-gray-300 py-2 pl-3 pr-9 text-sm text-gray-900 focus:border-brand-600 focus:ring-brand-600"
                    />
                    <button
                        v-if="q"
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400 hover:text-gray-700"
                        :aria-label="t('clear_search')"
                        @click="clearSearch"
                    >
                        <X class="size-4" aria-hidden="true" />
                    </button>
                </div>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-brand-700 px-3 py-2 text-sm font-medium text-white hover:bg-brand-800">
                    <Search class="size-4" aria-hidden="true" />
                    {{ t('search') }}
                </button>
            </form>

            <div class="w-full sm:w-56">
                <label for="article-sort" class="sr-only">{{ t('sort') }}</label>
                <SearchableSelect id="article-sort" v-model="sortModel" :options="sortOptions" />
            </div>

            <div role="group" :aria-label="t('view_mode')" class="ml-auto inline-flex overflow-hidden rounded-md border border-gray-300">
                <button
                    v-for="option in views"
                    :key="option.value"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm"
                    :class="view === option.value ? 'bg-brand-700 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                    :aria-pressed="view === option.value"
                    @click="setView(option.value)"
                >
                    <component :is="option.icon" class="size-4" aria-hidden="true" />
                    {{ t(option.key) }}
                </button>
            </div>
        </div>

        <p v-if="articles.data.length === 0" class="rounded-lg border border-dashed border-gray-300 py-12 text-center text-gray-600">{{ emptyText }}</p>

        <ul v-else :class="view === 'card' ? 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3' : 'space-y-4'">
            <li v-for="article in articles.data" :key="article.id">
                <ArticleListItem
                    :article="article"
                    :view="view"
                    :setting="listSetting[view]"
                    :show-date="listSetting.show_date"
                    :show-views="listSetting.show_views"
                />
            </li>
        </ul>

        <div v-if="articles.total > 0" class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4">
            <p class="text-sm text-gray-600" aria-live="polite">
                {{ t('total_items', { count: formatNumber(articles.total, front.lang) }) }}
                <span aria-hidden="true">·</span>
                {{ t('page_of', { current: articles.current_page, total: Math.max(1, articles.last_page) }) }}
            </p>
            <FrontPagination :current-page="articles.current_page" :last-page="articles.last_page" :url-for="(page) => urlFor(page)" />
        </div>
    </div>
</template>
