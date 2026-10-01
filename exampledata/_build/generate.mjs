// สร้างภาพตัวอย่างทั้งหมด → exampledata/_build/out/*.png (แล้วรัน convert.php เพื่อแปลง/ย้ายไป exampledata/images)
// ใช้: node exampledata/_build/generate.mjs [ตัวกรองชื่อ]
import { render, renderPdf } from './lib.mjs';
import * as T from './templates.mjs';
import { quickStart } from './pdf.mjs';

const only = process.argv[2] ?? '';

/** [ชื่อไฟล์ (ไม่มีนามสกุล), w, h, html, options] */
const jobs = [];
const add = (name, w, h, html, opts = {}) => jobs.push([name, w, h, html, opts]);

// ---------------------------------------------------------------- site
// โลโก้ = สัญลักษณ์อย่างเดียว (ชื่อเว็บไซต์ระบบแสดงเป็นตัวอักษรตามสีของแต่ละโซน — อ่านได้ทั้งพื้นสว่างและพื้นเข้ม)
add('site/logo', 256, 256, T.logo({ w: 256, h: 256, mark: true }), { transparent: true });
add('site/logo-mark', 256, 256, T.logo({ w: 256, h: 256, mark: true }), { transparent: true });
add('site/avatar-admin', 400, 400, T.avatar());

// ---------------------------------------------------------------- article covers (1200×675)
const covers = {
    'cat-news': ['navy', 'newspaper', 'grid'],
    'cat-user-guide': ['sky', 'book-open-text', 'dashboard'],
    'cat-external-services': ['indigo', 'plug-zap', 'form'],
    'cat-general': ['ocean', 'info', 'article'],
    'news-launch': ['navy', 'rocket', 'dashboard'],
    'news-page-builder': ['ocean', 'layout-template', 'layout'],
    'news-bilingual-seo': ['cyan', 'languages', 'globe'],
    'news-security-tips': ['indigo', 'shield-check', 'shield'],
    'news-roadmap': ['sky', 'map', 'roadmap'],
    'guide-article': ['navy', 'newspaper', 'article'],
    'guide-page': ['ocean', 'layout-dashboard', 'layout'],
    'guide-intropage': ['cyan', 'door-open', 'intro'],
    'guide-banner': ['sky', 'gallery-horizontal-end', 'slider'],
    'guide-popup': ['indigo', 'message-square-more', 'popup'],
    'ext-turnstile': ['indigo', 'shield-check', 'form'],
    'ext-google-map': ['ocean', 'map-pin', 'map'],
    'ext-google-analytics': ['cyan', 'chart-line', 'chart'],
    'ext-smtp-gmail': ['navy', 'mail', 'mail'],
    'general-introducing': ['navy', 'layers', 'dashboard'],
};
for (const [name, [theme, iconName, kind]] of Object.entries(covers)) {
    add(`article/${name}`, 1200, 675, T.cover({ theme, iconName, kind }));
}

// ---------------------------------------------------------------- module illustrations (1200×800) ใช้ในกลุ่มรูป
const modules = {
    'module-article': ['navy', 'newspaper', 'article'],
    'module-page': ['ocean', 'layout-dashboard', 'layout'],
    'module-banner': ['sky', 'gallery-horizontal-end', 'slider'],
    'module-popup': ['indigo', 'message-square-more', 'popup'],
    'module-intropage': ['cyan', 'door-open', 'intro'],
    'module-contactus': ['ocean', 'contact', 'form'],
    'module-report': ['navy', 'chart-column', 'chart'],
    'module-language': ['cyan', 'languages', 'globe'],
};
for (const [name, [theme, iconName, kind]] of Object.entries(modules)) {
    add(`article/${name}`, 1200, 800, T.moduleShot({ theme, iconName, kind }));
}

// ---------------------------------------------------------------- step diagrams ของบริการภายนอก (1200×675)
add(
    'article/ext-turnstile-steps',
    1200,
    675,
    T.steps({
        theme: 'indigo',
        title: 'Cloudflare Turnstile',
        iconName: 'shield-check',
        items: [
            ['log-in', 'Sign in to Cloudflare', 'dash.cloudflare.com'],
            ['circle-plus', 'Turnstile → Add widget', 'Hostname = your domain'],
            ['key-round', 'Copy Site Key & Secret Key', 'Widget mode: Managed'],
            ['settings', 'Paste in MicroCMS', 'Settings → Turnstile'],
        ],
    }),
);
add(
    'article/ext-google-map-steps',
    1200,
    675,
    T.steps({
        theme: 'ocean',
        title: 'Google Maps Embed API',
        iconName: 'map-pin',
        items: [
            ['folder-plus', 'Create a project', 'console.cloud.google.com'],
            ['toggle-right', 'Enable Maps Embed API', 'APIs & Services → Library'],
            ['key-round', 'Create API key', 'Restrict: HTTP referrers'],
            ['settings', 'Paste in MicroCMS', 'Settings → Google Map'],
        ],
    }),
);
add(
    'article/ext-google-analytics-steps',
    1200,
    675,
    T.steps({
        theme: 'cyan',
        title: 'Google Analytics 4',
        iconName: 'chart-line',
        items: [
            ['circle-plus', 'Create a GA4 property', 'analytics.google.com'],
            ['globe', 'Add a Web data stream', 'Enter your website URL'],
            ['copy', 'Copy Measurement ID', 'Format: G-XXXXXXXXXX'],
            ['settings', 'Paste in MicroCMS', 'Settings → Google Analytics'],
        ],
    }),
);
add(
    'article/ext-smtp-gmail-steps',
    1200,
    675,
    T.steps({
        theme: 'navy',
        title: 'SMTP with Gmail',
        iconName: 'mail',
        items: [
            ['shield-check', 'Turn on 2-Step Verification', 'myaccount.google.com'],
            ['key-round', 'Create an App Password', '16-character password'],
            ['server', 'smtp.gmail.com : 587', 'Encryption: TLS'],
            ['send', 'Send a test email', 'Settings → SMTP'],
        ],
    }),
);

// ---------------------------------------------------------------- banner (1920×823 = 21:9)
add('banner/banner-welcome', 1920, 823, T.banner({ theme: 'navy', iconName: 'layers', kind: 'dashboard', floats: ['sparkles', 'circle-check'] }));
add('banner/banner-news', 1920, 823, T.banner({ theme: 'ocean', iconName: 'newspaper', kind: 'grid', floats: ['bell', 'calendar'] }));
add('banner/banner-user-guide', 1920, 823, T.banner({ theme: 'sky', iconName: 'book-open-text', kind: 'layout', floats: ['mouse-pointer-click', 'list-checks'] }));
add('banner/banner-external-services', 1920, 823, T.banner({ theme: 'indigo', iconName: 'plug-zap', kind: 'form', floats: ['mail', 'map-pin'] }));

// ---------------------------------------------------------------- intropage / popup
add('intropage/welcome', 1920, 1080, T.welcome({ w: 1920, h: 1080 }));
add('popup/welcome', 1000, 560, T.welcome({ w: 1000, h: 560, theme: 'ocean', compact: true }));

// ---------------------------------------------------------------- ส่วนหัวของเมนู (1920×360)
add('menu/header-introducing', 1920, 360, T.header({ theme: 'navy', icons: ['layers', 'sparkles', 'info'] }));
add('menu/header-news', 1920, 360, T.header({ theme: 'ocean', icons: ['newspaper', 'bell', 'calendar'] }));
add('menu/header-user-guide', 1920, 360, T.header({ theme: 'sky', icons: ['book-open-text', 'mouse-pointer-click', 'list-checks'] }));
add('menu/header-external-services', 1920, 360, T.header({ theme: 'indigo', icons: ['plug-zap', 'mail', 'map-pin'] }));
add('menu/header-contactus', 1920, 360, T.header({ theme: 'cyan', icons: ['phone', 'mail', 'map-pin'] }));

// ---------------------------------------------------------------- หน้าเพจ
add('page/home-pattern', 1920, 700, T.softPattern({ w: 1920, h: 700 }));
add('page/home-feature', 1000, 750, T.featureCollage());
add('page/home-og', 1200, 630, T.ogImage());

// ---------------------------------------------------------------- ติดต่อเรา
add('contactus/map', 1200, 800, `<!doctype html><html><head><meta charset="utf-8"><link href="https://fonts.googleapis.com/css2?family=Prompt:wght@600&display=block" rel="stylesheet"><style>html,body{margin:0;overflow:hidden}</style></head><body>${T.mapSvg(1200, 800, 'navy', 'MicroCMS')}</body></html>`);

let done = 0;
for (const [name, w, h, html, opts] of jobs) {
    if (only && !name.includes(only)) continue;
    render(name, w, h, html, opts);
    done++;
    process.stdout.write(`✓ ${name}\n`);
}

if (!only || 'quick-start'.includes(only)) {
    renderPdf('files/microcms-quick-start-th', quickStart('th'));
    renderPdf('files/microcms-quick-start-en', quickStart('en'));
    process.stdout.write('✓ files/microcms-quick-start-{th,en}.pdf\n');
}

process.stdout.write(`${done} images\n`);
