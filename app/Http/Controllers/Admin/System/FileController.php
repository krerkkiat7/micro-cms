<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\File\StoreFileUploadRequest;
use App\Http\Requests\Admin\System\File\StoreFolderRequest;
use App\Models\FileInfo;
use App\Models\FolderInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Support\FileCache;
use App\Support\FileDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * โมดูลจัดการไฟล์ (file_info/folder_info) — ผู้ใช้หลังบ้านทุกคนเข้าใช้ได้เหมือน Dashboard/Profile
 * (**ไม่เช็ก** `hasPermission()`) เพราะเป็นพื้นที่ไฟล์ส่วนตัวของแต่ละคน — ทุก query จึงต้อง scope
 * ด้วย user_id เสมอ (ข้อกำหนด "แสดงเฉพาะข้อมูลของตัวเองเท่านั้น")
 *
 * หน้า index render ครั้งเดียวแบบ Inertia — ที่เหลือทั้งหมด (โฟลเดอร์/รายการไฟล์/อัพโหลด/ลบ) เป็น
 * ajax (JSON) เพราะต้องใช้ซ้ำได้จาก dialog เลือกไฟล์ที่ฝังอยู่ในฟอร์มของโมดูลอื่น ไม่ใช่แค่หน้านี้หน้าเดียว
 */
class FileController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /** ตัวเลือกการเรียงลำดับที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const SORT_OPTIONS = ['newest', 'oldest', 'name_asc', 'name_desc'];

    /**
     * หน้าจัดการไฟล์ — โหลดข้อมูลจริงทั้งหมดด้วย ajax หลัง mount (ดู endpoint อื่นด้านล่าง)
     */
    public function index(Request $request): Response
    {
        LogBackAccess::record('จัดการไฟล์');

        return Inertia::render('Admin/System/File/Index');
    }

    /**
     * รายการโฟลเดอร์ของผู้ใช้ปัจจุบัน
     */
    public function folders(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $folders = FolderInfo::query()
            ->ownedBy($userId)
            ->where('status', 'Y')
            ->withCount('files')
            ->orderBy('name')
            ->get(['id', 'name']);

        // จำนวนไฟล์ในโฟลเดอร์ราก ("ไม่มีโฟลเดอร์") — แสดงคู่กับ badge จำนวนของโฟลเดอร์อื่น ๆ เหมือนกัน
        $rootCount = FileInfo::query()->ownedBy($userId)->whereNull('folder_id')->count();

        return response()->json(['data' => $folders, 'root_count' => $rootCount]);
    }

    /**
     * สร้างโฟลเดอร์ใหม่ให้ผู้ใช้ปัจจุบัน
     */
    public function storeFolder(StoreFolderRequest $request): JsonResponse
    {
        $folder = FolderInfo::create([
            'user_id' => $request->user()->id,
            'name' => $request->validated('name'),
            'status' => 'Y',
            'created_by' => $request->user()->id,
        ]);

        LogBackAction::record('system.file.folder', 'create', $folder->name, $folder->id);

        return response()->json([
            'data' => ['id' => $folder->id, 'name' => $folder->name, 'files_count' => 0],
        ], 201);
    }

    /**
     * รายการไฟล์แบบแบ่งหน้า — folder_id ไม่ส่งมา/ว่าง = โฟลเดอร์ราก (ไม่มีโฟลเดอร์)
     */
    public function list(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $sort = in_array($request->query('sort'), self::SORT_OPTIONS, true)
            ? $request->query('sort')
            : self::SORT_OPTIONS[0];
        $perPage = (int) $request->query('per_page');
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0];
        $folderId = $request->query('folder_id');
        $isRoot = $folderId === null || $folderId === '';

        $files = FileInfo::query()
            ->ownedBy($request->user()->id)
            ->where('status', 'Y')
            ->when($isRoot, fn ($query) => $query->whereNull('folder_id'))
            ->when(! $isRoot, fn ($query) => $query->where('folder_id', $folderId))
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->when($sort === 'newest', fn ($query) => $query->orderBy('created_at', 'desc'))
            ->when($sort === 'oldest', fn ($query) => $query->orderBy('created_at', 'asc'))
            ->when($sort === 'name_asc', fn ($query) => $query->orderBy('name', 'asc'))
            ->when($sort === 'name_desc', fn ($query) => $query->orderBy('name', 'desc'))
            ->orderBy('id', 'desc') // tie-breaker ให้ลำดับเสถียร
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (FileInfo $file) => $this->fileToArray($file));

        return response()->json($files);
    }

    /**
     * อัพโหลดไฟล์ 1 ไฟล์ — ฝั่ง frontend ยิงหลาย request ขนานกันเองสำหรับ multi-file
     */
    public function upload(StoreFileUploadRequest $request): JsonResponse
    {
        $uploaded = $request->file('file');
        $userId = $request->user()->id;
        $extension = strtolower($uploaded->getClientOriginalExtension());
        $disk = config('filemanagement.disk');
        $subPath = config('filemanagement.base_path').'/'.now()->format('Y/m/d');

        $file = new FileInfo([
            'user_id' => $userId,
            'folder_id' => $request->validated('folder_id'),
            'name' => $uploaded->getClientOriginalName(),
            'extension' => $extension,
            'mime_type' => $uploaded->getMimeType(),
            'file_size' => $uploaded->getSize(),
            'status' => 'Y',
            'created_by' => $userId,
        ]);
        // สร้าง hash_name ก่อนเซฟ เพื่อใช้เป็นชื่อไฟล์จริงบน disk
        $file->hash_name = (string) Str::ulid().'.'.$extension;

        $file->path = $uploaded->storeAs($subPath, $file->hash_name, $disk);
        $file->save();

        LogBackAction::record('system.file', 'upload', $file->name, $file->id);

        // สร้าง thumbnail ขนาดที่ใช้บ่อยไว้ล่วงหน้า หลังส่ง response แล้ว (ผู้อัปโหลดไม่ต้องรอ — ไม่ต้องมี queue worker)
        if ($file->isImage()) {
            defer(fn () => FileDelivery::pregenerateThumbnails($file), 'thumbnails-'.$file->id);
        }

        return response()->json(['data' => $this->fileToArray($file)], 201);
    }

    /**
     * ลบไฟล์ (soft delete) — เฉพาะไฟล์ของตัวเอง
     */
    public function destroy(Request $request, string $file): JsonResponse
    {
        $model = FileInfo::query()->ownedBy($request->user()->id)->find($file);

        if (! $model) {
            return response()->json(['message' => 'ไม่พบไฟล์'], 404);
        }

        $name = $model->name;
        $id = $model->id;
        $hashName = $model->hash_name;

        // บันทึกผู้ลบก่อน soft delete (runSoftDelete ไม่ save attribute อื่น)
        $model->deleted_by = $request->user()->id;
        $model->save();
        $model->delete();

        FileCache::forget($hashName);

        LogBackAction::record('system.file', 'delete', $name, $id);

        return response()->json(['message' => 'ลบไฟล์เรียบร้อยแล้ว']);
    }

    /**
     * @return array<string, mixed>
     */
    private function fileToArray(FileInfo $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'hash_name' => $file->hash_name,
            'extension' => $file->extension,
            'file_size' => $file->file_size,
            'is_image' => $file->isImage(),
            'created_at' => $file->created_at,
        ];
    }
}
