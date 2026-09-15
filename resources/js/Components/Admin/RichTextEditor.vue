<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
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
} from 'lucide-vue-next';

/**
 * text editor สำหรับเนื้อหาที่ไม่ต้องการให้แทรกรูปภาพ/วิดีโอ/media — ตั้งใจไม่ใส่ extension รูปภาพ/วิดีโอ
 * ใด ๆ เพื่อบังคับตามที่ออกแบบไว้ (แทรกสื่อผ่าน part ประเภทอื่นแทน — ดู docs/PRD-article.md §0)
 */
const model = defineModel<string>({ required: true });

const editor = useEditor({
    content: model.value,
    extensions: [StarterKit, Underline, Link.configure({ openOnClick: false, autolink: true })],
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

:deep(.rich-text-content h2) {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0.75rem 0 0.375rem;
}
:deep(.rich-text-content h3) {
    font-size: 1rem;
    font-weight: 600;
    margin: 0.625rem 0 0.25rem;
}
:deep(.rich-text-content p) {
    margin: 0.375rem 0;
}
:deep(.rich-text-content ul) {
    list-style: disc;
    padding-left: 1.5rem;
    margin: 0.375rem 0;
}
:deep(.rich-text-content ol) {
    list-style: decimal;
    padding-left: 1.5rem;
    margin: 0.375rem 0;
}
:deep(.rich-text-content blockquote) {
    border-left: 3px solid #d1d5db;
    padding-left: 0.75rem;
    color: #6b7280;
    margin: 0.5rem 0;
}
:deep(.rich-text-content a) {
    color: #2563eb;
    text-decoration: underline;
}
</style>
