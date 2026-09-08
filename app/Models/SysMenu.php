<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SysMenu extends Model
{
    use SoftDeletes;

    protected $table = 'sys_menu';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'menu_group_id',
        'name',
        'route_name',
        'sort_order',
        'action_code',
        'status',
    ];

    /**
     * กลุ่มเมนูที่สังกัด
     */
    public function group()
    {
        return $this->belongsTo(SysMenuGroup::class, 'menu_group_id');
    }
}
