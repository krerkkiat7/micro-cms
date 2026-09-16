<?php

namespace App\Http\Controllers\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Article\StoreArticleItemRequest;
use App\Http\Requests\Admin\Article\UpdateArticleItemRequest;
use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemDetail;
use App\Models\ArticleItemInfo;
use App\Models\ArticleItemPart;
use App\Models\ArticleItemPartDetail;
use App\Models\ArticleItemPartFile;
use App\Models\ArticleTagInfo;
use App\Models\FileInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Support\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * จัดการบทความ (article_item_info + article_item_detail + เนื้อหาแบบแบ่ง part + แท็ก)
 * log action ทั้งหมดใช้ module_code = "article.item" (ดู docs/PRD-article.md §2)
 */
class ArticleItemController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * หน้ารายการบทความ — ค้นหา / กรอง / แบ่งหน้า (ชื่อ/หมวดหมู่ที่แสดงเป็นของภาษาหลักเสมอ)
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('article.item.view')) {
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
        $sortable = ['title', 'category', 'view_amount', 'publish_date', 'status'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'title';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $sortColumn = match ($sort) {
            'title' => 'd.title',
            'category' => 'cd.title',
            default => "article_item_info.{$sort}",
        };

        $items = ArticleItemInfo::query()
            ->join('article_item_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'article_item_info.id')->where('d.lang', $defaultLang);
            })
            ->leftJoin('article_category_detail as cd', function ($join) use ($defaultLang) {
                $join->on('cd.id', '=', 'article_item_info.article_category_info_id')->where('cd.lang', $defaultLang);
            })
            ->whereNull('d.deleted_at') // join ตรง ไม่ผ่าน scope ของ model ต้องกันเองไม่ให้ดึงแถวที่ถูกลบ
            ->select('article_item_info.*', 'd.title as title', 'd.intro_text as intro_text', 'cd.title as category_title')
            ->when($filters['q'] !== null, function ($query) use ($filters) {
                $term = $filters['q'];
                $query->where(fn ($inner) => $inner
                    ->where('d.title', 'like', "%{$term}%")
                    ->orWhere('d.intro_text', 'like', "%{$term}%"));
            })
            ->when($filters['status'] !== null, fn ($query) => $query->where('article_item_info.status', $filters['status']))
            ->when($filters['category_id'] !== null, fn ($query) => $query->where('article_item_info.article_category_info_id', $filters['category_id']))
            ->orderBy($sortColumn, $direction)
            ->orderBy('article_item_info.id') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (ArticleItemInfo $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'category_title' => $item->category_title,
                'view_amount' => $item->view_amount,
                'publish_date' => $item->publish_date,
                'status' => $item->status,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('รายการบทความ');
        }

        return Inertia::render('Admin/Article/Item/Index', [
            'items' => $items,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'categories' => $this->categoryOptions(),
            'can' => [
                'manage' => $request->user()->hasPermission('article.item.manage'),
            ],
        ]);
    }

    /**
     * ฟอร์มเพิ่มบทความ
     */
    public function add(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('article.item.manage')) {
            return redirect()->route('admin.article.item.index');
        }

        LogBackAccess::record('เพิ่มบทความ');

        return Inertia::render('Admin/Article/Item/Add', [
            'languages' => $this->languageOptions(),
            'categories' => $this->categoryOptions(),
            'tags' => $this->tagOptions(),
        ]);
    }

    /**
     * บันทึกบทความใหม่
     */
    public function store(StoreArticleItemRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('article.item.manage')) {
            return redirect()->route('admin.article.item.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $item = DB::transaction(function () use ($data, $actorId) {
            $item = ArticleItemInfo::create([
                'article_category_info_id' => $data['article_category_info_id'],
                'intro_image_id' => $data['intro_image_id'] ?? null,
                'publish_date' => $data['publish_date'],
                'publish_down' => $data['publish_down'] ?? null,
                'status' => $data['status'],
                'created_by' => $actorId,
            ]);

            foreach ($data['detail'] as $lang => $detail) {
                ArticleItemDetail::create([
                    'id' => $item->id,
                    'lang' => $lang,
                    ...$this->detailPayload($detail),
                    'status' => 'Y',
                    'created_by' => $actorId,
                ]);
            }

            $this->syncParts($item, $data['parts'] ?? [], $actorId);
            $this->syncTags($item, $data['tags'] ?? [], $actorId);

            return $item;
        });

        LogBackAction::record('article.item', 'create', $this->defaultTitle($data), $item->id);

        return redirect()
            ->route('admin.article.item.edit', $item->id)
            ->with('success', 'เพิ่มบทความเรียบร้อยแล้ว');
    }

    /**
     * ฟอร์มดู/แก้ไขบทความ
     */
    public function edit(Request $request, string $item): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('article.item.view')) {
            return redirect()->route('admin.article.item.index');
        }

        $model = ArticleItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.article.item.index');
        }

        $details = ArticleItemDetail::where('id', $model->id)->get()->keyBy('lang');
        $defaultLang = Setting::defaultLanguage();

        LogBackAccess::record('แก้ไขบทความ');
        LogBackAction::record('article.item', 'view', $details->get($defaultLang)?->title, $model->id);

        $introImage = $model->introImage;
        $parts = $model->parts()->with(['files.file', 'files.coverImage', 'details'])->get();

        return Inertia::render('Admin/Article/Item/Edit', [
            'item' => [
                'id' => $model->id,
                'article_category_info_id' => $model->article_category_info_id,
                'status' => $model->status,
                'publish_date' => optional($model->publish_date)->format('Y-m-d H:i:s'),
                'publish_down' => optional($model->publish_down)->format('Y-m-d H:i:s'),
                'intro_image' => $introImage ? $this->fileToArray($introImage) : null,
            ],
            'details' => collect($this->languageOptions())->mapWithKeys(fn (array $lang) => [
                $lang['code'] => $this->detailToArray($details->get($lang['code'])),
            ]),
            'parts' => $parts->map(fn (ArticleItemPart $part) => $this->partToArray($part))->values(),
            'tagIds' => $model->tags()->pluck('article_tag_info.id')->values(),
            'languages' => $this->languageOptions(),
            'categories' => $this->categoryOptions(),
            'tags' => $this->tagOptions(),
            'can' => [
                'manage' => $request->user()->hasPermission('article.item.manage'),
                'delete' => $request->user()->hasPermission('article.item.delete'),
            ],
        ]);
    }

    /**
     * บันทึกการแก้ไขบทความ
     */
    public function update(UpdateArticleItemRequest $request, string $item): RedirectResponse
    {
        if (! $request->user()->hasPermission('article.item.manage')) {
            return redirect()->route('admin.article.item.index');
        }

        $model = ArticleItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.article.item.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        DB::transaction(function () use ($model, $data, $actorId) {
            $model->fill([
                'article_category_info_id' => $data['article_category_info_id'],
                'intro_image_id' => $data['intro_image_id'] ?? null,
                'publish_date' => $data['publish_date'],
                'publish_down' => $data['publish_down'] ?? null,
                'status' => $data['status'],
                'updated_by' => $actorId,
            ])->save();

            foreach ($data['detail'] as $lang => $detail) {
                // ห้ามใช้ ArticleItemDetail::find()/->save() กับแถวที่ดึงมา — composite key (id+lang)
                // ต้อง update() ผ่าน query builder ตรง ๆ (เหมือน ArticleCategoryDetail)
                $updated = ArticleItemDetail::where('id', $model->id)
                    ->where('lang', $lang)
                    ->update($this->detailPayload($detail) + ['updated_by' => $actorId]);

                if ($updated === 0) {
                    ArticleItemDetail::create([
                        'id' => $model->id,
                        'lang' => $lang,
                        ...$this->detailPayload($detail),
                        'status' => 'Y',
                        'created_by' => $actorId,
                    ]);
                }
            }

            $this->syncParts($model, $data['parts'] ?? [], $actorId);
            $this->syncTags($model, $data['tags'] ?? [], $actorId);
        });

        LogBackAction::record('article.item', 'update', $this->defaultTitle($data), $model->id);

        return redirect()
            ->route('admin.article.item.edit', $model->id)
            ->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * ลบบทความ (soft delete)
     */
    public function destroy(Request $request, string $item): RedirectResponse
    {
        if (! $request->user()->hasPermission('article.item.delete')) {
            return redirect()->route('admin.article.item.index');
        }

        $model = ArticleItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.article.item.index');
        }

        $title = ArticleItemDetail::where('id', $model->id)
            ->where('lang', Setting::defaultLanguage())
            ->value('title');
        $id = $model->id;

        // บันทึกผู้ลบก่อน soft delete (runSoftDelete ไม่ save attribute อื่น)
        $model->deleted_by = $request->user()->id;
        $model->save();
        $model->delete();

        LogBackAction::record('article.item', 'delete', $title, $id);

        return redirect()
            ->route('admin.article.item.index')
            ->with('success', 'ลบบทความเรียบร้อยแล้ว');
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
     *
     * @return list<array{id: int, title: string|null}>
     */
    private function categoryOptions(): array
    {
        $defaultLang = Setting::defaultLanguage();

        return ArticleCategoryInfo::query()
            ->join('article_category_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'article_category_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('d.deleted_at')
            ->where('article_category_info.status', 'Y')
            ->orderBy('article_category_info.sort_order')
            ->get(['article_category_info.id', 'd.title as title'])
            ->map(fn ($row) => ['id' => $row->id, 'title' => $row->title])
            ->values()
            ->all();
    }

    /**
     * ตัวเลือกแท็ก (เฉพาะที่เปิดใช้งาน) สำหรับผูกกับบทความ — ชื่อเป็นของภาษาหลัก
     *
     * @return list<array{id: int, name: string|null}>
     */
    private function tagOptions(): array
    {
        $defaultLang = Setting::defaultLanguage();

        return ArticleTagInfo::query()
            ->join('article_tag_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'article_tag_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('d.deleted_at')
            ->where('article_tag_info.status', 'Y')
            ->orderBy('d.name')
            ->get(['article_tag_info.id', 'd.name as name'])
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])
            ->values()
            ->all();
    }

    /**
     * ตัดข้อมูลแยกภาษา 1 ภาษาจาก request ให้เหลือเฉพาะคอลัมน์ของ article_item_detail
     *
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private function detailPayload(array $detail): array
    {
        return [
            'title' => $detail['title'] ?? null,
            'intro_text' => $detail['intro_text'] ?? null,
            'slug' => $detail['slug'] ?? null,
            'meta_title' => $detail['meta_title'] ?? null,
            'meta_description' => $detail['meta_description'] ?? null,
            'meta_keywords' => $detail['meta_keywords'] ?? null,
            'og_title' => $detail['og_title'] ?? null,
            'og_description' => $detail['og_description'] ?? null,
        ];
    }

    /**
     * แปลงแถว ArticleItemDetail เป็น array สำหรับฟอร์ม (ค่าว่าง '' แทน null ให้ฟิลด์ผูกกับ input ได้ตรง ๆ)
     *
     * @return array<string, mixed>
     */
    private function detailToArray(?ArticleItemDetail $detail): array
    {
        return [
            'title' => $detail?->title ?? '',
            'intro_text' => $detail?->intro_text ?? '',
            'slug' => $detail?->slug ?? '',
            'meta_title' => $detail?->meta_title ?? '',
            'meta_description' => $detail?->meta_description ?? '',
            'meta_keywords' => $detail?->meta_keywords ?? '',
            'og_title' => $detail?->og_title ?? '',
            'og_description' => $detail?->og_description ?? '',
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
     * แปลง part พร้อมไฟล์/ข้อมูลแยกภาษา เป็น array สำหรับฟอร์มแก้ไข
     *
     * @return array<string, mixed>
     */
    private function partToArray(ArticleItemPart $part): array
    {
        $detailsByLang = $part->details->keyBy('lang');

        return [
            'part_type' => $part->part_type,
            'images_display_type' => $part->images_display_type,
            'setting' => $part->setting ?? [],
            'detail' => collect($this->languageOptions())->mapWithKeys(fn (array $lang) => [
                $lang['code'] => [
                    'title' => $detailsByLang->get($lang['code'])?->title ?? '',
                    'detail' => $detailsByLang->get($lang['code'])?->detail ?? '',
                ],
            ]),
            'files' => $part->files->map(fn (ArticleItemPartFile $file) => [
                'file' => $file->file ? $this->fileToArray($file->file) : null,
                'cover_image' => $file->coverImage ? $this->fileToArray($file->coverImage) : null,
                'video_type' => $file->video_type,
                'youtube_url' => $file->youtube_url,
                'description' => $file->description ?? [],
            ])->values(),
        ];
    }

    /**
     * ชื่อบทความของภาษาหลัก จาก validated data — ใช้บันทึก log action
     *
     * @param  array<string, mixed>  $data
     */
    private function defaultTitle(array $data): ?string
    {
        return $data['detail'][Setting::defaultLanguage()]['title'] ?? null;
    }

    /**
     * แทนที่ part ทั้งหมดของบทความด้วยชุดที่ส่งมาจากฟอร์ม — ฟอร์มส่งเนื้อหา part ทั้งชุดมาใหม่ทุกครั้ง
     * (เหมือน detail ต่อภาษา) การลบ/เพิ่ม/สลับลำดับ part ที่ผู้ใช้ทำในหน้าจอจึงจัดการง่ายกว่าการ diff เอง
     *
     * @param  list<array<string, mixed>>  $partsData
     */
    private function syncParts(ArticleItemInfo $item, array $partsData, int $actorId): void
    {
        $existingParts = $item->parts()->with(['files', 'details'])->get();

        foreach ($existingParts as $part) {
            foreach ($part->files as $file) {
                $file->deleted_by = $actorId;
                $file->save();
                $file->delete();
            }

            ArticleItemPartDetail::where('id', $part->id)->update(['deleted_by' => $actorId]);
            ArticleItemPartDetail::where('id', $part->id)->delete();

            $part->deleted_by = $actorId;
            $part->save();
            $part->delete();
        }

        foreach (array_values($partsData) as $index => $partData) {
            $part = ArticleItemPart::create([
                'article_item_info_id' => $item->id,
                'sort_order' => $index,
                'part_type' => $partData['part_type'],
                'images_display_type' => $partData['images_display_type'] ?? null,
                'setting' => $partData['setting'] ?? null,
                'created_by' => $actorId,
            ]);

            foreach ($partData['detail'] ?? [] as $lang => $detail) {
                $title = $detail['title'] ?? '';
                $text = $detail['detail'] ?? '';

                if ($title === '' && $text === '') {
                    continue;
                }

                ArticleItemPartDetail::create([
                    'id' => $part->id,
                    'lang' => $lang,
                    'title' => $title !== '' ? $title : null,
                    'detail' => $text !== '' ? $text : null,
                    'created_by' => $actorId,
                ]);
            }

            foreach ($partData['files'] ?? [] as $fileIndex => $fileData) {
                if (empty($fileData['file_id']) && empty($fileData['youtube_url'])) {
                    continue;
                }

                ArticleItemPartFile::create([
                    'article_item_part_id' => $part->id,
                    'sort_order' => $fileIndex,
                    'file_id' => $fileData['file_id'] ?? null,
                    'cover_image_id' => $fileData['cover_image_id'] ?? null,
                    'video_type' => $fileData['video_type'] ?? null,
                    'youtube_url' => $fileData['youtube_url'] ?? null,
                    'description' => $fileData['description'] ?? null,
                    'created_by' => $actorId,
                ]);
            }
        }
    }

    /**
     * ผูกแท็กของบทความใหม่ทั้งชุด (แทนที่ของเดิม)
     *
     * @param  list<int>  $tagIds
     */
    private function syncTags(ArticleItemInfo $item, array $tagIds, int $actorId): void
    {
        $item->tags()->sync(collect($tagIds)->mapWithKeys(fn (int $tagId) => [
            $tagId => ['created_by' => $actorId, 'updated_by' => $actorId],
        ])->all());
    }
}
