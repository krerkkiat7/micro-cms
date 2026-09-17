<?php

use App\Models\ArticleTagDetail;
use App\Models\ArticleTagInfo;
use App\Models\LogBackAction;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // seed สร้างแท็กตัวอย่างมาด้วย (ArticleSeeder) — เทสด้านล่างจึงค้นหา/กรองด้วยคำเฉพาะของเทสเอง
    $this->seed(DatabaseSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function validTagPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'status' => 'Y',
        'detail' => [
            'th' => ['name' => 'แท็กทดสอบ'],
            'en' => ['name' => 'Test Tag'],
        ],
    ], $overrides);
}

// ---------------------------------------------------------------- index

test('index redirects to dashboard without article.tag.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.article.tag.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('index renders for a user with article.tag.view', function () {
    actingAsUserWithPermissions(['article.tag.view']);

    $this->get(route('admin.article.tag.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Article/Tag/Index')
            ->has('tags.data')
        );
});

test('index shows the default-language name and filters by search term', function () {
    actingAsUserWithPermissions(['article.tag.view', 'article.tag.manage']);

    $this->post(route('admin.article.tag.store'), validTagPayload());

    $this->get(route('admin.article.tag.index', ['q' => 'แท็กทดสอบ']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('tags.data', 1)
            ->where('tags.data.0.name', 'แท็กทดสอบ')
        );

    $this->get(route('admin.article.tag.index', ['q' => 'ไม่มีอยู่จริงแน่นอน']))
        ->assertInertia(fn (Assert $page) => $page->has('tags.data', 0));
});

test('index defaults to sorting by name ascending', function () {
    actingAsUserWithPermissions(['article.tag.view', 'article.tag.manage']);

    $this->post(route('admin.article.tag.store'), validTagPayload([
        'detail' => ['th' => ['name' => 'ข ทดสอบเรียงลำดับ B'], 'en' => ['name' => 'Sort B']],
    ]));
    $this->post(route('admin.article.tag.store'), validTagPayload([
        'detail' => ['th' => ['name' => 'ก ทดสอบเรียงลำดับ A'], 'en' => ['name' => 'Sort A']],
    ]));

    $this->get(route('admin.article.tag.index', ['q' => 'ทดสอบเรียงลำดับ']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sort', 'name')
            ->where('direction', 'asc')
            ->where('tags.data.0.name', fn ($name) => str_starts_with($name, 'ก'))
        );
});

test('index filters by status', function () {
    actingAsUserWithPermissions(['article.tag.view', 'article.tag.manage']);

    $this->post(route('admin.article.tag.store'), validTagPayload([
        'status' => 'N',
        'detail' => ['th' => ['name' => 'แท็กปิดใช้งาน'], 'en' => ['name' => 'Disabled Tag']],
    ]));

    $this->get(route('admin.article.tag.index', ['q' => 'แท็กปิดใช้งาน', 'status' => 'N']))
        ->assertInertia(fn (Assert $page) => $page->has('tags.data', 1));

    $this->get(route('admin.article.tag.index', ['q' => 'แท็กปิดใช้งาน', 'status' => 'Y']))
        ->assertInertia(fn (Assert $page) => $page->has('tags.data', 0));
});

test('index reports how many articles use each tag', function () {
    actingAsUserWithPermissions(['article.tag.view', 'article.tag.manage']);

    $this->post(route('admin.article.tag.store'), validTagPayload([
        'detail' => ['th' => ['name' => 'แท็กนับจำนวน'], 'en' => ['name' => 'Count Tag']],
    ]));
    $tag = ArticleTagInfo::query()->latest('id')->first();

    $this->get(route('admin.article.tag.index', ['q' => 'แท็กนับจำนวน']))
        ->assertInertia(fn (Assert $page) => $page->where('tags.data.0.article_count', 0));

    expect($tag)->not->toBeNull();
});

// ---------------------------------------------------------------- add / store

test('add page redirects without article.tag.manage', function () {
    actingAsUserWithPermissions(['article.tag.view']);

    $this->get(route('admin.article.tag.add'))
        ->assertRedirect(route('admin.article.tag.index'));
});

test('add page renders with the system languages', function () {
    actingAsUserWithPermissions(['article.tag.manage']);

    $this->get(route('admin.article.tag.add'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Article/Tag/Add')
            ->where('languages', [
                ['code' => 'th', 'is_default' => true],
                ['code' => 'en', 'is_default' => false],
            ])
        );
});

test('store requires the name only for the default language', function () {
    actingAsUserWithPermissions(['article.tag.manage']);

    $this->from(route('admin.article.tag.add'))
        ->post(route('admin.article.tag.store'), validTagPayload([
            'detail' => ['th' => ['name' => ''], 'en' => ['name' => '']],
        ]))
        ->assertInvalid(['detail.th.name'])
        ->assertValid(['detail.en.name']);
});

test('store creates the tag with per-language details, no slug, and logs the action', function () {
    $me = actingAsUserWithPermissions(['article.tag.manage']);

    $response = $this->post(route('admin.article.tag.store'), validTagPayload());

    $tag = ArticleTagInfo::query()->latest('id')->first();

    expect($tag)->not->toBeNull()
        ->and($tag->status)->toBe('Y')
        ->and($tag->created_by)->toBe($me->id);

    $th = ArticleTagDetail::where('id', $tag->id)->where('lang', 'th')->first();
    $en = ArticleTagDetail::where('id', $tag->id)->where('lang', 'en')->first();

    expect($th->name)->toBe('แท็กทดสอบ')
        ->and($th->slug)->toBeNull() // ไม่มีฟิลด์ slug ในฟอร์มนี้
        ->and($en->name)->toBe('Test Tag')
        ->and($en->created_by)->toBe($me->id);

    $response->assertRedirect(route('admin.article.tag.edit', $tag->id))
        ->assertSessionHas('success');

    expect(LogBackAction::where('module_code', 'article.tag')
        ->where('action_type', 'create')
        ->where('ref_id', $tag->id)
        ->exists())->toBeTrue();
});

// ---------------------------------------------------------------- edit / update

test('edit redirects for a missing or soft-deleted tag', function () {
    actingAsUserWithPermissions(['article.tag.view']);

    $this->get(route('admin.article.tag.edit', 999999))
        ->assertRedirect(route('admin.article.tag.index'));

    actingAsUserWithPermissions(['article.tag.manage', 'article.tag.view', 'article.tag.delete']);
    $this->post(route('admin.article.tag.store'), validTagPayload());
    $tag = ArticleTagInfo::query()->latest('id')->first();
    $this->delete(route('admin.article.tag.destroy', $tag->id));

    $this->get(route('admin.article.tag.edit', $tag->id))
        ->assertRedirect(route('admin.article.tag.index'));
});

test('edit renders tag details keyed by language and logs access + view action', function () {
    actingAsUserWithPermissions(['article.tag.manage', 'article.tag.view']);
    $this->post(route('admin.article.tag.store'), validTagPayload());
    $tag = ArticleTagInfo::query()->latest('id')->first();

    $this->get(route('admin.article.tag.edit', $tag->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Article/Tag/Edit')
            ->where('details.th.name', 'แท็กทดสอบ')
            ->where('details.en.name', 'Test Tag')
            ->where('tag.article_count', 0)
            ->where('can.manage', true)
            ->where('can.delete', false)
        );

    expect(LogBackAction::where('module_code', 'article.tag')
        ->where('action_type', 'view')
        ->where('ref_id', $tag->id)
        ->exists())->toBeTrue();
});

test('update saves changes to the info row and every language detail row', function () {
    $me = actingAsUserWithPermissions(['article.tag.manage']);
    $this->post(route('admin.article.tag.store'), validTagPayload());
    $tag = ArticleTagInfo::query()->latest('id')->first();

    $payload = validTagPayload([
        'status' => 'N',
        'detail' => ['th' => ['name' => 'ชื่อใหม่'], 'en' => ['name' => 'Updated Name']],
    ]);

    $this->put(route('admin.article.tag.update', $tag->id), $payload)
        ->assertRedirect(route('admin.article.tag.edit', $tag->id))
        ->assertSessionHas('success');

    expect($tag->fresh()->status)->toBe('N')
        ->and($tag->fresh()->updated_by)->toBe($me->id);

    $th = ArticleTagDetail::where('id', $tag->id)->where('lang', 'th')->first();
    expect($th->name)->toBe('ชื่อใหม่')
        ->and($th->updated_by)->toBe($me->id);

    expect(LogBackAction::where('module_code', 'article.tag')
        ->where('action_type', 'update')
        ->where('ref_id', $tag->id)
        ->exists())->toBeTrue();
});

test('update redirects without article.tag.manage', function () {
    actingAsUserWithPermissions(['article.tag.manage']);
    $this->post(route('admin.article.tag.store'), validTagPayload());
    $tag = ArticleTagInfo::query()->latest('id')->first();

    actingAsUserWithPermissions(['article.tag.view']);

    $this->put(route('admin.article.tag.update', $tag->id), validTagPayload())
        ->assertRedirect(route('admin.article.tag.index'));
});

// ---------------------------------------------------------------- destroy

test('destroy redirects without article.tag.delete', function () {
    actingAsUserWithPermissions(['article.tag.manage']);
    $this->post(route('admin.article.tag.store'), validTagPayload());
    $tag = ArticleTagInfo::query()->latest('id')->first();

    actingAsUserWithPermissions(['article.tag.view']);

    $this->delete(route('admin.article.tag.destroy', $tag->id))
        ->assertRedirect(route('admin.article.tag.index'));

    expect($tag->fresh()->trashed())->toBeFalse();
});

test('destroy soft deletes the tag and logs the action', function () {
    actingAsUserWithPermissions(['article.tag.manage']);
    $this->post(route('admin.article.tag.store'), validTagPayload());
    $tag = ArticleTagInfo::query()->latest('id')->first();

    $me = actingAsUserWithPermissions(['article.tag.delete']);

    $this->delete(route('admin.article.tag.destroy', $tag->id))
        ->assertRedirect(route('admin.article.tag.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($tag);
    expect($tag->fresh()->deleted_by)->toBe($me->id);

    expect(LogBackAction::where('module_code', 'article.tag')
        ->where('action_type', 'delete')
        ->where('ref_id', $tag->id)
        ->exists())->toBeTrue();
});
