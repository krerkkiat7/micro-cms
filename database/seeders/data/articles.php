<?php

/**
 * เนื้อหาบทความตัวอย่าง (ใช้โดย ArticleSeeder) — ภาษาไทย/อังกฤษครบทุกบทความ
 *
 * part:
 *   ['text', 'title' => [th, en]|null, 'html' => [th, en]]
 *   ['image', 'title' => ..., 'file' => 'images/...', 'alt' => [th, en], 'size' => 'large'|'full'|...]
 *   ['images', 'title' => ..., 'display' => images_display_type, 'files' => [[path, [th, en]], ...]]
 *   ['documents', 'title' => ..., 'files' => [path, ...]]
 *
 * ภาพหน้าจอหลังบ้าน (images/guide/*) ถ่ายจากระบบจริงด้วย exampledata/_build/screenshots.mjs
 */

return [
    // ================================================================== ข่าวสาร
    [
        'key' => 'microcms-1-0-launch',
        'category' => 'news',
        'cover' => 'images/article/news-launch.jpg',
        'days_ago' => 3,
        'views' => 248,
        'tags' => ['microcms', 'announcement', 'release'],
        'title' => ['th' => 'เปิดตัว MicroCMS 1.0 อย่างเป็นทางการ', 'en' => 'Introducing MicroCMS 1.0'],
        'intro' => [
            'th' => 'MicroCMS เวอร์ชัน 1.0 พร้อมใช้งานแล้ว ระบบจัดการเนื้อหาขนาดเล็กที่ติดตั้งง่าย ใช้งานง่าย ครบทั้งบทความ หน้าเพจ ป้ายโฆษณา Popup และติดต่อเรา',
            'en' => 'MicroCMS 1.0 is here — a small content management system that is easy to install and easy to use, with articles, pages, banners, popups and a contact page.',
        ],
        'parts' => [
            ['text', 'html' => [
                'th' => '<p>เราตั้งใจสร้าง <strong>MicroCMS</strong> ให้เป็นระบบจัดการเว็บไซต์ที่ "พอดี" สำหรับองค์กรขนาดเล็กถึงกลาง ไม่ซับซ้อนเกินจำเป็น แต่ครบในสิ่งที่เว็บไซต์ทั่วไปต้องใช้ ตั้งแต่ข่าวประชาสัมพันธ์ หน้าเพจแนะนำองค์กร ไปจนถึงแบบฟอร์มติดต่อ</p>'
                    .'<h2>มีอะไรในเวอร์ชันนี้</h2><ul>'
                    .'<li><strong>บทความ</strong> — หมวดหมู่ แท็ก เนื้อหาแบบหลายส่วน (ข้อความ รูปภาพ กลุ่มรูป วิดีโอ เอกสาร) และรายงานยอดเข้าชม</li>'
                    .'<li><strong>หน้าเพจ</strong> — จัดโครงสร้างแบบแถว คอลัมน์ และ widget ได้เองโดยไม่ต้องเขียนโค้ด</li>'
                    .'<li><strong>ป้ายโฆษณา / Intropage / Popup</strong> — กำหนดช่วงเวลาเผยแพร่และนับจำนวนคลิกได้</li>'
                    .'<li><strong>Template</strong> — ปรับส่วนหัว ส่วนท้าย เมนูด้านข้าง และหน้า Loading ได้จากหลังบ้าน</li>'
                    .'<li><strong>ผู้ใช้งานและสิทธิ์</strong> — กำหนดสิทธิ์รายโมดูล พร้อมประวัติการใช้งานทุกการกระทำ</li></ul>',
                'en' => '<p>We built <strong>MicroCMS</strong> to be a "just right" website management system for small and medium organizations — no more complex than it needs to be, yet complete for what a typical website needs, from news and organization pages to a contact form.</p>'
                    .'<h2>What is in this release</h2><ul>'
                    .'<li><strong>Articles</strong> — categories, tags, multi-part content (text, image, gallery, video, documents) and view reports.</li>'
                    .'<li><strong>Pages</strong> — build layouts from rows, columns and widgets without writing code.</li>'
                    .'<li><strong>Banners / Intro page / Popups</strong> — schedule publishing windows and count clicks.</li>'
                    .'<li><strong>Templates</strong> — adjust the header, footer, side menu and loading screen from the back office.</li>'
                    .'<li><strong>Users and permissions</strong> — per-module permissions with a full history of every action.</li></ul>',
            ]],
            ['images', 'title' => ['th' => 'ภาพรวมของระบบ', 'en' => 'A quick look'], 'display' => 'thumbnail_carousel', 'files' => [
                ['images/guide/front-home.jpg', ['th' => 'หน้าแรกของเว็บไซต์ตัวอย่าง', 'en' => 'The sample website home page']],
                ['images/guide/admin-dashboard.jpg', ['th' => 'หน้า Dashboard ของหลังบ้าน', 'en' => 'The back-office dashboard']],
                ['images/article/module-article.jpg', ['th' => 'โมดูลบทความ', 'en' => 'Article module']],
                ['images/article/module-page.jpg', ['th' => 'โมดูลหน้าเพจ', 'en' => 'Page module']],
            ]],
            ['text', 'title' => ['th' => 'เริ่มต้นใช้งาน', 'en' => 'Getting started'], 'html' => [
                'th' => '<p>เว็บไซต์ที่คุณกำลังดูอยู่นี้สร้างจากข้อมูลตัวอย่างที่มาพร้อมกับการติดตั้ง ลองเข้าสู่ระบบหลังบ้านแล้วแก้ไขเนื้อหาเหล่านี้ได้ทันที หรือลบออกเมื่อพร้อมใส่เนื้อหาจริง อ่านขั้นตอนทั้งหมดได้ในหมวด <strong>การใช้งานระบบ</strong></p>',
                'en' => '<p>The website you are looking at is built from the sample data that ships with the installation. Sign in to the back office and edit any of it right away, or delete it when you are ready for real content. The full walkthrough is in the <strong>User Guide</strong> category.</p>',
            ]],
        ],
    ],
    [
        'key' => 'page-builder-rows-columns-widgets',
        'category' => 'news',
        'cover' => 'images/article/news-page-builder.jpg',
        'days_ago' => 8,
        'views' => 173,
        'tags' => ['microcms', 'page', 'release'],
        'title' => ['th' => 'จัดหน้าเพจเองได้ด้วยแถว คอลัมน์ และ Widget', 'en' => 'Build Pages with Rows, Columns and Widgets'],
        'intro' => [
            'th' => 'ไม่ต้องเขียนโค้ดก็ออกแบบหน้าเว็บได้ เลือก widget สำเร็จรูป จัดวางตามกริด 12 คอลัมน์ และดูตัวอย่างได้ทันทีในหน้าจอเดียว',
            'en' => 'Design a web page without writing code: pick ready-made widgets, place them on a 12-column grid and preview the result on the same screen.',
        ],
        'parts' => [
            ['text', 'html' => [
                'th' => '<p>หน้าเพจของ MicroCMS ประกอบจาก 3 ชั้น คือ <strong>แถว → คอลัมน์ → widget</strong> แต่ละชั้นกำหนดหัวเรื่อง สีหรือรูปพื้นหลัง ระยะขอบ และรูปแบบตัวอักษรได้อิสระ ทำให้หน้าแรกของเว็บไซต์ที่คุณเห็นอยู่นี้จัดขึ้นได้โดยไม่ต้องแก้ไขโค้ดแม้แต่บรรทัดเดียว</p>',
                'en' => '<p>A MicroCMS page is made of three levels: <strong>row → column → widget</strong>. Each level has its own title, background color or image, padding and typography, which is how the home page you are looking at was put together without touching a single line of code.</p>',
            ]],
            ['image', 'file' => 'images/article/module-page.jpg', 'alt' => ['th' => 'โครงสร้างหน้าเพจแบบแถว คอลัมน์ และ widget', 'en' => 'A page layout made of rows, columns and widgets']],
            ['text', 'title' => ['th' => 'Widget ที่มีให้เลือก', 'en' => 'Available widgets'], 'html' => [
                'th' => '<ul><li><strong>Slideshow</strong> — ภาพสไลด์เต็มความกว้างจากป้ายโฆษณาหรือบทความ</li>'
                    .'<li><strong>Slideset</strong> — การ์ดเลื่อนได้หลายใบต่อแถว เหมาะกับข่าวล่าสุด</li>'
                    .'<li><strong>Grid</strong> — รายการแบบกริด เลือกได้ทั้งการ์ด แถวมีรูป หรือแถวแสดงวันที่</li>'
                    .'<li><strong>Custom Text</strong> — เขียนเนื้อหาเอง พร้อมรูปภาพ กลุ่มรูป และวิดีโอ</li></ul>'
                    .'<p>ทุก widget ปรับจำนวนต่อแถวแยกตามขนาดหน้าจอ (คอมพิวเตอร์ โน้ตบุ๊ก แท็บเล็ต มือถือ) และเพิ่มปุ่ม "อ่านทั้งหมด" ที่ลิงก์ไปยังเมนูได้</p>',
                'en' => '<ul><li><strong>Slideshow</strong> — full-width slides from banners or articles.</li>'
                    .'<li><strong>Slideset</strong> — a scrollable row of cards, ideal for latest news.</li>'
                    .'<li><strong>Grid</strong> — a grid of items as cards, image rows or date rows.</li>'
                    .'<li><strong>Custom Text</strong> — your own content with images, galleries and video.</li></ul>'
                    .'<p>Every widget lets you set how many items appear per row on desktop, notebook, tablet and mobile, and can show a "View all" button that links to a menu.</p>',
            ]],
        ],
    ],
    [
        'key' => 'bilingual-seo-sitemap',
        'category' => 'news',
        'cover' => 'images/article/news-bilingual-seo.jpg',
        'days_ago' => 14,
        'views' => 131,
        'tags' => ['microcms', 'seo', 'language'],
        'title' => ['th' => 'รองรับสองภาษา พร้อม SEO และ Sitemap อัตโนมัติ', 'en' => 'Two Languages, Built-in SEO and an Automatic Sitemap'],
        'intro' => [
            'th' => 'เนื้อหาทุกโมดูลกรอกได้ทั้งภาษาไทยและภาษาอังกฤษ ระบบสร้าง meta tag, hreflang และ sitemap.xml ให้เองตามเมนูที่เผยแพร่',
            'en' => 'Every module accepts content in Thai and English, and the system generates meta tags, hreflang links and sitemap.xml from your published menus.',
        ],
        'parts' => [
            ['text', 'html' => [
                'th' => '<p>เลือกภาษาที่ใช้ได้ที่ <strong>ตั้งค่าระบบ</strong> ฟอร์มหลังบ้านจะแสดงช่องกรอกของแต่ละภาษาเคียงกัน ส่วนหน้าเว็บไซต์มีปุ่มสลับภาษาที่ส่วนหัว และ URL แยกตามภาษา เช่น <code>/th/...</code> และ <code>/en/...</code></p>'
                    .'<h2>SEO ที่ทำให้อัตโนมัติ</h2><ul>'
                    .'<li>กำหนด meta title / description / keywords และข้อความสำหรับแชร์โซเชียลได้รายหน้า</li>'
                    .'<li>ลิงก์ hreflang ระหว่างภาษา และ canonical URL ที่ถูกต้อง</li>'
                    .'<li>ข้อมูลโครงสร้าง (JSON-LD) สำหรับบทความและ breadcrumb</li>'
                    .'<li><code>sitemap.xml</code> และ <code>robots.txt</code> สร้างจากเมนูหน้าบ้านที่เผยแพร่อยู่</li></ul>',
                'en' => '<p>Choose the languages in <strong>System settings</strong>. Back-office forms then show each language side by side, and the website gets a language switcher in the header with separate URLs such as <code>/th/...</code> and <code>/en/...</code>.</p>'
                    .'<h2>SEO, done for you</h2><ul>'
                    .'<li>Set the meta title, description, keywords and social sharing text per page.</li>'
                    .'<li>Correct hreflang links between languages and canonical URLs.</li>'
                    .'<li>Structured data (JSON-LD) for articles and breadcrumbs.</li>'
                    .'<li><code>sitemap.xml</code> and <code>robots.txt</code> are generated from the published front menu.</li></ul>',
            ]],
            ['image', 'file' => 'images/article/module-language.jpg', 'alt' => ['th' => 'การสลับภาษาไทย/อังกฤษ', 'en' => 'Switching between Thai and English']],
            ['text', 'html' => [
                'th' => '<blockquote>เคล็ดลับ: เนื้อหาที่ต้องการให้ค้นหาเจอใน sitemap ต้องมีเมนูหน้าบ้านชี้ไปถึง เช่น หมวดหมู่บทความ หรือหน้าเพจ</blockquote>',
                'en' => '<blockquote>Tip: content only appears in the sitemap when a front menu leads to it, such as an article category or a page.</blockquote>',
            ]],
        ],
    ],
    [
        'key' => 'admin-security-tips',
        'category' => 'news',
        'cover' => 'images/article/news-security-tips.jpg',
        'days_ago' => 21,
        'views' => 96,
        'tags' => ['security', 'guide'],
        'title' => ['th' => '5 แนวทางดูแลความปลอดภัยสำหรับผู้ดูแลเว็บไซต์', 'en' => '5 Security Habits for Site Administrators'],
        'intro' => [
            'th' => 'ระบบที่ปลอดภัยเริ่มจากการตั้งค่าที่ดี รวมแนวทางง่าย ๆ ที่ผู้ดูแล MicroCMS ทำได้ทันทีหลังติดตั้ง',
            'en' => 'A secure site starts with good settings. Here are simple habits every MicroCMS administrator can adopt right after installation.',
        ],
        'parts' => [
            ['text', 'html' => [
                'th' => '<ol>'
                    .'<li><strong>เปลี่ยนรหัสผ่านเริ่มต้นทันที</strong> — บัญชีผู้ดูแลที่มากับข้อมูลตัวอย่างใช้รหัสผ่านที่ทุกคนรู้ ให้เปลี่ยนที่เมนูโปรไฟล์ก่อนเปิดใช้งานจริง</li>'
                    .'<li><strong>ให้สิทธิ์เท่าที่จำเป็น</strong> — สร้างกลุ่มผู้ใช้งานตามหน้าที่ เช่น "ผู้เขียนข่าว" ที่จัดการได้เฉพาะบทความ</li>'
                    .'<li><strong>เปิดการล็อกบัญชีอัตโนมัติ</strong> — ตั้งค่าจำนวนครั้งที่กรอกรหัสผ่านผิดได้ และเปิด CAPTCHA ที่หน้าเข้าสู่ระบบ</li>'
                    .'<li><strong>ตรวจสอบประวัติเป็นประจำ</strong> — ประวัติการเข้าสู่ระบบ การใช้งาน และการกระทำ ช่วยให้เห็นความผิดปกติได้เร็ว</li>'
                    .'<li><strong>ระวังสิทธิ์ Template</strong> — Custom JS ทำงานบนเว็บไซต์โดยตรง ควรให้สิทธิ์นี้เฉพาะผู้ดูแลที่ไว้ใจได้</li>'
                    .'</ol>',
                'en' => '<ol>'
                    .'<li><strong>Change the default password right away</strong> — the sample administrator account uses a well-known password. Change it from your profile before going live.</li>'
                    .'<li><strong>Grant only what is needed</strong> — create user groups by role, such as "News writers" who can manage articles only.</li>'
                    .'<li><strong>Turn on automatic lockout</strong> — limit failed sign-in attempts and enable CAPTCHA on the sign-in page.</li>'
                    .'<li><strong>Review the history regularly</strong> — sign-in, access and action logs help you spot anything unusual early.</li>'
                    .'<li><strong>Be careful with template rights</strong> — custom JavaScript runs directly on the website, so give this right to trusted administrators only.</li>'
                    .'</ol>',
            ]],
            ['image', 'file' => 'images/article/module-report.jpg', 'alt' => ['th' => 'สถิติและประวัติการใช้งาน', 'en' => 'Usage statistics and history']],
        ],
    ],
    [
        'key' => 'microcms-roadmap',
        'category' => 'news',
        'cover' => 'images/article/news-roadmap.jpg',
        'days_ago' => 30,
        'views' => 64,
        'tags' => ['microcms', 'announcement'],
        'title' => ['th' => 'แผนพัฒนา MicroCMS ในเฟสถัดไป', 'en' => 'What Is Next for MicroCMS'],
        'intro' => [
            'th' => 'หลังเปิดตัวเวอร์ชัน 1.0 ทีมพัฒนาวางแผนฟีเจอร์ถัดไปที่เน้นสมาชิกหน้าเว็บไซต์ การค้นหา และความปลอดภัย',
            'en' => 'After the 1.0 release, the next features focus on website members, search and security.',
        ],
        'parts' => [
            ['text', 'html' => [
                'th' => '<p>ฟีเจอร์ที่อยู่ในแผน (อาจปรับเปลี่ยนตามความเหมาะสม):</p><ul>'
                    .'<li><strong>สมาชิกหน้าเว็บไซต์</strong> — สมัครและเข้าสู่ระบบแยกจากหลังบ้าน พร้อมเนื้อหาเฉพาะสมาชิก</li>'
                    .'<li><strong>หน้าค้นหา</strong> — ค้นหาบทความและหน้าเพจจากส่วนหัวของเว็บไซต์</li>'
                    .'<li><strong>Content Security Policy</strong> — เพิ่มความปลอดภัยของสคริปต์บนหน้าเว็บไซต์</li>'
                    .'<li><strong>การเก็บรักษาประวัติ</strong> — กำหนดระยะเวลาเก็บ log และยอดเข้าชมอัตโนมัติ</li></ul>'
                    .'<p>มีข้อเสนอแนะเพิ่มเติม ส่งมาที่หน้า <strong>ติดต่อเรา</strong> ได้เลย</p>',
                'en' => '<p>Planned features (subject to change):</p><ul>'
                    .'<li><strong>Website members</strong> — sign-up and sign-in separate from the back office, with members-only content.</li>'
                    .'<li><strong>Search page</strong> — search articles and pages from the website header.</li>'
                    .'<li><strong>Content Security Policy</strong> — tighter script security on the website.</li>'
                    .'<li><strong>History retention</strong> — automatic retention periods for logs and view counts.</li></ul>'
                    .'<p>Have a suggestion? Send it through the <strong>Contact Us</strong> page.</p>',
            ]],
        ],
    ],

    // ================================================================== การใช้งานระบบ
    [
        'key' => 'how-to-manage-articles',
        'category' => 'user-guide',
        'cover' => 'images/article/guide-article.jpg',
        'days_ago' => 5,
        'views' => 152,
        'tags' => ['guide', 'getting-started', 'article'],
        'title' => ['th' => 'วิธีจัดการบทความ', 'en' => 'How to Manage Articles'],
        'intro' => [
            'th' => 'สร้างหมวดหมู่ เขียนบทความแบบหลายส่วน ใส่แท็ก และกำหนดการแสดงผลของรายการบทความ',
            'en' => 'Create categories, write multi-part articles, add tags and control how article lists are displayed.',
        ],
        'parts' => [
            ['text', 'title' => ['th' => '1. สร้างหมวดหมู่', 'en' => '1. Create a category'], 'html' => [
                'th' => '<p>ไปที่เมนู <strong>บทความ → หมวดหมู่</strong> แล้วกด "เพิ่ม" กรอกชื่อ ข้อความเกริ่นนำ และ slug ของแต่ละภาษา เลือกรูปปกจากคลังไฟล์ จากนั้นบันทึก หมวดหมู่ที่สร้างแล้วนำไปผูกกับเมนูหน้าบ้านเพื่อแสดงรายการบทความได้</p>',
                'en' => '<p>Go to <strong>Articles → Categories</strong> and click "Add". Fill in the name, intro text and slug for each language, pick a cover image from the file library and save. You can then link the category to a front menu to list its articles.</p>',
            ]],
            ['image', 'file' => 'images/guide/article-list.jpg', 'size' => 'full', 'alt' => ['th' => 'หน้ารายการบทความในหลังบ้าน', 'en' => 'The article list in the back office']],
            ['text', 'title' => ['th' => '2. เขียนบทความ', 'en' => '2. Write an article'], 'html' => [
                'th' => '<p>ที่เมนู <strong>บทความ → บทความ</strong> กด "เพิ่ม" เลือกหมวดหมู่ วันที่เผยแพร่ (และวันสิ้นสุดถ้ามี) รูปปก หัวเรื่อง และข้อความเกริ่นนำ เนื้อหาของบทความแบ่งเป็น <strong>ส่วน (part)</strong> เรียงต่อกัน เลือกได้ว่าแต่ละส่วนเป็น</p>'
                    .'<ul><li>ข้อความ (จัดรูปแบบ หัวข้อ รายการ ลิงก์)</li><li>รูปภาพ 1 รูป หรือกลุ่มรูป 7 รูปแบบ</li><li>วิดีโอ (ไฟล์ หรือ YouTube)</li><li>เอกสาร 1 ไฟล์ หรือหลายไฟล์</li></ul>'
                    .'<p>ลากเพื่อเรียงลำดับส่วน และซ่อนบางส่วนชั่วคราวได้ด้วยไอคอนรูปตา</p>',
                'en' => '<p>In <strong>Articles → Articles</strong>, click "Add" and choose the category, publish date (and an end date if needed), cover image, title and intro. The body is a sequence of <strong>parts</strong>, and each part can be:</p>'
                    .'<ul><li>Text (formatting, headings, lists, links)</li><li>A single image, or a gallery in 7 layouts</li><li>Video (an uploaded file or YouTube)</li><li>One or more documents</li></ul>'
                    .'<p>Drag parts to reorder them, and hide a part temporarily with the eye icon.</p>',
            ]],
            ['images', 'title' => ['th' => 'หน้าจอที่เกี่ยวข้อง', 'en' => 'Related screens'], 'display' => 'grid_lightbox', 'files' => [
                ['images/guide/article-form.jpg', ['th' => 'ฟอร์มข้อมูลทั่วไปของบทความ', 'en' => 'Article general information form']],
                ['images/guide/article-parts.jpg', ['th' => 'การเพิ่มส่วนของเนื้อหา', 'en' => 'Adding content parts']],
                ['images/guide/article-category.jpg', ['th' => 'หน้ารายการหมวดหมู่', 'en' => 'Category list']],
                ['images/guide/article-setting.jpg', ['th' => 'ตั้งค่าการแสดงผลบทความ', 'en' => 'Article display settings']],
            ]],
            ['text', 'title' => ['th' => '3. ตั้งค่าการแสดงผล', 'en' => '3. Display settings'], 'html' => [
                'th' => '<p>เมนู <strong>บทความ → ตั้งค่า</strong> ใช้กำหนดรูปแบบรายการ (การ์ดหรือแถว) จำนวนต่อหน้า การเรียงลำดับตั้งต้น อัตราส่วนรูป และส่วนที่แสดงในหน้ารายละเอียด เช่น รูปปก ปุ่มพิมพ์ และปุ่มแชร์ ดูยอดเข้าชมได้ที่ <strong>บทความ → รายงาน</strong></p>',
                'en' => '<p><strong>Articles → Settings</strong> controls the list style (cards or rows), items per page, default sorting, image ratios and what the detail page shows, such as the cover, print button and share buttons. View counts are in <strong>Articles → Reports</strong>.</p>',
            ]],
        ],
    ],
    [
        'key' => 'how-to-build-a-page',
        'category' => 'user-guide',
        'cover' => 'images/article/guide-page.jpg',
        'days_ago' => 6,
        'views' => 138,
        'tags' => ['guide', 'page'],
        'title' => ['th' => 'วิธีสร้างหน้าเพจด้วยแถว คอลัมน์ และ Widget', 'en' => 'How to Build a Page with Rows, Columns and Widgets'],
        'intro' => [
            'th' => 'สร้างหน้าเพจใหม่ จัดโครงสร้างในแท็บโครงสร้าง ตั้งค่า widget และผูกหน้าเพจกับเมนูหน้าบ้าน',
            'en' => 'Create a page, arrange it in the Layout tab, configure widgets and link the page to a front menu.',
        ],
        'parts' => [
            ['text', 'title' => ['th' => '1. สร้างหน้าเพจ', 'en' => '1. Create the page'], 'html' => [
                'th' => '<p>ไปที่เมนู <strong>Page → หน้าเพจ</strong> กด "เพิ่ม" กรอกหัวเรื่อง ข้อความเกริ่นนำ slug และข้อมูล SEO ของแต่ละภาษา เลือกพื้นหลังของทั้งหน้าได้ทั้งสีและรูปภาพ แล้วบันทึก</p>',
                'en' => '<p>Go to <strong>Page → Pages</strong>, click "Add" and fill in the title, intro text, slug and SEO details for each language. Set a background color or image for the whole page, then save.</p>',
            ]],
            ['text', 'title' => ['th' => '2. จัดโครงสร้าง', 'en' => '2. Arrange the layout'], 'html' => [
                'th' => '<p>เปิดแท็บ <strong>โครงสร้าง</strong> เพิ่มแถว แล้วเลือกขนาดคอลัมน์ในกริด 12 ช่อง (เช่น 7+5 หรือ 4+4+4) จากนั้นเพิ่ม widget ลงในคอลัมน์ ใช้ปุ่มเฟืองเพื่อตั้งค่า และปุ่มเรียงลำดับเพื่อย้ายแถว คอลัมน์ หรือ widget ข้ามกันได้ เมื่อเสร็จแล้วกด "บันทึกโครงสร้าง" ครั้งเดียว</p>',
                'en' => '<p>Open the <strong>Layout</strong> tab, add a row and choose column sizes on the 12-column grid (for example 7+5 or 4+4+4). Add widgets to the columns, use the gear button to configure them and the reorder button to move rows, columns or widgets around. When you are done, click "Save layout" once.</p>',
            ]],
            ['image', 'file' => 'images/guide/page-layout.jpg', 'size' => 'full', 'alt' => ['th' => 'แท็บโครงสร้างของหน้าเพจ', 'en' => 'The page Layout tab']],
            ['images', 'title' => ['th' => 'ตัวอย่างหน้าจอ', 'en' => 'Screens'], 'display' => 'full_width_slider', 'files' => [
                ['images/guide/page-list.jpg', ['th' => 'หน้ารายการหน้าเพจ', 'en' => 'Page list']],
                ['images/guide/page-widget.jpg', ['th' => 'การตั้งค่า widget', 'en' => 'Widget settings']],
            ]],
            ['text', 'title' => ['th' => '3. ผูกกับเมนู', 'en' => '3. Link it to a menu'], 'html' => [
                'th' => '<p>ไปที่ <strong>จัดการระบบ → จัดการเมนู</strong> เพิ่มเมนูประเภท "หน้าเพจ" แล้วเลือกหน้าที่สร้างไว้ ถ้าต้องการให้เป็นหน้าแรกของเว็บไซต์ ให้เลือก "เป็นหน้าหลัก" = ใช่</p>',
                'en' => '<p>Go to <strong>System → Menu</strong>, add a menu of type "Page" and pick the page you created. Set "Home page" to Yes to make it the website home page.</p>',
            ]],
        ],
    ],
    [
        'key' => 'how-to-set-up-intropage',
        'category' => 'user-guide',
        'cover' => 'images/article/guide-intropage.jpg',
        'days_ago' => 9,
        'views' => 87,
        'tags' => ['guide', 'intropage'],
        'title' => ['th' => 'วิธีตั้งค่า Intropage หน้าต้อนรับ', 'en' => 'How to Set Up the Intro Page'],
        'intro' => [
            'th' => 'Intropage คือหน้าที่ผู้ชมเห็นก่อนเข้าเว็บไซต์ เหมาะกับประกาศสำคัญหรือวาระพิเศษ แสดงได้ทั้งรูปภาพและวิดีโอ',
            'en' => 'The intro page is what visitors see before entering the website — ideal for key announcements or special occasions, with an image or video.',
        ],
        'parts' => [
            ['text', 'html' => [
                'th' => '<p>ที่เมนู <strong>Intropage</strong> กด "เพิ่ม" แล้วเลือกรูปแบบการแสดงผล</p><ul>'
                    .'<li><strong>รูปภาพ</strong> หรือ <strong>วิดีโอ</strong> จากคลังไฟล์</li><li><strong>ลิงก์วิดีโอ</strong> หรือ <strong>YouTube</strong></li></ul>'
                    .'<p>กำหนดขนาด (เต็มจอหรือภายในกรอบ) ข้อความประกอบ สีและรูปพื้นหลัง และปุ่ม เช่น "เข้าสู่เว็บไซต์" ซึ่งต้องมีเสมอ 1 ปุ่ม และเพิ่มปุ่มลิงก์อื่นได้</p>',
                'en' => '<p>In <strong>Intropage</strong>, click "Add" and choose how it is displayed:</p><ul>'
                    .'<li>An <strong>image</strong> or <strong>video</strong> from the file library</li><li>A <strong>video link</strong> or <strong>YouTube</strong></li></ul>'
                    .'<p>Set the size (full screen or inside the container), the accompanying text, background color or image, and the buttons. An "Enter site" button is always required, and you can add more link buttons.</p>',
            ]],
            ['image', 'file' => 'images/guide/intropage-form.jpg', 'size' => 'full', 'alt' => ['th' => 'ฟอร์ม Intropage', 'en' => 'Intro page form']],
            ['text', 'html' => [
                'th' => '<p>ระบบแสดง Intropage ที่อยู่ในช่วงวันที่เผยแพร่และใหม่ที่สุดเพียงรายการเดียว เมื่อพ้นช่วงเวลา ผู้ชมจะเข้าหน้าแรกตามเมนูโดยตรง</p>',
                'en' => '<p>Only one intro page is shown: the newest one inside its publishing window. Outside that window, visitors go straight to the home menu.</p>',
            ]],
        ],
    ],
    [
        'key' => 'how-to-manage-banners',
        'category' => 'user-guide',
        'cover' => 'images/article/guide-banner.jpg',
        'days_ago' => 11,
        'views' => 112,
        'tags' => ['guide', 'banner'],
        'title' => ['th' => 'วิธีจัดการป้ายโฆษณา (Banner)', 'en' => 'How to Manage Banners'],
        'intro' => [
            'th' => 'จัดกลุ่มป้ายโฆษณาตามตำแหน่ง ตั้งช่วงเวลาเผยแพร่ ลิงก์ไปยังเมนูหรือ URL และดูรายงานจำนวนคลิก',
            'en' => 'Group banners by position, schedule them, link them to a menu or URL and check click reports.',
        ],
        'parts' => [
            ['text', 'html' => [
                'th' => '<p>เริ่มจากสร้าง <strong>หมวดหมู่</strong> ตามตำแหน่งที่จะแสดง เช่น "Highlight" ของหน้าแรก จากนั้นเพิ่ม <strong>ป้ายโฆษณา</strong> แต่ละรายการ: เลือกรูป (แนะนำอัตราส่วน 21:9 สำหรับ slideshow) หัวเรื่อง ข้อความ และช่วงเวลาเผยแพร่</p>'
                    .'<h3>ลิงก์ของป้ายโฆษณา</h3><ul><li><strong>ไม่มีลิงก์</strong> — แสดงรูปอย่างเดียว</li><li><strong>เมนูหน้าบ้าน</strong> — ลิงก์ตามเมนู แก้ปลายทางที่เมนูที่เดียว</li><li><strong>กำหนดเอง</strong> — ใส่ URL เอง</li></ul>'
                    .'<p>นำป้ายโฆษณาไปแสดงด้วย widget Slideshow, Slideset หรือ Grid ในหน้าเพจ</p>',
                'en' => '<p>Start by creating a <strong>category</strong> for each position, such as the home page "Highlight". Then add each <strong>banner</strong>: pick an image (21:9 works best for slideshows), a title, text and a publishing window.</p>'
                    .'<h3>Banner links</h3><ul><li><strong>No link</strong> — image only.</li><li><strong>Front menu</strong> — follows a menu, so the target is changed in one place.</li><li><strong>Custom</strong> — enter your own URL.</li></ul>'
                    .'<p>Show banners on a page with the Slideshow, Slideset or Grid widget.</p>',
            ]],
            ['images', 'display' => 'multi_carousel', 'files' => [
                ['images/guide/banner-list.jpg', ['th' => 'หน้ารายการป้ายโฆษณา', 'en' => 'Banner list']],
                ['images/guide/banner-form.jpg', ['th' => 'ฟอร์มป้ายโฆษณา', 'en' => 'Banner form']],
                ['images/banner/banner-welcome.jpg', ['th' => 'ตัวอย่างรูปป้ายโฆษณา', 'en' => 'A sample banner image']],
            ]],
            ['text', 'html' => [
                'th' => '<p>ทุกครั้งที่ผู้ชมคลิกป้ายโฆษณาที่มีลิงก์ ระบบนับให้อัตโนมัติ ดูสรุปได้ที่ <strong>ป้ายโฆษณา → รายงาน</strong></p>',
                'en' => '<p>Clicks on linked banners are counted automatically. See the summary in <strong>Banners → Reports</strong>.</p>',
            ]],
        ],
    ],
    [
        'key' => 'how-to-create-a-popup',
        'category' => 'user-guide',
        'cover' => 'images/article/guide-popup.jpg',
        'days_ago' => 13,
        'views' => 79,
        'tags' => ['guide', 'popup'],
        'title' => ['th' => 'วิธีสร้าง Popup', 'en' => 'How to Create a Popup'],
        'intro' => [
            'th' => 'แจ้งข่าวด่วนหรือโปรโมชันด้วย Popup เลือกหน้าที่จะแสดง ตั้งช่วงเวลา และให้ผู้ชมเลือก "ไม่แสดงวันนี้อีก" ได้',
            'en' => 'Announce urgent news or promotions with a popup: choose where it appears, schedule it and let visitors hide it for the day.',
        ],
        'parts' => [
            ['text', 'html' => [
                'th' => '<p>ที่เมนู <strong>Popup</strong> กด "เพิ่ม" แล้วเลือกรูปแบบ</p><ul>'
                    .'<li><strong>Modal</strong> — กล่องกลางจอ มีได้หลายส่วนเลื่อนเป็นสไลด์ แต่ละส่วนเป็นรูป+ข้อความ รูปอย่างเดียว หรือข้อความอย่างเดียว</li>'
                    .'<li><strong>Floating</strong> — รูปลอยมุมจอ ไม่บังเนื้อหา</li></ul>'
                    .'<p>กำหนดหน้าที่แสดงได้ 3 แบบ: ทุกหน้า เฉพาะเมนูที่เลือก หรือไม่แสดง (ปิดชั่วคราว)</p>',
                'en' => '<p>In <strong>Popup</strong>, click "Add" and pick a style:</p><ul>'
                    .'<li><strong>Modal</strong> — a centered box with one or more slides; each slide is image + text, image only or text only.</li>'
                    .'<li><strong>Floating</strong> — an image floating in a corner that does not cover the content.</li></ul>'
                    .'<p>Choose where it appears: every page, selected menus only, or nowhere (temporarily off).</p>',
            ]],
            ['image', 'file' => 'images/guide/popup-form.jpg', 'size' => 'full', 'alt' => ['th' => 'ฟอร์ม Popup', 'en' => 'Popup form']],
            ['images', 'title' => ['th' => 'ผลลัพธ์ที่หน้าเว็บไซต์', 'en' => 'On the website'], 'display' => 'masonry_grid', 'files' => [
                ['images/guide/front-popup.jpg', ['th' => 'Popup ยินดีต้อนรับที่หน้าแรก', 'en' => 'The welcome popup on the home page']],
                ['images/guide/popup-list.jpg', ['th' => 'หน้ารายการ Popup', 'en' => 'Popup list']],
            ]],
        ],
    ],

    // ================================================================== การตั้งค่าบริการภายนอก
    [
        'key' => 'setup-cloudflare-turnstile',
        'category' => 'external-services',
        'cover' => 'images/article/ext-turnstile.jpg',
        'days_ago' => 4,
        'views' => 121,
        'tags' => ['guide', 'cloudflare', 'turnstile', 'security'],
        'title' => ['th' => 'ตั้งค่า Cloudflare Turnstile (CAPTCHA)', 'en' => 'Setting Up Cloudflare Turnstile (CAPTCHA)'],
        'intro' => [
            'th' => 'Turnstile ช่วยกันสแปมในแบบฟอร์มติดต่อเราและหน้าเข้าสู่ระบบ โดยผู้ใช้ไม่ต้องเลือกรูปภาพให้ยุ่งยาก ใช้งานฟรี',
            'en' => 'Turnstile protects the contact form and sign-in page from spam without making people solve picture puzzles — and it is free.',
        ],
        'parts' => [
            ['image', 'file' => 'images/article/ext-turnstile-steps.jpg', 'size' => 'full', 'alt' => ['th' => 'ขั้นตอนการตั้งค่า Turnstile', 'en' => 'Turnstile setup steps']],
            ['text', 'title' => ['th' => 'ขั้นตอน', 'en' => 'Steps'], 'html' => [
                'th' => '<ol><li>เข้าสู่ระบบ <a href="https://dash.cloudflare.com" target="_blank">Cloudflare Dashboard</a> (สมัครฟรีได้)</li>'
                    .'<li>เลือกเมนู <strong>Turnstile</strong> แล้วกด <strong>Add widget</strong> ตั้งชื่อ ใส่โดเมนของเว็บไซต์ใน Hostname และเลือกโหมด <strong>Managed</strong></li>'
                    .'<li>คัดลอก <strong>Site Key</strong> และ <strong>Secret Key</strong></li>'
                    .'<li>ใน MicroCMS ไปที่ <strong>จัดการระบบ → ตั้งค่าระบบ</strong> หัวข้อ Turnstile วางทั้งสองค่า แล้วบันทึก</li></ol>'
                    .'<p>เมื่อตั้งค่าครบ แบบฟอร์มหน้า <strong>ติดต่อเรา</strong> จะแสดงขึ้น และเปิด CAPTCHA ที่หน้าเข้าสู่ระบบหลังบ้านได้ (หัวข้อ "การเข้าสู่ระบบหลังบ้าน")</p>',
                'en' => '<ol><li>Sign in to the <a href="https://dash.cloudflare.com" target="_blank">Cloudflare Dashboard</a> (free sign-up).</li>'
                    .'<li>Open <strong>Turnstile</strong>, click <strong>Add widget</strong>, give it a name, enter your domain as the hostname and choose <strong>Managed</strong> mode.</li>'
                    .'<li>Copy the <strong>Site Key</strong> and <strong>Secret Key</strong>.</li>'
                    .'<li>In MicroCMS, go to <strong>System → Settings</strong>, Turnstile section, paste both values and save.</li></ol>'
                    .'<p>Once both keys are set, the form on the <strong>Contact Us</strong> page appears, and you can enable CAPTCHA on the back-office sign-in page ("Back-office sign-in" section).</p>',
            ]],
            ['image', 'file' => 'images/guide/settings-turnstile.jpg', 'size' => 'full', 'alt' => ['th' => 'หัวข้อ Turnstile ในหน้าตั้งค่าระบบ', 'en' => 'The Turnstile section of the settings page']],
            ['text', 'title' => ['th' => 'เกี่ยวกับข้อมูลตัวอย่าง', 'en' => 'About the sample data'], 'html' => [
                'th' => '<blockquote>ข้อมูลตัวอย่างใส่ <strong>คีย์ทดสอบ</strong> ของ Cloudflare ไว้ให้ (Site Key <code>1x00000000000000000000AA</code>) ซึ่งผ่านการตรวจสอบเสมอ เพื่อให้เห็นแบบฟอร์มติดต่อเราได้ทันที <strong>ก่อนเปิดใช้งานจริงต้องเปลี่ยนเป็นคีย์ของเว็บไซต์คุณ</strong> มิฉะนั้นจะไม่มีการป้องกันสแปม</blockquote>',
                'en' => '<blockquote>The sample data ships with Cloudflare <strong>test keys</strong> (Site Key <code>1x00000000000000000000AA</code>) that always pass, so you can see the contact form right away. <strong>Replace them with your own keys before going live</strong>, otherwise there is no spam protection.</blockquote>',
            ]],
        ],
    ],
    [
        'key' => 'setup-google-maps',
        'category' => 'external-services',
        'cover' => 'images/article/ext-google-map.jpg',
        'days_ago' => 7,
        'views' => 93,
        'tags' => ['guide', 'google', 'google-maps'],
        'title' => ['th' => 'ตั้งค่า Google Maps สำหรับหน้าติดต่อเรา', 'en' => 'Setting Up Google Maps for the Contact Page'],
        'intro' => [
            'th' => 'แสดงแผนที่ตั้งองค์กรแบบโต้ตอบได้ในหน้าติดต่อเรา ด้วย API key ของ Maps Embed API',
            'en' => 'Show an interactive map of your location on the contact page with a Maps Embed API key.',
        ],
        'parts' => [
            ['image', 'file' => 'images/article/ext-google-map-steps.jpg', 'size' => 'full', 'alt' => ['th' => 'ขั้นตอนการสร้าง API key', 'en' => 'Steps to create an API key']],
            ['text', 'title' => ['th' => 'สร้าง API key', 'en' => 'Create an API key'], 'html' => [
                'th' => '<ol><li>เข้า <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a> แล้วสร้างโปรเจกต์ใหม่</li>'
                    .'<li>ไปที่ <strong>APIs &amp; Services → Library</strong> ค้นหา <strong>Maps Embed API</strong> แล้วกด Enable</li>'
                    .'<li>ไปที่ <strong>Credentials → Create credentials → API key</strong></li>'
                    .'<li><strong>จำกัดการใช้งาน</strong> ของ key: เลือก Application restrictions เป็น <strong>Websites</strong> ใส่โดเมนของคุณ เช่น <code>https://www.example.com/*</code> และจำกัด API เฉพาะ Maps Embed API</li></ol>',
                'en' => '<ol><li>Open the <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a> and create a project.</li>'
                    .'<li>Go to <strong>APIs &amp; Services → Library</strong>, find <strong>Maps Embed API</strong> and click Enable.</li>'
                    .'<li>Go to <strong>Credentials → Create credentials → API key</strong>.</li>'
                    .'<li><strong>Restrict</strong> the key: set Application restrictions to <strong>Websites</strong> with your domain, such as <code>https://www.example.com/*</code>, and limit it to the Maps Embed API.</li></ol>',
            ]],
            ['text', 'title' => ['th' => 'ใส่ค่าใน MicroCMS', 'en' => 'Add it to MicroCMS'], 'html' => [
                'th' => '<p>วาง API key ที่ <strong>จัดการระบบ → ตั้งค่าระบบ</strong> หัวข้อ Google Map จากนั้นไปที่ <strong>Contact Us → ตั้งค่า</strong> เปิดหัวข้อ "Google Map" ในส่วนแผนที่ แล้วกดเลือกตำแหน่งจากแผนที่ (หรือกรอกละติจูด/ลองจิจูด)</p>'
                    .'<p>ถ้ายังไม่มี API key ใช้ <strong>รูปแผนที่</strong> แทนได้ เหมือนที่ข้อมูลตัวอย่างตั้งไว้</p>',
                'en' => '<p>Paste the key in <strong>System → Settings</strong>, Google Map section. Then go to <strong>Contact Us → Settings</strong>, turn on the "Google Map" section under Map and pick the location on the map (or enter the latitude and longitude).</p>'
                    .'<p>No API key yet? Use a <strong>map image</strong> instead, as the sample data does.</p>',
            ]],
            ['image', 'file' => 'images/guide/settings-google-map.jpg', 'size' => 'full', 'alt' => ['th' => 'หัวข้อ Google Map ในหน้าตั้งค่าระบบ', 'en' => 'The Google Map section of the settings page']],
        ],
    ],
    [
        'key' => 'setup-google-analytics',
        'category' => 'external-services',
        'cover' => 'images/article/ext-google-analytics.jpg',
        'days_ago' => 10,
        'views' => 84,
        'tags' => ['guide', 'google', 'google-analytics'],
        'title' => ['th' => 'ตั้งค่า Google Analytics 4', 'en' => 'Setting Up Google Analytics 4'],
        'intro' => [
            'th' => 'ติดตามจำนวนผู้เข้าชม ช่องทางที่มา และพฤติกรรมการใช้งานเว็บไซต์ด้วย Google Analytics เพียงใส่ Measurement ID',
            'en' => 'Track visitors, traffic sources and behavior with Google Analytics — all it takes is your Measurement ID.',
        ],
        'parts' => [
            ['image', 'file' => 'images/article/ext-google-analytics-steps.jpg', 'size' => 'full', 'alt' => ['th' => 'ขั้นตอนการตั้งค่า Google Analytics', 'en' => 'Google Analytics setup steps']],
            ['text', 'title' => ['th' => 'ขั้นตอน', 'en' => 'Steps'], 'html' => [
                'th' => '<ol><li>เข้า <a href="https://analytics.google.com" target="_blank">Google Analytics</a> แล้วสร้าง Account และ <strong>Property</strong> แบบ GA4</li>'
                    .'<li>เพิ่ม <strong>Data stream</strong> ประเภท Web ใส่ URL ของเว็บไซต์</li>'
                    .'<li>คัดลอก <strong>Measurement ID</strong> ที่ขึ้นต้นด้วย <code>G-</code></li>'
                    .'<li>ใน MicroCMS ไปที่ <strong>จัดการระบบ → ตั้งค่าระบบ</strong> หัวข้อ Google Analytics วาง Measurement ID แล้วบันทึก</li></ol>'
                    .'<p>ระบบจะใส่ Google tag ให้เฉพาะหน้าเว็บไซต์ (ไม่ใส่ในหลังบ้าน) ข้อมูลจะเริ่มแสดงในรายงาน Realtime ภายในไม่กี่นาที</p>',
                'en' => '<ol><li>Open <a href="https://analytics.google.com" target="_blank">Google Analytics</a> and create an account and a GA4 <strong>property</strong>.</li>'
                    .'<li>Add a <strong>Web data stream</strong> with your website URL.</li>'
                    .'<li>Copy the <strong>Measurement ID</strong>, which starts with <code>G-</code>.</li>'
                    .'<li>In MicroCMS, go to <strong>System → Settings</strong>, Google Analytics section, paste the Measurement ID and save.</li></ol>'
                    .'<p>The Google tag is added to website pages only (not the back office). Data starts appearing in the Realtime report within a few minutes.</p>',
            ]],
            ['image', 'file' => 'images/guide/settings-google-analytics.jpg', 'size' => 'full', 'alt' => ['th' => 'หัวข้อ Google Analytics ในหน้าตั้งค่าระบบ', 'en' => 'The Google Analytics section of the settings page']],
            ['text', 'html' => [
                'th' => '<blockquote>MicroCMS มีรายงานยอดเข้าชมของตัวเองอยู่แล้ว (บทความ หน้าเพจ ป้ายโฆษณา) Google Analytics เหมาะเมื่อต้องการข้อมูลเชิงลึก เช่น ช่องทางที่มาหรือ conversion</blockquote>',
                'en' => '<blockquote>MicroCMS already has its own view reports for articles, pages and banners. Google Analytics is useful when you need deeper insight, such as traffic sources or conversions.</blockquote>',
            ]],
        ],
    ],
    [
        'key' => 'setup-smtp-with-gmail',
        'category' => 'external-services',
        'cover' => 'images/article/ext-smtp-gmail.jpg',
        'days_ago' => 12,
        'views' => 105,
        'tags' => ['guide', 'smtp', 'gmail', 'google'],
        'title' => ['th' => 'ตั้งค่า SMTP สำหรับส่งอีเมลด้วย Gmail', 'en' => 'Sending Email over SMTP with Gmail'],
        'intro' => [
            'th' => 'ให้ระบบส่งอีเมล เช่น ลิงก์รีเซ็ตรหัสผ่าน ผ่านบัญชี Gmail ด้วย App Password อย่างปลอดภัย',
            'en' => 'Let the system send email, such as password-reset links, through a Gmail account using an App Password.',
        ],
        'parts' => [
            ['image', 'file' => 'images/article/ext-smtp-gmail-steps.jpg', 'size' => 'full', 'alt' => ['th' => 'ขั้นตอนการตั้งค่า SMTP ด้วย Gmail', 'en' => 'SMTP with Gmail setup steps']],
            ['text', 'title' => ['th' => 'สร้าง App Password', 'en' => 'Create an App Password'], 'html' => [
                'th' => '<ol><li>เข้า <a href="https://myaccount.google.com/security" target="_blank">Google Account → Security</a> แล้วเปิด <strong>2-Step Verification</strong></li>'
                    .'<li>ไปที่ <a href="https://myaccount.google.com/apppasswords" target="_blank">App passwords</a> ตั้งชื่อ เช่น "MicroCMS" แล้วกด Create</li>'
                    .'<li>คัดลอกรหัส 16 ตัวอักษรที่ได้ (ใช้แทนรหัสผ่าน Gmail ปกติ)</li></ol>',
                'en' => '<ol><li>Open <a href="https://myaccount.google.com/security" target="_blank">Google Account → Security</a> and turn on <strong>2-Step Verification</strong>.</li>'
                    .'<li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">App passwords</a>, enter a name such as "MicroCMS" and click Create.</li>'
                    .'<li>Copy the 16-character password (it replaces your normal Gmail password).</li></ol>',
            ]],
            ['text', 'title' => ['th' => 'ค่าที่ต้องกรอกใน MicroCMS', 'en' => 'Values to enter in MicroCMS'], 'html' => [
                'th' => '<p>ไปที่ <strong>จัดการระบบ → ตั้งค่าระบบ</strong> หัวข้อ SMTP แล้วกรอก</p><ul>'
                    .'<li><strong>Host:</strong> <code>smtp.gmail.com</code></li><li><strong>Port:</strong> <code>587</code></li>'
                    .'<li><strong>การเข้ารหัส:</strong> TLS</li><li><strong>ใช้การยืนยันตัวตน:</strong> ใช่</li>'
                    .'<li><strong>Username:</strong> อีเมล Gmail ของคุณ</li><li><strong>Password:</strong> App Password 16 ตัวอักษร</li>'
                    .'<li><strong>ชื่อและอีเมลผู้ส่ง:</strong> เช่น "MicroCMS" และอีเมล Gmail เดียวกัน</li></ul>'
                    .'<p>บันทึกแล้วกด <strong>ทดสอบส่งอีเมล</strong> เพื่อตรวจสอบ รหัสผ่านถูกเก็บแบบเข้ารหัส และไม่แสดงกลับบนหน้าจอ</p>',
                'en' => '<p>Go to <strong>System → Settings</strong>, SMTP section, and enter:</p><ul>'
                    .'<li><strong>Host:</strong> <code>smtp.gmail.com</code></li><li><strong>Port:</strong> <code>587</code></li>'
                    .'<li><strong>Encryption:</strong> TLS</li><li><strong>Use authentication:</strong> yes</li>'
                    .'<li><strong>Username:</strong> your Gmail address</li><li><strong>Password:</strong> the 16-character App Password</li>'
                    .'<li><strong>Sender name and email:</strong> for example "MicroCMS" and the same Gmail address</li></ul>'
                    .'<p>Save, then click <strong>Send test email</strong> to check. The password is stored encrypted and never shown on screen again.</p>',
            ]],
            ['image', 'file' => 'images/guide/settings-smtp.jpg', 'size' => 'full', 'alt' => ['th' => 'หัวข้อ SMTP ในหน้าตั้งค่าระบบ', 'en' => 'The SMTP section of the settings page']],
            ['text', 'html' => [
                'th' => '<blockquote>Gmail ส่วนตัวจำกัดจำนวนอีเมลต่อวัน ถ้าเว็บไซต์ส่งอีเมลจำนวนมาก แนะนำบริการส่งอีเมลโดยเฉพาะหรือ Google Workspace</blockquote>',
                'en' => '<blockquote>Personal Gmail accounts have a daily sending limit. For high volumes, use a dedicated email service or Google Workspace.</blockquote>',
            ]],
        ],
    ],

    // ================================================================== ทั่วไป
    [
        'key' => 'introducing-microcms',
        'category' => 'general',
        'cover' => 'images/article/general-introducing.jpg',
        'days_ago' => 1,
        'views' => 312,
        'tags' => ['microcms', 'getting-started'],
        'title' => ['th' => 'แนะนำระบบ MicroCMS', 'en' => 'About MicroCMS'],
        'intro' => [
            'th' => 'รู้จัก MicroCMS ระบบจัดการเนื้อหาขนาดเล็กที่ติดตั้งง่าย ใช้งานง่าย พร้อมโมดูลที่เว็บไซต์องค์กรต้องใช้ครบในที่เดียว',
            'en' => 'Meet MicroCMS — a small, easy-to-install, easy-to-use content management system with everything an organization website needs in one place.',
        ],
        'parts' => [
            ['text', 'html' => [
                'th' => '<p><strong>MicroCMS</strong> เป็นระบบจัดการเนื้อหาเว็บไซต์ (Content Management System) ที่ออกแบบให้ <strong>ติดตั้งง่าย ใช้งานง่าย</strong> ผู้ดูแลเว็บไซต์ปรับเนื้อหา หน้าตา และเมนูได้เองทั้งหมดจากหลังบ้าน โดยไม่ต้องมีความรู้ด้านการเขียนโปรแกรม</p>'
                    .'<h2>โมดูลหลัก</h2><ul>'
                    .'<li><strong>บทความ</strong> — ข่าวสาร ประกาศ และความรู้ แบ่งหมวดหมู่และแท็ก</li>'
                    .'<li><strong>หน้าเพจ</strong> — หน้าเว็บที่จัดโครงสร้างเองด้วยแถว คอลัมน์ และ widget</li>'
                    .'<li><strong>ป้ายโฆษณา</strong> — ภาพประชาสัมพันธ์ตามตำแหน่ง พร้อมนับคลิก</li>'
                    .'<li><strong>Intropage และ Popup</strong> — หน้าต้อนรับและหน้าต่างแจ้งข่าว</li>'
                    .'<li><strong>ติดต่อเรา</strong> — ข้อมูลติดต่อ แผนที่ และแบบฟอร์มที่ป้องกันสแปม</li>'
                    .'<li><strong>จัดการระบบ</strong> — ผู้ใช้งาน สิทธิ์ เมนู Template คลังไฟล์ และประวัติการใช้งาน</li></ul>',
                'en' => '<p><strong>MicroCMS</strong> is a website content management system designed to be <strong>easy to install and easy to use</strong>. Administrators manage content, appearance and menus entirely from the back office, with no programming knowledge required.</p>'
                    .'<h2>Core modules</h2><ul>'
                    .'<li><strong>Articles</strong> — news, announcements and knowledge, with categories and tags.</li>'
                    .'<li><strong>Pages</strong> — web pages you lay out yourself with rows, columns and widgets.</li>'
                    .'<li><strong>Banners</strong> — promotional images by position, with click counting.</li>'
                    .'<li><strong>Intro page and popups</strong> — a welcome screen and announcement windows.</li>'
                    .'<li><strong>Contact Us</strong> — contact details, a map and a spam-protected form.</li>'
                    .'<li><strong>System</strong> — users, permissions, menus, templates, the file library and activity history.</li></ul>',
            ]],
            ['images', 'title' => ['th' => 'ภาพรวมโมดูล', 'en' => 'Modules at a glance'], 'display' => 'justified_grid', 'files' => [
                ['images/article/module-article.jpg', ['th' => 'บทความ', 'en' => 'Articles']],
                ['images/article/module-page.jpg', ['th' => 'หน้าเพจ', 'en' => 'Pages']],
                ['images/article/module-banner.jpg', ['th' => 'ป้ายโฆษณา', 'en' => 'Banners']],
                ['images/article/module-popup.jpg', ['th' => 'Popup', 'en' => 'Popups']],
                ['images/article/module-intropage.jpg', ['th' => 'Intropage', 'en' => 'Intro page']],
                ['images/article/module-contactus.jpg', ['th' => 'ติดต่อเรา', 'en' => 'Contact Us']],
            ]],
            ['text', 'title' => ['th' => 'จุดเด่น', 'en' => 'Highlights'], 'html' => [
                'th' => '<ul><li>รองรับภาษาไทยและภาษาอังกฤษ พร้อม SEO, sitemap และข้อมูลโครงสร้างอัตโนมัติ</li>'
                    .'<li>เข้าถึงได้ง่าย (WCAG) — ปรับขนาดตัวอักษรและโหมดสีได้ที่ส่วนหัวของเว็บไซต์</li>'
                    .'<li>รูปภาพย่อขนาดและแปลงเป็น WebP อัตโนมัติ และแคชหน้าเว็บไซต์เพื่อความเร็ว</li>'
                    .'<li>บันทึกประวัติการเข้าสู่ระบบ การใช้งาน และการกระทำทุกรายการ</li></ul>'
                    .'<p>ดาวน์โหลดคู่มือเริ่มต้นใช้งานด้านล่าง หรืออ่านขั้นตอนละเอียดในหมวด <strong>การใช้งานระบบ</strong></p>',
                'en' => '<ul><li>Thai and English, with automatic SEO, sitemap and structured data.</li>'
                    .'<li>Accessible (WCAG) — font size and color modes are available in the website header.</li>'
                    .'<li>Images are resized and converted to WebP automatically, and pages are cached for speed.</li>'
                    .'<li>Every sign-in, page visit and action is logged.</li></ul>'
                    .'<p>Download the quick start guide below, or read the detailed steps in the <strong>User Guide</strong> category.</p>',
            ]],
            ['documents', 'title' => ['th' => 'คู่มือเริ่มต้นใช้งาน', 'en' => 'Quick start guide'], 'files' => [
                'files/microcms-quick-start-th.pdf',
                'files/microcms-quick-start-en.pdf',
            ]],
        ],
    ],
];
