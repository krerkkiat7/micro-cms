<?php

use App\Models\FileInfo;
use App\Models\SysAction;
use App\Models\SysSetting;
use App\Models\User;
use App\Models\UserGroup;
use App\Support\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

require_once __DIR__.'/Helpers/PageLayout.php';

function something()
{
    // ..
}

/**
 * สร้างผู้ใช้หลังบ้านในกลุ่มสิทธิ์ใหม่ที่มีเฉพาะ action codes ที่ระบุ แล้ว actingAs
 * (ต้อง seed sys_action ไว้ก่อน เช่น $this->seed(DatabaseSeeder::class))
 *
 * @param  list<string>  $codes
 * @param  array<string, mixed>  $attributes
 */
function actingAsUserWithPermissions(array $codes, array $attributes = []): User
{
    $group = UserGroup::create([
        'name' => 'Test Group '.uniqid(),
        'status' => 'Y',
    ]);

    if ($codes !== []) {
        $group->actions()->attach(
            SysAction::whereIn('code', $codes)->pluck('id')
        );
    }

    $user = User::factory()->create(array_merge(['usergroup_id' => $group->id], $attributes));

    test()->actingAs($user);

    return $user;
}

/**
 * สร้าง file_info พร้อมไฟล์จริงบน disk (fake) — ใช้เป็นโลโก้/favicon/ไฟล์ทั่วไปในเทส
 * (ต้อง Storage::fake('local') ไว้ก่อนในเทสที่เรียกใช้)
 */
function fakeFileInfo(string $name, string $hashName, string $extension, ?string $mimeType = null): FileInfo
{
    $path = "filemanager/{$hashName}";
    Storage::disk('local')->put($path, 'fake-bytes');

    return FileInfo::create([
        'name' => $name,
        'hash_name' => $hashName,
        'extension' => $extension,
        'mime_type' => $mimeType,
        'file_size' => 10,
        'path' => $path,
        'status' => 'Y',
    ]);
}

/**
 * เขียน sys_setting ตรง ๆ (ไม่ผ่าน SettingController) แล้วล้างแคชของกลุ่มนั้นทันที — routes/web.php เอง
 * ก็อ่าน App\Support\Setting::group('site') ตอน register route กลุ่ม {lang} (ผ่าน languageRoutePattern())
 * ทำให้แคชถูก warm ไว้ตั้งแต่ตอน boot ของเทสแล้ว (ก่อนโค้ดในเทสจะรันด้วยซ้ำ) — เขียนตรง ๆ โดยไม่ forget()
 * ค่านั้นจะไม่ถูกเห็นจนกว่า cache TTL (1 วัน) จะหมดอายุ ต่างจากการเขียนผ่าน controller จริงที่ forget() ให้เสมออยู่แล้ว
 */
function setSiteSetting(string $name, string $value): void
{
    SysSetting::create(['group' => 'site', 'name' => $name, 'value' => $value]);
    Setting::forget('site');
}

/**
 * ลบค่าตั้งค่าที่ข้อมูลตัวอย่างใส่ไว้ (เช่น คีย์ทดสอบของ Turnstile, โลโก้) — สำหรับเทสที่ต้องเริ่มจากสถานะ "ยังไม่ตั้งค่า"
 *
 * @param  list<string>  $names  ว่าง = ทั้งกลุ่ม
 */
function forgetSampleSetting(string $group, array $names = []): void
{
    SysSetting::query()
        ->where('group', $group)
        ->when($names !== [], fn ($query) => $query->whereIn('name', $names))
        ->forceDelete();
    Setting::forget($group);
}
