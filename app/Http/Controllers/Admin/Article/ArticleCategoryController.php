<?php

namespace App\Http\Controllers\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Article\StoreArticleCategoryRequest;
use App\Http\Requests\Admin\Article\UpdateArticleCategoryRequest;
use App\Models\ArticleCategoryDetail;
use App\Models\ArticleCategoryInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Support\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * จัดการหมวดหมู่บทความ (article_category_info + article_category_detail)
 * log action ทั้งหมดใช้ module_code = "article.category" (ดู docs/PRD-article.md §1)
 */
class ArticleCategoryController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * หน้ารายการหมวดหมู่บทความ — ค้นหา / กรอง / แบ่งหน้า (ชื่อที่แสดงเป็นของภาษาหลักเสมอ)
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('article.category.view')) {
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
        $sortable = ['title', 'sort_order', 'status'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'title';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $sortColumn = $sort === 'title' ? 'd.title' : "article_category_info.{$sort}";

        $categories = ArticleCategoryInfo::query()
            ->join('article_category_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'article_category_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('d.deleted_at') // join ตรง ไม่ผ่าน scope ของ model ต้องกันเองไม่ให้ดึงแถวที่ถูกลบ
            ->select('article_category_info.*', 'd.title as title', 'd.intro_text as intro_text')
            ->when($filters['q'] !== null, function ($query) use ($filters) {
                $term = $filters['q'];
                $query->where(fn ($inner) => $inner
                    ->where('d.title', 'like', "%{$term}%")
                    ->orWhere('d.intro_text', 'like', "%{$term}%"));
            })
            ->when($filters['status'] !== null, fn ($query) => $query->where('article_category_info.status', $filters['status']))
            ->orderBy($sortColumn, $direction)
            ->orderBy('article_category_info.id') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (ArticleCategoryInfo $category) => [
                'id' => $category->id,
                'title' => $category->title,
                'sort_order' => $category->sort_order,
                // ยังไม่มีตาราง article_item — จะผูกจำนวนบทความจริงตอนออกแบบโมดูลบทความ (ดู docs/PRD-article.md §2)
                'article_count' => 0,
                'status' => $category->status,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('รายการหมวดหมู่บทความ');
        }

        return Inertia::render('Admin/Article/Category/Index', [
            'categories' => $categories,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'can' => [
                'manage' => $request->user()->hasPermission('article.category.manage'),
            ],
        ]);
    }

    /**
     * ฟอร์มเพิ่มหมวดหมู่บทความ
     */
    public function add(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('article.category.manage')) {
            return redirect()->route('admin.article.category.index');
        }

        LogBackAccess::record('เพิ่มหมวดหมู่บทความ');

        return Inertia::render('Admin/Article/Category/Add', [
            'languages' => $this->languageOptions(),
        ]);
    }

    /**
     * บันทึกหมวดหมู่บทความใหม่
     */
    public function store(StoreArticleCategoryRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('article.category.manage')) {
            return redirect()->route('admin.article.category.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $category = ArticleCategoryInfo::create([
            'intro_image_id' => $data['intro_image_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'],
            'created_by' => $actorId,
        ]);

        foreach ($data['detail'] as $lang => $detail) {
            ArticleCategoryDetail::create([
                'id' => $category->id,
                'lang' => $lang,
                ...$this->detailPayload($detail),
                'status' => 'Y',
                'created_by' => $actorId,
            ]);
        }

        LogBackAction::record('article.category', 'create', $this->defaultTitle($data), $category->id);

        return redirect()
            ->route('admin.article.category.edit', $category->id)
            ->with('success', 'เพิ่มหมวดหมู่เรียบร้อยแล้ว');
    }

    /**
     * ฟอร์มดู/แก้ไขหมวดหมู่บทความ
     */
    public function edit(Request $request, string $category): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('article.category.view')) {
            return redirect()->route('admin.article.category.index');
        }

        $model = ArticleCategoryInfo::find($category);

        if (! $model) {
            return redirect()->route('admin.article.category.index');
        }

        $details = ArticleCategoryDetail::where('id', $model->id)->get()->keyBy('lang');
        $defaultLang = Setting::defaultLanguage();

        LogBackAccess::record('แก้ไขหมวดหมู่บทความ');
        LogBackAction::record('article.category', 'view', $details->get($defaultLang)?->title, $model->id);

        $introImage = $model->introImage;

        return Inertia::render('Admin/Article/Category/Edit', [
            'category' => [
                'id' => $model->id,
                'sort_order' => $model->sort_order,
                'status' => $model->status,
                'intro_image' => $introImage ? [
                    'id' => $introImage->id,
                    'name' => $introImage->name,
                    'hash_name' => $introImage->hash_name,
                    'extension' => $introImage->extension,
                    'file_size' => $introImage->file_size,
                    'is_image' => $introImage->isImage(),
                    'created_at' => $introImage->created_at,
                ] : null,
            ],
            'details' => collect($this->languageOptions())->mapWithKeys(function (array $lang) use ($details) {
                $detail = $details->get($lang['code']);

                return [$lang['code'] => [
                    'title' => $detail?->title ?? '',
                    'intro_text' => $detail?->intro_text ?? '',
                    'detail' => $detail?->detail ?? '',
                    'slug' => $detail?->slug ?? '',
                    'meta_title' => $detail?->meta_title ?? '',
                    'meta_description' => $detail?->meta_description ?? '',
                    'meta_keywords' => $detail?->meta_keywords ?? '',
                    'og_title' => $detail?->og_title ?? '',
                    'og_description' => $detail?->og_description ?? '',
                ]];
            }),
            'languages' => $this->languageOptions(),
            'can' => [
                'manage' => $request->user()->hasPermission('article.category.manage'),
                'delete' => $request->user()->hasPermission('article.category.delete'),
            ],
        ]);
    }

    /**
     * บันทึกการแก้ไขหมวดหมู่บทความ
     */
    public function update(UpdateArticleCategoryRequest $request, string $category): RedirectResponse
    {
        if (! $request->user()->hasPermission('article.category.manage')) {
            return redirect()->route('admin.article.category.index');
        }

        $model = ArticleCategoryInfo::find($category);

        if (! $model) {
            return redirect()->route('admin.article.category.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $model->fill([
            'intro_image_id' => $data['intro_image_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'],
            'updated_by' => $actorId,
        ])->save();

        foreach ($data['detail'] as $lang => $detail) {
            // ห้ามใช้ ArticleCategoryDetail::find()/->save() กับแถวที่ดึงมา — Eloquent ไม่รู้จัก
            // composite key (id+lang) เอง default query ของ save() จะ WHERE ด้วย id อย่างเดียว
            // ทำให้ทับข้อมูลภาษาอื่นของหมวดหมู่เดียวกันโดยไม่ตั้งใจ ต้อง update() ผ่าน query builder ตรง ๆ
            $updated = ArticleCategoryDetail::where('id', $model->id)
                ->where('lang', $lang)
                ->update($this->detailPayload($detail) + ['updated_by' => $actorId]);

            if ($updated === 0) {
                ArticleCategoryDetail::create([
                    'id' => $model->id,
                    'lang' => $lang,
                    ...$this->detailPayload($detail),
                    'status' => 'Y',
                    'created_by' => $actorId,
                ]);
            }
        }

        LogBackAction::record('article.category', 'update', $this->defaultTitle($data), $model->id);

        return redirect()
            ->route('admin.article.category.edit', $model->id)
            ->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * ลบหมวดหมู่บทความ (soft delete)
     */
    public function destroy(Request $request, string $category): RedirectResponse
    {
        if (! $request->user()->hasPermission('article.category.delete')) {
            return redirect()->route('admin.article.category.index');
        }

        $model = ArticleCategoryInfo::find($category);

        if (! $model) {
            return redirect()->route('admin.article.category.index');
        }

        $title = ArticleCategoryDetail::where('id', $model->id)
            ->where('lang', Setting::defaultLanguage())
            ->value('title');
        $id = $model->id;

        // บันทึกผู้ลบก่อน soft delete (runSoftDelete ไม่ save attribute อื่น)
        $model->deleted_by = $request->user()->id;
        $model->save();
        $model->delete();

        LogBackAction::record('article.category', 'delete', $title, $id);

        return redirect()
            ->route('admin.article.category.index')
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
     * ตัดข้อมูลแยกภาษา 1 ภาษาจาก request ให้เหลือเฉพาะคอลัมน์ของ article_category_detail
     *
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private function detailPayload(array $detail): array
    {
        return [
            'title' => $detail['title'] ?? null,
            'intro_text' => $detail['intro_text'] ?? null,
            'detail' => $detail['detail'] ?? null,
            'slug' => $detail['slug'] ?? null,
            'meta_title' => $detail['meta_title'] ?? null,
            'meta_description' => $detail['meta_description'] ?? null,
            'meta_keywords' => $detail['meta_keywords'] ?? null,
            'og_title' => $detail['og_title'] ?? null,
            'og_description' => $detail['og_description'] ?? null,
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
