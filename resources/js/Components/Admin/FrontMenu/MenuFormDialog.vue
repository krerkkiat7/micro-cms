<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import LayoutDialog from '@/Components/Admin/PageLayout/LayoutDialog.vue';
import PositionPicker from '@/Components/Admin/IntropageBackground/PositionPicker.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import ArticleItemPickerDialog from './ArticleItemPickerDialog.vue';
import PageItemPickerDialog from './PageItemPickerDialog.vue';
import { STATUS_OPTIONS, LINK_TARGET_OPTIONS } from '@/utils/options';
import { SHOW_OPTIONS, CONTAINER_OPTIONS, FONT_SIZE_OPTIONS } from '@/utils/pageLayout';
import { YES_NO_OPTIONS, FrontMenuType, menuTypeOptions, parentMenuOptions, emptyMenuDetails, defaultMenuName } from '@/utils/frontMenu';
import type { FileItem, FrontMenuNode, LanguageOption } from '@/types';

const props = defineProps<{
    show: boolean;
    menu: FrontMenuNode | null;
    tree: FrontMenuNode[];
    languages: LanguageOption[];
    menuTypes: Record<string, string>;
    articleCategories: { value: string; label: string }[];
    fonts: string[];
}>();

const emit = defineEmits<{
    close: [];
}>();

const form = useForm({
    parent_id: null as number | null,
    menu_type: FrontMenuType.NONE as string,
    target_article_category_id: null as number | null,
    target_article_item_id: null as number | null,
    target_page_item_id: null as number | null,
    url: '',
    link_target: '_self',
    is_home: 'N',
    show_header_image: 'N',
    header_image_id: null as number | null,
    show_title: 'Y',
    title_font_size: 28,
    title_font_family: 'Sarabun',
    title_color: '#000000',
    title_bold: 'N',
    show_subtitle: 'Y',
    subtitle_font_size: 16,
    subtitle_font_family: 'Sarabun',
    subtitle_color: '#000000',
    subtitle_bold: 'N',
    header_content_align: 'center',
    use_container: 'Y',
    show_breadcrumb: 'Y',
    status: 'Y',
    detail: emptyMenuDetails(props.languages),
});

const headerImage = ref<FileItem[]>([]);
const selectedArticleLabel = ref<string>('');
const selectedPageLabel = ref<string>('');
const showArticlePicker = ref(false);
const showPagePicker = ref(false);

watch(
    () => props.show,
    (show) => {
        if (!show) return;

        form.clearErrors();
        headerImage.value = [];
        selectedArticleLabel.value = '';
        selectedPageLabel.value = '';

        const menu = props.menu;

        form.parent_id = menu?.parent_id ?? null;
        form.menu_type = menu?.menu_type ?? FrontMenuType.NONE;
        form.target_article_category_id = menu?.target_article_category_id ?? null;
        form.target_article_item_id = menu?.target_article_item_id ?? null;
        form.target_page_item_id = menu?.target_page_item_id ?? null;
        form.url = menu?.url ?? '';
        form.link_target = menu?.link_target ?? '_self';
        form.is_home = menu?.is_home ?? 'N';
        form.show_header_image = menu?.show_header_image ?? 'N';
        form.header_image_id = menu?.header_image_id ?? null;
        form.show_title = menu?.show_title ?? 'Y';
        form.title_font_size = menu?.title_font_size ?? 28;
        form.title_font_family = menu?.title_font_family ?? 'Sarabun';
        form.title_color = menu?.title_color ?? '#000000';
        form.title_bold = menu?.title_bold ?? 'N';
        form.show_subtitle = menu?.show_subtitle ?? 'Y';
        form.subtitle_font_size = menu?.subtitle_font_size ?? 16;
        form.subtitle_font_family = menu?.subtitle_font_family ?? 'Sarabun';
        form.subtitle_color = menu?.subtitle_color ?? '#000000';
        form.subtitle_bold = menu?.subtitle_bold ?? 'N';
        form.header_content_align = menu?.header_content_align ?? 'center';
        form.use_container = menu?.use_container ?? 'Y';
        form.show_breadcrumb = menu?.show_breadcrumb ?? 'Y';
        form.status = menu?.status ?? 'Y';

        const detail = emptyMenuDetails(props.languages);
        if (menu) {
            for (const lang of Object.keys(detail)) {
                const existing = menu.detail[lang];
                if (existing) {
                    detail[lang] = { name: existing.name ?? '', title: existing.title ?? '', subtitle: existing.subtitle ?? '' };
                }
            }
            if (menu.menu_type === FrontMenuType.ARTICLE_ITEM) selectedArticleLabel.value = menu.target_label ?? '';
            if (menu.menu_type === FrontMenuType.PAGE) selectedPageLabel.value = menu.target_label ?? '';
        }
        form.detail = detail;
    },
);

watch(headerImage, (files) => {
    form.header_image_id = files[0]?.id ?? null;
});

const parentOptions = computed(() => [{ value: '', label: 'ไม่มี (เมนูระดับบนสุด)' }, ...parentMenuOptions(props.tree, props.menu?.id ?? null)]);

const parentIdSelect = computed({
    get: () => (form.parent_id === null ? '' : String(form.parent_id)),
    set: (value: string) => {
        form.parent_id = value === '' ? null : Number(value);
    },
});

const categorySelect = computed({
    get: () => (form.target_article_category_id === null ? '' : String(form.target_article_category_id)),
    set: (value: string) => {
        form.target_article_category_id = value === '' ? null : Number(value);
    },
});

const typeOptions = computed(() => menuTypeOptions(props.menuTypes));

const fontOptions = computed(() => props.fonts.map((font) => ({ value: font, label: font })));

const sizeOptions = (current: number) => {
    const value = String(current);

    return FONT_SIZE_OPTIONS.some((o) => o.value === value) ? FONT_SIZE_OPTIONS : [...FONT_SIZE_OPTIONS, { value, label: `${value} px` }];
};

const titleFontSize = computed({
    get: () => String(form.title_font_size),
    set: (value: string) => (form.title_font_size = Number(value)),
});

const subtitleFontSize = computed({
    get: () => String(form.subtitle_font_size),
    set: (value: string) => (form.subtitle_font_size = Number(value)),
});

function detailError(lang: string, field: string): string | undefined {
    return (form.errors as Record<string, string>)[`detail.${lang}.${field}`];
}

function pickArticle(article: { id: number; title: string }) {
    form.target_article_item_id = article.id;
    selectedArticleLabel.value = article.title;
    showArticlePicker.value = false;
}

function pickPage(page: { id: number; title: string }) {
    form.target_page_item_id = page.id;
    selectedPageLabel.value = page.title;
    showPagePicker.value = false;
}

function close() {
    emit('close');
}

function submit() {
    const options = { onSuccess: () => emit('close') };

    if (props.menu) {
        form.put(route('admin.system.menu.update', props.menu.id), options);
    } else {
        form.post(route('admin.system.menu.store'), options);
    }
}

const dialogTitle = computed(() => (props.menu ? `แก้ไขเมนู: ${defaultMenuName(props.menu)}` : 'เพิ่มเมนู'));
</script>

<template>
    <LayoutDialog
        :show="show"
        size="lg"
        :title="dialogTitle"
        confirm-text="บันทึก"
        :confirm-disabled="form.processing"
        @close="close"
        @confirm="submit"
    >
        <div class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="Parent Menu" />
                    <SearchableSelect v-model="parentIdSelect" :options="parentOptions" />
                    <InputError :message="form.errors.parent_id" />
                </div>
                <div>
                    <InputLabel value="ประเภทเมนู" required />
                    <SearchableSelect v-model="form.menu_type" :options="typeOptions" />
                    <InputError :message="form.errors.menu_type" />
                </div>
            </div>

            <div v-if="form.menu_type === 'external'">
                <InputLabel value="Url" required />
                <TextInput v-model="form.url" type="text" placeholder="https://" />
                <InputError :message="form.errors.url" />
            </div>

            <div v-if="form.menu_type === 'article_category'">
                <InputLabel value="หมวดหมู่บทความ" required />
                <SearchableSelect v-model="categorySelect" :options="articleCategories" />
                <InputError :message="form.errors.target_article_category_id" />
            </div>

            <div v-if="form.menu_type === 'article_item'">
                <InputLabel value="บทความ" required />
                <div class="flex items-center gap-3">
                    <SecondaryButton type="button" @click="showArticlePicker = true">เลือกบทความ</SecondaryButton>
                    <span v-if="selectedArticleLabel" class="text-sm text-gray-600">{{ selectedArticleLabel }}</span>
                </div>
                <InputError :message="form.errors.target_article_item_id" />
            </div>

            <div v-if="form.menu_type === 'page'">
                <InputLabel value="หน้าเพจ" required />
                <div class="flex items-center gap-3">
                    <SecondaryButton type="button" @click="showPagePicker = true">เลือกหน้าเพจ</SecondaryButton>
                    <span v-if="selectedPageLabel" class="text-sm text-gray-600">{{ selectedPageLabel }}</span>
                </div>
                <InputError :message="form.errors.target_page_item_id" />
            </div>

            <div v-if="form.menu_type !== FrontMenuType.NONE && form.menu_type !== FrontMenuType.HEADING" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="Link Target" />
                    <SearchableSelect v-model="form.link_target" :options="LINK_TARGET_OPTIONS" />
                    <InputError :message="form.errors.link_target" />
                </div>
                <div>
                    <InputLabel value="เป็นหน้าหลัก" />
                    <SearchableSelect v-model="form.is_home" :options="YES_NO_OPTIONS" />
                    <InputError :message="form.errors.is_home" />
                </div>
            </div>

            <LangFieldGroup label="ชื่อเมนู" :languages="languages" required>
                <template #default="{ lang }">
                    <TextInput v-model="form.detail[lang.code].name" type="text" />
                    <InputError :message="detailError(lang.code, 'name')" />
                </template>
            </LangFieldGroup>

            <div class="space-y-4 border-t border-gray-100 pt-5">
                <h3 class="text-sm font-medium text-gray-600">หัวเรื่องของหน้าเป้าหมาย</h3>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="แสดงรูปภาพส่วนหัว" />
                        <SearchableSelect v-model="form.show_header_image" :options="SHOW_OPTIONS" />
                    </div>
                    <div v-if="form.show_header_image === 'Y'">
                        <InputLabel value="ส่วนภาพส่วนหัว" />
                        <FilePickerField v-model="headerImage" :accept="['jpg', 'jpeg', 'png', 'webp']" />
                        <InputError :message="form.errors.header_image_id" />
                    </div>
                </div>

                <div>
                    <InputLabel value="แสดงหัวเรื่อง" />
                    <SearchableSelect v-model="form.show_title" :options="SHOW_OPTIONS" />
                </div>
                <template v-if="form.show_title === 'Y'">
                    <LangFieldGroup label="ข้อความหัวเรื่อง" :languages="languages">
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].title" type="text" />
                            <InputError :message="detailError(lang.code, 'title')" />
                        </template>
                    </LangFieldGroup>
                    <div class="grid gap-4 sm:grid-cols-4">
                        <div>
                            <InputLabel value="ขนาดฟอนต์" />
                            <SearchableSelect v-model="titleFontSize" :options="sizeOptions(form.title_font_size)" />
                        </div>
                        <div>
                            <InputLabel value="ฟอนต์" />
                            <SearchableSelect v-model="form.title_font_family" :options="fontOptions" />
                        </div>
                        <div>
                            <InputLabel value="สีตัวอักษร" />
                            <ColorPickerInput v-model="form.title_color" />
                        </div>
                        <div>
                            <InputLabel value="ตัวหนา" />
                            <SearchableSelect v-model="form.title_bold" :options="YES_NO_OPTIONS" />
                        </div>
                    </div>
                </template>

                <div>
                    <InputLabel value="แสดงหัวเรื่องรอง" />
                    <SearchableSelect v-model="form.show_subtitle" :options="SHOW_OPTIONS" />
                </div>
                <template v-if="form.show_subtitle === 'Y'">
                    <LangFieldGroup label="ข้อความหัวเรื่องรอง" :languages="languages">
                        <template #default="{ lang }">
                            <TextInput v-model="form.detail[lang.code].subtitle" type="text" />
                            <InputError :message="detailError(lang.code, 'subtitle')" />
                        </template>
                    </LangFieldGroup>
                    <div class="grid gap-4 sm:grid-cols-4">
                        <div>
                            <InputLabel value="ขนาดฟอนต์" />
                            <SearchableSelect v-model="subtitleFontSize" :options="sizeOptions(form.subtitle_font_size)" />
                        </div>
                        <div>
                            <InputLabel value="ฟอนต์" />
                            <SearchableSelect v-model="form.subtitle_font_family" :options="fontOptions" />
                        </div>
                        <div>
                            <InputLabel value="สีตัวอักษร" />
                            <ColorPickerInput v-model="form.subtitle_color" />
                        </div>
                        <div>
                            <InputLabel value="ตัวหนา" />
                            <SearchableSelect v-model="form.subtitle_bold" :options="YES_NO_OPTIONS" />
                        </div>
                    </div>
                </template>

                <div>
                    <InputLabel value="จัดตำแหน่ง" />
                    <PositionPicker v-model="form.header_content_align" />
                </div>
            </div>

            <div class="grid gap-4 border-t border-gray-100 pt-5 sm:grid-cols-3">
                <div>
                    <InputLabel value="พื้นที่ความกว้าง" />
                    <SearchableSelect v-model="form.use_container" :options="CONTAINER_OPTIONS" />
                </div>
                <div>
                    <InputLabel value="แสดง Breadcrumb" />
                    <SearchableSelect v-model="form.show_breadcrumb" :options="SHOW_OPTIONS" />
                </div>
                <div>
                    <InputLabel value="สถานะ" />
                    <SearchableSelect v-model="form.status" :options="STATUS_OPTIONS" />
                </div>
            </div>
        </div>
    </LayoutDialog>

    <ArticleItemPickerDialog :show="showArticlePicker" @close="showArticlePicker = false" @select="pickArticle" />
    <PageItemPickerDialog :show="showPagePicker" @close="showPagePicker = false" @select="pickPage" />
</template>
