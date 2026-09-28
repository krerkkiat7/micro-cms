<?php

use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemInfo;
use App\Models\User;
use App\Support\SystemInfo;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('audit shows creator and updater names and marks deleted users', function () {
    $creator = User::factory()->create(['firstname' => 'สมชาย', 'lastname' => 'ใจดี']);
    $updater = User::factory()->create(['firstname' => 'สมหญิง', 'lastname' => 'รักงาน']);
    $updater->delete();

    $category = ArticleCategoryInfo::query()->orderBy('id')->firstOrFail();
    $category->forceFill(['created_by' => $creator->id, 'updated_by' => $updater->id])->saveQuietly();

    $info = SystemInfo::audit($category->fresh());

    expect($info['created_by'])->toBe($creator->name)
        ->and($info['updated_by'])->toBe($updater->name.' (ถูกลบแล้ว)')
        ->and($info['created_at'])->not->toBeNull();

    $category->forceFill(['created_by' => null, 'updated_by' => 999999])->saveQuietly();

    expect(SystemInfo::audit($category->fresh()))->toMatchArray(['created_by' => null, 'updated_by' => null]);
});

test('article category edit page sends system info and the article count', function () {
    actingAsUserWithPermissions(['article.category.view']);
    $category = ArticleCategoryInfo::query()->orderBy('id')->firstOrFail();
    $count = ArticleItemInfo::query()->where('article_category_info_id', $category->id)->count();

    $this->get(route('admin.article.category.edit', $category->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('systemInfo.created_at')
            ->has('systemInfo.updated_by')
            ->where('articleCount', $count));
});
