<?php

use App\Models\ArticleItemInfo;
use App\Support\PageWidget\CategoryListWidget;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

// เทสรอบทวนสอบ performance/cache (phase 1)

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('public files are served without starting a session', function () {
    Storage::fake('local');
    fakeFileInfo('pic.jpg', 'perf-pic.jpg', 'jpg', 'image/jpeg');

    $response = $this->get(route('front.file.get', 'perf-pic.jpg'))->assertOk();

    expect($response->headers->getCookies())->toBe([])
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');

    // ไม่พบไฟล์ = 404 (หน้า error ต้องแสดงได้แม้ไม่มี session)
    $this->get(route('front.file.get', 'missing.jpg'))->assertNotFound();
});

test('an article list page beyond the last page returns 404', function () {
    $categoryId = ArticleItemInfo::query()->where('status', 'Y')->value('article_category_info_id');

    $this->get("/th/article/category/{$categoryId}")->assertOk();
    $this->get("/th/article/category/{$categoryId}?page=999")->assertNotFound();
});

test('unknown tags render an empty list without error', function () {
    $this->get('/th/article/tag/'.urlencode('ไม่มีแท็กนี้แน่นอน'))->assertOk();
});

test('list widgets never load more than the item cap', function () {
    expect(CategoryListWidget::MAX_ITEMS_LIMIT)->toBe(100);
});
