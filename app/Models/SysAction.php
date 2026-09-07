<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysAction extends Model
{
    //
    protected $table = 'sys_action';
    protected $fillable = ['action_group_id', 'code', 'name'];
}
