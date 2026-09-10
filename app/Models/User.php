<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    protected $table = 'sys_user';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'titlename',
        'firstname',
        'lastname',
        'email',
        'password',
        'user_type',
        'mobile',
        'phone',
        'line',
        'facebook',
        'status',
        'usergroup_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'password_changed_at',
        'password_changed_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'last_failed_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    /**
     * ชื่อเต็มแบบอ่านง่าย (คำนำหน้า + ชื่อ + นามสกุล) — ใช้แสดงผลใน UI ที่เดิมอ้าง $user->name
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(implode(' ', array_filter([
                $this->titlename,
                $this->firstname,
                $this->lastname,
            ]))),
        );
    }

    public function group()
    {
        return $this->belongsTo(UserGroup::class, 'usergroup_id');
    }

    public function hasPermission(string $actionCode): bool
    {
        if (! $this->group) {
            return false;
        }

        // ดึงรหัส Action ทั้งหมดของผู้ใช้มาเช็กใน Array (คล้ายระบบเดิมของคุณ)
        return $this->group->actions->pluck('code')->contains($actionCode);
    }

    public function getPermissionsArray(): array
    {
        if (! $this->group) {
            return [];
        }

        return $this->group->actions->pluck('code')->toArray();
    }
}
