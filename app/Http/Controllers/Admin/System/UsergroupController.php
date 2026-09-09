<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\StoreUsergroupRequest;
use App\Http\Requests\Admin\System\UpdateUsergroupRequest;
use App\Models\UserGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsergroupController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * หน้ารายการกลุ่มผู้ใช้งาน — ค้นหา / กรอง / แบ่งหน้า
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.usergroup.view')) {
            return redirect()->route('admin.dashboard');
        }

        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $perPage = (int) $request->query('per_page');

        $filters = [
            'q' => $q !== '' ? $q : null,
            'status' => in_array($status, ['Y', 'N'], true) ? $status : null,
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0],
        ];

        // การเรียงลำดับ — เริ่มต้นที่ วันที่สร้าง มากไปน้อย
        $sortable = ['name', 'status', 'actions_count', 'users_count', 'created_at'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $groups = UserGroup::query()
            ->withCount([
                'actions',
                'users as users_count' => fn ($query) => $query->where('user_type', 'back'),
            ])
            ->when($filters['q'] !== null, function ($query) use ($filters) {
                $term = $filters['q'];

                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when($filters['status'] !== null, fn ($query) => $query->where('status', $filters['status']))
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (UserGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'status' => $group->status,
                'actions_count' => $group->actions_count,
                'users_count' => $group->users_count,
                'can_edit' => $group->can_edit,
                'can_delete' => $group->can_delete,
            ]);

        return Inertia::render('Admin/System/Usergroup/Index', [
            'groups' => $groups,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'can' => [
                'manage' => $request->user()->hasPermission('system.usergroup.manage'),
            ],
        ]);
    }

    /**
     * ฟอร์มเพิ่มกลุ่มผู้ใช้งาน
     */
    public function add(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.usergroup.manage')) {
            return redirect()->route('admin.system.usergroup.index');
        }

        return Inertia::render('Admin/System/Usergroup/Add');
    }

    /**
     * บันทึกกลุ่มผู้ใช้งานใหม่
     */
    public function store(StoreUsergroupRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.usergroup.manage')) {
            return redirect()->route('admin.system.usergroup.index');
        }

        $group = UserGroup::create($request->validated() + [
            'can_edit' => 'Y',
            'can_delete' => 'Y',
        ]);

        return redirect()
            ->route('admin.system.usergroup.edit', $group->id)
            ->with('success', 'เพิ่มกลุ่มผู้ใช้งานเรียบร้อยแล้ว');
    }

    /**
     * ฟอร์มดู/แก้ไขกลุ่มผู้ใช้งาน
     */
    public function edit(Request $request, string $usergroup): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.usergroup.view')) {
            return redirect()->route('admin.system.usergroup.index');
        }

        $group = $this->resolveGroup($usergroup);

        if (! $group) {
            return redirect()->route('admin.system.usergroup.index');
        }

        return Inertia::render('Admin/System/Usergroup/Edit', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'status' => $group->status,
                'can_edit' => $group->can_edit,
                'can_delete' => $group->can_delete,
                'actions_count' => $group->actions_count,
                'users_count' => $group->users_count,
                'created_at' => $group->created_at,
            ],
            'can' => [
                'manage' => $request->user()->hasPermission('system.usergroup.manage'),
                'delete' => $request->user()->hasPermission('system.usergroup.delete'),
                'rights' => $request->user()->hasPermission('system.usergroup.rights'),
            ],
        ]);
    }

    /**
     * บันทึกการแก้ไขกลุ่มผู้ใช้งาน
     */
    public function update(UpdateUsergroupRequest $request, string $usergroup): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.usergroup.manage')) {
            return redirect()->route('admin.system.usergroup.index');
        }

        $group = $this->resolveGroup($usergroup);

        if (! $group) {
            return redirect()->route('admin.system.usergroup.index');
        }

        // กลุ่มระบบ (can_edit = N) แก้ไขไม่ได้
        if ($group->can_edit === 'N') {
            return back()->withErrors(['name' => 'กลุ่มนี้เป็นกลุ่มระบบ ไม่อนุญาตให้แก้ไข']);
        }

        $group->fill($request->validated())->save();

        return redirect()
            ->route('admin.system.usergroup.edit', $group->id)
            ->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * ลบกลุ่มผู้ใช้งาน (soft delete)
     */
    public function destroy(Request $request, string $usergroup): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.usergroup.delete')) {
            return redirect()->route('admin.system.usergroup.index');
        }

        $group = $this->resolveGroup($usergroup);

        if (! $group) {
            return redirect()->route('admin.system.usergroup.index');
        }

        // กลุ่มระบบ (can_delete = N) ลบไม่ได้
        if ($group->can_delete === 'N') {
            return back()->withErrors(['group' => 'กลุ่มนี้เป็นกลุ่มระบบ ไม่อนุญาตให้ลบ']);
        }

        // ห้ามลบกลุ่มที่ยังมีสมาชิก (ผู้ใช้หลังบ้านที่ยังไม่ถูกลบ)
        if ($group->users()->where('user_type', 'back')->exists()) {
            return back()->withErrors(['group' => 'ไม่สามารถลบกลุ่มที่ยังมีสมาชิกอยู่']);
        }

        $group->delete();

        return redirect()
            ->route('admin.system.usergroup.index')
            ->with('success', 'ลบกลุ่มผู้ใช้งานเรียบร้อยแล้ว');
    }

    /**
     * หน้ากำหนดสิทธิ์ของกลุ่ม (ยังเป็นหน้าเปล่า — จะพัฒนาต่อภายหลัง)
     */
    public function rights(Request $request, string $usergroup): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.usergroup.rights')) {
            return redirect()->route('admin.system.usergroup.index');
        }

        $group = $this->resolveGroup($usergroup);

        if (! $group) {
            return redirect()->route('admin.system.usergroup.index');
        }

        return Inertia::render('Admin/System/Usergroup/Rights', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
        ]);
    }

    /**
     * กลุ่มผู้ใช้งานที่ยังไม่ถูกลบ (SoftDeletes ตัด trashed ให้อยู่แล้ว)
     */
    private function resolveGroup(string $id): ?UserGroup
    {
        return UserGroup::query()
            ->withCount([
                'actions',
                'users as users_count' => fn ($query) => $query->where('user_type', 'back'),
            ])
            ->find($id);
    }
}
