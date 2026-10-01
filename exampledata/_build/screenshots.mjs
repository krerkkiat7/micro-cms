// ถ่ายภาพหน้าจอระบบจริง (หลัง seed ข้อมูลตัวอย่างแล้ว) → exampledata/_build/out/guide/*.png แล้วรัน convert.php
//
// ต้องมี playwright-core (ไม่ต้องดาวน์โหลด browser — ใช้ Microsoft Edge ที่ติดตั้งในเครื่อง):
//   npm i --prefix <โฟลเดอร์ใดก็ได้> playwright-core
//   PW_HOME=<โฟลเดอร์นั้น> BASE_URL=http://localhost:8001 node exampledata/_build/screenshots.mjs
import { createRequire } from 'node:module';
import { join } from 'node:path';
import { mkdirSync } from 'node:fs';
import { OUT_DIR, BROWSER } from './lib.mjs';

const require = createRequire(process.env.PW_HOME ? join(process.env.PW_HOME, 'noop.js') : import.meta.url);
const { chromium } = require('playwright-core');

const BASE = process.env.BASE_URL ?? 'http://localhost:8001';
const EMAIL = process.env.ADMIN_EMAIL ?? 'admin@microcms.com';
const PASSWORD = process.env.ADMIN_PASSWORD ?? 'P@ssw0rd';
const DIR = join(OUT_DIR, 'guide');
mkdirSync(DIR, { recursive: true });

const browser = await chromium.launch({ executablePath: BROWSER, headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1, locale: 'th-TH' });
const page = await context.newPage();

const settle = async () => {
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(600);
};

const shot = async (name, { full = false, clip } = {}) => {
    await settle();
    await page.screenshot({ path: join(DIR, `${name}.png`), fullPage: full, clip });
    console.log(`✓ guide/${name}`);
};

/** ภาพเฉพาะการ์ดที่มีหัวข้อ h2 ตามข้อความ (หน้าตั้งค่า) */
const card = async (name, heading) => {
    const h2 = page.locator('h2', { hasText: heading }).first();
    await h2.scrollIntoViewIfNeeded();
    const box = h2.locator('xpath=ancestor::*[contains(@class,"bg-white")][1]');
    await settle();
    await box.screenshot({ path: join(DIR, `${name}.png`) });
    console.log(`✓ guide/${name}`);
};

/** เลื่อนให้หัวข้อ h2 อยู่ด้านบนของจอ แล้วถ่ายทั้งจอ */
const scrollToHeading = async (heading, offset = 90) => {
    const y = await page.locator('h2', { hasText: heading }).first().evaluate((el) => el.getBoundingClientRect().top + window.scrollY);
    await page.evaluate((top) => window.scrollTo(0, top), y - offset);
};

// ---------------------------------------------------------------- หน้าบ้าน (ปิด popup ก่อนสำหรับภาพหน้าแรก)
await page.goto(`${BASE}/th/page/item/1`);
await settle();
await page.waitForTimeout(1500);
await shot('front-popup');
await page.keyboard.press('Escape');
await page.waitForTimeout(500);
await shot('front-home');

// ---------------------------------------------------------------- เข้าสู่ระบบหลังบ้าน
await page.goto(`${BASE}/admin/login`);
await page.fill('input[type=email]', EMAIL);
await page.fill('input[type=password]', PASSWORD);
await Promise.all([page.waitForURL('**/admin/dashboard'), page.press('input[type=password]', 'Enter')]);
await shot('admin-dashboard');

// ---------------------------------------------------------------- บทความ
const articleId = process.env.ARTICLE_ID ?? '6';
await page.goto(`${BASE}/admin/article/item`);
await shot('article-list');
await page.goto(`${BASE}/admin/article/item/${articleId}/edit`);
await shot('article-form');
await scrollToHeading('เนื้อหา');
await shot('article-parts');
await page.goto(`${BASE}/admin/article/category`);
await shot('article-category');
await page.goto(`${BASE}/admin/article/setting`);
await shot('article-setting');

// ---------------------------------------------------------------- หน้าเพจ
await page.goto(`${BASE}/admin/page/item`);
await shot('page-list');
await page.goto(`${BASE}/admin/page/item/1/layout`);
await settle();
await page.waitForTimeout(1500);
await shot('page-layout');
const widgetSettings = page.locator('button[title^="ตั้งค่า"][title*="idget"]');
if ((await widgetSettings.count()) > 2) {
    await widgetSettings.nth(3).click({ force: true });
    await page.waitForTimeout(1200);
    await shot('page-widget');
    await page.keyboard.press('Escape');
} else {
    console.warn('! ไม่พบปุ่มตั้งค่า widget — ใช้ภาพโครงสร้างแทน');
    await page.evaluate(() => window.scrollTo(0, 900));
    await shot('page-widget');
}

// ---------------------------------------------------------------- intropage / banner / popup
await page.goto(`${BASE}/admin/intropage/item/1/edit`);
await shot('intropage-form');
await page.goto(`${BASE}/admin/banner/item`);
await shot('banner-list');
await page.goto(`${BASE}/admin/banner/item/1/edit`);
await shot('banner-form');
await page.goto(`${BASE}/admin/popup/item`);
await shot('popup-list');
await page.goto(`${BASE}/admin/popup/item/1/edit`);
await shot('popup-form');

// ---------------------------------------------------------------- ตั้งค่าระบบ (เฉพาะการ์ดของแต่ละบริการ)
await page.goto(`${BASE}/admin/system/setting`);
await settle();
await card('settings-turnstile', 'Turnstile CAPTCHA');
await card('settings-google-map', 'Google Map');
await card('settings-google-analytics', 'Google Analytics');
await card('settings-smtp', 'SMTP');

await browser.close();
