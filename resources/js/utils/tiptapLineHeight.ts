import { Extension } from '@tiptap/vue-3';

/**
 * ระยะห่างระหว่างบรรทัดของ RichTextEditor — ตั้งต่อย่อหน้า/หัวข้อ เก็บเป็น style="line-height: …" บนแท็กนั้น
 * (ไม่มี extension ทางการของ TipTap ที่ทำงานระดับ block จึงเขียนเอง) ค่าเริ่มต้น 1.5 มาจาก CSS (.rich-text-content / .front-prose)
 * การเลือก 1.5 จึงเท่ากับล้างค่า ไม่เขียน style ลงไป — ค่าที่หน้าบ้านรับต้องตรงกับ HtmlSanitizer::LINE_HEIGHTS
 */
export const LINE_HEIGHTS = ['1', '1.15', '1.5', '1.75', '2', '2.5', '3'] as const;

export const DEFAULT_LINE_HEIGHT = '1.5';

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        lineHeight: {
            setLineHeight: (value: string) => ReturnType;
            unsetLineHeight: () => ReturnType;
        };
    }
}

export const LineHeight = Extension.create<{ types: string[] }>({
    name: 'lineHeight',

    addOptions() {
        return { types: ['paragraph', 'heading'] };
    },

    addGlobalAttributes() {
        return [
            {
                types: this.options.types,
                attributes: {
                    lineHeight: {
                        default: null,
                        parseHTML: (element) => element.style.lineHeight || null,
                        renderHTML: (attributes) => (attributes.lineHeight ? { style: `line-height: ${attributes.lineHeight}` } : {}),
                    },
                },
            },
        ];
    },

    addCommands() {
        return {
            setLineHeight:
                (value) =>
                ({ commands }) => {
                    if (value === DEFAULT_LINE_HEIGHT) {
                        return this.options.types.map((type) => commands.resetAttributes(type, 'lineHeight')).some(Boolean);
                    }

                    return this.options.types.map((type) => commands.updateAttributes(type, { lineHeight: value })).some(Boolean);
                },
            unsetLineHeight:
                () =>
                ({ commands }) =>
                    this.options.types.map((type) => commands.resetAttributes(type, 'lineHeight')).some(Boolean),
        };
    },
});
