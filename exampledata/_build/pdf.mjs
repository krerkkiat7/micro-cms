// คู่มือเริ่มต้นใช้งาน (PDF) — ใช้เป็นไฟล์แนบตัวอย่างใน part เอกสารของบทความ "แนะนำระบบ"
import { FONTS, icon } from './lib.mjs';

const TEXT = {
    th: {
        title: 'คู่มือเริ่มต้นใช้งาน MicroCMS',
        subtitle: 'Micro-CMS ติดตั้งง่าย ใช้งานง่าย — สรุปขั้นตอนสำคัญสำหรับผู้ดูแลเว็บไซต์',
        version: 'เวอร์ชัน 1.0',
        steps: [
            ['log-in', 'เข้าสู่ระบบหลังบ้าน', 'เปิด <b>/admin</b> แล้วเข้าสู่ระบบด้วยบัญชีผู้ดูแล จากนั้นเปลี่ยนรหัสผ่านเริ่มต้นทันทีที่เมนูโปรไฟล์'],
            ['settings', 'ตั้งค่าเว็บไซต์', 'เมนู <b>จัดการระบบ → ตั้งค่าระบบ</b>: ชื่อเว็บไซต์ โลโก้ (.png) favicon (.ico) ภาษา โซนเวลา ข้อมูลติดต่อ และโซเชียลมีเดีย'],
            ['folder-open', 'อัปโหลดไฟล์', 'เมนู <b>จัดการไฟล์</b>: อัปโหลดรูปภาพ/เอกสาร จัดเป็นโฟลเดอร์ ระบบสร้างรูปย่อ (thumbnail) และ WebP ให้อัตโนมัติ'],
            ['newspaper', 'สร้างเนื้อหา', 'สร้างหมวดหมู่และบทความ ป้ายโฆษณา หน้าเพจ (จัดแถว คอลัมน์ widget) Intropage และ Popup ได้จากเมนูของแต่ละโมดูล'],
            ['menu', 'จัดเมนูหน้าบ้าน', 'เมนู <b>จัดการระบบ → จัดการเมนู</b>: ผูกเมนูกับหน้าเพจ หมวดหมู่ บทความ หรือติดต่อเรา ตั้งเมนูหน้าแรก และลากเพื่อเรียงลำดับ'],
            ['palette', 'ปรับหน้าตาด้วย Template', 'เมนู <b>จัดการระบบ → จัดการ Template</b>: ตั้งค่าส่วนหัว เนื้อหา ส่วนท้าย เมนูด้านข้าง หน้า Loading และ Custom CSS'],
            ['users', 'ผู้ใช้งานและสิทธิ์', 'สร้างกลุ่มผู้ใช้งาน กำหนดสิทธิ์รายโมดูล แล้วเพิ่มผู้ใช้งานเข้ากลุ่ม ตรวจสอบประวัติการใช้งานได้ทุกการกระทำ'],
        ],
        tipsTitle: 'ก่อนเปิดใช้งานจริง',
        tips: [
            'ตั้งค่า <b>APP_URL</b> ให้ตรงกับโดเมนจริง และเปลี่ยนรหัสผ่านผู้ดูแลเริ่มต้น',
            'ตั้ง cron <b>php artisan schedule:run</b> ทุกนาที เพื่อบันทึกยอดเข้าชม',
            'ตั้งค่า SMTP เพื่อให้ส่งอีเมลรีเซ็ตรหัสผ่านได้ และ Turnstile เพื่อเปิดฟอร์มติดต่อเรา',
            'ข้อมูลตัวอย่างแก้ไขต่อหรือลบออกได้ทั้งหมด',
        ],
        footer: 'MicroCMS — เอกสารตัวอย่าง (สร้างขึ้นเพื่อสาธิตระบบ)',
    },
    en: {
        title: 'MicroCMS Quick Start Guide',
        subtitle: 'An easy-to-install, easy-to-use micro CMS — the key steps for site administrators',
        version: 'Version 1.0',
        steps: [
            ['log-in', 'Sign in to the back office', 'Open <b>/admin</b> and sign in with the administrator account, then change the default password from your profile right away.'],
            ['settings', 'Configure the site', '<b>System → Settings</b>: site name, logo (.png), favicon (.ico), languages, time zone, contact details and social media.'],
            ['folder-open', 'Upload files', '<b>File manager</b>: upload images and documents into folders. Thumbnails and WebP versions are generated automatically.'],
            ['newspaper', 'Create content', 'Create article categories and articles, banners, pages (rows, columns and widgets), an intro page and popups from each module menu.'],
            ['menu', 'Build the front menu', '<b>System → Menu</b>: link menus to pages, categories, articles or the contact page, choose the home menu and drag to reorder.'],
            ['palette', 'Style it with a template', '<b>System → Template</b>: configure the header, body, footer, side menu, loading screen and custom CSS.'],
            ['users', 'Users and permissions', 'Create user groups, grant per-module permissions and add users to groups. Every action is recorded in the history logs.'],
        ],
        tipsTitle: 'Before going live',
        tips: [
            'Set <b>APP_URL</b> to your real domain and change the default administrator password.',
            'Add a cron entry for <b>php artisan schedule:run</b> every minute so view counts are saved.',
            'Configure SMTP for password-reset emails, and Turnstile to enable the contact form.',
            'All sample data can be edited further or deleted.',
        ],
        footer: 'MicroCMS — sample document (created to demonstrate the system)',
    },
};

export function quickStart(lang) {
    const t = TEXT[lang];
    return `<!doctype html><html lang="${lang}"><head><meta charset="utf-8">${FONTS}<style>
@page{size:A4;margin:0}
*{box-sizing:border-box}body{margin:0;font-family:'Sarabun','Prompt',sans-serif;color:#1F2937;font-size:15px;line-height:1.6}
.sheet{width:210mm;min-height:297mm;padding:0;position:relative;page-break-after:always}
.hero{background:linear-gradient(135deg,#0B1F44,#1E3A8A 55%,#2563EB);color:#fff;padding:26mm 18mm 18mm;position:relative;overflow:hidden}
.hero:after{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.12) 1.2px,transparent 1.2px);background-size:22px 22px}
.brand{display:flex;align-items:center;gap:12px;font-family:Prompt;font-size:22px;font-weight:600;position:relative;z-index:1}
.mark{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#60A5FA,#2563EB);display:flex;align-items:center;justify-content:center}
h1{font-family:Prompt;font-weight:600;font-size:34px;margin:16mm 0 4mm;position:relative;z-index:1;line-height:1.3}
.sub{font-size:17px;opacity:.9;position:relative;z-index:1}
.ver{display:inline-block;margin-top:8mm;padding:3px 14px;border-radius:20px;background:rgba(255,255,255,.18);font-size:13px;position:relative;z-index:1}
.content{padding:12mm 18mm}
.step{display:flex;gap:14px;padding:10px 0;border-bottom:1px solid #E5E7EB;break-inside:avoid}
.num{flex:none;width:40px;height:40px;border-radius:12px;background:#EFF6FF;color:#1D4ED8;display:flex;align-items:center;justify-content:center}
.step h3{font-family:Prompt;font-weight:600;font-size:16px;margin:0 0 2px;color:#0F2A5C}
.step p{margin:0;color:#4B5563}
.tips{margin-top:9mm;background:#EFF6FF;border-left:4px solid #2563EB;border-radius:8px;padding:6mm 7mm;break-inside:avoid}
.tips h2{font-family:Prompt;font-size:18px;margin:0 0 3mm;color:#1E3A8A}
.tips li{margin:2px 0}
.foot{position:absolute;bottom:10mm;left:18mm;right:18mm;font-size:11px;color:#9CA3AF;border-top:1px solid #E5E7EB;padding-top:3mm}
</style></head><body><div class="sheet">
<div class="hero"><div class="brand"><div class="mark">${icon('layers', { size: 26, stroke: 2, color: '#fff' })}</div>MicroCMS</div>
<h1>${t.title}</h1><div class="sub">${t.subtitle}</div><div class="ver">${t.version}</div></div>
<div class="content">${t.steps
        .map(([ic, h, p], i) => `<div class="step"><div class="num">${icon(ic, { size: 22, stroke: 2 })}</div><div><h3>${i + 1}. ${h}</h3><p>${p}</p></div></div>`)
        .join('')}
<div class="tips"><h2>${t.tipsTitle}</h2><ul>${t.tips.map((x) => `<li>${x}</li>`).join('')}</ul></div></div>
<div class="foot">${t.footer}</div></div></body></html>`;
}
