<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * โฟลเดอร์ในโมดูลจัดการไฟล์ — เป็นของผู้ใช้คนเดียว (user_id) ใช้จัดกลุ่มไฟล์ใน file_info
 * ไม่มีการลบโฟลเดอร์ในหน้าจอปัจจุบัน (เพิ่มได้อย่างเดียว) — softDeletes ไว้เผื่ออนาคต
 */
class FolderInfo extends Model
{
    use SoftDeletes;

    protected $table = 'folder_info';

    protected $fillable = [
        'user_id',
        'name',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * ไฟล์ที่อยู่ในโฟลเดอร์นี้
     */
    public function files()
    {
        return $this->hasMany(FileInfo::class, 'folder_id');
    }

    /**
     * เจ้าของโฟลเดอร์ (sys_user)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * จำกัดเฉพาะโฟลเดอร์ของผู้ใช้คนหนึ่ง
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
