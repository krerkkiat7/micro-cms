<?php

namespace Database\Seeders\Support;

use App\Models\FileInfo;
use App\Models\FolderInfo;
use App\Models\User;
use App\Support\FileDelivery;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * นำเข้าไฟล์ตัวอย่างจาก exampledata/ เข้าโมดูลจัดการไฟล์ (file_info + ไฟล์จริงบน disk) แทนการอัปโหลดผ่านหน้าจอ
 * — เลียนแบบ Admin\System\FileController::upload(): path = filemanager/Y/m/d/{ULID}.{ext}, เจ้าของ = ผู้ดูแลที่ seed ไว้
 *
 * seeder ใช้ WithoutModelEvents → hook creating ของ FileInfo (เติม hash_name) ไม่ทำงาน จึงตั้ง hash_name เอง
 * ตอนรันเทส ไม่คัดลอกไฟล์/ไม่สร้าง thumbnail (สร้างเฉพาะแถว file_info) — เทสเกือบทุกไฟล์ seed DatabaseSeeder
 */
class SampleFiles
{
    public const SOURCE_DIR = 'exampledata';

    /** @var array<string, int> path ต้นฉบับ → file_info.id (นำเข้าครั้งเดียวต่อไฟล์) */
    private static array $imported = [];

    /** @var array<string, int> ชื่อโฟลเดอร์ → folder_info.id */
    private static array $folders = [];

    /** @var list<FileInfo> */
    private static array $images = [];

    /**
     * @param  string  $path  path ใต้ exampledata/ เช่น images/article/news-launch.jpg
     * @param  string  $folder  ชื่อโฟลเดอร์ในโมดูลจัดการไฟล์
     * @param  string|null  $name  ชื่อไฟล์ที่แสดง (ไม่ระบุ = ชื่อไฟล์ต้นฉบับ)
     */
    public static function import(string $path, string $folder, ?string $name = null): int
    {
        $key = $folder.'|'.$path;
        // id ที่จำไว้อาจมาจากฐานข้อมูลชุดก่อน (เทสที่ seed เฉพาะบาง seeder ในแต่ละเทส) — ใช้ต่อเฉพาะเมื่อยังมีอยู่จริง
        if (isset(self::$imported[$key]) && FileInfo::whereKey(self::$imported[$key])->exists()) {
            return self::$imported[$key];
        }
        if (isset(self::$imported[$key])) {
            self::reset();
        }

        $source = base_path(self::SOURCE_DIR.'/'.$path);
        if (! is_file($source)) {
            throw new RuntimeException("ไม่พบไฟล์ตัวอย่าง: {$source}");
        }

        $adminId = self::adminId();
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $hashName = Str::ulid().'.'.$extension;
        $storedPath = config('filemanagement.base_path').'/'.now()->format('Y/m/d').'/'.$hashName;

        if (! app()->runningUnitTests()) {
            Storage::disk(config('filemanagement.disk'))->put($storedPath, file_get_contents($source));
        }

        $file = FileInfo::create([
            'user_id' => $adminId,
            'folder_id' => self::folderId($folder, $adminId),
            'name' => $name ?? basename($source),
            'hash_name' => $hashName,
            'extension' => $extension,
            'mime_type' => config("filemanagement.allowed.{$extension}") ?? (mime_content_type($source) ?: null),
            'file_size' => filesize($source),
            'path' => $storedPath,
            'status' => 'Y',
            'created_by' => $adminId,
        ]);

        if ($file->isImage()) {
            self::$images[] = $file;
        }

        return self::$imported[$key] = $file->id;
    }

    /**
     * สร้าง thumbnail ล่วงหน้าของรูปที่นำเข้า (เหมือนหลังอัปโหลด) — ไม่ทำตอนรันเทส
     */
    public static function pregenerateThumbnails(): int
    {
        $count = 0;
        if (! app()->runningUnitTests()) {
            foreach (self::$images as $file) {
                $count += FileDelivery::pregenerateThumbnails($file);
            }
        }

        self::$images = [];

        return $count;
    }

    /** ล้าง cache ภายใน (เทสที่ seed หลายรอบใน process เดียว — DB ถูก refresh แต่ static ยังอยู่) */
    public static function reset(): void
    {
        self::$imported = [];
        self::$folders = [];
        self::$images = [];
    }

    public static function adminId(): ?int
    {
        return User::where('email', 'admin@microcms.com')->where('user_type', 'back')->value('id');
    }

    private static function folderId(string $name, ?int $adminId): int
    {
        return self::$folders[$name] ??= FolderInfo::create([
            'user_id' => $adminId,
            'name' => $name,
            'status' => 'Y',
            'created_by' => $adminId,
        ])->id;
    }
}
