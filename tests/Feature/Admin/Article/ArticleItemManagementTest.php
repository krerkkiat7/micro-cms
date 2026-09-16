<?php

use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemDetail;
use App\Models\ArticleItemInfo;
use App\Models\ArticleItemPart;
use App\Models\ArticleItemPartDetail;
use App\Models\ArticleItemPartFile;
use App\Models\ArticleTagInfo;
use App\Models\LogBackAction;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // seed สร้างหมวดหมู่+แท็กตัวอย่างมาด้วย (ArticleSeeder) — เทสด้านล่างใช้หมวดหมู่แรกที่มีอยู่แล้วนี้
    $this->seed(DatabaseSeeder::class);
    $this->category = ArticleCategoryInfo::query()->firstOrFail();
});

/**
 * @return array<string, mixed>
 */
function validItemPayload(int $categoryId, array $overrides = []): array
{
    return array_replace_recursive([
        'article_category_info_id' => $categoryId,
        'intro_image_id' => null,
        'publish_date' => now()->format('Y-m-d H:i:s'),
        'publish_down' => null,
        'status' => 'Y',
        'tags' => [],
        'detail' => [
            'th' => [
                'title' => 'บทความทดสอบ',
                'intro_text' => 'ข้อความเกริ่นนำภาษาไทย',
                'slug' => 'test-item-th',
            ],
            'en' => [
                'title' => 'Test Article',
                'intro_text' => 'English intro',
                'slug' => 'test-item-en',
            ],
        ],
        'parts' => [
            [
                'part_type' => 'text',
                'detail' => [
                    'th' => ['title' => 'หัวข้อ', 'detail' => '<p>เนื้อหาภาษาไทย</p>'],
                    'en' => ['title' => 'Heading', 'detail' => '<p>English content</p>'],
                ],
            ],
        ],
    ], $overrides);
}

// ---------------------------------------------------------------- index

test('index redirects to dashboard without article.item.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.article.item.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('index renders for a user with article.item.view', function () {
    actingAsUserWithPermissions(['article.item.view']);

    $this->get(route('admin.article.item.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Article/Item/Index')
            ->has('items.data')
            ->has('categories')
        );
});

test('index shows the default-language title and category, and filters by search term', function () {
    actingAsUserWithPermissions(['article.item.view', 'article.item.manage']);

    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id));

    $this->get(route('admin.article.item.index', ['q' => 'บทความทดสอบ']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.title', 'บทความทดสอบ')
            ->where('items.data.0.category_title', fn ($title) => $title !== null)
        );

    $this->get(route('admin.article.item.index', ['q' => 'ไม่มีอยู่จริงแน่นอน']))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 0));
});

test('index defaults to sorting by title ascending', function () {
    actingAsUserWithPermissions(['article.item.view', 'article.item.manage']);

    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id, [
        'detail' => ['th' => ['title' => 'ข ทดสอบเรียงลำดับ B', 'slug' => 'sort-b-th'], 'en' => ['title' => 'Sort B', 'slug' => 'sort-b-en']],
    ]));
    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id, [
        'detail' => ['th' => ['title' => 'ก ทดสอบเรียงลำดับ A', 'slug' => 'sort-a-th'], 'en' => ['title' => 'Sort A', 'slug' => 'sort-a-en']],
    ]));

    $this->get(route('admin.article.item.index', ['q' => 'ทดสอบเรียงลำดับ']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sort', 'title')
            ->where('direction', 'asc')
            ->where('items.data.0.title', fn ($title) => str_starts_with($title, 'ก'))
        );
});

test('index filters by category', function () {
    actingAsUserWithPermissions(['article.item.view', 'article.item.manage']);

    $otherCategory = ArticleCategoryInfo::query()->where('id', '!=', $this->category->id)->first();

    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id, [
        'detail' => ['th' => ['title' => 'บทความหมวดแรก', 'slug' => 'first-cat-th'], 'en' => ['title' => 'First Cat', 'slug' => 'first-cat-en']],
    ]));
    $this->post(route('admin.article.item.store'), validItemPayload($otherCategory->id, [
        'detail' => ['th' => ['title' => 'บทความหมวดสอง', 'slug' => 'second-cat-th'], 'en' => ['title' => 'Second Cat', 'slug' => 'second-cat-en']],
    ]));

    $this->get(route('admin.article.item.index', ['category_id' => $this->category->id, 'q' => 'บทความหมวด']))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.title', 'บทความหมวดแรก'));
});

// ---------------------------------------------------------------- add / store

test('add page redirects without article.item.manage', function () {
    actingAsUserWithPermissions(['article.item.view']);

    $this->get(route('admin.article.item.add'))
        ->assertRedirect(route('admin.article.item.index'));
});

test('add page renders languages, categories and tags', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    $this->get(route('admin.article.item.add'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Article/Item/Add')
            ->has('languages')
            ->has('categories')
            ->has('tags')
        );
});

test('store requires the title only for the default language', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    $this->from(route('admin.article.item.add'))
        ->post(route('admin.article.item.store'), validItemPayload($this->category->id, [
            'detail' => ['th' => ['title' => ''], 'en' => ['title' => '']],
        ]))
        ->assertInvalid(['detail.th.title'])
        ->assertValid(['detail.en.title']);
});

test('store requires a category and a publish date', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    $this->from(route('admin.article.item.add'))
        ->post(route('admin.article.item.store'), validItemPayload($this->category->id, [
            'article_category_info_id' => null,
            'publish_date' => null,
        ]))
        ->assertInvalid(['article_category_info_id', 'publish_date']);
});

test('store rejects a publish_down before publish_date', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    $this->from(route('admin.article.item.add'))
        ->post(route('admin.article.item.store'), validItemPayload($this->category->id, [
            'publish_date' => '2026-01-10 00:00:00',
            'publish_down' => '2026-01-01 00:00:00',
        ]))
        ->assertInvalid(['publish_down']);
});

test('store creates the article with per-language details, a text part and logs the action', function () {
    $me = actingAsUserWithPermissions(['article.item.manage']);

    $response = $this->post(route('admin.article.item.store'), validItemPayload($this->category->id));

    $item = ArticleItemInfo::query()->latest('id')->first();

    expect($item)->not->toBeNull()
        ->and($item->article_category_info_id)->toBe($this->category->id)
        ->and($item->status)->toBe('Y')
        ->and($item->created_by)->toBe($me->id);

    $th = ArticleItemDetail::where('id', $item->id)->where('lang', 'th')->first();
    $en = ArticleItemDetail::where('id', $item->id)->where('lang', 'en')->first();

    expect($th->title)->toBe('บทความทดสอบ')
        ->and($th->slug)->toBe('test-item-th')
        ->and($en->title)->toBe('Test Article');

    $part = ArticleItemPart::where('article_item_info_id', $item->id)->first();
    expect($part)->not->toBeNull()->and($part->part_type)->toBe('text');

    $partDetailTh = ArticleItemPartDetail::where('id', $part->id)->where('lang', 'th')->first();
    expect($partDetailTh->detail)->toBe('<p>เนื้อหาภาษาไทย</p>');

    $response->assertRedirect(route('admin.article.item.edit', $item->id))
        ->assertSessionHas('success');

    expect(LogBackAction::where('module_code', 'article.item')
        ->where('action_type', 'create')
        ->where('ref_id', $item->id)
        ->exists())->toBeTrue();
});

test('store allows saving with no parts at all', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    // array_replace_recursive([], ...) ไม่ลบ key 'parts' เดิมที่ validItemPayload() ตั้งไว้ (merge แบบ recursive
    // ไม่ล้างค่าเดิมด้วยอาเรย์ว่าง) จึงต้องตัด 'parts' ออกจาก payload โดยตรงแทน
    $payload = validItemPayload($this->category->id);
    unset($payload['parts']);

    $this->post(route('admin.article.item.store'), $payload)->assertSessionHas('success');

    $item = ArticleItemInfo::query()->latest('id')->first();
    expect(ArticleItemPart::where('article_item_info_id', $item->id)->count())->toBe(0);
});

test('store attaches selected tags', function () {
    actingAsUserWithPermissions(['article.item.manage']);
    $tag = ArticleTagInfo::query()->firstOrFail();

    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id, [
        'tags' => [$tag->id],
    ]));

    $item = ArticleItemInfo::query()->latest('id')->first();

    expect($item->tags()->pluck('article_tag_info.id')->all())->toBe([$tag->id]);
});

test('store saves a video part with a youtube link', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id, [
        'parts' => [
            [
                'part_type' => 'video',
                'detail' => ['th' => ['title' => 'วิดีโอ'], 'en' => ['title' => 'Video']],
                'files' => [
                    ['video_type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                ],
            ],
        ],
    ]))->assertSessionHas('success');

    $item = ArticleItemInfo::query()->latest('id')->first();
    $part = ArticleItemPart::where('article_item_info_id', $item->id)->first();
    $file = ArticleItemPartFile::where('article_item_part_id', $part->id)->first();

    expect($part->part_type)->toBe('video')
        ->and($file->video_type)->toBe('youtube')
        ->and($file->youtube_url)->toBe('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
});

test('store rejects a malformed youtube url', function () {
    actingAsUserWithPermissions(['article.item.manage']);

    $this->from(route('admin.article.item.add'))
        ->post(route('admin.article.item.store'), validItemPayload($this->category->id, [
            'parts' => [
                [
                    'part_type' => 'video',
                    'files' => [
                        ['video_type' => 'youtube', 'youtube_url' => 'https://example.com/not-youtube'],
                    ],
                ],
            ],
        ]))
        ->assertInvalid(['parts.0.files.0.youtube_url']);
});

// ---------------------------------------------------------------- edit / update

test('edit redirects for a missing or soft-deleted article', function () {
    actingAsUserWithPermissions(['article.item.view']);

    $this->get(route('admin.article.item.edit', 999999))
        ->assertRedirect(route('admin.article.item.index'));

    actingAsUserWithPermissions(['article.item.manage', 'article.item.view', 'article.item.delete']);
    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id));
    $item = ArticleItemInfo::query()->latest('id')->first();
    $this->delete(route('admin.article.item.destroy', $item->id));

    $this->get(route('admin.article.item.edit', $item->id))
        ->assertRedirect(route('admin.article.item.index'));
});

test('edit renders article details, parts and tags, and logs access + view action', function () {
    actingAsUserWithPermissions(['article.item.manage', 'article.item.view']);
    $tag = ArticleTagInfo::query()->firstOrFail();
    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id, ['tags' => [$tag->id]]));
    $item = ArticleItemInfo::query()->latest('id')->first();

    $this->get(route('admin.article.item.edit', $item->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Article/Item/Edit')
            ->where('details.th.title', 'บทความทดสอบ')
            ->where('details.en.title', 'Test Article')
            ->where('tagIds', [$tag->id])
            ->has('parts', 1)
            ->where('parts.0.part_type', 'text')
            ->where('can.manage', true)
            ->where('can.delete', false)
        );

    expect(LogBackAction::where('module_code', 'article.item')
        ->where('action_type', 'view')
        ->where('ref_id', $item->id)
        ->exists())->toBeTrue();
});

test('edit exposes publish_date and publish_down formatted as datetime strings', function () {
    // กันบั๊กที่ ArticleItemInfo ไม่ cast publish_date/publish_down เป็น datetime — ถ้าลืม cast
    // optional($model->publish_date)->format(...) จะคืน null เงียบ ๆ (Optional::__call เช็ก is_object())
    // ทำให้ช่องวันที่ในฟอร์มแก้ไขว่างเปล่าทั้งที่บันทึกข้อมูลไว้ถูกต้องแล้วในฐานข้อมูล
    actingAsUserWithPermissions(['article.item.manage', 'article.item.view']);
    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id, [
        'publish_date' => '2026-05-01 09:30:00',
        'publish_down' => '2026-06-01 18:00:00',
    ]));
    $item = ArticleItemInfo::query()->latest('id')->first();

    $this->get(route('admin.article.item.edit', $item->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('item.publish_date', '2026-05-01 09:30:00')
            ->where('item.publish_down', '2026-06-01 18:00:00')
        );
});

test('update saves changes to the info row, every language detail row and replaces parts', function () {
    $me = actingAsUserWithPermissions(['article.item.manage']);
    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id));
    $item = ArticleItemInfo::query()->latest('id')->first();
    $originalPartId = ArticleItemPart::where('article_item_info_id', $item->id)->value('id');

    $payload = validItemPayload($this->category->id, [
        'status' => 'N',
        'detail' => ['th' => ['title' => 'ชื่อใหม่'], 'en' => ['title' => 'Updated Name']],
        'parts' => [
            [
                'part_type' => 'text',
                'detail' => [
                    'th' => ['title' => 'หัวข้อใหม่', 'detail' => '<p>เนื้อหาใหม่</p>'],
                    'en' => ['title' => 'New Heading', 'detail' => '<p>New content</p>'],
                ],
            ],
        ],
    ]);

    $this->put(route('admin.article.item.update', $item->id), $payload)
        ->assertRedirect(route('admin.article.item.edit', $item->id))
        ->assertSessionHas('success');

    expect($item->fresh()->status)->toBe('N')
        ->and($item->fresh()->updated_by)->toBe($me->id);

    $th = ArticleItemDetail::where('id', $item->id)->where('lang', 'th')->first();
    expect($th->title)->toBe('ชื่อใหม่')
        ->and($th->updated_by)->toBe($me->id);

    // part เดิมถูกแทนที่ด้วยแถวใหม่ทั้งหมด (id ไม่คงเดิม แต่เนื้อหาต้องอัปเดต)
    expect(ArticleItemPart::find($originalPartId))->toBeNull();
    $newPart = ArticleItemPart::where('article_item_info_id', $item->id)->first();
    $newPartDetailTh = ArticleItemPartDetail::where('id', $newPart->id)->where('lang', 'th')->first();
    expect($newPartDetailTh->detail)->toBe('<p>เนื้อหาใหม่</p>');

    expect(LogBackAction::where('module_code', 'article.item')
        ->where('action_type', 'update')
        ->where('ref_id', $item->id)
        ->exists())->toBeTrue();
});

test('update redirects without article.item.manage', function () {
    actingAsUserWithPermissions(['article.item.manage']);
    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id));
    $item = ArticleItemInfo::query()->latest('id')->first();

    actingAsUserWithPermissions(['article.item.view']);

    $this->put(route('admin.article.item.update', $item->id), validItemPayload($this->category->id))
        ->assertRedirect(route('admin.article.item.index'));
});

// ---------------------------------------------------------------- destroy

test('destroy redirects without article.item.delete', function () {
    actingAsUserWithPermissions(['article.item.manage']);
    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id));
    $item = ArticleItemInfo::query()->latest('id')->first();

    actingAsUserWithPermissions(['article.item.view']);

    $this->delete(route('admin.article.item.destroy', $item->id))
        ->assertRedirect(route('admin.article.item.index'));

    expect($item->fresh()->trashed())->toBeFalse();
});

test('destroy soft deletes the article and logs the action', function () {
    actingAsUserWithPermissions(['article.item.manage']);
    $this->post(route('admin.article.item.store'), validItemPayload($this->category->id));
    $item = ArticleItemInfo::query()->latest('id')->first();

    $me = actingAsUserWithPermissions(['article.item.delete']);

    $this->delete(route('admin.article.item.destroy', $item->id))
        ->assertRedirect(route('admin.article.item.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($item);
    expect($item->fresh()->deleted_by)->toBe($me->id);

    expect(LogBackAction::where('module_code', 'article.item')
        ->where('action_type', 'delete')
        ->where('ref_id', $item->id)
        ->exists())->toBeTrue();
});
