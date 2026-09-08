<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysAction extends Model
{
    protected $table = 'sys_action';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'action_group_id', 'parent_id', 'code', 'name', 'sort_order'];

    /**
     * กลุ่มสิทธิ์ที่สังกัด
     */
    public function group()
    {
        return $this->belongsTo(SysActionGroup::class, 'action_group_id');
    }

    /**
     * สิทธิ์แม่ (สำหรับ tree)
     */
    public function parent()
    {
        return $this->belongsTo(SysAction::class, 'parent_id');
    }

    /**
     * สิทธิ์ลูก (สำหรับ tree)
     */
    public function children()
    {
        return $this->hasMany(SysAction::class, 'parent_id');
    }
}
