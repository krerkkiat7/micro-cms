<?php

use App\Models\FileInfo;
use App\Models\FolderInfo;
use App\Models\User;
use App\Support\FileDelivery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
});

// ---------------------------------------------------------------- index

test('guests are redirected to login', function () {
    $this->get(route('admin.system.file.index'))
        ->assertRedirect(route('admin.login'));
});

test('any logged in back user can open the page — no permission required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.system.file.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/System/File/Index'));
});

// ---------------------------------------------------------------- folders

test('a user can create a folder and see only their own folders', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    FolderInfo::create(['user_id' => $other->id, 'name' => 'ของคนอื่น', 'status' => 'Y']);

    $response = $this->actingAs($user)
        ->postJson(route('admin.system.file.folders.store'), ['name' => 'งานของฉัน']);

    $response->assertCreated()->assertJsonPath('data.name', 'งานของฉัน');

    $this->assertDatabaseHas('folder_info', [
        'user_id' => $user->id,
        'name' => 'งานของฉัน',
    ]);

    $list = $this->actingAs($user)->getJson(route('admin.system.file.folders'));
    $list->assertOk()->assertJsonCount(1, 'data');
});

test('folders endpoint reports the root ("no folder") file count', function () {
    $user = User::factory()->create();
    FileInfo::create(['user_id' => $user->id, 'name' => 'a.jpg', 'hash_name' => 'a.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y', 'folder_id' => null]);
    FileInfo::create(['user_id' => $user->id, 'name' => 'b.jpg', 'hash_name' => 'b.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y', 'folder_id' => null]);
    $folder = FolderInfo::create(['user_id' => $user->id, 'name' => 'งาน', 'status' => 'Y']);
    FileInfo::create(['user_id' => $user->id, 'name' => 'c.jpg', 'hash_name' => 'c.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y', 'folder_id' => $folder->id]);

    $this->actingAs($user)
        ->getJson(route('admin.system.file.folders'))
        ->assertOk()
        ->assertJsonPath('root_count', 2)
        ->assertJsonPath('data.0.files_count', 1);
});

test('folder name must be unique per user but can repeat across users', function () {
    $user = User::factory()->create();
    FolderInfo::create(['user_id' => $user->id, 'name' => 'ซ้ำ', 'status' => 'Y']);

    $this->actingAs($user)
        ->postJson(route('admin.system.file.folders.store'), ['name' => 'ซ้ำ'])
        ->assertJsonValidationErrors('name');
});

// ---------------------------------------------------------------- upload

test('uploading an allowed image succeeds and is stored on the local disk', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $response = $this->actingAs($user)
        ->postJson(route('admin.system.file.upload'), ['file' => $file]);

    $response->assertCreated()->assertJsonPath('data.name', 'photo.jpg');

    $stored = FileInfo::firstOrFail();
    expect($stored->user_id)->toBe($user->id)
        ->and($stored->extension)->toBe('jpg')
        ->and($stored->path)->toStartWith('filemanager/'.now()->format('Y/m/d'));

    Storage::disk('local')->assertExists($stored->path);
});

test('uploading a disallowed extension is rejected', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload');

    $this->actingAs($user)
        ->postJson(route('admin.system.file.upload'), ['file' => $file])
        ->assertJsonValidationErrors('file');
});

test('uploading a file whose mime type does not match its extension is rejected', function () {
    $user = User::factory()->create();
    // ชื่อไฟล์ลงท้าย .jpg แต่บังคับ mime เป็น pdf — ต้องโดนเช็ก mime-vs-นามสกุลจับ
    $file = UploadedFile::fake()->create('fake.jpg', 10, 'application/pdf');

    $this->actingAs($user)
        ->postJson(route('admin.system.file.upload'), ['file' => $file])
        ->assertJsonValidationErrors('file');
});

test('uploading a file larger than 5MB is rejected', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf');

    $this->actingAs($user)
        ->postJson(route('admin.system.file.upload'), ['file' => $file])
        ->assertJsonValidationErrors('file');
});

// ---------------------------------------------------------------- list & destroy

test('the file list only shows the current user\'s files', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    FileInfo::create(['user_id' => $user->id, 'name' => 'mine.jpg', 'hash_name' => 'a.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y']);
    FileInfo::create(['user_id' => $other->id, 'name' => 'theirs.jpg', 'hash_name' => 'b.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y']);

    $this->actingAs($user)
        ->getJson(route('admin.system.file.list'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'mine.jpg');
});

test('a user can soft delete their own file but not another user\'s file', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $mine = FileInfo::create(['user_id' => $user->id, 'name' => 'mine.jpg', 'hash_name' => 'a.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y']);
    $theirs = FileInfo::create(['user_id' => $other->id, 'name' => 'theirs.jpg', 'hash_name' => 'b.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y']);

    $this->actingAs($user)->deleteJson(route('admin.system.file.destroy', $theirs->id))->assertNotFound();
    $this->assertDatabaseHas('file_info', ['id' => $theirs->id, 'deleted_at' => null]);

    $this->actingAs($user)->deleteJson(route('admin.system.file.destroy', $mine->id))->assertOk();
    $this->assertSoftDeleted('file_info', ['id' => $mine->id]);
});

// ---------------------------------------------------------------- serve

test('an authenticated user can view, download, and thumbnail any file by hash_name', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();

    $upload = $this->actingAs($owner)->postJson(route('admin.system.file.upload'), [
        'file' => UploadedFile::fake()->image('cover.png', 300, 200),
    ])->assertCreated();

    $hash = $upload->json('data.hash_name');

    $this->actingAs($viewer)
        ->get(route('admin.system.file.get', $hash))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    $downloadResponse = $this->actingAs($viewer)
        ->get(route('admin.system.file.get.download', $hash))
        ->assertOk();
    expect($downloadResponse->headers->get('Content-Disposition'))
        ->toContain('attachment')
        ->toContain('cover.png');

    $this->actingAs($viewer)
        ->get(route('admin.system.file.get.thumbnail.size', ['size' => 80, 'hashname' => $hash]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    // ขนาดนอก config('filemanagement.admin_thumbnail_sizes') = 404 (กันสร้างรูปขนาดใหญ่/ไม่จำกัดจำนวน)
    $this->actingAs($viewer)
        ->get(route('admin.system.file.get.thumbnail.size', ['size' => 50000, 'hashname' => $hash]))
        ->assertNotFound();
});

test('serving an unknown hash_name returns 404', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.system.file.get', 'does-not-exist.jpg'))
        ->assertNotFound();
});

test('guests cannot access the file serving routes', function () {
    $this->get(route('admin.system.file.get', 'whatever.jpg'))
        ->assertRedirect(route('admin.login'));
});

// ---------------------------------------------------------------- thumbnail ล่วงหน้า

test('uploading an image pre-generates the common thumbnail sizes', function () {
    $user = User::factory()->create();

    $hash = $this->actingAs($user)->postJson(route('admin.system.file.upload'), [
        'file' => UploadedFile::fake()->image('wide.jpg', 2400, 1200),
    ])->assertCreated()->json('data.hash_name');

    $file = FileInfo::where('hash_name', $hash)->firstOrFail();

    foreach (config('filemanagement.pregenerate_thumbnail_sizes') as $width) {
        Storage::disk('local')->assertExists(FileDelivery::thumbnailPath($file, $width));
    }

    // ย่อจริง และไม่ขยายเกินต้นฉบับ
    [$w640] = getimagesize(Storage::disk('local')->path(FileDelivery::thumbnailPath($file, 640)));
    expect($w640)->toBe(640);
});

test('non-image uploads create no thumbnails and the backfill command fills in missing sizes', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson(route('admin.system.file.upload'), [
        'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
    ])->assertCreated();

    expect(Storage::disk('local')->allFiles('filemanager/thumbnails'))->toBe([]);

    // รูปที่อัปโหลดก่อนมีระบบสร้างล่วงหน้า (ไม่มี thumbnail) → คำสั่ง files:thumbnails สร้างให้
    $path = UploadedFile::fake()->image('old.png', 800, 600)->storeAs('filemanager/2026/01/01', 'old-image.png', 'local');
    $old = FileInfo::create([
        'user_id' => $user->id, 'name' => 'old.png', 'hash_name' => 'old-image.png', 'extension' => 'png',
        'mime_type' => 'image/png', 'path' => $path, 'status' => 'Y',
    ]);

    $this->artisan('files:thumbnails')->assertSuccessful();

    foreach (config('filemanagement.pregenerate_thumbnail_sizes') as $width) {
        Storage::disk('local')->assertExists(FileDelivery::thumbnailPath($old, $width));
    }

    // รูปเล็กกว่าขนาดที่ขอ = ไม่ขยาย (คงความกว้างเดิม)
    [$w1920] = getimagesize(Storage::disk('local')->path(FileDelivery::thumbnailPath($old, 1920)));
    expect($w1920)->toBe(800);
});

// ---------------------------------------------------------------- WebP

test('uploading an image also pre-generates WebP thumbnails', function () {
    $hash = $this->actingAs(User::factory()->create())->postJson(route('admin.system.file.upload'), [
        'file' => UploadedFile::fake()->image('photo.jpg', 1400, 900),
    ])->assertCreated()->json('data.hash_name');

    $file = FileInfo::where('hash_name', $hash)->firstOrFail();

    foreach (config('filemanagement.pregenerate_thumbnail_sizes') as $width) {
        Storage::disk('local')->assertExists(FileDelivery::webpPath($file, $width));
    }

    expect(getimagesize(Storage::disk('local')->path(FileDelivery::webpPath($file, 640)))['mime'])->toBe('image/webp');
});

test('thumbnails are served as WebP only to browsers that accept it', function () {
    $hash = $this->actingAs(User::factory()->create())->postJson(route('admin.system.file.upload'), [
        'file' => UploadedFile::fake()->image('photo.png', 1000, 800),
    ])->assertCreated()->json('data.hash_name');

    $url = route('front.file.thumbnail', ['size' => 640, 'hashname' => $hash]);

    $webp = $this->get($url, ['Accept' => 'image/avif,image/webp,*/*'])->assertOk();
    expect($webp->headers->get('Content-Type'))->toBe('image/webp')
        ->and($webp->headers->get('Vary'))->toContain('Accept');

    $png = $this->get($url, ['Accept' => 'image/png,*/*'])->assertOk();
    expect($png->headers->get('Content-Type'))->toBe('image/png')
        ->and($png->headers->get('Vary'))->toContain('Accept')
        ->and($png->headers->get('ETag'))->not->toBe($webp->headers->get('ETag'));

    // ไฟล์ต้นฉบับ (/file/get) ไม่แปลง
    expect($this->get(route('front.file.get', $hash), ['Accept' => 'image/webp,*/*'])->headers->get('Content-Type'))->toBe('image/png');
});

test('GIF images get no WebP thumbnails', function () {
    $hash = $this->actingAs(User::factory()->create())->postJson(route('admin.system.file.upload'), [
        'file' => UploadedFile::fake()->image('anim.gif', 300, 200),
    ])->assertCreated()->json('data.hash_name');

    $file = FileInfo::where('hash_name', $hash)->firstOrFail();

    Storage::disk('local')->assertExists(FileDelivery::thumbnailPath($file, 200));
    Storage::disk('local')->assertMissing(FileDelivery::webpPath($file, 200));

    $response = $this->get(route('front.file.thumbnail', ['size' => 640, 'hashname' => $hash]), ['Accept' => 'image/webp,*/*']);
    expect($response->headers->get('Content-Type'))->toBe('image/gif');
});
