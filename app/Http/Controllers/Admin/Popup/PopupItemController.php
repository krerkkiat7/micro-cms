<?php

namespace App\Http\Controllers\Admin\Popup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Popup\StorePopupItemRequest;
use App\Http\Requests\Admin\Popup\UpdatePopupItemRequest;
use App\Models\FileInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Models\PopupItemInfo;
use App\Models\PopupItemPart;
use App\Models\PopupItemPartDetail;
use App\Support\FrontMenuTree;
use App\Support\Setting;
use App\Support\SystemInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * จัดการ popup (popup_item_info + part + เมนูที่แสดง) — part และเมนูส่งมาพร้อม store/update ทั้งชุด
 * log action ทั้งหมดใช้ module_code = "popup.item" (ดู docs/PRD-popup.md §2)
 */
class PopupItemController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /** ฟิลด์ข้อมูลร่วมที่บันทึกตรงลง popup_item_info */
    private const INFO_FIELDS = [
        'name', 'display_type', 'show_dismiss_today', 'show_arrows', 'show_dots', 'autoplay',
        'slide_interval', 'slide_speed', 'menu_mode', 'publish_date', 'publish_down', 'status',
    ];

    /**
     * หน้ารายการ popup — ค้นหาจากชื่อ / กรองสถานะ / เรียง / แบ่งหน้า
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('popup.item.view')) {
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

        // เรียงจากวันที่เผยแพร่ใหม่สุดเป็นค่าเริ่มต้น
        $sortable = ['name', 'publish_date', 'publish_down', 'status'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'publish_date';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $items = PopupItemInfo::query()
            ->when($filters['q'] !== null, fn ($query) => $query->where('name', 'like', "%{$filters['q']}%"))
            ->when($filters['status'] !== null, fn ($query) => $query->where('status', $filters['status']))
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (PopupItemInfo $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'display_type' => $item->display_type,
                'publish_date' => optional($item->publish_date)->format('Y-m-d H:i:s'),
                'publish_down' => optional($item->publish_down)->format('Y-m-d H:i:s'),
                'status' => $item->status,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('รายการ Popup');
        }

        return Inertia::render('Admin/Popup/Item/Index', [
            'items' => $items,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'can' => [
                'manage' => $request->user()->hasPermission('popup.item.manage'),
            ],
        ]);
    }

    /**
     * ฟอร์มเพิ่ม popup
     */
    public function add(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('popup.item.manage')) {
            return redirect()->route('admin.popup.item.index');
        }

        LogBackAccess::record('เพิ่ม Popup');

        return Inertia::render('Admin/Popup/Item/Add', [
            'languages' => $this->languageOptions(),
            'menuTree' => FrontMenuTree::adminCheckTree(PopupItemInfo::MENU_TYPES),
        ]);
    }

    /**
     * บันทึก popup ใหม่
     */
    public function store(StorePopupItemRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('popup.item.manage')) {
            return redirect()->route('admin.popup.item.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $item = DB::transaction(function () use ($data, $actorId) {
            $item = PopupItemInfo::create([
                ...$this->infoPayload($data),
                'created_by' => $actorId,
            ]);

            $this->syncParts($item, $data['parts'], $actorId);
            $this->syncMenus($item, $data, $actorId);

            return $item;
        });

        LogBackAction::record('popup.item', 'create', $item->name, $item->id);

        return redirect()
            ->route('admin.popup.item.edit', $item->id)
            ->with('success', 'เพิ่ม Popup เรียบร้อยแล้ว');
    }

    /**
     * ฟอร์มดู/แก้ไข popup
     */
    public function edit(Request $request, string $item): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('popup.item.view')) {
            return redirect()->route('admin.popup.item.index');
        }

        $model = PopupItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.popup.item.index');
        }

        LogBackAccess::record('แก้ไข Popup');
        LogBackAction::record('popup.item', 'view', $model->name, $model->id);

        $languages = $this->languageOptions();
        $parts = $model->parts()->with(['image', 'details'])->get();

        return Inertia::render('Admin/Popup/Item/Edit', [
            'item' => [
                'id' => $model->id,
                ...collect(self::INFO_FIELDS)->mapWithKeys(fn (string $field) => [$field => $model->{$field}])->all(),
                'publish_date' => optional($model->publish_date)->format('Y-m-d H:i:s'),
                'publish_down' => optional($model->publish_down)->format('Y-m-d H:i:s'),
                'sort_order' => $model->sort_order,
                'menu_ids' => $model->menus()->pluck('front_menu_info.id')->map(fn ($id) => (int) $id)->all(),
                'parts' => $parts->map(fn (PopupItemPart $part) => $this->partToArray($part, $languages))->all(),
            ],
            'languages' => $languages,
            'menuTree' => FrontMenuTree::adminCheckTree(PopupItemInfo::MENU_TYPES),
            'systemInfo' => SystemInfo::audit($model),
            'can' => [
                'manage' => $request->user()->hasPermission('popup.item.manage'),
                'delete' => $request->user()->hasPermission('popup.item.delete'),
            ],
        ]);
    }

    /**
     * บันทึกการแก้ไข popup
     */
    public function update(UpdatePopupItemRequest $request, string $item): RedirectResponse
    {
        if (! $request->user()->hasPermission('popup.item.manage')) {
            return redirect()->route('admin.popup.item.index');
        }

        $model = PopupItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.popup.item.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        DB::transaction(function () use ($model, $data, $actorId) {
            $model->fill([
                ...$this->infoPayload($data),
                'updated_by' => $actorId,
            ])->save();

            $this->syncParts($model, $data['parts'], $actorId);
            $this->syncMenus($model, $data, $actorId);
        });

        LogBackAction::record('popup.item', 'update', $model->name, $model->id);

        return redirect()
            ->route('admin.popup.item.edit', $model->id)
            ->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * ลบ popup (soft delete)
     */
    public function destroy(Request $request, string $item): RedirectResponse
    {
        if (! $request->user()->hasPermission('popup.item.delete')) {
            return redirect()->route('admin.popup.item.index');
        }

        $model = PopupItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.popup.item.index');
        }

        $name = $model->name;
        $id = $model->id;

        // บันทึกผู้ลบก่อน soft delete (runSoftDelete ไม่ save attribute อื่น)
        $model->deleted_by = $request->user()->id;
        $model->save();
        $model->delete();

        LogBackAction::record('popup.item', 'delete', $name, $id);

        return redirect()
            ->route('admin.popup.item.index')
            ->with('success', 'ลบ Popup เรียบร้อยแล้ว');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function infoPayload(array $data): array
    {
        return [
            ...collect(self::INFO_FIELDS)->mapWithKeys(fn (string $field) => [$field => $data[$field] ?? null])->all(),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    /**
     * แทนที่ part ทั้งหมดด้วยชุดที่ส่งมาจากฟอร์ม (ลบของเดิมแบบ soft delete แล้วสร้างใหม่ตามลำดับ)
     * เทียบเคียง ArticleItemController::syncParts() — ฟอร์มส่ง part ทั้งชุดมาทุกครั้ง
     *
     * @param  list<array<string, mixed>>  $partsData
     */
    private function syncParts(PopupItemInfo $item, array $partsData, int $actorId): void
    {
        foreach ($item->parts()->get() as $part) {
            PopupItemPartDetail::where('id', $part->id)->update(['deleted_by' => $actorId]);
            PopupItemPartDetail::where('id', $part->id)->delete();

            $part->deleted_by = $actorId;
            $part->save();
            $part->delete();
        }

        foreach (array_values($partsData) as $index => $partData) {
            $type = $partData['part_type'];
            $hasImage = $type !== 'text';
            $hasText = $type !== 'image';

            $part = PopupItemPart::create([
                'popup_item_info_id' => $item->id,
                'part_type' => $type,
                // ค่าของส่วนที่ประเภทนี้ไม่ใช้ ไม่เก็บ (เช่น เปลี่ยนจากรูปภาพ + ข้อความเป็นข้อความ)
                'image_id' => $hasImage ? ($partData['image_id'] ?? null) : null,
                'image_size' => $partData['image_size'] ?? 'full',
                'url' => ($partData['url'] ?? '') !== '' ? $partData['url'] : null,
                'link_target' => $partData['link_target'] ?? '_blank',
                'sort_order' => $index,
                'status' => $partData['status'] ?? 'Y',
                'created_by' => $actorId,
            ]);

            if (! $hasText) {
                continue;
            }

            foreach ($partData['detail'] ?? [] as $lang => $text) {
                if (trim(html_entity_decode(strip_tags((string) $text))) === '') {
                    continue;
                }

                PopupItemPartDetail::create([
                    'id' => $part->id,
                    'lang' => $lang,
                    'detail' => $text,
                    'created_by' => $actorId,
                ]);
            }
        }
    }

    /**
     * ผูกเมนูที่แสดงใหม่ทั้งชุด — เก็บเฉพาะเมื่อเลือก "เมนูที่ระบุ" (โหมดอื่นล้างเมนูที่เคยเลือกออก)
     *
     * @param  array<string, mixed>  $data
     */
    private function syncMenus(PopupItemInfo $item, array $data, int $actorId): void
    {
        $menuIds = $data['menu_mode'] === 'selected' ? ($data['menu_ids'] ?? []) : [];

        $item->menus()->sync(collect($menuIds)->mapWithKeys(fn ($menuId) => [
            (int) $menuId => ['created_by' => $actorId, 'updated_by' => $actorId],
        ])->all());
    }

    /**
     * @param  list<array{code: string, is_default: bool}>  $languages
     * @return array<string, mixed>
     */
    private function partToArray(PopupItemPart $part, array $languages): array
    {
        $details = $part->details->keyBy('lang');

        return [
            'id' => $part->id,
            'part_type' => $part->part_type,
            'image' => $part->image ? $this->fileToArray($part->image) : null,
            'image_size' => $part->image_size,
            'url' => $part->url ?? '',
            'link_target' => $part->link_target,
            'status' => $part->status,
            'detail' => collect($languages)->mapWithKeys(fn (array $lang) => [
                $lang['code'] => (string) ($details->get($lang['code'])?->detail ?? ''),
            ])->all(),
        ];
    }

    /**
     * รายการภาษาที่ระบบเปิดใช้งาน (ภาษาหลักขึ้นก่อน)
     *
     * @return list<array{code: string, is_default: bool}>
     */
    private function languageOptions(): array
    {
        $defaultLang = Setting::defaultLanguage();

        $others = array_values(array_filter(Setting::selectedLanguages(), fn (string $code) => $code !== $defaultLang));
        sort($others);

        return array_map(
            fn (string $code) => ['code' => $code, 'is_default' => $code === $defaultLang],
            array_merge([$defaultLang], $others),
        );
    }

    /**
     * แปลงไฟล์ (file_info) เป็น array รูปแบบเดียวกับที่ FilePickerField.vue ใช้งาน
     *
     * @return array<string, mixed>
     */
    private function fileToArray(FileInfo $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'hash_name' => $file->hash_name,
            'extension' => $file->extension,
            'file_size' => $file->file_size,
            'is_image' => $file->isImage(),
            'created_at' => $file->created_at,
        ];
    }
}
