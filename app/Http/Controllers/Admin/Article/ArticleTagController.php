<?php

namespace App\Http\Controllers\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Article\StoreArticleTagRequest;
use App\Http\Requests\Admin\Article\UpdateArticleTagRequest;
use App\Models\ArticleTagDetail;
use App\Models\ArticleTagInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Support\Setting;
use App\Support\SystemInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * จัดการแท็กบทความ (article_tag_info + article_tag_detail) — หน้ารายการ/เพิ่ม/แก้ไข/ลบ เต็มรูปแบบ
 * ตรวจสิทธิ์ article.item.view/manage/delete — ใช้ชุดเดียวกับบทความทั้งหมด ไม่แยกสิทธิ์ article.tag.*
 * ต่างหาก เพราะแท็กสร้างใหม่ได้จากในฟอร์มบทความอยู่แล้ว (ดู quickStore() ด้านล่าง) จึงต้องสัมพันธ์กับสิทธิ์
 * ของบทความเสมอ log action ทั้งหมดใช้ module_code = "article.tag" (ดู docs/PRD-article.md §2.1)
 *
 * ไม่มีฟิลด์ slug ในฟอร์ม — หน้าบ้านที่จะแสดงรายการตามแท็กใช้ชื่อแท็กตรง ๆ ไม่ผ่าน slug
 * (คอลัมน์ slug ยังอยู่ในตาราง แต่ปล่อยว่างจากทางนี้ — มีค่าเฉพาะแท็กที่สร้างผ่าน quickStore() ด้านล่าง)
 *
 * เมธอด search()/quickStore() แยกจาก CRUD หลัก — ใช้เฉพาะจาก TagPicker.vue ในฟอร์มเพิ่ม/แก้ไขบทความ
 * (พิมพ์ชื่อแท็กที่ยังไม่มีแล้วสร้างทันทีโดยไม่ออกจากฟอร์มบทความ)
 */
class ArticleTagController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * หน้ารายการแท็กบทความ — ค้นหา / กรอง / แบ่งหน้า (ชื่อที่แสดงเป็นของภาษาหลักเสมอ)
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('article.item.view')) {
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
        $sortable = ['name', 'status'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $sortColumn = $sort === 'name' ? 'd.name' : "article_tag_info.{$sort}";

        $tags = ArticleTagInfo::query()
            ->join('article_tag_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'article_tag_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('d.deleted_at') // join ตรง ไม่ผ่าน scope ของ model ต้องกันเองไม่ให้ดึงแถวที่ถูกลบ
            ->select('article_tag_info.*', 'd.name as name')
            // withCount ต้องมาหลัง select() เสมอ — select() แทนที่ทั้ง column list เดิม
            // ถ้าเรียกก่อน subquery ของ withCount ที่เพิ่งเติมไว้จะถูกล้างทิ้งไปด้วย
            ->withCount('items') // จำนวนบทความที่แนบแท็กนี้ไว้ — ช่วยตัดสินใจก่อนลบ
            ->when($filters['q'] !== null, fn ($query) => $query->where('d.name', 'like', "%{$filters['q']}%"))
            ->when($filters['status'] !== null, fn ($query) => $query->where('article_tag_info.status', $filters['status']))
            ->orderBy($sortColumn, $direction)
            ->orderBy('article_tag_info.id') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (ArticleTagInfo $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'article_count' => $tag->items_count,
                'status' => $tag->status,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('รายการแท็กบทความ');
        }

        return Inertia::render('Admin/Article/Tag/Index', [
            'tags' => $tags,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'can' => [
                'manage' => $request->user()->hasPermission('article.item.manage'),
            ],
        ]);
    }

    /**
     * ฟอร์มเพิ่มแท็กบทความ
     */
    public function add(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('article.item.manage')) {
            return redirect()->route('admin.article.tag.index');
        }

        LogBackAccess::record('เพิ่มแท็กบทความ');

        return Inertia::render('Admin/Article/Tag/Add', [
            'languages' => $this->languageOptions(),
        ]);
    }

    /**
     * บันทึกแท็กบทความใหม่
     */
    public function store(StoreArticleTagRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('article.item.manage')) {
            return redirect()->route('admin.article.tag.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $tag = ArticleTagInfo::create([
            'status' => $data['status'],
            'created_by' => $actorId,
        ]);

        foreach ($data['detail'] as $lang => $detail) {
            ArticleTagDetail::create([
                'id' => $tag->id,
                'lang' => $lang,
                'name' => $detail['name'] ?? null,
                'status' => 'Y',
                'created_by' => $actorId,
            ]);
        }

        LogBackAction::record('article.tag', 'create', $this->defaultName($data), $tag->id);

        return redirect()
            ->route('admin.article.tag.edit', $tag->id)
            ->with('success', 'เพิ่มแท็กเรียบร้อยแล้ว');
    }

    /**
     * ฟอร์มดู/แก้ไขแท็กบทความ
     */
    public function edit(Request $request, string $tag): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('article.item.view')) {
            return redirect()->route('admin.article.tag.index');
        }

        $model = ArticleTagInfo::find($tag);

        if (! $model) {
            return redirect()->route('admin.article.tag.index');
        }

        $details = ArticleTagDetail::where('id', $model->id)->get()->keyBy('lang');
        $defaultLang = Setting::defaultLanguage();

        LogBackAccess::record('แก้ไขแท็กบทความ');
        LogBackAction::record('article.tag', 'view', $details->get($defaultLang)?->name, $model->id);

        return Inertia::render('Admin/Article/Tag/Edit', [
            'tag' => [
                'id' => $model->id,
                'status' => $model->status,
                'article_count' => $model->items()->count(),
            ],
            'details' => collect($this->languageOptions())->mapWithKeys(function (array $lang) use ($details) {
                $detail = $details->get($lang['code']);

                return [$lang['code'] => [
                    'name' => $detail?->name ?? '',
                ]];
            }),
            'languages' => $this->languageOptions(),
            'systemInfo' => SystemInfo::audit($model),
            'articleCount' => $model->items()->count(),
            'can' => [
                'manage' => $request->user()->hasPermission('article.item.manage'),
                'delete' => $request->user()->hasPermission('article.item.delete'),
            ],
        ]);
    }

    /**
     * บันทึกการแก้ไขแท็กบทความ
     */
    public function update(UpdateArticleTagRequest $request, string $tag): RedirectResponse
    {
        if (! $request->user()->hasPermission('article.item.manage')) {
            return redirect()->route('admin.article.tag.index');
        }

        $model = ArticleTagInfo::find($tag);

        if (! $model) {
            return redirect()->route('admin.article.tag.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $model->fill([
            'status' => $data['status'],
            'updated_by' => $actorId,
        ])->save();

        foreach ($data['detail'] as $lang => $detail) {
            // ห้ามใช้ ArticleTagDetail::find()/->save() กับแถวที่ดึงมา — composite key (id+lang)
            // ต้อง update() ผ่าน query builder ตรง ๆ เหมือน ArticleCategoryController
            $updated = ArticleTagDetail::where('id', $model->id)
                ->where('lang', $lang)
                ->update(['name' => $detail['name'] ?? null, 'updated_by' => $actorId]);

            if ($updated === 0) {
                ArticleTagDetail::create([
                    'id' => $model->id,
                    'lang' => $lang,
                    'name' => $detail['name'] ?? null,
                    'status' => 'Y',
                    'created_by' => $actorId,
                ]);
            }
        }

        LogBackAction::record('article.tag', 'update', $this->defaultName($data), $model->id);

        return redirect()
            ->route('admin.article.tag.edit', $model->id)
            ->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * ลบแท็กบทความ (soft delete)
     */
    public function destroy(Request $request, string $tag): RedirectResponse
    {
        if (! $request->user()->hasPermission('article.item.delete')) {
            return redirect()->route('admin.article.tag.index');
        }

        $model = ArticleTagInfo::find($tag);

        if (! $model) {
            return redirect()->route('admin.article.tag.index');
        }

        $name = ArticleTagDetail::where('id', $model->id)
            ->where('lang', Setting::defaultLanguage())
            ->value('name');
        $id = $model->id;

        // บันทึกผู้ลบก่อน soft delete (runSoftDelete ไม่ save attribute อื่น)
        $model->deleted_by = $request->user()->id;
        $model->save();
        $model->delete();

        LogBackAction::record('article.tag', 'delete', $name, $id);

        return redirect()
            ->route('admin.article.tag.index')
            ->with('success', 'ลบแท็กเรียบร้อยแล้ว');
    }

    /**
     * ค้นหาแท็กจากชื่อภาษาหลัก — คืนไม่เกิน 10 รายการ (ใช้จาก TagPicker.vue)
     * คืนทั้งแท็กที่ใช้งานและไม่ใช้งาน (สถานะติดไปกับผลลัพธ์) เพื่อให้เลือกแท็กที่ไม่ใช้งานได้แต่แสดงเป็นสีเทา
     * ไม่คืนแท็กที่ถูกลบ (soft delete) เพราะ ArticleTagInfo::query() กรองให้อัตโนมัติอยู่แล้ว
     */
    public function search(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('article.item.manage'), 403);

        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json(['data' => []]);
        }

        $defaultLang = Setting::defaultLanguage();

        $tags = ArticleTagInfo::query()
            ->join('article_tag_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'article_tag_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('d.deleted_at')
            ->where('d.name', 'like', "%{$term}%")
            ->orderBy('d.name')
            ->limit(10)
            ->get(['article_tag_info.id', 'article_tag_info.status', 'd.name as name']);

        return response()->json(['data' => $tags]);
    }

    /**
     * สร้างแท็กใหม่แบบด่วน (ใช้ตอนพิมพ์ชื่อแท็กที่ยังไม่มีในระบบจาก TagPicker.vue) — ต้องกรอกชื่อครบทุกภาษาที่ระบบเปิดใช้
     * ชื่อห้ามซ้ำกับแท็กที่มีอยู่แล้วในภาษาเดียวกัน (ทั้งที่ใช้งานและไม่ใช้งาน) เหมือนหน้าจัดการแท็กโดยตรง แต่ไม่นับ
     * แท็กที่ถูกลบไปแล้ว (เช็กผ่าน article_tag_info.deleted_at เพราะ article_tag_detail ไม่ได้ถูกลบพร้อมพาเรนต์)
     */
    public function quickStore(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('article.item.manage'), 403);

        $rules = [];
        foreach (Setting::selectedLanguages() as $lang) {
            $rules["name.{$lang}"] = [
                'required', 'string', 'max:100',
                Rule::unique('article_tag_detail', 'name')->where(fn ($query) => $query
                    ->where('lang', $lang)
                    ->whereIn('id', fn ($sub) => $sub->select('id')->from('article_tag_info')->whereNull('deleted_at'))),
            ];
        }
        $rules['name'] = ['required', 'array'];

        $data = $request->validate($rules);
        $actorId = $request->user()->id;

        $tagInfo = ArticleTagInfo::create([
            'status' => 'Y',
            'created_by' => $actorId,
        ]);

        foreach ($data['name'] as $lang => $name) {
            $slugBase = str($name)->slug()->value();

            ArticleTagDetail::create([
                'id' => $tagInfo->id,
                'lang' => $lang,
                'name' => $name,
                'slug' => $this->uniqueSlug($lang, $slugBase !== '' ? $slugBase : "tag-{$tagInfo->id}"),
                'status' => 'Y',
                'created_by' => $actorId,
            ]);
        }

        $defaultLang = Setting::defaultLanguage();

        return response()->json([
            'data' => [
                'id' => $tagInfo->id,
                'name' => $data['name'][$defaultLang] ?? reset($data['name']),
            ],
        ], 201);
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
     * ชื่อแท็กของภาษาหลัก จาก validated data — ใช้บันทึก log action
     *
     * @param  array<string, mixed>  $data
     */
    private function defaultName(array $data): ?string
    {
        return $data['detail'][Setting::defaultLanguage()]['name'] ?? null;
    }

    /**
     * กัน slug ชนกันภายในภาษาเดียวกัน (unique(['lang','slug'])) โดยเติมเลขต่อท้ายถ้าจำเป็น
     */
    private function uniqueSlug(string $lang, string $base): string
    {
        $slug = $base;
        $suffix = 1;

        while (ArticleTagDetail::where('lang', $lang)->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
