// ตัวช่วยของสคริปต์ generate ภาพตัวอย่าง — อ่านไอคอน lucide (ISC) จาก node_modules แล้ว render HTML ด้วย Edge headless
import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

export const BUILD_DIR = dirname(fileURLToPath(import.meta.url));
export const ROOT = resolve(BUILD_DIR, '..', '..');
export const OUT_DIR = join(BUILD_DIR, 'out');

const EDGE_CANDIDATES = [
    process.env.EDGE_PATH,
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
].filter(Boolean);

export const BROWSER = EDGE_CANDIDATES.find((p) => existsSync(p));

/** ไอคอน lucide เป็น SVG string (stroke = currentColor) */
export function icon(name, { size = 48, stroke = 1.75, color = 'currentColor' } = {}) {
    const file = join(ROOT, 'node_modules/lucide-vue-next/dist/esm/icons', `${name}.js`);
    const src = readFileSync(file, 'utf8');
    const match = src.match(/createLucideIcon\("[^"]+",\s*(\[[\s\S]*\])\);/);
    if (!match) throw new Error(`lucide icon parse failed: ${name}`);
    const nodes = Function(`return ${match[1]}`)();
    const inner = nodes
        .map(([tag, attrs]) => {
            const a = Object.entries(attrs)
                .filter(([k]) => k !== 'key')
                .map(([k, v]) => `${k}="${v}"`)
                .join(' ');
            return `<${tag} ${a}/>`;
        })
        .join('');
    return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="${color}" stroke-width="${stroke}" stroke-linecap="round" stroke-linejoin="round">${inner}</svg>`;
}

export const FONTS =
    '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' +
    '<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=block" rel="stylesheet">';

export function page(w, h, body, css = '', { transparent = false } = {}) {
    return `<!doctype html><html><head><meta charset="utf-8">${FONTS}<style>
*{box-sizing:border-box}html,body{margin:0;padding:0;width:${w}px;height:${h}px;overflow:hidden;${transparent ? 'background:transparent' : ''}}
body{font-family:'Prompt','Sarabun','Leelawadee UI',sans-serif;-webkit-font-smoothing:antialiased}
${css}</style></head><body>${body}</body></html>`;
}

/** render HTML → PNG ด้วย browser headless (ขนาดตาม w×h) */
export function render(name, w, h, html, { transparent = false } = {}) {
    if (!BROWSER) throw new Error('ไม่พบ Microsoft Edge / Chrome — ตั้ง EDGE_PATH');
    const htmlDir = join(OUT_DIR, '_html');
    mkdirSync(htmlDir, { recursive: true });
    const safe = name.replace(/[\\/]/g, '__');
    const htmlPath = join(htmlDir, `${safe}.html`);
    const pngPath = join(OUT_DIR, `${name}.png`);
    mkdirSync(dirname(pngPath), { recursive: true });
    writeFileSync(htmlPath, html);
    const args = [
        '--headless=new',
        '--disable-gpu',
        '--hide-scrollbars',
        '--force-device-scale-factor=1',
        `--window-size=${w},${h}`,
        '--virtual-time-budget=6000',
        `--screenshot=${pngPath}`,
    ];
    if (transparent) args.push('--default-background-color=00000000');
    args.push(pathToFileURL(htmlPath).href);
    execFileSync(BROWSER, args, { stdio: 'ignore' });
    if (!existsSync(pngPath)) throw new Error(`render failed: ${name}`);
    return pngPath;
}

/** HTML → PDF (A4) */
export function renderPdf(name, html) {
    const htmlDir = join(OUT_DIR, '_html');
    mkdirSync(htmlDir, { recursive: true });
    const htmlPath = join(htmlDir, `${name.replace(/[\\/]/g, '__')}.html`);
    const pdfPath = join(OUT_DIR, `${name}.pdf`);
    mkdirSync(dirname(pdfPath), { recursive: true });
    writeFileSync(htmlPath, html);
    execFileSync(
        BROWSER,
        ['--headless=new', '--disable-gpu', '--no-pdf-header-footer', '--virtual-time-budget=6000', `--print-to-pdf=${pdfPath}`, pathToFileURL(htmlPath).href],
        { stdio: 'ignore' },
    );
    return pdfPath;
}
