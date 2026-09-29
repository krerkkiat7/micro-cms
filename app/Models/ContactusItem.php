<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ข้อมูลที่ผู้ชมส่งมาจากแบบฟอร์มติดต่อเราที่หน้าบ้าน (ดู docs/PRD-contactus.md)
 *
 * ไม่มี $fillable — ข้อมูลจากหน้าบ้านกำหนด attribute ทีละตัวใน controller เท่านั้น (กัน mass assignment
 * ของคอลัมน์สถานะ/หมายเหตุ/IP จาก input ของผู้ชม)
 */
class ContactusItem extends Model
{
    use SoftDeletes;

    /** สถานะการดำเนินการ => ชื่อที่แสดง */
    public const PROCESS_STATUSES = [
        'unread' => 'ยังไม่ได้อ่าน',
        'read' => 'อ่านแล้ว',
        'considering' => 'พิจารณา',
        'done' => 'เสร็จสิ้น',
    ];

    protected $table = 'contactus_item';

    protected $guarded = ['*'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
