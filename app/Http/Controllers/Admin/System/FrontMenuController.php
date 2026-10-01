<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\FrontMenu\StoreFrontMenuRequest;
use App\Http\Requests\Admin\System\FrontMenu\UpdateFrontMenuRequest;
use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemInfo;
use App\Models\FrontMenuDetail;
use App\Models\FrontMenuInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Models\PageItemInfo;
use App\Support\FrontMenuType;
use App\Support\PageTextStyle;
use App\Support\Setting;
use App\Support\SystemInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FrontMenuController extends Controller
{
    private const HOME_REQUIRED_MESSAGE = 'ต้องมีเมนูหน้าแรกเสมอ — หากต้องการเปลี่ยน ให้ตั้งเมนูอื่นเป็นหน้าแรกแทน';

    /**
     * ชื่อผู้กระทำ (sys_user.id => ชื่อ) ของเมนูทั้งหมดในหน้ารายการ — เตรียมใน index() ใช้ใน menuNode()
     *
     * @var array<int, string>
     */
    private array $auditNames = [];

    /**
     * หน้ารายการเมนูหน้าบ้านแบบ tree
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.menu.view')) {
            return redirect()->route('admin.dashboard');
        }

        if (count($request->query()) === 0) {
            LogBackAccess::record('จัดการเมนูหน้าบ้าน');
        }

        $menus = FrontMenuInfo::query()
            ->with([
                'details',
                'headerImage',
                'targetArticleCategory.details',
                'targetArticleItem.details',
                'targetArticleItem.category.details',
                'targetPageItem.details',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // ชื่อผู้สร้าง/ผู้ปรับปรุงของทุกเมนูดึงครั้งเดียว (แสดงใน "ข้อมูลระบบ" ของ dialog แก้ไข)
        $this->auditNames = SystemInfo::names([...$menus->pluck('created_by')->all(), ...$menus->pluck('updated_by')->all()]);

        return Inertia::render('Admin/System/FrontMenu/Index', [
            'tree' => $this->buildTree($menus),
            'languages' => $this->languageOptions(),
            'menuTypes' => FrontMenuType::OPTIONS,
            'articleCategories' => $this->articleCategoryOptions(),
            'fonts' => PageTextStyle::fontNames(),
            'fontsUrl' => PageTextStyle::fontsStylesheetUrl(),
            'can' => [
                'manage' => $request->user()->hasPermission('system.menu.manage'),
                'delete' => $request->user()->hasPermission('system.menu.delete'),
            ],
        ]);
    }

    public function store(StoreFrontMenuRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.menu.manage')) {
            return redirect()->route('admin.system.menu.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $menu = DB::transaction(function () use ($data, $actorId) {
            if (($data['is_home'] ?? 'N') === 'Y') {
                FrontMenuInfo::where('is_home', 'Y')->update(['is_home' => 'N', 'updated_by' => $actorId]);
            }

            // เรียงไปอยู่ท้ายสุดภายใต้ parent เดียวกันเสมอ (ไม่ใช้ default 0 ของ migration เพราะจะไปแทรกบนสุด)
            $nextSortOrder = 1 + (int) FrontMenuInfo::where('parent_id', $data['parent_id'] ?? null)->max('sort_order');

            $menu = FrontMenuInfo::create($this->infoPayload($data) + ['sort_order' => $nextSortOrder, 'created_by' => $actorId]);

            foreach ($data['detail'] as $lang => $detail) {
                FrontMenuDetail::create([
                    'id' => $menu->id,
                    'lang' => $lang,
                    ...$this->detailPayload($detail),
                    'status' => 'Y',
                    'created_by' => $actorId,
                ]);
            }

            return $menu;
        });

        LogBackAction::record('system.menu', 'create', $this->defaultName($data), $menu->id);

        return redirect()
            ->route('admin.system.menu.index')
            ->with('success', 'เพิ่มเมนูเรียบร้อยแล้ว');
    }

    public function update(UpdateFrontMenuRequest $request, string $menu): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.menu.manage')) {
            return redirect()->route('admin.system.menu.index');
        }

        $model = FrontMenuInfo::find($menu);

        if (! $model) {
            return redirect()->route('admin.system.menu.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        // ต้องมีเมนูหน้าแรกเสมอ — ยกเลิกได้ด้วยการตั้งเมนูอื่นเป็นหน้าแรกแทน (ไม่งั้น / และ /{lang} จะ 404 เมื่อไม่มี Intropage)
        if ($model->is_home === 'Y' && ($data['is_home'] ?? 'N') !== 'Y') {
            return back()->withErrors(['is_home' => self::HOME_REQUIRED_MESSAGE]);
        }

        DB::transaction(function () use ($data, $actorId, $model) {
            if (($data['is_home'] ?? 'N') === 'Y' && $model->is_home !== 'Y') {
                FrontMenuInfo::where('is_home', 'Y')->update(['is_home' => 'N', 'updated_by' => $actorId]);
            }

            $model->fill($this->infoPayload($data) + ['updated_by' => $actorId])->save();

            foreach ($data['detail'] as $lang => $detail) {
                // ห้ามใช้ FrontMenuDetail::find()/->save() — composite key (id+lang) ต้อง update() ผ่าน query builder ตรง ๆ
                $updated = FrontMenuDetail::where('id', $model->id)
                    ->where('lang', $lang)
                    ->update($this->detailPayload($detail) + ['updated_by' => $actorId]);

                if ($updated === 0) {
                    FrontMenuDetail::create([
                        'id' => $model->id,
                        'lang' => $lang,
                        ...$this->detailPayload($detail),
                        'status' => 'Y',
                        'created_by' => $actorId,
                    ]);
                }
            }
        });

        LogBackAction::record('system.menu', 'update', $this->defaultName($data), $model->id);

        return redirect()
            ->route('admin.system.menu.index')
            ->with('success', 'บันทึกเมนูเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, string $menu): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.menu.delete')) {
            return redirect()->route('admin.system.menu.index');
        }

        $model = FrontMenuInfo::find($menu);

        if (! $model) {
            return redirect()->route('admin.system.menu.index');
        }

        if (FrontMenuInfo::where('parent_id', $model->id)->exists()) {
            return back()->withErrors(['menu' => 'ไม่สามารถลบเมนูที่มีเมนูลูกอยู่ได้']);
        }

        if ($model->is_home === 'Y') {
            return back()->withErrors(['menu' => 'ไม่สามารถลบเมนูที่ตั้งเป็นหน้าหลักอยู่ได้']);
        }

        $name = $this->detailName($model, Setting::defaultLanguage()) ?? (string) $model->id;
        $id = $model->id;

        $model->deleted_by = $request->user()->id;
        $model->save();
        $model->delete();

        LogBackAction::record('system.menu', 'delete', $name, $id);

        return redirect()
            ->route('admin.system.menu.index')
            ->with('success', 'ลบเมนูเรียบร้อยแล้ว');
    }

    /**
     * เมนูนี้เป็นเมนูหน้าแรก หรือมีเมนูหน้าแรกเป็นลูกหลาน
     */
    private function containsHomeMenu(FrontMenuInfo $model): bool
    {
        if ($model->is_home === 'Y') {
            return true;
        }

        $descendantIds = $this->descendantIds($model->id);

        return $descendantIds !== [] && FrontMenuInfo::whereIn('id', $descendantIds)->where('is_home', 'Y')->exists();
    }

    /**
     * สลับแสดง/ซ่อนเมนู — ซ่อนเมนูประเภท "เมนูหัวข้อ" จะซ่อนเมนูลูกทุกระดับไปด้วย (ไม่ cascade ตอนเปิดกลับ)
     */
    public function toggleStatus(Request $request, string $menu): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.menu.manage')) {
            return redirect()->route('admin.system.menu.index');
        }

        $model = FrontMenuInfo::find($menu);

        if (! $model) {
            return redirect()->route('admin.system.menu.index');
        }

        $actorId = $request->user()->id;
        $newStatus = $model->status === 'Y' ? 'N' : 'Y';

        // ซ่อนเมนูหน้าแรก (หรือเมนูหัวข้อที่มีเมนูหน้าแรกอยู่ข้างใน) ไม่ได้ — เมนูที่ไม่แสดงไม่ถูกใช้เป็นหน้าแรก
        if ($newStatus === 'N' && $this->containsHomeMenu($model)) {
            return back()->withErrors(['menu' => 'ไม่สามารถซ่อนเมนูที่ตั้งเป็นหน้าแรก (หรือเมนูหัวข้อที่มีเมนูหน้าแรกอยู่ข้างใน) ได้ กรุณาตั้งเมนูอื่นเป็นหน้าแรกก่อน']);
        }

        DB::transaction(function () use ($model, $newStatus, $actorId) {
            $model->fill(['status' => $newStatus, 'updated_by' => $actorId])->save();

            if ($newStatus === 'N' && $model->menu_type === FrontMenuType::HEADING) {
                $descendantIds = $this->descendantIds($model->id);

                if ($descendantIds !== []) {
                    FrontMenuInfo::whereIn('id', $descendantIds)->update(['status' => 'N', 'updated_by' => $actorId]);
                }
            }
        });

        $name = $this->detailName($model, Setting::defaultLanguage()) ?? (string) $model->id;
        LogBackAction::record('system.menu', 'update', $name, $model->id);

        return back()->with('success', 'ปรับสถานะเมนูเรียบร้อยแล้ว');
    }

    /**
     * บันทึกลำดับ/โครงสร้าง tree ทั้งชุดจาก dialog เรียงลำดับ — รับรายการแบบแบน {id, parent_id, sort_order}
     */
    public function reorder(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.menu.manage')) {
            return redirect()->route('admin.system.menu.index');
        }

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*.id' => ['required', 'integer'],
            'order.*.parent_id' => ['nullable', 'integer'],
            'order.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $items = collect($validated['order']);
        $menus = FrontMenuInfo::whereIn('id', $items->pluck('id'))->get()->keyBy('id');

        if ($menus->count() !== $items->count()) {
            return back()->withErrors(['order' => 'มีรายการเมนูที่ไม่ถูกต้อง']);
        }

        // parent ใหม่ (ถ้าไม่ null) ต้องเป็นเมนูประเภท "เมนูหัวข้อ" และต้องอยู่ในระบบจริง
        foreach ($items as $item) {
            if ($item['parent_id'] === null) {
                continue;
            }

            $parent = $menus->get($item['parent_id']) ?? FrontMenuInfo::find($item['parent_id']);

            if (! $parent || $parent->menu_type !== FrontMenuType::HEADING) {
                return back()->withErrors(['order' => 'วางเมนูได้เฉพาะภายใต้เมนูประเภท "เมนูหัวข้อ" เท่านั้น']);
            }
        }

        // กัน cycle — parent ใหม่ต้องไม่ใช่ตัวเองหรือลูกหลานของตัวเอง (เทียบกับ parent_id ใหม่ทั้งชุดที่ส่งมา)
        $newParentOf = $items->pluck('parent_id', 'id');

        foreach ($items as $item) {
            $ancestorId = $item['parent_id'];
            $path = [$item['id'] => true];

            while ($ancestorId !== null) {
                if (isset($path[$ancestorId])) {
                    return back()->withErrors(['order' => 'โครงสร้างเมนูที่ส่งมาเกิดการวนลูป']);
                }
                $path[$ancestorId] = true;
                $ancestorId = $newParentOf->get($ancestorId, fn () => FrontMenuInfo::find($ancestorId)?->parent_id);
            }
        }

        $actorId = $request->user()->id;

        DB::transaction(function () use ($items, $menus, $actorId) {
            foreach ($items as $item) {
                $menus->get($item['id'])->fill([
                    'parent_id' => $item['parent_id'],
                    'sort_order' => $item['sort_order'],
                    'updated_by' => $actorId,
                ])->save();
            }
        });

        LogBackAction::record('system.menu', 'update', 'เรียงลำดับเมนู', null);

        return back()->with('success', 'เรียงลำดับเมนูเรียบร้อยแล้ว');
    }

    /**
     * รายการบทความสำหรับ dialog "เลือกบทความ" (ประเภทเมนู "บทความ - รายละเอียดบทความ")
     * แสดงเฉพาะบทความที่เผยแพร่อยู่จริง (status='Y' + อยู่ในช่วง publish_date/publish_down — เทียบเคียง
     * App\Support\PageWidget\ReadsArticles::articleQuery()) ค้นหาได้จากชื่อ+เกริ่นนำ กรองตามหมวดหมู่ได้
     */
    public function pickArticles(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('system.menu.view')) {
            abort(403);
        }

        $defaultLang = Setting::defaultLanguage();
        $now = now();
        $q = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category_id');
        $categoryId = is_numeric($categoryId) ? (int) $categoryId : null;

        $sortable = ['title', 'category', 'publish_date'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'title';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $sortColumn = match ($sort) {
            'category' => 'cd.title',
            'publish_date' => 'article_item_info.publish_date',
            default => 'd.title',
        };

        $articles = ArticleItemInfo::query()
            ->join('article_item_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'article_item_info.id')->where('d.lang', $defaultLang);
            })
            ->leftJoin('article_category_detail as cd', function ($join) use ($defaultLang) {
                $join->on('cd.id', '=', 'article_item_info.article_category_info_id')->where('cd.lang', $defaultLang);
            })
            ->whereNull('article_item_info.deleted_at')
            ->whereNull('d.deleted_at')
            ->where('article_item_info.status', 'Y')
            ->where(fn ($query) => $query->whereNull('article_item_info.publish_date')->orWhere('article_item_info.publish_date', '<=', $now))
            ->where(fn ($query) => $query->whereNull('article_item_info.publish_down')->orWhere('article_item_info.publish_down', '>', $now))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(fn ($inner) => $inner
                    ->where('d.title', 'like', "%{$q}%")
                    ->orWhere('d.intro_text', 'like', "%{$q}%"));
            })
            ->when($categoryId !== null, fn ($query) => $query->where('article_item_info.article_category_info_id', $categoryId))
            ->select(
                'article_item_info.id',
                'd.title as title',
                'article_item_info.article_category_info_id as category_id',
                'cd.title as category_title',
                'article_item_info.publish_date',
            )
            ->orderBy($sortColumn, $direction)
            ->orderBy('article_item_info.id') // tie-breaker ให้ลำดับเสถียร
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ArticleItemInfo $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'category_id' => $item->category_id,
                'category_title' => $item->category_title,
                'publish_date' => optional($item->publish_date)->format('Y-m-d H:i:s'),
            ]);

        return response()->json($articles);
    }

    /**
     * รายการหน้าเพจสำหรับ dialog "เลือกหน้าเพจ" (ประเภทเมนู "หน้าเพจ")
     */
    public function pickPages(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('system.menu.view')) {
            abort(403);
        }

        $defaultLang = Setting::defaultLanguage();
        $q = trim((string) $request->query('q', ''));

        $pages = PageItemInfo::query()
            ->join('page_item_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'page_item_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('page_item_info.deleted_at')
            ->whereNull('d.deleted_at')
            ->where('page_item_info.status', 'Y')
            ->when($q !== '', fn ($query) => $query->where('d.title', 'like', "%{$q}%"))
            ->select('page_item_info.id', 'd.title as title')
            ->orderBy('d.title')
            ->paginate(20)
            ->withQueryString();

        return response()->json($pages);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function infoPayload(array $data): array
    {
        return [
            'parent_id' => $data['parent_id'] ?? null,
            'menu_type' => $data['menu_type'],
            'target_article_category_id' => $data['target_article_category_id'] ?? null,
            'target_article_item_id' => $data['target_article_item_id'] ?? null,
            'target_page_item_id' => $data['target_page_item_id'] ?? null,
            'url' => $data['url'] ?? null,
            'link_target' => $data['link_target'],
            'is_home' => $data['is_home'],
            'show_header_image' => $data['show_header_image'],
            'header_image_id' => $data['header_image_id'] ?? null,
            'header_image_aspect_ratio' => $data['header_image_aspect_ratio'],
            'header_image_fit' => $data['header_image_fit'],
            'header_image_background' => $data['header_image_background'],
            'show_title' => $data['show_title'],
            'title_font_size' => $data['title_font_size'],
            'title_font_family' => $data['title_font_family'],
            'title_color' => $data['title_color'],
            'title_bold' => $data['title_bold'],
            'show_subtitle' => $data['show_subtitle'],
            'subtitle_font_size' => $data['subtitle_font_size'],
            'subtitle_font_family' => $data['subtitle_font_family'],
            'subtitle_color' => $data['subtitle_color'],
            'subtitle_bold' => $data['subtitle_bold'],
            'header_content_align' => $data['header_content_align'],
            'use_container' => $data['use_container'],
            'show_breadcrumb' => $data['show_breadcrumb'],
            'status' => $data['status'],
        ];
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private function detailPayload(array $detail): array
    {
        return [
            'name' => $detail['name'] ?? null,
            'title' => $detail['title'] ?? null,
            'subtitle' => $detail['subtitle'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function defaultName(array $data): string
    {
        $defaultLang = Setting::defaultLanguage();

        return $data['detail'][$defaultLang]['name'] ?? '';
    }

    private function detailName(FrontMenuInfo $menu, string $lang): ?string
    {
        return FrontMenuDetail::where('id', $menu->id)->where('lang', $lang)->value('name');
    }

    /**
     * @return list<int>
     */
    private function descendantIds(int $rootId): array
    {
        $ids = [];
        $queue = [$rootId];

        while ($queue !== []) {
            $childIds = FrontMenuInfo::whereIn('parent_id', $queue)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $ids = array_merge($ids, $childIds);
            $queue = $childIds;
        }

        return $ids;
    }

    /**
     * แปลง collection แบบแบนเป็น tree — เทียบเคียง UsergroupController::buildActionTree()/actionNode()
     *
     * @param  Collection<int, FrontMenuInfo>  $menus
     * @return list<array<string, mixed>>
     */
    private function buildTree(Collection $menus): array
    {
        $ids = $menus->pluck('id')->flip();

        return $menus
            ->filter(fn (FrontMenuInfo $menu) => $menu->parent_id === null || ! $ids->has($menu->parent_id))
            ->map(fn (FrontMenuInfo $menu) => $this->menuNode($menu, $menus))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, FrontMenuInfo>  $siblings
     * @param  array<int, true>  $path
     * @return array<string, mixed>
     */
    private function menuNode(FrontMenuInfo $menu, Collection $siblings, array $path = []): array
    {
        $path[$menu->id] = true;

        return [
            'id' => $menu->id,
            'parent_id' => $menu->parent_id,
            'system_info' => SystemInfo::audit($menu, [], $this->auditNames),
            'menu_type' => $menu->menu_type,
            'target_article_category_id' => $menu->target_article_category_id,
            'target_article_item_id' => $menu->target_article_item_id,
            'target_page_item_id' => $menu->target_page_item_id,
            'url' => $menu->url,
            'link_target' => $menu->link_target,
            'is_home' => $menu->is_home,
            'show_header_image' => $menu->show_header_image,
            'header_image_id' => $menu->header_image_id,
            'header_image' => $menu->headerImage ? [
                'id' => $menu->headerImage->id,
                'name' => $menu->headerImage->name,
                'hash_name' => $menu->headerImage->hash_name,
                'extension' => $menu->headerImage->extension,
                'file_size' => $menu->headerImage->file_size,
                'is_image' => $menu->headerImage->isImage(),
                'created_at' => $menu->headerImage->created_at,
            ] : null,
            'header_image_aspect_ratio' => $menu->header_image_aspect_ratio,
            'header_image_fit' => $menu->header_image_fit,
            'header_image_background' => $menu->header_image_background,
            'show_title' => $menu->show_title,
            'title_font_size' => $menu->title_font_size,
            'title_font_family' => $menu->title_font_family,
            'title_color' => $menu->title_color,
            'title_bold' => $menu->title_bold,
            'show_subtitle' => $menu->show_subtitle,
            'subtitle_font_size' => $menu->subtitle_font_size,
            'subtitle_font_family' => $menu->subtitle_font_family,
            'subtitle_color' => $menu->subtitle_color,
            'subtitle_bold' => $menu->subtitle_bold,
            'header_content_align' => $menu->header_content_align,
            'use_container' => $menu->use_container,
            'show_breadcrumb' => $menu->show_breadcrumb,
            'sort_order' => $menu->sort_order,
            'status' => $menu->status,
            // ชื่อเมนูของภาษาหลัก — ใช้แสดงในรายการ/การเรียงลำดับ/ตัวเลือกพาเรนต์ (ไม่พึ่งลำดับ key ของ `detail`
            // ซึ่งเรียงตามรหัสภาษาจาก DB ทำให้ en มาก่อน th)
            'name' => $menu->details->firstWhere('lang', Setting::defaultLanguage())?->name,
            'detail' => $menu->details->keyBy('lang')->map(fn (FrontMenuDetail $d) => [
                'name' => $d->name,
                'title' => $d->title,
                'subtitle' => $d->subtitle,
            ]),
            'target_label' => $this->targetLabel($menu),
            'target_article_item_category' => $this->targetArticleItemCategoryLabel($menu),
            'children' => $siblings
                ->filter(fn (FrontMenuInfo $child) => $child->parent_id === $menu->id && ! isset($path[$child->id]))
                ->map(fn (FrontMenuInfo $child) => $this->menuNode($child, $siblings, $path))
                ->values()
                ->all(),
        ];
    }

    /**
     * ชื่อเป้าหมายภาษาหลัก ไว้แสดงในรายการ (หมวดหมู่/บทความ/หน้าเพจที่เมนูนี้ผูกอยู่)
     */
    private function targetLabel(FrontMenuInfo $menu): ?string
    {
        $defaultLang = Setting::defaultLanguage();

        return match ($menu->menu_type) {
            FrontMenuType::ARTICLE_CATEGORY => $menu->targetArticleCategory?->details
                ->firstWhere('lang', $defaultLang)?->title,
            FrontMenuType::ARTICLE_ITEM => $menu->targetArticleItem?->details
                ->firstWhere('lang', $defaultLang)?->title,
            FrontMenuType::PAGE => $menu->targetPageItem?->details
                ->firstWhere('lang', $defaultLang)?->title,
            FrontMenuType::CONTACTUS => 'หน้าติดต่อเรา',
            default => null,
        };
    }

    /**
     * ชื่อหมวดหมู่ (ภาษาหลัก) ของบทความเป้าหมาย — เฉพาะ menu_type = article_item ไว้แสดง badge ตอนเปิดแก้ไขเมนู
     */
    private function targetArticleItemCategoryLabel(FrontMenuInfo $menu): ?string
    {
        if ($menu->menu_type !== FrontMenuType::ARTICLE_ITEM) {
            return null;
        }

        $defaultLang = Setting::defaultLanguage();

        return $menu->targetArticleItem?->category?->details
            ->firstWhere('lang', $defaultLang)?->title;
    }

    /**
     * ตัวเลือกหมวดหมู่บทความ (ชื่อภาษาหลัก) สำหรับ dropdown เลือกหมวดหมู่ — ประเภทเมนู "บทความ - รายการบทความตามหมวดหมู่"
     *
     * @return list<array{value: string, label: string}>
     */
    private function articleCategoryOptions(): array
    {
        $defaultLang = Setting::defaultLanguage();

        return ArticleCategoryInfo::query()
            ->join('article_category_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'article_category_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('article_category_info.deleted_at')
            ->whereNull('d.deleted_at')
            ->where('article_category_info.status', 'Y')
            ->orderBy('d.title')
            ->get(['article_category_info.id', 'd.title as title'])
            ->map(fn ($row) => ['value' => (string) $row->id, 'label' => $row->title])
            ->all();
    }

    /**
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
}
