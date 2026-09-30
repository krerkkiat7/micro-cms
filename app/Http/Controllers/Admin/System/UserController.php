<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\StoreUserRequest;
use App\Http\Requests\Admin\System\UpdateUserPasswordRequest;
use App\Http\Requests\Admin\System\UpdateUserRequest;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Models\User;
use App\Models\UserGroup;
use App\Support\SystemInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /** ข้อความเมื่อผู้ใช้นอกกลุ่มระบบพยายามจัดการผู้ใช้/ย้ายผู้ใช้เข้ากลุ่มระบบ */
    public const SYSTEM_GROUP_MESSAGE = 'การจัดการผู้ใช้งานในกลุ่มระบบ (เช่น Super Admin) หรือการย้ายผู้ใช้งานเข้ากลุ่มระบบ ต้องให้ผู้ใช้งานในกลุ่มระบบเป็นผู้ดำเนินการ';

    /**
     * หน้ารายการผู้ใช้งานหลังบ้าน — ค้นหา / กรอง / แบ่งหน้า
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.user.view')) {
            return redirect()->route('admin.dashboard');
        }

        $q = trim((string) $request->query('q', ''));
        $usergroupId = trim((string) $request->query('usergroup_id', ''));
        $status = $request->query('status');
        $perPage = (int) $request->query('per_page');

        $filters = [
            'q' => $q !== '' ? $q : null,
            'usergroup_id' => $usergroupId !== '' ? $usergroupId : null,
            'status' => in_array($status, ['Y', 'N'], true) ? $status : null,
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0],
        ];

        // การเรียงลำดับ — เริ่มต้นที่ วันที่สร้าง มากไปน้อย
        $sortable = ['name', 'group', 'email', 'created_at', 'last_login_at', 'status'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $users = User::query()
            ->where('user_type', 'back')
            ->with(['group:id,name', 'profileImage:id,hash_name'])
            ->when($filters['q'] !== null, function ($query) use ($filters) {
                $term = $filters['q'];

                $query->where(function ($inner) use ($term) {
                    $inner->where('firstname', 'like', "%{$term}%")
                        ->orWhere('lastname', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('mobile', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");

                    // รองรับค้นแบบ "ชื่อ นามสกุล" พร้อมกัน
                    if (str_contains($term, ' ')) {
                        [$first, $last] = explode(' ', $term, 2);
                        $inner->orWhere(fn ($w) => $w
                            ->where('firstname', 'like', '%'.trim($first).'%')
                            ->where('lastname', 'like', '%'.trim($last).'%'));
                    }
                });
            })
            ->when($filters['usergroup_id'] !== null, fn ($query) => $query->where('usergroup_id', $filters['usergroup_id']))
            ->when($filters['status'] !== null, fn ($query) => $query->where('status', $filters['status']))
            ->when($sort === 'name', fn ($query) => $query
                ->orderBy('firstname', $direction)
                ->orderBy('lastname', $direction))
            ->when($sort === 'group', fn ($query) => $query->orderBy(
                UserGroup::query()
                    ->select('name')
                    ->whereColumn('sys_usergroup.id', 'sys_user.usergroup_id'),
                $direction
            ))
            ->when(in_array($sort, ['email', 'created_at', 'last_login_at', 'status'], true),
                fn ($query) => $query->orderBy($sort, $direction))
            ->orderBy('id', 'desc') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'group' => $user->group?->name,
                'status' => $user->status,
                'created_at' => $user->created_at,
                'last_login_at' => $user->last_login_at,
                'profile_image_hash_name' => $user->profileImage?->hash_name,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('จัดการผู้ใช้งาน');
        }

        return Inertia::render('Admin/System/User/Index', [
            'users' => $users,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'userGroups' => $this->userGroupOptions($request->user(), includeAll: true),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'can' => [
                'manage' => $request->user()->hasPermission('system.user.manage'),
            ],
        ]);
    }

    /**
     * ฟอร์มเพิ่มผู้ใช้งาน
     */
    public function add(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.user.manage')) {
            return redirect()->route('admin.system.user.index');
        }

        LogBackAccess::record('เพิ่มผู้ใช้งาน');

        return Inertia::render('Admin/System/User/Add', [
            'userGroups' => $this->userGroupOptions($request->user()),
            'systemGroupMessage' => $request->user()->isSystemUser() ? null : self::SYSTEM_GROUP_MESSAGE,
        ]);
    }

    /**
     * บันทึกผู้ใช้งานใหม่ (user_type = back)
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.user.manage')) {
            return redirect()->route('admin.system.user.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        if ($this->movesIntoSystemGroup($request->user(), (int) $data['usergroup_id'])) {
            return back()->withErrors(['usergroup_id' => self::SYSTEM_GROUP_MESSAGE])->withInput();
        }

        $user = User::create([
            'titlename' => $data['titlename'],
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'mobile' => $data['mobile'] ?? null,
            'phone' => $data['phone'] ?? null,
            'line' => $data['line'] ?? null,
            'facebook' => $data['facebook'] ?? null,
            'profile_image_id' => $data['profile_image_id'] ?? null,
            'usergroup_id' => $data['usergroup_id'],
            'status' => $data['status'],
            'password' => Hash::make($data['password']),
            'user_type' => 'back',
            'created_by' => $actorId,
            // สร้างครั้งแรก = ตั้งรหัสผ่านครั้งแรก
            'password_changed_at' => now(),
            'password_changed_by' => $actorId,
        ]);

        LogBackAction::record('system.user', 'create', $user->name, $user->id);

        return redirect()
            ->route('admin.system.user.edit', $user->id)
            ->with('success', 'เพิ่มผู้ใช้งานเรียบร้อยแล้ว');
    }

    /**
     * ฟอร์มดู/แก้ไขผู้ใช้งาน
     */
    public function edit(Request $request, string $user): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.user.view')) {
            return redirect()->route('admin.system.user.index');
        }

        $model = $this->resolveBackUser($user);

        if (! $model) {
            return redirect()->route('admin.system.user.index');
        }

        LogBackAccess::record('แก้ไขผู้ใช้งาน');
        LogBackAction::record('system.user', 'view', $model->name, $model->id);

        $profileImage = $model->profileImage; // อาจเป็น null ทั้งกรณียังไม่ได้เลือก และไฟล์ถูกลบไปแล้ว

        // ผู้ใช้ในกลุ่มระบบ + ผู้ใช้ปัจจุบันไม่ได้อยู่กลุ่มระบบ → ดูได้อย่างเดียว (แสดงข้อความแนะนำในหน้าจอ)
        $protected = $this->isProtected($request->user(), $model);

        return Inertia::render('Admin/System/User/Edit', [
            'user' => [
                'id' => $model->id,
                'titlename' => $model->titlename,
                'firstname' => $model->firstname,
                'lastname' => $model->lastname,
                'name' => $model->name,
                'email' => $model->email,
                'mobile' => $model->mobile,
                'phone' => $model->phone,
                'line' => $model->line,
                'facebook' => $model->facebook,
                'usergroup_id' => $model->usergroup_id,
                'status' => $model->status,
                'created_at' => $model->created_at,
                'last_login_at' => $model->last_login_at,
                'failed_login_count' => $model->failed_login_count,
                'last_failed_login_at' => $model->last_failed_login_at,
                'profile_image' => $profileImage ? [
                    'id' => $profileImage->id,
                    'name' => $profileImage->name,
                    'hash_name' => $profileImage->hash_name,
                    'extension' => $profileImage->extension,
                    'file_size' => $profileImage->file_size,
                    'is_image' => $profileImage->isImage(),
                    'created_at' => $profileImage->created_at,
                ] : null,
            ],
            'userGroups' => $this->userGroupOptions($request->user(), $model->usergroup_id),
            'isSelf' => $model->id === $request->user()->id,
            'protected' => $protected,
            'systemGroupMessage' => $request->user()->isSystemUser() ? null : self::SYSTEM_GROUP_MESSAGE,
            'systemInfo' => SystemInfo::audit($model),
            'can' => [
                'manage' => ! $protected && $request->user()->hasPermission('system.user.manage'),
                'delete' => ! $protected && $request->user()->hasPermission('system.user.delete'),
                'password' => ! $protected && $request->user()->hasPermission('system.user.password'),
            ],
        ]);
    }

    /**
     * บันทึกการแก้ไขผู้ใช้งาน
     */
    public function update(UpdateUserRequest $request, string $user): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.user.manage')) {
            return redirect()->route('admin.system.user.index');
        }

        $model = $this->resolveBackUser($user);

        if (! $model) {
            return redirect()->route('admin.system.user.index');
        }

        $data = $request->validated();

        if ($this->isProtected($request->user(), $model)
            || ($data['usergroup_id'] != $model->usergroup_id && $this->movesIntoSystemGroup($request->user(), (int) $data['usergroup_id']))) {
            return back()->withErrors(['usergroup_id' => self::SYSTEM_GROUP_MESSAGE]);
        }

        // กันไม่ให้ระงับบัญชีของตัวเอง
        if ($model->id === $request->user()->id && $data['status'] === 'N') {
            return back()->withErrors(['status' => 'ไม่สามารถระงับบัญชีของตัวเองได้']);
        }

        $model->fill([
            'titlename' => $data['titlename'],
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'mobile' => $data['mobile'] ?? null,
            'phone' => $data['phone'] ?? null,
            'line' => $data['line'] ?? null,
            'facebook' => $data['facebook'] ?? null,
            'profile_image_id' => $data['profile_image_id'] ?? null,
            'usergroup_id' => $data['usergroup_id'],
            'status' => $data['status'],
            'updated_by' => $request->user()->id,
        ]);

        // เปิดบัญชีคืน (N → Y) — รีเซ็ตตัวนับ login ไม่สำเร็จ ไม่งั้นพิมพ์ผิดอีกครั้งเดียวก็ถูกล็อกอัตโนมัติซ้ำทันที
        if ($model->isDirty('status') && $model->status === 'Y') {
            $model->failed_login_count = 0;
            $model->last_failed_login_at = null;
        }

        $model->save();

        LogBackAction::record('system.user', 'update', $model->name, $model->id);

        return redirect()
            ->route('admin.system.user.edit', $model->id)
            ->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * ลบผู้ใช้งาน (soft delete)
     */
    public function destroy(Request $request, string $user): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.user.delete')) {
            return redirect()->route('admin.system.user.index');
        }

        $model = $this->resolveBackUser($user);

        if (! $model) {
            return redirect()->route('admin.system.user.index');
        }

        // กันไม่ให้ลบบัญชีของตัวเอง
        if ($model->id === $request->user()->id) {
            return back()->withErrors(['user' => 'ไม่สามารถลบบัญชีของตัวเองได้']);
        }

        if ($this->isProtected($request->user(), $model)) {
            return back()->withErrors(['user' => self::SYSTEM_GROUP_MESSAGE]);
        }

        // เก็บข้อมูลไว้ก่อนลบ เพื่อบันทึก log
        $name = $model->name;
        $id = $model->id;

        // บันทึกผู้ลบก่อน soft delete (runSoftDelete ไม่ save attribute อื่น)
        $model->deleted_by = $request->user()->id;
        $model->save();

        $model->delete();

        LogBackAction::record('system.user', 'delete', $name, $id);

        return redirect()
            ->route('admin.system.user.index')
            ->with('success', 'ลบผู้ใช้งานเรียบร้อยแล้ว');
    }

    /**
     * ฟอร์มเปลี่ยนรหัสผ่านให้ผู้ใช้งาน
     */
    public function password(Request $request, string $user): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.user.password')) {
            return redirect()->route('admin.system.user.index');
        }

        $model = $this->resolveBackUser($user);

        if (! $model) {
            return redirect()->route('admin.system.user.index');
        }

        if ($this->isProtected($request->user(), $model)) {
            return redirect()->route('admin.system.user.edit', $model->id);
        }

        LogBackAccess::record('เปลี่ยนรหัสผ่านผู้ใช้งาน');
        LogBackAction::record('system.user.password', 'view', $model->name, $model->id);

        return Inertia::render('Admin/System/User/Password', [
            'user' => [
                'id' => $model->id,
                'name' => $model->name,
                'email' => $model->email,
            ],
        ]);
    }

    /**
     * บันทึกรหัสผ่านใหม่
     */
    public function passwordUpdate(UpdateUserPasswordRequest $request, string $user): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.user.password')) {
            return redirect()->route('admin.system.user.index');
        }

        $model = $this->resolveBackUser($user);

        if (! $model) {
            return redirect()->route('admin.system.user.index');
        }

        if ($this->isProtected($request->user(), $model)) {
            return redirect()->route('admin.system.user.edit', $model->id)->withErrors(['password' => self::SYSTEM_GROUP_MESSAGE]);
        }

        $actorId = $request->user()->id;

        $model->update([
            'password' => Hash::make($request->validated()['password']),
            'password_changed_at' => now(),
            'password_changed_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        // หมุน remember token — คุกกี้ "จดจำฉัน" เดิมของผู้ใช้คนนั้นใช้ไม่ได้อีก (session อื่นหลุดผ่าน auth.session)
        $model->setRememberToken(Str::random(60));
        $model->save();

        LogBackAction::record('system.user.password', 'update', $model->name, $model->id);

        return redirect()
            ->route('admin.system.user.edit', $model->id)
            ->with('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
    }

    /**
     * ผู้ใช้งานหลังบ้านที่ยังไม่ถูกลบ (find() ตัด soft-deleted ให้อยู่แล้ว)
     */
    private function resolveBackUser(string $id): ?User
    {
        return User::query()->where('user_type', 'back')->find($id);
    }

    /**
     * ผู้ใช้เป้าหมายอยู่ในกลุ่มระบบ แต่ผู้กระทำไม่ได้อยู่ในกลุ่มระบบ → ห้ามแก้ไข/ลบ/เปลี่ยนรหัสผ่าน (กันการยกระดับสิทธิ์)
     */
    private function isProtected(User $actor, User $target): bool
    {
        return $target->isSystemUser() && ! $actor->isSystemUser();
    }

    /**
     * ผู้กระทำที่ไม่ได้อยู่ในกลุ่มระบบพยายามใส่ผู้ใช้เข้ากลุ่มระบบ
     */
    private function movesIntoSystemGroup(User $actor, int $usergroupId): bool
    {
        return ! $actor->isSystemUser()
            && UserGroup::query()->whereKey($usergroupId)->where('can_edit', 'N')->exists();
    }

    /**
     * ตัวเลือกกลุ่มผู้ใช้งาน (สำหรับ dropdown) — กลุ่มที่ใช้งานอยู่ + กลุ่มปัจจุบันของผู้ใช้ที่แก้ไข (แม้ถูกปิดใช้งานก็ยังอยู่ในรายการ
     * จะได้บันทึกซ้ำได้โดยไม่ต้องเปลี่ยนกลุ่ม); `includeAll` = ทุกกลุ่ม (ตัวกรองหน้ารายการ)
     * `disabled` = กลุ่มระบบที่ผู้ใช้ปัจจุบันเลือกไม่ได้ (ไม่ได้อยู่กลุ่มระบบ)
     *
     * @return list<array{id: int, name: string, status: string, disabled: bool}>
     */
    private function userGroupOptions(User $actor, ?int $currentId = null, bool $includeAll = false): array
    {
        $actorIsSystem = $actor->isSystemUser();

        return UserGroup::query()
            ->when(! $includeAll, fn ($query) => $query->where(fn ($w) => $w
                ->where('status', 'Y')
                ->when($currentId, fn ($or) => $or->orWhere('id', $currentId))))
            ->orderBy('name')
            ->get(['id', 'name', 'status', 'can_edit'])
            ->map(fn (UserGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'status' => $group->status,
                'disabled' => ! $includeAll && $group->isSystem() && ! $actorIsSystem && $group->id !== $currentId,
            ])
            ->all();
    }
}
