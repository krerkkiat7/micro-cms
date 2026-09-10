<?php

namespace App\Models;

use Database\Factories\UserGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserGroup extends Model
{
    /** @use HasFactory<UserGroupFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'sys_usergroup';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'description', 'status', 'can_edit', 'can_delete', 'created_by', 'updated_by', 'deleted_by'];

    /**
     * สิทธิ์ (action) ที่ผูกกับกลุ่มนี้
     *
     * pivot `sys_usergroup_action` เก็บ `created_by`/`updated_by` (ผู้กำหนดสิทธิ์) + timestamps
     */
    public function actions()
    {
        return $this->belongsToMany(SysAction::class, 'sys_usergroup_action', 'usergroup_id', 'action_id')
            ->withPivot(['created_by', 'updated_by'])
            ->withTimestamps();
    }

    /**
     * ผู้ใช้ที่อยู่ในกลุ่มนี้ (ทั้ง back/front — กรอง user_type ที่จุดเรียกใช้)
     */
    public function users()
    {
        return $this->hasMany(User::class, 'usergroup_id');
    }
}
