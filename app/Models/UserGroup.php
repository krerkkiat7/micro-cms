<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    protected $table = 'sys_usergroup';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'description', 'status'];

    public function actions()
    {
        return $this->belongsToMany(SysAction::class, 'sys_usergroup_action', 'usergroup_id', 'action_id');
    }
}
