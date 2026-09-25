<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * ไฟล์ที่อัพโหลดในโมดูลจัดการไฟล์ — เป็นของผู้ใช้คนเดียว (user_id), เก็บจริงอยู่นอก public/
 * (`path` เป็น path บน disk ต่อจาก root ของพื้นที่จัดเก็บ — ดู config('filemanagement'))
 *
 * `hash_name` คือชื่อไฟล์ที่จัดเก็บจริง (ไม่เปิดเผยความหมาย) ใช้เป็นค่าใน URL เสิร์ฟไฟล์
 * (/admin/file/get/{hashname}) แทน id เพื่อไม่ให้เดา id ไล่ดูไฟล์คนอื่นได้
 */
class FileInfo extends Model
{
    use FlushesFrontCache, SoftDeletes;

    protected $table = 'file_info';

    protected $fillable = [
        'user_id',
        'folder_id',
        'name',
        'hash_name',
        'file_size',
        'extension',
        'mime_type',
        'path',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * เติม hash_name อัตโนมัติถ้ายังไม่ตั้ง — ULID + นามสกุล (แบบเดียวกับ token ของ LogBackAccess)
     */
    protected static function booted(): void
    {
        static::creating(function (self $file) {
            if (! $file->hash_name) {
                $file->hash_name = (string) Str::ulid().($file->extension ? '.'.$file->extension : '');
            }
        });
    }

    /**
     * โฟลเดอร์ที่ไฟล์นี้อยู่ (null = โฟลเดอร์ราก/ไม่มีโฟลเดอร์)
     */
    public function folder()
    {
        return $this->belongsTo(FolderInfo::class, 'folder_id');
    }

    /**
     * เจ้าของไฟล์ (sys_user)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * จำกัดเฉพาะไฟล์ของผู้ใช้คนหนึ่ง
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * เป็นไฟล์รูปภาพหรือไม่ (ใช้ตัดสินใจว่าจะสร้าง thumbnail ได้ไหม)
     */
    public function isImage(): bool
    {
        return in_array(strtolower((string) $this->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }
}
