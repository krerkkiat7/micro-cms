<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\AssignUsergroupRightsRequest;
use App\Http\Requests\Admin\System\StoreUsergroupRequest;
use App\Http\Requests\Admin\System\UpdateUsergroupRequest;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Models\SysAction;
use App\Models\SysActionGroup;
use App\Models\UserGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        // การเรียงลำดับ — เริ่มต้นที่ ชื่อกลุ่ม น้อยไปมาก
        $sortable = ['name', 'status', 'actions_count', 'users_count', 'created_at'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $groups = UserGroup::query()
            ->withCount(['actions', 'users'])
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

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('จัดการกลุ่มผู้ใช้งาน');
        }

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

        LogBackAccess::record('เพิ่มกลุ่มผู้ใช้งาน');

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
            'created_by' => $request->user()->id,
        ]);

        LogBackAction::record('system.usergroup', 'create', $group->name, $group->id);

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

        LogBackAccess::record('แก้ไขกลุ่มผู้ใช้งาน');
        LogBackAction::record('system.usergroup', 'view', $group->name, $group->id);

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

        $group->fill($request->validated() + [
            'updated_by' => $request->user()->id,
        ])->save();

        LogBackAction::record('system.usergroup', 'update', $group->name, $group->id);

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

        // ห้ามลบกลุ่มที่ยังมีสมาชิก (ผู้ใช้ที่ยังไม่ถูกลบ ทั้ง back/front)
        if ($group->users()->exists()) {
            return back()->withErrors(['group' => 'ไม่สามารถลบกลุ่มที่ยังมีสมาชิกอยู่']);
        }

        // เก็บข้อมูลไว้ก่อนลบ เพื่อบันทึก log
        $name = $group->name;
        $id = $group->id;

        // บันทึกผู้ลบก่อน soft delete (runSoftDelete ไม่ save attribute อื่น)
        $group->deleted_by = $request->user()->id;
        $group->save();

        $group->delete();

        LogBackAction::record('system.usergroup', 'delete', $name, $id);

        return redirect()
            ->route('admin.system.usergroup.index')
            ->with('success', 'ลบกลุ่มผู้ใช้งานเรียบร้อยแล้ว');
    }

    /**
     * หน้ากำหนดสิทธิ์ของกลุ่ม — ต้นไม้สิทธิ์ตาม sys_action_group / sys_action
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

        $actionGroups = SysActionGroup::query()
            ->where('status', 'Y')
            ->with(['actions' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SysActionGroup $actionGroup) => [
                'id' => $actionGroup->id,
                'name' => $actionGroup->name,
                'total' => $actionGroup->actions->count(),
                'actions' => $this->buildActionTree($actionGroup->actions),
            ])
            ->filter(fn ($actionGroup) => $actionGroup['total'] > 0)
            ->values()
            ->all();

        LogBackAccess::record('กำหนดสิทธิ์กลุ่มผู้ใช้งาน');
        LogBackAction::record('system.usergroup.rights', 'view', $group->name, $group->id);

        return Inertia::render('Admin/System/Usergroup/Rights', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'can_edit' => $group->can_edit,
            ],
            'actionGroups' => $actionGroups,
            'checkedIds' => $group->actions()->pluck('sys_action.id')->all(),
        ]);
    }

    /**
     * บันทึกสิทธิ์ของกลุ่ม — ล้าง pivot เดิมทั้งหมดแล้วบันทึกใหม่ (ลบออกจริง)
     */
    public function rightsUpdate(AssignUsergroupRightsRequest $request, string $usergroup): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.usergroup.rights')) {
            return redirect()->route('admin.system.usergroup.index');
        }

        $group = $this->resolveGroup($usergroup);

        if (! $group) {
            return redirect()->route('admin.system.usergroup.index');
        }

        // กลุ่มระบบ (can_edit = N) แสดงอย่างเดียว บันทึกไม่ได้
        if ($group->can_edit === 'N') {
            return back()->withErrors(['action_ids' => 'กลุ่มนี้เป็นกลุ่มระบบ ไม่อนุญาตให้แก้ไข']);
        }

        $ids = $this->pruneOrphanActions($request->validated()['action_ids'] ?? []);

        $group->actions()->detach();

        if ($ids !== []) {
            // บันทึกผู้กำหนดสิทธิ์ + วันที่ลง pivot (withTimestamps จัดการ created_at/updated_at)
            $actorId = $request->user()->id;
            $group->actions()->attach($ids, [
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }

        LogBackAction::record('system.usergroup.rights', 'update', $group->name, $group->id);

        return redirect()
            ->route('admin.system.usergroup.rights', $group->id)
            ->with('success', 'บันทึกสิทธิ์เรียบร้อยแล้ว');
    }

    /**
     * แปลง collection ของ sys_action (เรียง sort_order แล้ว) เป็น tree ตาม parent_id
     * - root = action ที่ parent_id เป็น null หรือ parent อยู่นอก collection นี้
     * - กัน parent_id ที่วนลูป (cycle) ด้วย $path
     *
     * @param  Collection<int, SysAction>  $actions
     * @return list<array{id: string, code: string, name: string, children: array<mixed>}>
     */
    private function buildActionTree(Collection $actions): array
    {
        $ids = $actions->pluck('id')->flip();

        return $actions
            ->filter(fn (SysAction $action) => $action->parent_id === null
                || ! $ids->has($action->parent_id))
            ->map(fn (SysAction $action) => $this->actionNode($action, $actions))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, SysAction>  $siblings
     * @param  array<string, true>  $path
     * @return array{id: string, code: string, name: string, children: array<mixed>}
     */
    private function actionNode(SysAction $action, Collection $siblings, array $path = []): array
    {
        $path[$action->id] = true;

        return [
            'id' => $action->id,
            'code' => $action->code,
            'name' => $action->name,
            'children' => $siblings
                ->filter(fn (SysAction $child) => $child->parent_id === $action->id
                    && ! isset($path[$child->id]))
                ->map(fn (SysAction $child) => $this->actionNode($child, $siblings, $path))
                ->values()
                ->all(),
        ];
    }

    /**
     * ตัด action ที่ ancestor ยังไม่ถูกเลือกออก (กัน UI ส่งข้อมูลไม่ครบสาย)
     *
     * @param  list<string>  $ids
     * @return list<string>
     */
    private function pruneOrphanActions(array $ids): array
    {
        $parentOf = SysAction::query()->pluck('parent_id', 'id');
        $selected = array_flip($ids);

        return array_values(array_filter($ids, function ($id) use ($parentOf, $selected) {
            $seen = [];
            $cursor = $parentOf[$id] ?? null;

            while ($cursor !== null && ! isset($seen[$cursor])) {
                if (! isset($selected[$cursor])) {
                    return false;
                }
                $seen[$cursor] = true;
                $cursor = $parentOf[$cursor] ?? null;
            }

            return true;
        }));
    }

    /**
     * กลุ่มผู้ใช้งานที่ยังไม่ถูกลบ (SoftDeletes ตัด trashed ให้อยู่แล้ว)
     */
    private function resolveGroup(string $id): ?UserGroup
    {
        return UserGroup::query()
            ->withCount(['actions', 'users'])
            ->find($id);
    }
}
