<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysActionGroup extends Model
{
    protected $table = 'sys_action_group';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'sort_order'];

    /**
     * สิทธิ์ทั้งหมดในกลุ่มนี้
     */
    public function actions()
    {
        return $this->hasMany(SysAction::class, 'action_group_id');
    }
}
