<?php

use App\Models\ArticleTagDetail;
use App\Models\ArticleTagInfo;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    // seed สร้างแท็กตัวอย่างมาด้วย (ArticleSeeder) — เทสด้านล่างค้นหา/สร้างด้วยคำเฉพาะของเทสเอง
    $this->seed(DatabaseSeeder::class);
});

// ---------------------------------------------------------------- search

test('search requires article.item.manage', function () {
    actingAsUserWithPermissions(['article.item.view']);

    $this->get(route('admin.article.tag.search', ['q' => 'ความรู้']))
        ->assertForbidden();
});

test('search returns tags matching the default-language name', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    $this->get(route('admin.article.tag.search', ['q' => 'ความรู้']))
        ->assertOk()
        ->assertJsonFragment(['name' => 'ความรู้']);

    $this->get(route('admin.article.tag.search', ['q' => 'ไม่มีอยู่จริงแน่นอน']))
        ->assertOk()
        ->assertJson(['data' => []]);
});

test('search returns nothing for an empty query', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    $this->get(route('admin.article.tag.search', ['q' => '']))
        ->assertOk()
        ->assertJson(['data' => []]);
});

test('search includes inactive tags along with their status', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    $tag = ArticleTagInfo::create(['status' => 'N', 'created_by' => 1]);
    ArticleTagDetail::create(['id' => $tag->id, 'lang' => 'th', 'name' => 'แท็กปิดใช้งานค้นหา', 'status' => 'Y', 'created_by' => 1]);

    $this->get(route('admin.article.tag.search', ['q' => 'แท็กปิดใช้งานค้นหา']))
        ->assertOk()
        ->assertJsonFragment(['name' => 'แท็กปิดใช้งานค้นหา', 'status' => 'N']);
});

// ---------------------------------------------------------------- quickStore (ajax, ใช้จาก TagPicker.vue)

test('creating a tag requires article.item.manage', function () {
    actingAsUserWithPermissions(['article.item.view']);

    $this->postJson(route('admin.article.tag.quickStore'), [
        'name' => ['th' => 'แท็กทดสอบ', 'en' => 'Test Tag'],
    ])->assertForbidden();
});

test('creating a tag requires a name for every selected language', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    $this->postJson(route('admin.article.tag.quickStore'), [
        'name' => ['th' => 'แท็กทดสอบ', 'en' => ''],
    ])->assertInvalid(['name.en']);
});

test('quickStore creates a tag with detail rows for every language and returns the default-language name', function () {
    $me = actingAsUserWithPermissions(['article.item.manage']);

    $response = $this->postJson(route('admin.article.tag.quickStore'), [
        'name' => ['th' => 'แท็กทดสอบใหม่', 'en' => 'Brand New Tag'],
    ]);

    $response->assertCreated()->assertJsonPath('data.name', 'แท็กทดสอบใหม่');

    $tagId = $response->json('data.id');
    $tagInfo = ArticleTagInfo::find($tagId);

    expect($tagInfo)->not->toBeNull()->and($tagInfo->created_by)->toBe($me->id);

    $th = ArticleTagDetail::where('id', $tagId)->where('lang', 'th')->first();
    $en = ArticleTagDetail::where('id', $tagId)->where('lang', 'en')->first();

    expect($th->name)->toBe('แท็กทดสอบใหม่')
        ->and($en->name)->toBe('Brand New Tag')
        ->and($th->slug)->not->toBeNull();
});

test('quickStore rejects a name that already exists for that language, even if the existing tag is inactive', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    $tag = ArticleTagInfo::create(['status' => 'N', 'created_by' => 1]);
    ArticleTagDetail::create(['id' => $tag->id, 'lang' => 'th', 'name' => 'แท็กปิดใช้งานซ้ำด่วน', 'status' => 'Y', 'created_by' => 1]);

    $this->postJson(route('admin.article.tag.quickStore'), [
        'name' => ['th' => 'แท็กปิดใช้งานซ้ำด่วน', 'en' => 'Some New Name'],
    ])->assertInvalid(['name.th']);
});
