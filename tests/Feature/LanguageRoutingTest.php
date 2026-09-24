<?php

use App\Support\Setting;
use Database\Seeders\IntropageSeeder;
use Illuminate\Support\Facades\App;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * หมายเหตุสำคัญ: route `where('lang', Setting::languageRoutePattern())` (routes/web.php) evaluate
 * ตอน register route ซึ่งเกิดขึ้นครั้งเดียวตอน boot ของแอป (ก่อนโค้ดในเทสแต่ละตัวจะรันด้วยซ้ำ) — การเรียก
 * setSiteSetting() *ระหว่าง* เทสจึงเปลี่ยน regex ของ route ที่ compile ไปแล้วไม่ได้ในเทสเดียวกัน
 * (เหมือนของจริง: เปลี่ยนค่าตั้งค่าแล้วต้องรอ request ถัดไปถึงจะมีผล เพราะ routes/web.php ถูก include ใหม่
 * ทุก request อยู่แล้วในสภาพแวดล้อมปกติที่ไม่ได้ใช้ route:cache/Octane) — ทดสอบว่า languageRoutePattern()
 * คำนวณ string ถูกต้องแยกต่างหาก (ไม่ผ่าน HTTP) แทนการเทสผ่าน request จริงที่เปลี่ยนค่ากลางเทส
 */

// ---------------------------------------------------------------- ยังไม่ได้ตั้งค่า (fallback th,en / th)

// หน้าแรก (/ และ /{lang}) แสดง Intropage — seed Intropage ตัวอย่างไว้ให้มีหน้าให้แสดง (ไม่มี = redirect ไปหน้าแรกตามเมนู / 404)

test('root renders the th intropage when languages are not configured', function () {
    $this->seed(IntropageSeeder::class);

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->where('front.lang', 'th'));
});

test('both th and en are reachable when languages are not configured', function () {
    $this->seed(IntropageSeeder::class);

    $this->get('/th')->assertOk();
    $this->get('/en')->assertOk();
});

// ---------------------------------------------------------------- ตั้งค่าไว้แล้ว (ผ่านการ redirect/middleware
// ที่ประเมินตอน dispatch request จริง จึงเห็นค่าที่เปลี่ยนกลางเทสได้ ต่างจาก route where() ด้านบน)

test('root uses the configured lang_default', function () {
    setSiteSetting('lang_selected', 'th,en');
    setSiteSetting('lang_default', 'en');
    $this->seed(IntropageSeeder::class);

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->where('front.lang', 'en'));
    expect(App::getLocale())->toBe('en');
});

test('SetLocale sets the app locale to the matched {lang} segment', function () {
    setSiteSetting('lang_selected', 'th,en');
    setSiteSetting('lang_default', 'th');

    $this->get('/en');

    expect(App::getLocale())->toBe('en');
});

// ---------------------------------------------------------------- App\Support\Setting ล้วน ๆ (ไม่ผ่าน HTTP)
// ตรงนี้คือส่วนที่ routes/web.php และ SetLocale พึ่งพาจริง ๆ — ทดสอบตรงเพื่อเลี่ยงปัญหา route compile ข้างต้น

test('languageRoutePattern reflects lang_selected', function () {
    setSiteSetting('lang_selected', 'en');

    expect(Setting::languageRoutePattern())->toBe('en');
});

test('languageRoutePattern supports more than 2 languages', function () {
    setSiteSetting('lang_selected', 'th,en,fr');

    expect(Setting::languageRoutePattern())->toBe('th|en|fr');
});

test('languageRoutePattern falls back to th|en when lang_selected is unset', function () {
    expect(Setting::languageRoutePattern())->toBe('th|en');
});

test('defaultLanguage ignores a stale lang_default that is no longer in lang_selected', function () {
    setSiteSetting('lang_selected', 'en');
    setSiteSetting('lang_default', 'th'); // ค่าเก่าที่ไม่ตรงกับ lang_selected อีกแล้ว (เช่น แก้ DB ตรง ๆ)

    expect(Setting::defaultLanguage())->toBe('en');
});
