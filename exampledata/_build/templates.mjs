// แม่แบบภาพประกอบ (HTML) — โทนน้ำเงิน, รูปทรงเรียบ ๆ + mockup หน้าจอแบบ skeleton, ไม่ใช้ภาพของบุคคลที่สาม
import { icon, page } from './lib.mjs';

export const THEMES = {
    navy: ['#0B1F44', '#1E3A8A', '#2563EB', '#60A5FA'],
    sky: ['#082F49', '#0369A1', '#0EA5E9', '#7DD3FC'],
    indigo: ['#1E1B4B', '#3730A3', '#6366F1', '#A5B4FC'],
    ocean: ['#0F2A5C', '#1D4ED8', '#38BDF8', '#BAE6FD'],
    cyan: ['#0C2D48', '#0E5E8C', '#22A6D9', '#A5E4F7'],
};

const BASE_CSS = `
.bg{position:absolute;inset:0}
.dots{position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.13) 1.3px,transparent 1.3px);background-size:30px 30px}
.glow{position:absolute;border-radius:50%;filter:blur(70px)}
.win{position:absolute;background:#fff;border-radius:18px;box-shadow:0 30px 70px rgba(2,12,40,.35),0 6px 18px rgba(2,12,40,.18);overflow:hidden}
.win .bar{height:38px;background:#F1F5F9;display:flex;align-items:center;gap:7px;padding:0 14px;border-bottom:1px solid #E2E8F0}
.win .bar i{width:11px;height:11px;border-radius:50%;background:#CBD5E1;display:block}
.win .bar .url{margin-left:14px;height:16px;flex:1;max-width:60%;border-radius:8px;background:#E2E8F0}
.win .body{position:absolute;top:38px;left:0;right:0;bottom:0;padding:22px}
.sk{background:#E2E8F0;border-radius:6px;height:12px;margin:0 0 10px}
.sk.d{background:#CBD5E1}
.card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden}
.tile{position:absolute;border-radius:36px;background:rgba(255,255,255,.12);border:1.5px solid rgba(255,255,255,.28);display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:0 20px 50px rgba(0,0,0,.18);backdrop-filter:blur(6px)}
.chip{position:absolute;height:14px;border-radius:7px;background:rgba(255,255,255,.28)}
.float{position:absolute;background:#fff;border-radius:14px;box-shadow:0 18px 40px rgba(2,12,40,.28);padding:14px 16px;display:flex;align-items:center;gap:12px;color:#0F172A;font-weight:500}
.float .ic{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;color:#fff}
`;

function bg(t, angle = 135) {
    const [c0, c1, c2, c3] = THEMES[t];
    return `<div class="bg" style="background:linear-gradient(${angle}deg,${c0} 0%,${c1} 52%,${c2} 100%)"></div>
<div class="glow" style="width:520px;height:520px;left:-120px;bottom:-200px;background:${c3};opacity:.35"></div>
<div class="glow" style="width:460px;height:460px;right:-80px;top:-160px;background:${c2};opacity:.55"></div>
<div class="dots"></div>`;
}

function accentGrad(t) {
    const [, c1, c2, c3] = THEMES[t];
    return `linear-gradient(135deg,${c3} 0%,${c2} 60%,${c1} 100%)`;
}

const lines = (widths, dark = false) => widths.map((w) => `<div class="sk${dark ? ' d' : ''}" style="width:${w}%"></div>`).join('');

/** เนื้อหาในหน้าต่าง mockup แต่ละแบบ (w,h = พื้นที่ body) */
export function mockup(kind, t, w, h) {
    const a = accentGrad(t);
    const [, c1, c2, c3] = THEMES[t];
    switch (kind) {
        case 'article':
            return `<div style="display:flex;gap:20px;height:100%">
<div style="flex:1.2"><div style="height:${h * 0.45}px;border-radius:12px;background:${a};margin-bottom:16px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.9)">${icon('image', { size: 54 })}</div>${lines([90, 100, 96, 70])}${lines([100, 84, 60])}</div>
<div style="flex:.6">${[0, 1, 2].map(() => `<div style="display:flex;gap:10px;margin-bottom:14px"><div style="width:58px;height:44px;border-radius:8px;background:${c3};opacity:.7"></div><div style="flex:1">${lines([100, 70])}</div></div>`).join('')}</div></div>`;
        case 'grid':
            return `<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">${[0, 1, 2, 3, 4, 5]
                .map((i) => `<div class="card"><div style="height:${h * 0.22}px;background:${i % 2 ? c3 : a};opacity:${i % 2 ? 0.75 : 1}"></div><div style="padding:10px">${lines([90, 60])}</div></div>`)
                .join('')}</div>`;
        case 'layout':
            return `<div style="display:flex;flex-direction:column;gap:12px;height:100%">
<div style="flex:1.1;border:2px dashed ${c2};border-radius:10px;background:${c3}33;display:flex;align-items:center;justify-content:center;color:${c1}">${icon('gallery-horizontal', { size: 40 })}</div>
<div style="flex:1;display:flex;gap:12px"><div style="flex:7;border:2px dashed #94A3B8;border-radius:10px;padding:12px">${lines([60, 100, 90, 70])}</div><div style="flex:5;border:2px dashed #94A3B8;border-radius:10px;background:${c3}55"></div></div>
<div style="flex:.9;display:flex;gap:12px">${[0, 1, 2].map(() => `<div style="flex:1;border:2px dashed #94A3B8;border-radius:10px;padding:10px">${lines([80, 50])}</div>`).join('')}</div></div>`;
        case 'slider':
            return `<div style="position:relative;height:${h * 0.72}px;border-radius:12px;background:${a};overflow:hidden">
<div style="position:absolute;left:28px;bottom:30px;width:45%">${[80, 55].map((w2) => `<div class="sk" style="width:${w2}%;background:rgba(255,255,255,.78)"></div>`).join('')}</div>
<div style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.85);display:flex;align-items:center;justify-content:center;color:${c1}">${icon('chevron-left', { size: 20, stroke: 2.5 })}</div>
<div style="position:absolute;right:12px;top:50%;transform:translateY(-50%);width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.85);display:flex;align-items:center;justify-content:center;color:${c1}">${icon('chevron-right', { size: 20, stroke: 2.5 })}</div>
<div style="position:absolute;bottom:12px;left:50%;transform:translateX(-50%);display:flex;gap:7px">${[1, 0, 0, 0].map((on) => `<i style="display:block;width:${on ? 22 : 8}px;height:8px;border-radius:4px;background:rgba(255,255,255,${on ? 1 : 0.6})"></i>`).join('')}</div></div>
<div style="display:flex;gap:12px;margin-top:14px">${[0, 1, 2, 3].map(() => `<div style="flex:1;height:${h * 0.14}px;border-radius:8px;background:${c3};opacity:.6"></div>`).join('')}</div>`;
        case 'popup':
            return `<div style="position:absolute;inset:0;padding:22px">${lines([40, 100, 92, 96, 80, 100, 70, 90])}</div>
<div style="position:absolute;inset:0;background:rgba(15,23,42,.45)"></div>
<div style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:${w * 0.62}px;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,.35)">
<div style="height:${h * 0.38}px;background:${a};display:flex;align-items:center;justify-content:center;color:#fff">${icon('party-popper', { size: 52 })}</div>
<div style="padding:16px">${lines([70, 95, 55])}<div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px"><div class="sk" style="width:38%;margin:0"></div><div style="width:84px;height:28px;border-radius:8px;background:${c2}"></div></div></div>
<div style="position:absolute;right:10px;top:10px;width:26px;height:26px;border-radius:50%;background:rgba(255,255,255,.9);display:flex;align-items:center;justify-content:center;color:#334155">${icon('x', { size: 16, stroke: 2.5 })}</div></div>`;
        case 'intro':
            return `<div style="position:absolute;inset:0;background:${a};display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px">
<div style="color:#fff">${icon('sparkles', { size: 64 })}</div>
<div style="width:46%;height:16px;border-radius:8px;background:rgba(255,255,255,.85)"></div><div style="width:30%;height:12px;border-radius:6px;background:rgba(255,255,255,.6)"></div>
<div style="display:flex;gap:12px;margin-top:10px"><div style="width:120px;height:36px;border-radius:18px;background:#fff"></div><div style="width:120px;height:36px;border-radius:18px;border:2px solid #fff"></div></div></div>`;
        case 'form':
            return `<div style="display:flex;gap:22px;height:100%"><div style="flex:1">${['', '', ''].map(() => `<div class="sk d" style="width:30%;height:9px;margin-bottom:7px"></div><div style="height:34px;border:1.5px solid #CBD5E1;border-radius:8px;margin-bottom:14px"></div>`).join('')}
<div style="display:flex;align-items:center;gap:10px;height:52px;border:1.5px solid #CBD5E1;border-radius:8px;padding:0 12px;background:#F8FAFC;margin-bottom:14px"><div style="width:24px;height:24px;border-radius:50%;background:#16A34A;color:#fff;display:flex;align-items:center;justify-content:center">${icon('check', { size: 16, stroke: 3 })}</div><div class="sk d" style="width:28%;margin:0"></div><div style="margin-left:auto;color:${c2}">${icon('shield-check', { size: 26 })}</div></div>
<div style="width:140px;height:38px;border-radius:9px;background:${c2}"></div></div>
<div style="width:36%;border-radius:12px;background:${c3}44;padding:16px">${lines([70, 100, 90, 60, 85])}</div></div>`;
        case 'map':
            return `<div style="position:absolute;inset:0">${mapSvg(w, h + 38, t)}</div>`;
        case 'chart':
            return `<div style="display:flex;gap:12px;margin-bottom:16px">${[0, 1, 2].map((i) => `<div class="card" style="flex:1;padding:12px"><div class="sk" style="width:50%;height:9px"></div><div style="height:20px;width:${60 - i * 10}%;border-radius:6px;background:${i === 0 ? c2 : c3}"></div></div>`).join('')}</div>
<div class="card" style="padding:14px;height:${h * 0.58}px;display:flex;align-items:flex-end;gap:10px">${[38, 52, 44, 68, 60, 82, 74, 92, 70, 86]
                .map((v, i) => `<div style="flex:1;height:${v}%;border-radius:6px 6px 2px 2px;background:${i % 3 === 2 ? c2 : c3}"></div>`)
                .join('')}</div>`;
        case 'mail':
            return `<div style="display:flex;gap:18px;height:100%"><div style="width:30%">${lines([80, 60, 70, 50, 66])}</div><div style="flex:1">${[0, 1, 2, 3]
                .map((i) => `<div class="card" style="display:flex;gap:12px;align-items:center;padding:12px;margin-bottom:12px"><div style="width:38px;height:38px;border-radius:50%;background:${i === 0 ? c2 : c3};color:#fff;display:flex;align-items:center;justify-content:center">${icon('mail', { size: 20 })}</div><div style="flex:1">${lines([50, 90])}</div></div>`)
                .join('')}</div></div>`;
        case 'shield':
            return `<div style="display:flex;gap:24px;align-items:center;height:100%"><div style="width:38%;display:flex;justify-content:center;color:${c2}">${icon('shield-check', { size: 150, stroke: 1.4 })}</div><div style="flex:1">${[0, 1, 2, 3, 4]
                .map((i) => `<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px"><div style="width:26px;height:26px;border-radius:7px;background:${i < 4 ? '#16A34A' : '#CBD5E1'};color:#fff;display:flex;align-items:center;justify-content:center">${icon('check', { size: 16, stroke: 3 })}</div><div class="sk" style="flex:1;margin:0;width:${90 - i * 8}%"></div></div>`)
                .join('')}</div></div>`;
        case 'globe':
            return `<div style="display:flex;gap:22px;height:100%"><div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:${c2}">${icon('globe', { size: 130, stroke: 1.3 })}<div style="display:flex;gap:10px;margin-top:18px"><div style="padding:6px 16px;border-radius:16px;background:${c2};color:#fff;font-weight:600">TH</div><div style="padding:6px 16px;border-radius:16px;border:2px solid ${c2};color:${c2};font-weight:600">EN</div></div></div>
<div style="flex:1;padding-top:10px">${tree(c2, c3)}</div></div>`;
        case 'roadmap':
            return `<div style="position:relative;height:100%;padding-top:20px"><div style="position:absolute;left:30px;top:20px;bottom:10px;width:3px;background:${c3}"></div>${[0, 1, 2, 3]
                .map((i) => `<div style="position:relative;display:flex;gap:18px;align-items:center;margin-bottom:${h * 0.07}px"><div style="width:22px;height:22px;margin-left:20px;border-radius:50%;background:${i === 0 ? c2 : '#fff'};border:3px solid ${c2};z-index:1"></div><div class="card" style="flex:1;padding:12px">${lines([40 + i * 10, 80])}</div></div>`)
                .join('')}</div>`;
        case 'dashboard':
        default:
            return `<div style="display:flex;gap:16px;height:100%"><div style="width:22%;border-radius:10px;background:#1E293B;padding:14px">${[0, 1, 2, 3, 4, 5].map((i) => `<div style="height:10px;border-radius:5px;background:${i === 1 ? c3 : '#475569'};margin-bottom:14px;width:${80 - (i % 3) * 12}%"></div>`).join('')}</div>
<div style="flex:1"><div style="display:flex;gap:12px;margin-bottom:14px">${[0, 1, 2].map((i) => `<div class="card" style="flex:1;padding:12px"><div class="sk" style="width:55%;height:9px"></div><div style="height:18px;width:${70 - i * 12}%;border-radius:5px;background:${i ? c3 : c2}"></div></div>`).join('')}</div>
<div class="card" style="height:${h * 0.7}px;padding:14px">${lineChart(c2, c3)}</div></div></div>`;
    }
}

function tree(c2, c3) {
    const node = (lvl, w, strong) =>
        `<div style="display:flex;align-items:center;gap:8px;margin:0 0 13px ${lvl * 26}px"><div style="width:14px;height:14px;border-radius:4px;background:${strong ? c2 : c3}"></div><div class="sk" style="width:${w}%;margin:0"></div></div>`;
    return node(0, 50, true) + node(1, 60) + node(1, 45) + node(2, 50) + node(2, 40) + node(1, 55) + node(0, 45, true) + node(1, 50);
}

function lineChart(c2, c3) {
    return `<svg width="100%" height="100%" viewBox="0 0 400 160" preserveAspectRatio="none"><defs><linearGradient id="lg" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="${c3}" stop-opacity=".7"/><stop offset="1" stop-color="${c3}" stop-opacity="0"/></linearGradient></defs>
<path d="M0 130 C40 110 70 120 100 95 S160 70 200 80 S270 40 310 50 S370 20 400 15 L400 160 L0 160Z" fill="url(#lg)"/><path d="M0 130 C40 110 70 120 100 95 S160 70 200 80 S270 40 310 50 S370 20 400 15" fill="none" stroke="${c2}" stroke-width="3"/></svg>`;
}

export function mapSvg(w, h, t = 'navy', label = '') {
    const [, c1, c2] = THEMES[t];
    return `<svg width="${w}" height="${h}" viewBox="0 0 1200 800" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
<rect width="1200" height="800" fill="#EEF3FA"/>
<g fill="#DCEBDD"><rect x="70" y="80" width="230" height="170" rx="18"/><rect x="820" y="520" width="280" height="200" rx="18"/><circle cx="980" cy="170" r="90"/></g>
<path d="M-20 600 C200 560 300 650 520 610 S860 470 1220 520" fill="none" stroke="#BFDBFE" stroke-width="46"/>
<g fill="#E2E8F0">${Array.from({ length: 6 }, (_, r) => Array.from({ length: 9 }, (_, c) => `<rect x="${360 + c * 50 - (r % 2) * 20}" y="${110 + r * 62}" width="36" height="44" rx="6"/>`).join('')).join('')}</g>
<g stroke="#FFFFFF" stroke-linecap="round" fill="none"><path d="M0 330 H1200" stroke-width="30"/><path d="M640 0 V800" stroke-width="30"/><path d="M0 120 L1200 760" stroke-width="18"/><path d="M180 0 V800" stroke-width="16"/><path d="M0 470 H1200" stroke-width="14"/><path d="M960 0 V800" stroke-width="14"/></g>
<g stroke="#CBD5E1" stroke-width="2" fill="none"><path d="M0 330 H1200" stroke-dasharray="14 12"/><path d="M640 0 V800" stroke-dasharray="14 12"/></g>
<circle cx="640" cy="330" r="80" fill="${c2}" fill-opacity=".12"/><circle cx="640" cy="330" r="40" fill="${c2}" fill-opacity=".18"/>
<path d="M640 330 C640 330 590 270 590 238 A50 50 0 0 1 690 238 C690 270 640 330 640 330Z" fill="${c1}" stroke="#fff" stroke-width="5"/><circle cx="640" cy="238" r="18" fill="#fff"/>
${label ? `<g><rect x="${640 - label.length * 10 - 24}" y="350" width="${label.length * 20 + 48}" height="54" rx="27" fill="#fff" stroke="${c2}" stroke-width="2"/><text x="640" y="386" text-anchor="middle" font-family="Prompt,sans-serif" font-size="26" font-weight="600" fill="${c1}">${label}</text></g>` : ''}
</svg>`;
}

function win(x, y, w, h, kind, t, extra = '') {
    return `<div class="win" style="left:${x}px;top:${y}px;width:${w}px;height:${h}px;${extra}"><div class="bar"><i></i><i></i><i></i><div class="url"></div></div><div class="body">${mockup(kind, t, w - 44, h - 82)}</div></div>`;
}

/** รูปปก 1200×675 (16:9) — ไม่มีตัวอักษร ใช้ได้ทุกภาษา */
export function cover({ theme = 'navy', iconName, kind }) {
    return page(1200, 675, `${bg(theme)}
<div class="tile" style="left:96px;top:170px;width:250px;height:250px">${icon(iconName, { size: 128, stroke: 1.5 })}</div>
<div class="chip" style="left:96px;top:452px;width:200px"></div><div class="chip" style="left:96px;top:480px;width:140px;opacity:.6"></div>
${win(430, 110, 680, 460, kind, theme)}`, BASE_CSS);
}

/** banner 1920×823 (21:9) — ซ้ายว่างไว้ให้ข้อความของ slideshow */
export function banner({ theme = 'navy', iconName, kind, floats = [] }) {
    const [, , c2] = THEMES[theme];
    return page(1920, 823, `${bg(theme, 120)}
<div style="position:absolute;inset:0;background:linear-gradient(90deg,rgba(2,12,40,.55) 0%,rgba(2,12,40,.15) 45%,rgba(2,12,40,0) 60%)"></div>
${win(1010, 150, 760, 520, kind, theme)}
<div class="tile" style="left:810px;top:540px;width:170px;height:170px;border-radius:30px">${icon(iconName, { size: 86, stroke: 1.5 })}</div>
${floats.map((f, i) => `<div class="float" style="${i === 0 ? 'left:1600px;top:90px' : 'left:1640px;top:600px'}"><div class="ic" style="background:${c2}">${icon(f, { size: 24 })}</div><div><div class="sk" style="width:110px;margin-bottom:7px"></div><div class="sk d" style="width:70px;margin:0"></div></div></div>`).join('')}`, BASE_CSS);
}

/** ภาพส่วนหัวของเมนู 1920×360 — ข้อความหัวเรื่องมาจากระบบ */
export function header({ theme = 'navy', icons = [] }) {
    const [, , c2, c3] = THEMES[theme];
    return page(1920, 360, `${bg(theme, 100)}
<svg style="position:absolute;inset:0" width="1920" height="360" viewBox="0 0 1920 360"><path d="M0 290 C320 230 560 330 900 280 S1500 190 1920 250 V360 H0Z" fill="${c3}" fill-opacity=".14"/><path d="M0 320 C380 280 700 360 1040 320 S1600 260 1920 300 V360 H0Z" fill="${c2}" fill-opacity=".22"/></svg>
${icons.map((n, i) => `<div style="position:absolute;right:${120 + i * 210}px;top:${70 + (i % 2) * 70}px;color:rgba(255,255,255,${0.2 - i * 0.03});transform:rotate(${i % 2 ? 8 : -8}deg)">${icon(n, { size: 170 - i * 20, stroke: 1.2 })}</div>`).join('')}`, BASE_CSS + '.glow{opacity:.18!important}');
}

/** ภาพประกอบโมดูล 1200×800 พื้นสว่าง — ใช้ในกลุ่มรูป */
export function moduleShot({ theme = 'navy', iconName, kind }) {
    const [, c1, c2, c3] = THEMES[theme];
    return page(1200, 800, `<div class="bg" style="background:linear-gradient(160deg,#F8FAFF 0%,#EAF2FF 100%)"></div>
<div class="glow" style="width:500px;height:500px;right:-120px;top:-160px;background:${c3};opacity:.45"></div>
<div class="glow" style="width:420px;height:420px;left:-140px;bottom:-180px;background:${c2};opacity:.22"></div>
${win(150, 110, 900, 590, kind, theme)}
<div class="tile" style="left:70px;top:520px;width:180px;height:180px;border-radius:32px;background:linear-gradient(135deg,${c2},${c1});border:none">${icon(iconName, { size: 90, stroke: 1.5 })}</div>`, BASE_CSS);
}

/** แผนภาพขั้นตอน 1200×675 (ข้อความอังกฤษสั้น ๆ ตามชื่อเมนูของบริการ) */
export function steps({ theme = 'indigo', title, iconName, items }) {
    const [, c1, c2, c3] = THEMES[theme];
    const n = items.length;
    const cw = (1200 - 120 - (n - 1) * 26) / n;
    return page(1200, 675, `<div class="bg" style="background:linear-gradient(160deg,#F8FAFF 0%,#E8F0FF 100%)"></div>
<div class="glow" style="width:520px;height:520px;right:-160px;top:-220px;background:${c3};opacity:.5"></div>
<div style="position:absolute;left:60px;top:58px;display:flex;align-items:center;gap:18px"><div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,${c2},${c1});color:#fff;display:flex;align-items:center;justify-content:center">${icon(iconName, { size: 36 })}</div><div style="font-size:38px;font-weight:600;color:#0F172A">${title}</div></div>
<div style="position:absolute;left:60px;top:190px;display:flex;gap:26px">${items
        .map(
            ([ic, label, sub], i) => `<div style="width:${cw}px;height:380px;background:#fff;border-radius:22px;box-shadow:0 18px 44px rgba(15,23,42,.10);border:1px solid #E2E8F0;padding:30px 24px;position:relative">
<div style="width:46px;height:46px;border-radius:50%;background:${c2};color:#fff;font-size:22px;font-weight:600;display:flex;align-items:center;justify-content:center">${i + 1}</div>
<div style="margin:34px 0 26px;color:${c1}">${icon(ic, { size: 76, stroke: 1.5 })}</div>
<div style="font-size:24px;font-weight:600;color:#0F172A;line-height:1.3">${label}</div>
<div style="font-size:17px;color:#64748B;margin-top:10px;line-height:1.45;font-family:Sarabun,Prompt,sans-serif">${sub}</div>
${i < n - 1 ? `<div style="position:absolute;right:-24px;top:174px;width:22px;color:${c2};z-index:2">${icon('chevron-right', { size: 22, stroke: 3 })}</div>` : ''}</div>`,
        )
        .join('')}</div>`, BASE_CSS);
}

/** ภาพยินดีต้อนรับ (intropage / popup) — ข้อความสองภาษา */
export function welcome({ w, h, theme = 'navy', compact = false }) {
    const s = compact ? 0.55 : 1;
    return page(w, h, `${bg(theme, 140)}
<svg style="position:absolute;inset:0" width="${w}" height="${h}" viewBox="0 0 1920 1080" preserveAspectRatio="none"><path d="M0 860 C420 760 760 930 1160 840 S1700 700 1920 760 V1080 H0Z" fill="#fff" fill-opacity=".07"/><path d="M0 940 C460 880 820 1010 1240 950 S1760 860 1920 900 V1080 H0Z" fill="#fff" fill-opacity=".08"/></svg>
<div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;color:#fff;padding-bottom:${compact ? 0 : 60}px">
<div class="tile" style="position:relative;width:${150 * s}px;height:${150 * s}px;border-radius:${34 * s}px;margin-bottom:${38 * s}px">${icon('layers', { size: 84 * s, stroke: 1.6 })}</div>
<div style="font-size:${118 * s}px;font-weight:600;line-height:1.25;letter-spacing:.5px;text-shadow:0 6px 30px rgba(0,0,0,.25)">ยินดีต้อนรับ</div>
<div style="font-size:${52 * s}px;font-weight:300;opacity:.92;margin-top:${10 * s}px">Welcome to <b style="font-weight:600">MicroCMS</b></div>
<div style="width:${120 * s}px;height:${5 * s}px;border-radius:3px;background:${THEMES[theme][3]};margin-top:${34 * s}px"></div></div>`, BASE_CSS);
}

export function logo({ w = 640, h = 160, mark = false, dark = false }) {
    const markHtml = `<div style="width:${h * 0.8}px;height:${h * 0.8}px;border-radius:${h * 0.22}px;background:linear-gradient(135deg,#60A5FA 0%,#2563EB 55%,#1E3A8A 100%);display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:inset 0 -${h * 0.04}px 0 rgba(0,0,0,.12)">${icon('layers', { size: h * 0.48, stroke: 2 })}</div>`;
    if (mark) {
        return page(w, h, `<div style="width:${w}px;height:${h}px;display:flex;align-items:center;justify-content:center">${markHtml.replace(`width:${h * 0.8}px;height:${h * 0.8}px`, `width:${h}px;height:${h}px`)}</div>`, '', { transparent: true });
    }
    return page(w, h, `<div style="height:${h}px;display:flex;align-items:center;gap:${h * 0.14}px;padding-left:${h * 0.08}px">${markHtml}
<div style="font-size:${h * 0.5}px;font-weight:600;letter-spacing:-.5px;color:${dark ? '#fff' : '#0F2A5C'}">Micro<span style="color:${dark ? '#93C5FD' : '#2563EB'};font-weight:700">CMS</span></div></div>`, '', { transparent: true });
}

export function avatar() {
    return page(400, 400, `<div class="bg" style="background:linear-gradient(135deg,#1E3A8A,#3B82F6)"></div><div class="dots"></div>
<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff">${icon('user-round', { size: 210, stroke: 1.4 })}</div>`, BASE_CSS);
}

/** พื้นหลังจาง ๆ ของแถวในหน้าแรก */
export function softPattern({ w, h }) {
    return page(w, h, `<div class="bg" style="background:linear-gradient(180deg,#F8FBFF 0%,#EEF4FF 100%)"></div>
<div class="bg" style="background-image:radial-gradient(rgba(37,99,235,.10) 1.4px,transparent 1.4px);background-size:34px 34px"></div>
<div class="glow" style="width:620px;height:620px;right:-200px;top:-260px;background:#BFDBFE;opacity:.55"></div>
<div class="glow" style="width:520px;height:520px;left:-200px;bottom:-280px;background:#C7D2FE;opacity:.45"></div>`, BASE_CSS);
}

/** ภาพประกอบหน้าแรก: กล่องโมดูลซ้อนกัน */
export function featureCollage() {
    const t = 'navy';
    const [, c1, c2] = THEMES[t];
    return page(1000, 750, `<div class="bg" style="background:linear-gradient(160deg,#EFF6FF 0%,#DBEAFE 100%)"></div>
<div class="glow" style="width:420px;height:420px;right:-120px;top:-140px;background:#93C5FD;opacity:.6"></div>
${win(70, 70, 620, 420, 'layout', t)}
${win(360, 300, 560, 380, 'grid', t)}
${[['newspaper', 60, 560], ['image', 800, 90], ['message-square-more', 760, 230]].map(([n, x, y]) => `<div class="float" style="left:${x}px;top:${y}px;padding:12px"><div class="ic" style="background:linear-gradient(135deg,${c2},${c1})">${icon(n, { size: 24 })}</div></div>`).join('')}`, BASE_CSS);
}

/** ภาพแชร์โซเชียล (OG) 1200×630 */
export function ogImage() {
    return page(1200, 630, `${bg('navy', 135)}
<div style="position:absolute;left:90px;top:0;bottom:0;display:flex;flex-direction:column;justify-content:center;color:#fff;width:560px">
<div style="display:flex;align-items:center;gap:20px"><div class="tile" style="position:relative;width:96px;height:96px;border-radius:24px">${icon('layers', { size: 54, stroke: 1.7 })}</div><div style="font-size:64px;font-weight:600">MicroCMS</div></div>
<div style="font-size:34px;font-weight:400;margin-top:26px;line-height:1.45">ติดตั้งง่าย ใช้งานง่าย<br><span style="opacity:.8;font-size:28px;font-weight:300">Easy to install, easy to use</span></div></div>
${win(700, 120, 560, 400, 'dashboard', 'navy')}`, BASE_CSS);
}
