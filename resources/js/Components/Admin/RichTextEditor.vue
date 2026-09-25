<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import TextAlign from '@tiptap/extension-text-align';
import {
    Bold as BoldIcon,
    Italic as ItalicIcon,
    Underline as UnderlineIcon,
    Strikethrough,
    Heading2,
    Heading3,
    List,
    ListOrdered,
    Quote,
    Link2,
    Undo2,
    Redo2,
    AlignLeft,
    AlignCenter,
    AlignRight,
    AlignJustify,
    ArrowUpDown,
    Check,
} from 'lucide-vue-next';
import { DEFAULT_LINE_HEIGHT, LINE_HEIGHTS, LineHeight } from '@/utils/tiptapLineHeight';

/**
 * text editor สำหรับเนื้อหาที่ไม่ต้องการให้แทรกรูปภาพ/วิดีโอ/media — ตั้งใจไม่ใส่ extension รูปภาพ/วิดีโอ
 * ใด ๆ เพื่อบังคับตามที่ออกแบบไว้ (แทรกสื่อผ่าน part ประเภทอื่นแทน — ดู docs/PRD-article.md §0)
 * จัดข้อความ (ซ้าย/กึ่งกลาง/ขวา/เต็มแนว) และระยะห่างระหว่างบรรทัด (default 1.5) ตั้งต่อย่อหน้า/หัวข้อ เก็บเป็น style บนแท็ก —
 * หน้าบ้านเก็บไว้เฉพาะ 2 ค่านี้ (App\Support\Front\HtmlSanitizer) สไตล์เนื้อหาอยู่ที่ .rich-text-content ใน app.css (ใช้ร่วมกับตัวอย่าง)
 */
const model = defineModel<string>({ required: true });

const editor = useEditor({
    content: model.value,
    extensions: [
        StarterKit,
        Underline,
        Link.configure({ openOnClick: false, autolink: true }),
        TextAlign.configure({ types: ['heading', 'paragraph'], alignments: ['left', 'center', 'right', 'justify'] }),
        LineHeight,
    ],
    editorProps: {
        attributes: {
            class: 'rich-text-content min-h-[180px] px-4 py-3 text-sm text-gray-800 focus:outline-none',
        },
    },
    onUpdate: ({ editor }) => {
        model.value = editor.getHTML();
    },
});

// sync กลับเข้า editor เมื่อ model ถูกเปลี่ยนจากภายนอก (เช่น โหลดข้อมูลภาษาอื่นมาแทน) — ข้ามถ้าเนื้อหาตรงกันอยู่แล้ว
// (กัน loop กับ onUpdate ด้านบน)
watch(model, (value) => {
    if (editor.value && value !== editor.value.getHTML()) {
        editor.value.commands.setContent(value, { emitUpdate: false });
    }
});

onBeforeUnmount(() => editor.value?.destroy());

const ALIGNS = [
    { value: 'left', label: 'ชิดซ้าย', icon: AlignLeft },
    { value: 'center', label: 'กึ่งกลาง', icon: AlignCenter },
    { value: 'right', label: 'ชิดขวา', icon: AlignRight },
    { value: 'justify', label: 'เต็มแนว (justify)', icon: AlignJustify },
] as const;

// ชิดซ้ายเป็นค่าปกติ (ไม่เขียน style) — ปุ่มชิดซ้ายจึงไฮไลต์เมื่อยังไม่ได้ตั้งตำแหน่งอื่น
function isAlign(value: string): boolean {
    if (!editor.value) return false;
    if (value === 'left') return !ALIGNS.some((a) => a.value !== 'left' && editor.value!.isActive({ textAlign: a.value }));

    return editor.value.isActive({ textAlign: value });
}

function setAlign(value: string) {
    if (value === 'left') {
        editor.value?.chain().focus().unsetTextAlign().run();
    } else {
        editor.value?.chain().focus().setTextAlign(value).run();
    }
}

// ระยะห่างระหว่างบรรทัด — เมนูเล็ก ๆ บน toolbar (ปุ่มคำสั่งของ editor แบบโปรแกรมพิมพ์เอกสาร ไม่ใช่ช่องฟอร์ม)
const lineMenuOpen = ref(false);
const currentLineHeight = computed(() => {
    if (!editor.value) return DEFAULT_LINE_HEIGHT;
    const type = editor.value.isActive('heading') ? 'heading' : 'paragraph';

    return (editor.value.getAttributes(type).lineHeight as string | null) || DEFAULT_LINE_HEIGHT;
});

function setLineHeight(value: string) {
    editor.value?.chain().focus().setLineHeight(value).run();
    lineMenuOpen.value = false;
}

function closeLineMenu(event: FocusEvent) {
    const container = event.currentTarget as HTMLElement;

    if (!container.contains(event.relatedTarget as Node | null)) lineMenuOpen.value = false;
}

function setLink() {
    const url = window.prompt('ลิงก์ (URL)');
    if (url === null) return;

    if (url === '') {
        editor.value?.chain().focus().unsetLink().run();
        return;
    }

    editor.value?.chain().focus().setLink({ href: url }).run();
}
</script>

<template>
    <div
        class="overflow-hidden rounded-lg border border-gray-300 bg-white shadow-xs focus-within:border-brand-500 focus-within:ring-3 focus-within:ring-brand-500/10"
    >
        <div
            v-if="editor"
            class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-gray-50 px-2 py-1.5"
        >
            <button type="button" class="toolbar-btn" :class="{ active: editor.isActive('bold') }" title="ตัวหนา" @click="editor.chain().focus().toggleBold().run()"><BoldIcon class="size-4" /></button>
            <button type="button" class="toolbar-btn" :class="{ active: editor.isActive('italic') }" title="ตัวเอียง" @click="editor.chain().focus().toggleItalic().run()"><ItalicIcon class="size-4" /></button>
            <button type="button" class="toolbar-btn" :class="{ active: editor.isActive('underline') }" title="ขีดเส้นใต้" @click="editor.chain().focus().toggleUnderline().run()"><UnderlineIcon class="size-4" /></button>
            <button type="button" class="toolbar-btn" :class="{ active: editor.isActive('strike') }" title="ขีดฆ่า" @click="editor.chain().focus().toggleStrike().run()"><Strikethrough class="size-4" /></button>
            <span class="mx-1 h-5 w-px bg-gray-300" aria-hidden="true" />
            <button type="button" class="toolbar-btn" :class="{ active: editor.isActive('heading', { level: 2 }) }" title="หัวข้อใหญ่" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"><Heading2 class="size-4" /></button>
            <button type="button" class="toolbar-btn" :class="{ active: editor.isActive('heading', { level: 3 }) }" title="หัวข้อรอง" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"><Heading3 class="size-4" /></button>
            <span class="mx-1 h-5 w-px bg-gray-300" aria-hidden="true" />
            <button type="button" class="toolbar-btn" :class="{ active: editor.isActive('bulletList') }" title="รายการจุด" @click="editor.chain().focus().toggleBulletList().run()"><List class="size-4" /></button>
            <button type="button" class="toolbar-btn" :class="{ active: editor.isActive('orderedList') }" title="รายการลำดับเลข" @click="editor.chain().focus().toggleOrderedList().run()"><ListOrdered class="size-4" /></button>
            <button type="button" class="toolbar-btn" :class="{ active: editor.isActive('blockquote') }" title="ข้อความคำพูด" @click="editor.chain().focus().toggleBlockquote().run()"><Quote class="size-4" /></button>
            <button type="button" class="toolbar-btn" :class="{ active: editor.isActive('link') }" title="ลิงก์" @click="setLink"><Link2 class="size-4" /></button>
            <span class="mx-1 h-5 w-px bg-gray-300" aria-hidden="true" />
            <button
                v-for="align in ALIGNS"
                :key="align.value"
                type="button"
                class="toolbar-btn"
                :class="{ active: isAlign(align.value) }"
                :title="align.label"
                @click="setAlign(align.value)"
            >
                <component :is="align.icon" class="size-4" />
            </button>
            <div class="relative" @focusout="closeLineMenu">
                <button
                    type="button"
                    class="toolbar-btn gap-1 text-xs"
                    :class="{ active: lineMenuOpen }"
                    title="ระยะห่างระหว่างบรรทัด"
                    aria-haspopup="menu"
                    :aria-expanded="lineMenuOpen"
                    @click="lineMenuOpen = !lineMenuOpen"
                >
                    <ArrowUpDown class="size-4" /> {{ currentLineHeight }}
                </button>
                <div v-if="lineMenuOpen" class="absolute left-0 top-full z-20 mt-1 w-40 rounded-lg border border-gray-200 bg-white py-1 shadow-lg" role="menu">
                    <p class="px-3 pb-1 pt-0.5 text-[11px] text-gray-400">ระยะห่างระหว่างบรรทัด</p>
                    <button
                        v-for="value in LINE_HEIGHTS"
                        :key="value"
                        type="button"
                        role="menuitem"
                        class="flex w-full items-center justify-between px-3 py-1.5 text-left text-sm text-gray-700 hover:bg-gray-100"
                        @click="setLineHeight(value)"
                    >
                        <span>{{ value }}<span v-if="value === DEFAULT_LINE_HEIGHT" class="text-xs text-gray-400"> (ค่าเริ่มต้น)</span></span>
                        <Check v-if="value === currentLineHeight" class="size-4 text-brand-600" />
                    </button>
                </div>
            </div>
            <span class="mx-1 h-5 w-px bg-gray-300" aria-hidden="true" />
            <button type="button" class="toolbar-btn" title="เลิกทำ" @click="editor.chain().focus().undo().run()"><Undo2 class="size-4" /></button>
            <button type="button" class="toolbar-btn" title="ทำซ้ำ" @click="editor.chain().focus().redo().run()"><Redo2 class="size-4" /></button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>

<style scoped>
.toolbar-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.375rem;
    border-radius: 0.375rem;
    color: #4b5563;
}
.toolbar-btn:hover {
    background-color: #e5e7eb;
}
.toolbar-btn.active {
    background-color: #dbeafe;
    color: #1d4ed8;
}
</style>
