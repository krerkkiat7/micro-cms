<?php

namespace App\Http\Controllers\Admin\Banner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Banner\StoreBannerItemRequest;
use App\Http\Requests\Admin\Banner\UpdateBannerItemRequest;
use App\Models\BannerCategoryInfo;
use App\Models\BannerItemDetail;
use App\Models\BannerItemInfo;
use App\Models\FileInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Support\Setting;
use App\Support\SystemInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * จัดการป้ายโฆษณา (banner_item_info + banner_item_detail) — แสดงผลเป็นรูปภาพเท่านั้น (รูปภาพใช้ร่วมทุกภาษา
 * ไม่แยกเหมือนบทความ) พร้อมลิงก์ที่กดไปได้ ไม่มีเนื้อหาแบบแบ่ง part หรือแท็กเหมือนบทความ
 * log action ทั้งหมดใช้ module_code = "banner.item" (ดู docs/PRD-banner.md §2)
 */
class BannerItemController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * หน้ารายการป้ายโฆษณา — ค้นหา / กรอง / แบ่งหน้า (ชื่อ/หมวดหมู่ที่แสดงเป็นของภาษาหลักเสมอ)
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.item.view')) {
            return redirect()->route('admin.dashboard');
        }

        $defaultLang = Setting::defaultLanguage();

        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $categoryId = $request->query('category_id');
        $perPage = (int) $request->query('per_page');

        $filters = [
            'q' => $q !== '' ? $q : null,
            'status' => in_array($status, ['Y', 'N'], true) ? $status : null,
            'category_id' => is_numeric($categoryId) ? (int) $categoryId : null,
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0],
        ];

        // เรียงจากชื่อ (ภาษาหลัก) น้อยไปมากเป็นค่าเริ่มต้น
        $sortable = ['title', 'category', 'sort_order', 'publish_date', 'status'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'title';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $sortColumn = match ($sort) {
            'title' => 'd.title',
            'category' => 'cd.title',
            default => "banner_item_info.{$sort}",
        };

        $items = BannerItemInfo::query()
            ->join('banner_item_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'banner_item_info.id')->where('d.lang', $defaultLang);
            })
            ->leftJoin('banner_category_detail as cd', function ($join) use ($defaultLang) {
                $join->on('cd.id', '=', 'banner_item_info.banner_category_info_id')->where('cd.lang', $defaultLang);
            })
            ->leftJoin('file_info as img', 'img.id', '=', 'banner_item_info.intro_image_id')
            ->whereNull('d.deleted_at') // join ตรง ไม่ผ่าน scope ของ model ต้องกันเองไม่ให้ดึงแถวที่ถูกลบ
            ->select('banner_item_info.*', 'd.title as title', 'd.intro_text as intro_text', 'cd.title as category_title', 'img.hash_name as intro_image_hash_name')
            ->when($filters['q'] !== null, function ($query) use ($filters) {
                $term = $filters['q'];
                $query->where(fn ($inner) => $inner
                    ->where('d.title', 'like', "%{$term}%")
                    ->orWhere('d.intro_text', 'like', "%{$term}%"));
            })
            ->when($filters['status'] !== null, fn ($query) => $query->where('banner_item_info.status', $filters['status']))
            ->when($filters['category_id'] !== null, fn ($query) => $query->where('banner_item_info.banner_category_info_id', $filters['category_id']))
            ->orderBy($sortColumn, $direction)
            ->orderBy('banner_item_info.id') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (BannerItemInfo $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'category_title' => $item->category_title,
                'intro_image_hash_name' => $item->intro_image_hash_name,
                'sort_order' => $item->sort_order,
                'publish_date' => optional($item->publish_date)->format('Y-m-d H:i:s'),
                'status' => $item->status,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('รายการป้ายโฆษณา');
        }

        return Inertia::render('Admin/Banner/Item/Index', [
            'items' => $items,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'categories' => $this->categoryOptions(),
            'can' => [
                'manage' => $request->user()->hasPermission('banner.item.manage'),
            ],
        ]);
    }

    /**
     * ฟอร์มเพิ่มป้ายโฆษณา
     */
    public function add(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.item.manage')) {
            return redirect()->route('admin.banner.item.index');
        }

        LogBackAccess::record('เพิ่มป้ายโฆษณา');

        return Inertia::render('Admin/Banner/Item/Add', [
            'languages' => $this->languageOptions(),
            'categories' => $this->categoryOptions(),
        ]);
    }

    /**
     * บันทึกป้ายโฆษณาใหม่
     */
    public function store(StoreBannerItemRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.item.manage')) {
            return redirect()->route('admin.banner.item.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $item = BannerItemInfo::create([
            'banner_category_info_id' => $data['banner_category_info_id'],
            'intro_image_id' => $data['intro_image_id'] ?? null,
            'url' => $data['url'] ?? null,
            'link_target' => $data['link_target'] ?? null,
            'publish_date' => $data['publish_date'] ?? null,
            'publish_down' => $data['publish_down'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'],
            'created_by' => $actorId,
        ]);

        foreach ($data['detail'] as $lang => $detail) {
            BannerItemDetail::create([
                'id' => $item->id,
                'lang' => $lang,
                ...$this->detailPayload($detail),
                'status' => 'Y',
                'created_by' => $actorId,
            ]);
        }

        LogBackAction::record('banner.item', 'create', $this->defaultTitle($data), $item->id);

        return redirect()
            ->route('admin.banner.item.edit', $item->id)
            ->with('success', 'เพิ่มป้ายโฆษณาเรียบร้อยแล้ว');
    }

    /**
     * ฟอร์มดู/แก้ไขป้ายโฆษณา
     */
    public function edit(Request $request, string $item): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.item.view')) {
            return redirect()->route('admin.banner.item.index');
        }

        $model = BannerItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.banner.item.index');
        }

        $details = BannerItemDetail::where('id', $model->id)->get()->keyBy('lang');
        $defaultLang = Setting::defaultLanguage();

        LogBackAccess::record('แก้ไขป้ายโฆษณา');
        LogBackAction::record('banner.item', 'view', $details->get($defaultLang)?->title, $model->id);

        $introImage = $model->introImage;

        return Inertia::render('Admin/Banner/Item/Edit', [
            'item' => [
                'id' => $model->id,
                'banner_category_info_id' => $model->banner_category_info_id,
                'status' => $model->status,
                'url' => $model->url,
                'link_target' => $model->link_target,
                'publish_date' => optional($model->publish_date)->format('Y-m-d H:i:s'),
                'publish_down' => optional($model->publish_down)->format('Y-m-d H:i:s'),
                'sort_order' => $model->sort_order,
                'intro_image' => $introImage ? $this->fileToArray($introImage) : null,
            ],
            'details' => collect($this->languageOptions())->mapWithKeys(fn (array $lang) => [
                $lang['code'] => $this->detailToArray($details->get($lang['code'])),
            ]),
            'languages' => $this->languageOptions(),
            'categories' => $this->categoryOptions(),
            'systemInfo' => SystemInfo::audit($model),
            'clickCount' => (int) $model->click_amount,
            'can' => [
                'manage' => $request->user()->hasPermission('banner.item.manage'),
                'delete' => $request->user()->hasPermission('banner.item.delete'),
            ],
        ]);
    }

    /**
     * บันทึกการแก้ไขป้ายโฆษณา
     */
    public function update(UpdateBannerItemRequest $request, string $item): RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.item.manage')) {
            return redirect()->route('admin.banner.item.index');
        }

        $model = BannerItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.banner.item.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $model->fill([
            'banner_category_info_id' => $data['banner_category_info_id'],
            'intro_image_id' => $data['intro_image_id'] ?? null,
            'url' => $data['url'] ?? null,
            'link_target' => $data['link_target'] ?? null,
            'publish_date' => $data['publish_date'] ?? null,
            'publish_down' => $data['publish_down'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'],
            'updated_by' => $actorId,
        ])->save();

        foreach ($data['detail'] as $lang => $detail) {
            // ห้ามใช้ BannerItemDetail::find()/->save() กับแถวที่ดึงมา — composite key (id+lang)
            // ต้อง update() ผ่าน query builder ตรง ๆ (เหมือน BannerCategoryDetail)
            $updated = BannerItemDetail::where('id', $model->id)
                ->where('lang', $lang)
                ->update($this->detailPayload($detail) + ['updated_by' => $actorId]);

            if ($updated === 0) {
                BannerItemDetail::create([
                    'id' => $model->id,
                    'lang' => $lang,
                    ...$this->detailPayload($detail),
                    'status' => 'Y',
                    'created_by' => $actorId,
                ]);
            }
        }

        LogBackAction::record('banner.item', 'update', $this->defaultTitle($data), $model->id);

        return redirect()
            ->route('admin.banner.item.edit', $model->id)
            ->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * ลบป้ายโฆษณา (soft delete)
     */
    public function destroy(Request $request, string $item): RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.item.delete')) {
            return redirect()->route('admin.banner.item.index');
        }

        $model = BannerItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.banner.item.index');
        }

        $title = BannerItemDetail::where('id', $model->id)
            ->where('lang', Setting::defaultLanguage())
            ->value('title');
        $id = $model->id;

        // บันทึกผู้ลบก่อน soft delete (runSoftDelete ไม่ save attribute อื่น)
        $model->deleted_by = $request->user()->id;
        $model->save();
        $model->delete();

        LogBackAction::record('banner.item', 'delete', $title, $id);

        return redirect()
            ->route('admin.banner.item.index')
            ->with('success', 'ลบป้ายโฆษณาเรียบร้อยแล้ว');
    }

    /**
     * รายการภาษาที่ระบบเปิดใช้งาน พร้อมระบุว่าภาษาไหนเป็นภาษาหลัก — ใช้สร้างฟอร์มข้อมูลแยกภาษา
     *
     * @return list<array{code: string, is_default: bool}>
     */
    private function languageOptions(): array
    {
        $defaultLang = Setting::defaultLanguage();

        $others = array_values(array_filter(Setting::selectedLanguages(), fn (string $code) => $code !== $defaultLang));
        sort($others);
        $ordered = array_merge([$defaultLang], $others);

        return array_map(
            fn (string $code) => ['code' => $code, 'is_default' => $code === $defaultLang],
            $ordered,
        );
    }

    /**
     * ตัวเลือกหมวดหมู่ (เฉพาะที่เปิดใช้งาน) สำหรับ dropdown เลือกหมวดหมู่ — ชื่อเป็นของภาษาหลัก
     * หมวดหมู่ป้ายโฆษณาไม่มี sort_order (ต่างจากหมวดหมู่บทความ) จึงเรียงตามชื่อแทน
     *
     * @return list<array{id: int, title: string|null}>
     */
    private function categoryOptions(): array
    {
        $defaultLang = Setting::defaultLanguage();

        return BannerCategoryInfo::query()
            ->join('banner_category_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'banner_category_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('d.deleted_at')
            ->where('banner_category_info.status', 'Y')
            ->orderBy('d.title')
            ->get(['banner_category_info.id', 'd.title as title'])
            ->map(fn ($row) => ['id' => $row->id, 'title' => $row->title])
            ->values()
            ->all();
    }

    /**
     * ตัดข้อมูลแยกภาษา 1 ภาษาจาก request ให้เหลือเฉพาะคอลัมน์ของ banner_item_detail
     *
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private function detailPayload(array $detail): array
    {
        return [
            'title' => $detail['title'] ?? null,
            'intro_text' => $detail['intro_text'] ?? null,
        ];
    }

    /**
     * แปลงแถว BannerItemDetail เป็น array สำหรับฟอร์ม (ค่าว่าง '' แทน null ให้ฟิลด์ผูกกับ input ได้ตรง ๆ)
     *
     * @return array<string, mixed>
     */
    private function detailToArray(?BannerItemDetail $detail): array
    {
        return [
            'title' => $detail?->title ?? '',
            'intro_text' => $detail?->intro_text ?? '',
        ];
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

    /**
     * ชื่อป้ายโฆษณาของภาษาหลัก จาก validated data — ใช้บันทึก log action
     *
     * @param  array<string, mixed>  $data
     */
    private function defaultTitle(array $data): ?string
    {
        return $data['detail'][Setting::defaultLanguage()]['title'] ?? null;
    }
}
