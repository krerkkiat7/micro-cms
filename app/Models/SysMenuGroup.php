<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SysMenuGroup extends Model
{
    use SoftDeletes;

    protected $table = 'sys_menu_group';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'sort_order', 'status'];

    /**
     * เมนูย่อยในกลุ่มนี้
     */
    public function menus()
    {
        return $this->hasMany(SysMenu::class, 'menu_group_id');
    }
}
