<?php

namespace App\Support\Front;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * ทำความสะอาด rich text (จาก RichTextEditor ของหลังบ้าน — TipTap StarterKit + Underline + Link) ก่อนแสดงที่หน้าบ้านด้วย v-html
 * แบบ allowlist: เหลือเฉพาะแท็กจัดรูปแบบพื้นฐาน, ลิงก์ที่ปลอดภัย (http/https/mailto/tel/#/path ภายใน) และตัด attribute อื่นทั้งหมด
 * (on*, class ฯลฯ) — กัน XSS กรณีข้อมูลในฐานถูกแก้ตรง/ถูกวางมาจากที่อื่น; `style` เก็บไว้เฉพาะบนแท็ก block (ย่อหน้า/หัวข้อ/รายการ/คำพูด)
 * และเหลือแค่ 2 ค่าจาก editor: text-align (ซ้าย/กึ่งกลาง/ขวา/เต็มแนว) และ line-height (ตัวเลขในรายการ LINE_HEIGHTS) ค่าอื่นถูกตัดทิ้ง
 *
 * แท็กอันตราย (script/style/iframe/…) ถูกลบทั้งเนื้อหา; แท็กอื่นที่ไม่อยู่ใน allowlist ถูกถอดออกแต่เก็บข้อความข้างในไว้
 * h1 ถูกลดเป็น h2 (หน้าบ้านมี h1 ของหน้าอยู่แล้ว — ใช้ h1 ได้ครั้งเดียวต่อหน้าเพื่อ SEO/ลำดับหัวเรื่องของ WCAG)
 */
final class HtmlSanitizer
{
    private const ALLOWED = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'code', 'pre', 'blockquote',
        'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'hr', 'a', 'sub', 'sup', 'mark', 'span',
    ];

    /** แท็กที่เก็บ style จัดข้อความ/ระยะบรรทัดไว้ได้ */
    private const STYLED_BLOCKS = ['p', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'blockquote'];

    private const TEXT_ALIGNS = ['left', 'center', 'right', 'justify'];

    /** ระยะห่างระหว่างบรรทัดที่ RichTextEditor ตั้งได้ — ตรงกับ LINE_HEIGHTS ใน resources/js/utils/tiptapLineHeight.ts */
    private const LINE_HEIGHTS = ['1', '1.15', '1.5', '1.75', '2', '2.5', '3'];

    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'select', 'button',
        'svg', 'math', 'template', 'noscript', 'link', 'meta', 'base', 'frame', 'frameset', 'applet',
    ];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__sanitize_root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__sanitize_root');

        if (! $root instanceof DOMElement) {
            return '';
        }

        self::walk($root);

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $doc->saveHTML($child);
        }

        return trim($output);
    }

    private static function walk(DOMNode $node): void
    {
        // คัดลอกรายการลูกก่อน เพราะจะแก้ DOM ระหว่างวน
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }

            if ($tag === 'h1') {
                $child = self::rename($child, 'h2');
                $tag = 'h2';
            }

            self::walk($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                // ถอดแท็กออก เก็บลูกไว้
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            self::cleanAttributes($child, $tag);
        }
    }

    private static function cleanAttributes(DOMElement $element, string $tag): void
    {
        $href = $tag === 'a' ? $element->getAttribute('href') : null;
        $target = $tag === 'a' ? $element->getAttribute('target') : null;
        $style = in_array($tag, self::STYLED_BLOCKS, true) ? self::safeStyle($element->getAttribute('style')) : '';

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $element->removeAttribute($attribute->nodeName);
        }

        if ($style !== '') {
            $element->setAttribute('style', $style);
        }

        if ($tag !== 'a') {
            return;
        }

        $safe = FrontUrl::safeExternal($href);

        if ($safe === null) {
            return; // ลิงก์ไม่ปลอดภัย/ว่าง = เหลือเป็นข้อความธรรมดา (a ไม่มี href)
        }

        $element->setAttribute('href', $safe);

        if ($target === '_blank') {
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /**
     * เหลือเฉพาะ text-align / line-height ที่อยู่ในรายการที่อนุญาต (ค่าอื่นทั้งหมดตัดทิ้ง) — คืนสตริง style ใหม่ หรือ '' ถ้าไม่เหลืออะไร
     */
    private static function safeStyle(string $style): string
    {
        $kept = [];

        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_pad(array_map('trim', explode(':', $declaration, 2)), 2, '');
            $property = strtolower($property);
            $value = strtolower($value);

            if ($property === 'text-align' && in_array($value, self::TEXT_ALIGNS, true)) {
                $kept['text-align'] = $value;
            } elseif ($property === 'line-height' && in_array($value, self::LINE_HEIGHTS, true)) {
                $kept['line-height'] = $value;
            }
        }

        return implode('; ', array_map(fn (string $property, string $value) => "{$property}: {$value}", array_keys($kept), $kept));
    }

    private static function rename(DOMElement $element, string $tag): DOMElement
    {
        $replacement = $element->ownerDocument->createElement($tag);

        while ($element->firstChild) {
            $replacement->appendChild($element->firstChild);
        }

        $element->parentNode->replaceChild($replacement, $element);

        return $replacement;
    }
}
