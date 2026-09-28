<?php

namespace App\Http\Controllers\Admin\Banner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Banner\StoreBannerCategoryRequest;
use App\Http\Requests\Admin\Banner\UpdateBannerCategoryRequest;
use App\Models\BannerCategoryDetail;
use App\Models\BannerCategoryInfo;
use App\Models\BannerItemInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Support\Setting;
use App\Support\SystemInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * จัดการหมวดหมู่ป้ายโฆษณา (banner_category_info + banner_category_detail) — หมวดหมู่ในโมดูลนี้ทำหน้าที่เป็น
 * "ตำแหน่งที่ใช้แสดงผล" (เช่น ไฮไลท์, หน่วยงานที่เกี่ยวข้อง) จึงไม่มีรูปภาพ/ลำดับ/SEO เหมือนหมวดหมู่บทความ
 * log action ทั้งหมดใช้ module_code = "banner.category" (ดู docs/PRD-banner.md §1)
 */
class BannerCategoryController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * หน้ารายการหมวดหมู่ป้ายโฆษณา — ค้นหา / กรอง / แบ่งหน้า (ชื่อที่แสดงเป็นของภาษาหลักเสมอ)
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.category.view')) {
            return redirect()->route('admin.dashboard');
        }

        $defaultLang = Setting::defaultLanguage();

        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $perPage = (int) $request->query('per_page');

        $filters = [
            'q' => $q !== '' ? $q : null,
            'status' => in_array($status, ['Y', 'N'], true) ? $status : null,
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0],
        ];

        // เรียงจากชื่อ (ภาษาหลัก) น้อยไปมากเป็นค่าเริ่มต้น
        $sortable = ['title', 'status'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'title';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $sortColumn = $sort === 'title' ? 'd.title' : "banner_category_info.{$sort}";

        $categories = BannerCategoryInfo::query()
            ->join('banner_category_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'banner_category_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('d.deleted_at') // join ตรง ไม่ผ่าน scope ของ model ต้องกันเองไม่ให้ดึงแถวที่ถูกลบ
            ->selectRaw(
                'banner_category_info.*, d.title as title, d.intro_text as intro_text, '.
                '(select count(*) from banner_item_info bi where bi.banner_category_info_id = banner_category_info.id and bi.deleted_at is null) as banner_count'
            )
            ->when($filters['q'] !== null, function ($query) use ($filters) {
                $term = $filters['q'];
                $query->where(fn ($inner) => $inner
                    ->where('d.title', 'like', "%{$term}%")
                    ->orWhere('d.intro_text', 'like', "%{$term}%"));
            })
            ->when($filters['status'] !== null, fn ($query) => $query->where('banner_category_info.status', $filters['status']))
            ->orderBy($sortColumn, $direction)
            ->orderBy('banner_category_info.id') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (BannerCategoryInfo $category) => [
                'id' => $category->id,
                'title' => $category->title,
                'banner_count' => (int) $category->banner_count,
                'status' => $category->status,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('รายการหมวดหมู่ป้ายโฆษณา');
        }

        return Inertia::render('Admin/Banner/Category/Index', [
            'categories' => $categories,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'can' => [
                'manage' => $request->user()->hasPermission('banner.category.manage'),
            ],
        ]);
    }

    /**
     * ฟอร์มเพิ่มหมวดหมู่ป้ายโฆษณา
     */
    public function add(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.category.manage')) {
            return redirect()->route('admin.banner.category.index');
        }

        LogBackAccess::record('เพิ่มหมวดหมู่ป้ายโฆษณา');

        return Inertia::render('Admin/Banner/Category/Add', [
            'languages' => $this->languageOptions(),
        ]);
    }

    /**
     * บันทึกหมวดหมู่ป้ายโฆษณาใหม่
     */
    public function store(StoreBannerCategoryRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.category.manage')) {
            return redirect()->route('admin.banner.category.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $category = BannerCategoryInfo::create([
            'status' => $data['status'],
            'created_by' => $actorId,
        ]);

        foreach ($data['detail'] as $lang => $detail) {
            BannerCategoryDetail::create([
                'id' => $category->id,
                'lang' => $lang,
                ...$this->detailPayload($detail),
                'status' => 'Y',
                'created_by' => $actorId,
            ]);
        }

        LogBackAction::record('banner.category', 'create', $this->defaultTitle($data), $category->id);

        return redirect()
            ->route('admin.banner.category.edit', $category->id)
            ->with('success', 'เพิ่มหมวดหมู่เรียบร้อยแล้ว');
    }

    /**
     * ฟอร์มดู/แก้ไขหมวดหมู่ป้ายโฆษณา
     */
    public function edit(Request $request, string $category): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.category.view')) {
            return redirect()->route('admin.banner.category.index');
        }

        $model = BannerCategoryInfo::find($category);

        if (! $model) {
            return redirect()->route('admin.banner.category.index');
        }

        $details = BannerCategoryDetail::where('id', $model->id)->get()->keyBy('lang');
        $defaultLang = Setting::defaultLanguage();

        LogBackAccess::record('แก้ไขหมวดหมู่ป้ายโฆษณา');
        LogBackAction::record('banner.category', 'view', $details->get($defaultLang)?->title, $model->id);

        return Inertia::render('Admin/Banner/Category/Edit', [
            'category' => [
                'id' => $model->id,
                'status' => $model->status,
            ],
            'details' => collect($this->languageOptions())->mapWithKeys(function (array $lang) use ($details) {
                $detail = $details->get($lang['code']);

                return [$lang['code'] => [
                    'title' => $detail?->title ?? '',
                    'intro_text' => $detail?->intro_text ?? '',
                ]];
            }),
            'languages' => $this->languageOptions(),
            'systemInfo' => SystemInfo::audit($model),
            'bannerCount' => BannerItemInfo::query()->where('banner_category_info_id', $model->id)->count(),
            'can' => [
                'manage' => $request->user()->hasPermission('banner.category.manage'),
                'delete' => $request->user()->hasPermission('banner.category.delete'),
            ],
        ]);
    }

    /**
     * บันทึกการแก้ไขหมวดหมู่ป้ายโฆษณา
     */
    public function update(UpdateBannerCategoryRequest $request, string $category): RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.category.manage')) {
            return redirect()->route('admin.banner.category.index');
        }

        $model = BannerCategoryInfo::find($category);

        if (! $model) {
            return redirect()->route('admin.banner.category.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $model->fill([
            'status' => $data['status'],
            'updated_by' => $actorId,
        ])->save();

        foreach ($data['detail'] as $lang => $detail) {
            // ห้ามใช้ BannerCategoryDetail::find()/->save() กับแถวที่ดึงมา — Eloquent ไม่รู้จัก
            // composite key (id+lang) เอง default query ของ save() จะ WHERE ด้วย id อย่างเดียว
            // ทำให้ทับข้อมูลภาษาอื่นของหมวดหมู่เดียวกันโดยไม่ตั้งใจ ต้อง update() ผ่าน query builder ตรง ๆ
            $updated = BannerCategoryDetail::where('id', $model->id)
                ->where('lang', $lang)
                ->update($this->detailPayload($detail) + ['updated_by' => $actorId]);

            if ($updated === 0) {
                BannerCategoryDetail::create([
                    'id' => $model->id,
                    'lang' => $lang,
                    ...$this->detailPayload($detail),
                    'status' => 'Y',
                    'created_by' => $actorId,
                ]);
            }
        }

        LogBackAction::record('banner.category', 'update', $this->defaultTitle($data), $model->id);

        return redirect()
            ->route('admin.banner.category.edit', $model->id)
            ->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * ลบหมวดหมู่ป้ายโฆษณา (soft delete)
     */
    public function destroy(Request $request, string $category): RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.category.delete')) {
            return redirect()->route('admin.banner.category.index');
        }

        $model = BannerCategoryInfo::find($category);

        if (! $model) {
            return redirect()->route('admin.banner.category.index');
        }

        $title = BannerCategoryDetail::where('id', $model->id)
            ->where('lang', Setting::defaultLanguage())
            ->value('title');
        $id = $model->id;

        // บันทึกผู้ลบก่อน soft delete (runSoftDelete ไม่ save attribute อื่น)
        $model->deleted_by = $request->user()->id;
        $model->save();
        $model->delete();

        LogBackAction::record('banner.category', 'delete', $title, $id);

        return redirect()
            ->route('admin.banner.category.index')
            ->with('success', 'ลบหมวดหมู่เรียบร้อยแล้ว');
    }

    /**
     * รายการภาษาที่ระบบเปิดใช้งาน พร้อมระบุว่าภาษาไหนเป็นภาษาหลัก — ใช้สร้างฟอร์มข้อมูลแยกภาษา
     *
     * @return list<array{code: string, is_default: bool}>
     */
    private function languageOptions(): array
    {
        $defaultLang = Setting::defaultLanguage();

        // ภาษาหลักอยู่ซ้ายสุดเสมอ ภาษาที่เหลือเรียงตามตัวอักษรต่อจากนั้น
        $others = array_values(array_filter(Setting::selectedLanguages(), fn (string $code) => $code !== $defaultLang));
        sort($others);
        $ordered = array_merge([$defaultLang], $others);

        return array_map(
            fn (string $code) => ['code' => $code, 'is_default' => $code === $defaultLang],
            $ordered,
        );
    }

    /**
     * ตัดข้อมูลแยกภาษา 1 ภาษาจาก request ให้เหลือเฉพาะคอลัมน์ของ banner_category_detail
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
     * ชื่อหมวดหมู่ของภาษาหลัก จาก validated data — ใช้บันทึก log action
     *
     * @param  array<string, mixed>  $data
     */
    private function defaultTitle(array $data): ?string
    {
        return $data['detail'][Setting::defaultLanguage()]['title'] ?? null;
    }
}
