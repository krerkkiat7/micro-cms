<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysActionGroup extends Model
{
    //
    protected $table = 'sys_action_group';
    protected $fillable = ['name', 'sort_order'];
}
