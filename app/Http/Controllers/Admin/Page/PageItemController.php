<?php

namespace App\Http\Controllers\Admin\Page;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Page\StorePageItemRequest;
use App\Http\Requests\Admin\Page\UpdatePageItemLayoutRequest;
use App\Http\Requests\Admin\Page\UpdatePageItemRequest;
use App\Models\FileInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Models\PageItemColumn;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemRow;
use App\Models\PageItemWidget;
use App\Support\PageLayoutSync;
use App\Support\PageTextStyle;
use App\Support\PageWidget\PageWidgetRegistry;
use App\Support\Setting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * จัดการหน้าเพจเดี่ยว (page_item_info + page_item_detail) และโครงสร้างการแสดงผล แถว → คอลัมน์ → widget
 * (page_item_row/column/widget + *_detail) — ข้อมูลทั่วไปกับโครงสร้างอยู่คนละหน้า (tab) และบันทึกแยกกัน
 * log action: module_code = "page.item" (ข้อมูลทั่วไป) และ "page.item.layout" (โครงสร้าง)
 */
class PageItemController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * หน้ารายการหน้าเพจ — ค้นหา / กรอง / แบ่งหน้า (ชื่อที่แสดงเป็นของภาษาหลักเสมอ)
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('page.item.view')) {
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
        $sortable = ['title', 'created_at', 'status'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'title';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $sortColumn = match ($sort) {
            'title' => 'd.title',
            default => "page_item_info.{$sort}",
        };

        $items = PageItemInfo::query()
            ->join('page_item_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'page_item_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('d.deleted_at') // join ตรง ไม่ผ่าน scope ของ model ต้องกันเองไม่ให้ดึงแถวที่ถูกลบ
            ->select('page_item_info.*', 'd.title as title')
            ->when($filters['q'] !== null, fn ($query) => $query->where('d.title', 'like', '%'.$filters['q'].'%'))
            ->when($filters['status'] !== null, fn ($query) => $query->where('page_item_info.status', $filters['status']))
            ->orderBy($sortColumn, $direction)
            ->orderBy('page_item_info.id') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (PageItemInfo $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
                'status' => $item->status,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('รายการหน้าเพจ');
        }

        return Inertia::render('Admin/Page/Item/Index', [
            'items' => $items,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'can' => [
                'manage' => $request->user()->hasPermission('page.item.manage'),
            ],
        ]);
    }

    /**
     * ฟอร์มเพิ่มหน้าเพจ
     */
    public function add(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('page.item.manage')) {
            return redirect()->route('admin.page.item.index');
        }

        LogBackAccess::record('เพิ่มหน้าเพจ');

        return Inertia::render('Admin/Page/Item/Add', [
            'languages' => $this->languageOptions(),
        ]);
    }

    /**
     * บันทึกหน้าเพจใหม่ (ข้อมูลทั่วไป) — โครงสร้างไปเพิ่มต่อที่ tab "โครงสร้าง" หลังสร้างเสร็จ
     */
    public function store(StorePageItemRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('page.item.manage')) {
            return redirect()->route('admin.page.item.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $item = DB::transaction(function () use ($data, $actorId) {
            $item = PageItemInfo::create([
                ...$this->commonPayload($data),
                'created_by' => $actorId,
            ]);

            foreach ($data['detail'] as $lang => $detail) {
                PageItemDetail::create([
                    'id' => $item->id,
                    'lang' => $lang,
                    ...$this->detailPayload($detail),
                    'status' => 'Y',
                    'created_by' => $actorId,
                ]);
            }

            return $item;
        });

        LogBackAction::record('page.item', 'create', $this->defaultTitle($data), $item->id);

        return redirect()
            ->route('admin.page.item.edit', $item->id)
            ->with('success', 'เพิ่มหน้าเพจเรียบร้อยแล้ว');
    }

    /**
     * ฟอร์มดู/แก้ไขหน้าเพจ (tab ข้อมูลทั่วไป)
     */
    public function edit(Request $request, string $item): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('page.item.view')) {
            return redirect()->route('admin.page.item.index');
        }

        $model = PageItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.page.item.index');
        }

        $details = PageItemDetail::where('id', $model->id)->get()->keyBy('lang');
        $defaultLang = Setting::defaultLanguage();

        LogBackAccess::record('แก้ไขหน้าเพจ');
        LogBackAction::record('page.item', 'view', $details->get($defaultLang)?->title, $model->id);

        $introImage = $model->introImage;
        $backgroundImage = $model->backgroundImage;

        return Inertia::render('Admin/Page/Item/Edit', [
            'item' => [
                'id' => $model->id,
                'intro_image' => $introImage ? $this->fileToArray($introImage) : null,
                'background_color' => $model->background_color,
                'background_image' => $backgroundImage ? $this->fileToArray($backgroundImage) : null,
                'background_repeat' => $model->background_repeat,
                'background_size' => $model->background_size,
                'background_attachment' => $model->background_attachment,
                'background_position' => $model->background_position,
                'status' => $model->status,
            ],
            'details' => collect($this->languageOptions())->mapWithKeys(fn (array $lang) => [
                $lang['code'] => $this->detailToArray($details->get($lang['code'])),
            ]),
            'languages' => $this->languageOptions(),
            'can' => [
                'manage' => $request->user()->hasPermission('page.item.manage'),
                'delete' => $request->user()->hasPermission('page.item.delete'),
            ],
        ]);
    }

    /**
     * บันทึกการแก้ไขหน้าเพจ (ข้อมูลทั่วไป)
     */
    public function update(UpdatePageItemRequest $request, string $item): RedirectResponse
    {
        if (! $request->user()->hasPermission('page.item.manage')) {
            return redirect()->route('admin.page.item.index');
        }

        $model = PageItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.page.item.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        DB::transaction(function () use ($model, $data, $actorId) {
            $model->fill([
                ...$this->commonPayload($data),
                'updated_by' => $actorId,
            ])->save();

            foreach ($data['detail'] as $lang => $detail) {
                // ห้ามใช้ PageItemDetail::find()/->save() กับแถวที่ดึงมา — composite key (id+lang)
                // ต้อง update() ผ่าน query builder ตรง ๆ (ตรวจว่ามีแถวก่อน ไม่ใช้จำนวนแถวที่ update เป็นตัวตัดสิน)
                $query = PageItemDetail::where('id', $model->id)->where('lang', $lang);

                if ($query->exists()) {
                    $query->update($this->detailPayload($detail) + ['updated_by' => $actorId]);
                } else {
                    PageItemDetail::create([
                        'id' => $model->id,
                        'lang' => $lang,
                        ...$this->detailPayload($detail),
                        'status' => 'Y',
                        'created_by' => $actorId,
                    ]);
                }
            }
        });

        LogBackAction::record('page.item', 'update', $this->defaultTitle($data), $model->id);

        return redirect()
            ->route('admin.page.item.edit', $model->id)
            ->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * ลบหน้าเพจ (soft delete)
     */
    public function destroy(Request $request, string $item): RedirectResponse
    {
        if (! $request->user()->hasPermission('page.item.delete')) {
            return redirect()->route('admin.page.item.index');
        }

        $model = PageItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.page.item.index');
        }

        $title = PageItemDetail::where('id', $model->id)
            ->where('lang', Setting::defaultLanguage())
            ->value('title');
        $id = $model->id;

        // บันทึกผู้ลบก่อน soft delete (runSoftDelete ไม่ save attribute อื่น)
        $model->deleted_by = $request->user()->id;
        $model->save();
        $model->delete();

        // page_item_detail ไม่ถูก soft delete พร้อมพาเรนต์ แต่ unique(lang, slug) ที่ระดับ DB ยังนับแถวพวกนี้อยู่
        // — เคลียร์ slug ทิ้งเพื่อให้ใช้ slug เดิมกับหน้าใหม่ได้หลังลบ (validation เช็กผ่านพาเรนต์อยู่แล้ว)
        PageItemDetail::where('id', $id)->update(['slug' => null]);

        LogBackAction::record('page.item', 'delete', $title, $id);

        return redirect()
            ->route('admin.page.item.index')
            ->with('success', 'ลบหน้าเพจเรียบร้อยแล้ว');
    }

    /**
     * หน้าโครงสร้าง (tab โครงสร้าง) — แสดง/จัดการ แถว → คอลัมน์ → widget ของหน้าเพจ
     */
    public function layout(Request $request, string $item): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('page.item.view')) {
            return redirect()->route('admin.page.item.index');
        }

        $model = PageItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.page.item.index');
        }

        $title = PageItemDetail::where('id', $model->id)
            ->where('lang', Setting::defaultLanguage())
            ->value('title');

        LogBackAccess::record('โครงสร้างหน้าเพจ');
        LogBackAction::record('page.item.layout', 'view', $title, $model->id);

        $backgroundImage = $model->backgroundImage;
        $languages = $this->languageOptions();

        $rows = $model->rows()
            ->with([
                'backgroundImage', 'details',
                'columns.backgroundImage', 'columns.details',
                'columns.widgets.details',
                ...PageWidgetRegistry::relations('columns.widgets.'),
            ])
            ->get();

        return Inertia::render('Admin/Page/Item/Layout', [
            'item' => [
                'id' => $model->id,
                'title' => $title,
                'background_color' => $model->background_color,
                'background_image' => $backgroundImage ? $this->fileToArray($backgroundImage) : null,
                'background_repeat' => $model->background_repeat,
                'background_size' => $model->background_size,
                'background_attachment' => $model->background_attachment,
                'background_position' => $model->background_position,
                'layout_updated_at' => optional($model->layout_updated_at)->format('Y-m-d H:i:s'),
            ],
            'rows' => $rows->map(fn (PageItemRow $row) => $this->rowToArray($row, $languages))->values(),
            'languages' => $languages,
            'fonts' => PageTextStyle::fontNames(),
            'fontsUrl' => PageTextStyle::fontsStylesheetUrl(),
            'widgetOptions' => PageWidgetRegistry::options(),
            'can' => [
                'manage' => $request->user()->hasPermission('page.item.manage'),
            ],
        ]);
    }

    /**
     * ข้อมูลตัวอย่างของ widget ตามค่าตั้งค่าที่กำลังแก้ (ยังไม่บันทึก) — ใช้แสดงตัวอย่างในหน้าโครงสร้าง
     * ไม่ใช่หน้าจอจึงไม่บันทึก log; ไม่ส่ง URL ปลายทางของลิงก์ (ตัวอย่างกดไม่ได้)
     */
    public function widgetPreview(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('page.item.view')) {
            return response()->json(['message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $type = PageWidgetRegistry::find($request->query('widget_type'));

        if ($type === null) {
            return response()->json(['message' => 'ประเภท Widget ไม่ถูกต้อง'], 422);
        }

        $setting = (array) $request->query('setting', []);
        $validator = Validator::make($setting, $type->previewRules(), $type->messages());

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'items' => []], 422);
        }

        return response()->json(['items' => $type->preview($setting)]);
    }

    /**
     * บันทึกโครงสร้างทั้งหน้า (แถว → คอลัมน์ → widget) + บันทึกวัน/ผู้บันทึกโครงสร้างลง page_item_info
     */
    public function layoutUpdate(UpdatePageItemLayoutRequest $request, string $item): RedirectResponse
    {
        if (! $request->user()->hasPermission('page.item.manage')) {
            return redirect()->route('admin.page.item.index');
        }

        $model = PageItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.page.item.index');
        }

        $data = $request->validated();

        (new PageLayoutSync($model, $request->user()->id))->sync($data['rows'] ?? []);

        $title = PageItemDetail::where('id', $model->id)
            ->where('lang', Setting::defaultLanguage())
            ->value('title');

        LogBackAction::record('page.item.layout', 'update', $title, $model->id);

        return redirect()
            ->route('admin.page.item.layout', $model->id)
            ->with('success', 'บันทึกโครงสร้างเรียบร้อยแล้ว');
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
     * ตัดข้อมูลร่วม (ไม่แยกภาษา) จาก validated data ให้เหลือเฉพาะคอลัมน์ของ page_item_info
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function commonPayload(array $data): array
    {
        return [
            'intro_image_id' => $data['intro_image_id'] ?? null,
            'background_color' => $data['background_color'] ?? null,
            'background_image_id' => $data['background_image_id'] ?? null,
            'background_repeat' => $data['background_repeat'] ?? null,
            'background_size' => $data['background_size'] ?? null,
            'background_attachment' => $data['background_attachment'] ?? null,
            'background_position' => $data['background_position'] ?? null,
            'status' => $data['status'],
        ];
    }

    /**
     * ตัดข้อมูลแยกภาษา 1 ภาษาจาก request ให้เหลือเฉพาะคอลัมน์ของ page_item_detail
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
     * แปลงแถว PageItemDetail เป็น array สำหรับฟอร์ม (ค่าว่าง '' แทน null ให้ฟิลด์ผูกกับ input ได้ตรง ๆ)
     *
     * @return array<string, mixed>
     */
    private function detailToArray(?PageItemDetail $detail): array
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
     * ฟิลด์พื้นหลัง (สี/รูป/CSS 4 ค่า) ของแถว/คอลัมน์/widget ในรูปแบบที่หน้าโครงสร้างใช้
     *
     * @return array<string, mixed>
     */
    private function backgroundToArray(PageItemRow|PageItemColumn|PageItemWidget $model): array
    {
        $image = $model->backgroundImage;

        return [
            'background_color' => $model->background_color,
            'background_image' => $image ? $this->fileToArray($image) : null,
            'background_repeat' => $model->background_repeat,
            'background_size' => $model->background_size,
            'background_attachment' => $model->background_attachment,
            'background_position' => $model->background_position,
        ];
    }

    /**
     * ค่าการจัดรูปแบบตัวอักษร 12 ค่า (ขนาด/ฟอนต์/การจัดตำแหน่ง/สี ของหัวเรื่อง/หัวเรื่องรอง/ข้อความเกริ่นนำ) ของแถว/คอลัมน์/widget
     *
     * @return array<string, mixed>
     */
    private function textStyleToArray(PageItemRow|PageItemColumn|PageItemWidget $model): array
    {
        return $model->only(PageTextStyle::columns());
    }

    /**
     * ข้อมูลแยกภาษาของแถว/คอลัมน์/widget เป็น {lang: {title, subtitle, intro_text}} ครบทุกภาษาที่เปิดใช้ (ภาษาที่ยังไม่มีข้อมูล = '')
     *
     * @param  Collection<int, Model>  $details
     * @param  list<array{code: string, is_default: bool}>  $languages
     * @return array<string, array{title: string, subtitle: string, intro_text: string}>
     */
    private function layoutDetailToArray(Collection $details, array $languages): array
    {
        $byLang = $details->keyBy('lang');
        $result = [];

        foreach ($languages as $lang) {
            $detail = $byLang->get($lang['code']);
            $result[$lang['code']] = [
                'title' => $detail?->title ?? '',
                'subtitle' => $detail?->subtitle ?? '',
                'intro_text' => $detail?->intro_text ?? '',
            ];
        }

        return $result;
    }

    /**
     * ค่าตั้งค่าเฉพาะประเภทของ widget (จากตารางของประเภทนั้น) — ประเภทเดิมที่ไม่มีตารางตั้งค่า = object ว่าง
     *
     * @return array<string, mixed>|object
     */
    private function widgetSettingToArray(PageItemWidget $widget): array|object
    {
        $type = PageWidgetRegistry::find($widget->widget_type);

        return $type ? $type->toArray($widget->getRelation($type->relation())) : (object) [];
    }

    /**
     * @param  list<array{code: string, is_default: bool}>  $languages
     * @return array<string, mixed>
     */
    private function rowToArray(PageItemRow $row, array $languages): array
    {
        return [
            'id' => $row->id,
            'status' => $row->status,
            'show_title' => $row->show_title,
            'use_container' => $row->use_container,
            ...$this->backgroundToArray($row),
            ...$this->textStyleToArray($row),
            'detail' => $this->layoutDetailToArray($row->details, $languages),
            'columns' => $row->columns->map(fn (PageItemColumn $column) => [
                'id' => $column->id,
                'status' => $column->status,
                'show_title' => $column->show_title,
                'column_size' => $column->column_size,
                ...$this->backgroundToArray($column),
                ...$this->textStyleToArray($column),
                'detail' => $this->layoutDetailToArray($column->details, $languages),
                'widgets' => $column->widgets->map(fn (PageItemWidget $widget) => [
                    'id' => $widget->id,
                    'status' => $widget->status,
                    'show_title' => $widget->show_title,
                    'widget_type' => $widget->widget_type,
                    'setting' => $this->widgetSettingToArray($widget),
                    ...$this->backgroundToArray($widget),
                    ...$this->textStyleToArray($widget),
                    'detail' => $this->layoutDetailToArray($widget->details, $languages),
                ])->values(),
            ])->values(),
        ];
    }

    /**
     * ชื่อหน้าเพจของภาษาหลัก จาก validated data — ใช้บันทึก log action
     *
     * @param  array<string, mixed>  $data
     */
    private function defaultTitle(array $data): ?string
    {
        return $data['detail'][Setting::defaultLanguage()]['title'] ?? null;
    }
}
