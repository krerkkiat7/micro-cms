<?php

namespace App\Http\Controllers\Admin\Intropage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Intropage\StoreIntropageItemRequest;
use App\Http\Requests\Admin\Intropage\UpdateIntropageItemRequest;
use App\Models\FileInfo;
use App\Models\IntropageItemButton;
use App\Models\IntropageItemDetail;
use App\Models\IntropageItemInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Support\PageTextStyle;
use App\Support\Setting;
use App\Support\SystemInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * จัดการหน้า Intropage (intropage_item_info + intropage_item_detail + intropage_item_button) — บันทึกเก็บได้
 * หลายชุด แต่ "แสดงจริง" ได้ทีละ 1 ชุด (กติกาเลือกว่าจะแสดงชุดไหนคำนวณที่หน้าบ้านในรอบถัดไป ดู
 * docs/PRD-intropage.md) log action ทั้งหมดใช้ module_code = "intropage.item" (ปุ่มไม่แยก module_code
 * เหมือนที่ article ไม่แยก .part)
 */
class IntropageItemController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * หน้ารายการ Intropage — ค้นหา / กรอง / แบ่งหน้า (ชื่อที่แสดงเป็นของภาษาหลักเสมอ)
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('intropage.item.view')) {
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
        $sortable = ['title', 'publish_date', 'publish_down', 'status'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'title';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $sortColumn = match ($sort) {
            'title' => 'd.title',
            default => "intropage_item_info.{$sort}",
        };

        $items = IntropageItemInfo::query()
            ->leftJoin('intropage_item_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'intropage_item_info.id')->where('d.lang', $defaultLang)->whereNull('d.deleted_at');
            })
            ->select('intropage_item_info.*', $this->detailWithFallback('intropage_item_detail', 'intropage_item_info', 'title'))
            ->when($filters['q'] !== null, fn ($query) => $query->where('d.title', 'like', '%'.$filters['q'].'%'))
            ->when($filters['status'] !== null, fn ($query) => $query->where('intropage_item_info.status', $filters['status']))
            ->orderBy($sortColumn, $direction)
            ->orderBy('intropage_item_info.id') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (IntropageItemInfo $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'publish_date' => optional($item->publish_date)->format('Y-m-d H:i:s'),
                'publish_down' => optional($item->publish_down)->format('Y-m-d H:i:s'),
                'status' => $item->status,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('รายการ Intropage');
        }

        return Inertia::render('Admin/Intropage/Item/Index', [
            'items' => $items,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'can' => [
                'manage' => $request->user()->hasPermission('intropage.item.manage'),
            ],
        ]);
    }

    /**
     * ฟอร์มเพิ่ม Intropage
     */
    public function add(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('intropage.item.manage')) {
            return redirect()->route('admin.intropage.item.index');
        }

        LogBackAccess::record('เพิ่ม Intropage');

        return Inertia::render('Admin/Intropage/Item/Add', [
            'languages' => $this->languageOptions(),
            'fonts' => PageTextStyle::fontNames(),
            'fontsUrl' => PageTextStyle::fontsStylesheetUrl(),
        ]);
    }

    /**
     * บันทึก Intropage ใหม่
     */
    public function store(StoreIntropageItemRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('intropage.item.manage')) {
            return redirect()->route('admin.intropage.item.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $item = DB::transaction(function () use ($data, $actorId) {
            $item = IntropageItemInfo::create([
                ...$this->commonPayload($data),
                'created_by' => $actorId,
            ]);

            foreach ($data['detail'] as $lang => $detail) {
                IntropageItemDetail::create([
                    'id' => $item->id,
                    'lang' => $lang,
                    ...$this->detailPayload($detail),
                    'status' => 'Y',
                    'created_by' => $actorId,
                ]);
            }

            $this->syncButtons($item, $data['buttons'], $actorId);

            return $item;
        });

        LogBackAction::record('intropage.item', 'create', $this->defaultTitle($data), $item->id);

        return redirect()
            ->route('admin.intropage.item.edit', $item->id)
            ->with('success', 'เพิ่ม Intropage เรียบร้อยแล้ว');
    }

    /**
     * ฟอร์มดู/แก้ไข Intropage
     */
    public function edit(Request $request, string $item): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('intropage.item.view')) {
            return redirect()->route('admin.intropage.item.index');
        }

        $model = IntropageItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.intropage.item.index');
        }

        $details = IntropageItemDetail::where('id', $model->id)->get()->keyBy('lang');
        $defaultLang = Setting::defaultLanguage();

        LogBackAccess::record('แก้ไข Intropage');
        LogBackAction::record('intropage.item', 'view', $details->get($defaultLang)?->title, $model->id);

        $backgroundImage = $model->backgroundImage;
        $imageFile = $model->imageFile;
        $vdoFile = $model->vdoFile;
        $buttons = $model->buttons()->with('buttonImage')->get();

        return Inertia::render('Admin/Intropage/Item/Edit', [
            'item' => [
                'id' => $model->id,
                'background_color' => $model->background_color,
                'background_image' => $backgroundImage ? $this->fileToArray($backgroundImage) : null,
                'background_repeat' => $model->background_repeat,
                'background_size' => $model->background_size,
                'background_attachment' => $model->background_attachment,
                'background_position' => $model->background_position,
                'display_type' => $model->display_type,
                'display_size' => $model->display_size,
                'image_file' => $imageFile ? $this->fileToArray($imageFile) : null,
                'vdo_file' => $vdoFile ? $this->fileToArray($vdoFile) : null,
                'vdo_url' => $model->vdo_url,
                'detail_font_family' => $model->detail_font_family,
                'detail_font_size' => (int) $model->detail_font_size,
                'detail_color' => $model->detail_color,
                'show_button' => $model->show_button,
                'button_font_size' => (int) $model->button_font_size,
                'button_font_family' => $model->button_font_family,
                'publish_date' => optional($model->publish_date)->format('Y-m-d H:i:s'),
                'publish_down' => optional($model->publish_down)->format('Y-m-d H:i:s'),
                'status' => $model->status,
            ],
            'details' => collect($this->languageOptions())->mapWithKeys(fn (array $lang) => [
                $lang['code'] => $this->detailToArray($details->get($lang['code'])),
            ]),
            'buttons' => $buttons->map(fn (IntropageItemButton $button) => $this->buttonToArray($button))->values(),
            'languages' => $this->languageOptions(),
            'fonts' => PageTextStyle::fontNames(),
            'fontsUrl' => PageTextStyle::fontsStylesheetUrl(),
            'systemInfo' => SystemInfo::audit($model),
            'can' => [
                'manage' => $request->user()->hasPermission('intropage.item.manage'),
                'delete' => $request->user()->hasPermission('intropage.item.delete'),
            ],
        ]);
    }

    /**
     * บันทึกการแก้ไข Intropage
     */
    public function update(UpdateIntropageItemRequest $request, string $item): RedirectResponse
    {
        if (! $request->user()->hasPermission('intropage.item.manage')) {
            return redirect()->route('admin.intropage.item.index');
        }

        $model = IntropageItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.intropage.item.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        DB::transaction(function () use ($model, $data, $actorId) {
            $model->fill([
                ...$this->commonPayload($data),
                'updated_by' => $actorId,
            ])->save();

            foreach ($data['detail'] as $lang => $detail) {
                // ห้ามใช้ IntropageItemDetail::find()/->save() กับแถวที่ดึงมา — composite key (id+lang)
                // ต้อง update() ผ่าน query builder ตรง ๆ (เหมือน BannerItemDetail)
                $updated = IntropageItemDetail::where('id', $model->id)
                    ->where('lang', $lang)
                    ->update($this->detailPayload($detail) + ['updated_by' => $actorId]);

                if ($updated === 0) {
                    IntropageItemDetail::create([
                        'id' => $model->id,
                        'lang' => $lang,
                        ...$this->detailPayload($detail),
                        'status' => 'Y',
                        'created_by' => $actorId,
                    ]);
                }
            }

            $this->syncButtons($model, $data['buttons'], $actorId);
        });

        LogBackAction::record('intropage.item', 'update', $this->defaultTitle($data), $model->id);

        return redirect()
            ->route('admin.intropage.item.edit', $model->id)
            ->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * ลบ Intropage (soft delete)
     */
    public function destroy(Request $request, string $item): RedirectResponse
    {
        if (! $request->user()->hasPermission('intropage.item.delete')) {
            return redirect()->route('admin.intropage.item.index');
        }

        $model = IntropageItemInfo::find($item);

        if (! $model) {
            return redirect()->route('admin.intropage.item.index');
        }

        $title = IntropageItemDetail::where('id', $model->id)
            ->where('lang', Setting::defaultLanguage())
            ->value('title');
        $id = $model->id;

        // บันทึกผู้ลบก่อน soft delete (runSoftDelete ไม่ save attribute อื่น)
        $model->deleted_by = $request->user()->id;
        $model->save();
        $model->delete();

        LogBackAction::record('intropage.item', 'delete', $title, $id);

        return redirect()
            ->route('admin.intropage.item.index')
            ->with('success', 'ลบ Intropage เรียบร้อยแล้ว');
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
     * ตัดข้อมูลร่วม (ไม่แยกภาษา ไม่รวมปุ่ม) จาก validated data ให้เหลือเฉพาะคอลัมน์ของ intropage_item_info
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function commonPayload(array $data): array
    {
        return [
            'background_color' => $data['background_color'] ?? null,
            'background_image_id' => $data['background_image_id'] ?? null,
            'background_repeat' => $data['background_repeat'] ?? null,
            'background_size' => $data['background_size'] ?? null,
            'background_attachment' => $data['background_attachment'] ?? null,
            'background_position' => $data['background_position'] ?? null,
            'display_type' => $data['display_type'],
            'display_size' => $data['display_size'],
            'image_file_id' => $data['display_type'] === 'image' ? ($data['image_file_id'] ?? null) : null,
            'vdo_file_id' => $data['display_type'] === 'vdo' ? ($data['vdo_file_id'] ?? null) : null,
            'vdo_url' => in_array($data['display_type'], ['vdourl', 'youtubeurl'], true) ? ($data['vdo_url'] ?? null) : null,
            'detail_font_family' => $data['detail_font_family'],
            'detail_font_size' => $data['detail_font_size'],
            'detail_color' => $data['detail_color'],
            'show_button' => $data['show_button'],
            'button_font_size' => $data['button_font_size'],
            'button_font_family' => $data['button_font_family'],
            'publish_date' => $data['publish_date'],
            'publish_down' => $data['publish_down'],
            'status' => $data['status'],
        ];
    }

    /**
     * ตัดข้อมูลแยกภาษา 1 ภาษาจาก request ให้เหลือเฉพาะคอลัมน์ของ intropage_item_detail
     *
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private function detailPayload(array $detail): array
    {
        return [
            'title' => $detail['title'] ?? null,
            'detail' => $detail['detail'] ?? null,
        ];
    }

    /**
     * แปลงแถว IntropageItemDetail เป็น array สำหรับฟอร์ม (ค่าว่าง '' แทน null ให้ฟิลด์ผูกกับ input ได้ตรง ๆ)
     *
     * @return array<string, mixed>
     */
    private function detailToArray(?IntropageItemDetail $detail): array
    {
        return [
            'title' => $detail?->title ?? '',
            'detail' => $detail?->detail ?? '',
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
     * แปลงปุ่มเป็น array สำหรับฟอร์มแก้ไข
     *
     * @return array<string, mixed>
     */
    private function buttonToArray(IntropageItemButton $button): array
    {
        return [
            'button_type' => $button->button_type,
            'button_display_type' => $button->button_display_type,
            'background_color' => $button->background_color,
            'text_color' => $button->text_color,
            'button_image' => $button->buttonImage ? $this->fileToArray($button->buttonImage) : null,
            'url' => $button->url,
            'link_target' => $button->link_target,
            'texts' => $button->texts ?? [],
        ];
    }

    /**
     * ชื่อ Intropage ของภาษาหลัก จาก validated data — ใช้บันทึก log action
     *
     * @param  array<string, mixed>  $data
     */
    private function defaultTitle(array $data): ?string
    {
        return $data['detail'][Setting::defaultLanguage()]['title'] ?? null;
    }

    /**
     * แทนที่ปุ่มทั้งหมดของ Intropage ด้วยชุดที่ส่งมาจากฟอร์ม — ฟอร์มส่งปุ่มทั้งชุดมาใหม่ทุกครั้ง (เหมือน
     * detail ต่อภาษา และเหมือน part ของบทความ) การลบ/เพิ่ม/สลับลำดับปุ่มที่ผู้ใช้ทำในหน้าจอจึงจัดการง่ายกว่า
     * การ diff เอง — ความถูกต้องของ "ต้องมีปุ่ม home แถวเดียว" เช็กไว้แล้วที่ FormRequest
     *
     * @param  list<array<string, mixed>>  $buttonsData
     */
    private function syncButtons(IntropageItemInfo $item, array $buttonsData, int $actorId): void
    {
        foreach ($item->buttons as $button) {
            $button->deleted_by = $actorId;
            $button->save();
            $button->delete();
        }

        foreach (array_values($buttonsData) as $index => $buttonData) {
            $buttonType = $buttonData['button_type'];
            $displayType = $buttonData['button_display_type'] ?? 'text';

            IntropageItemButton::create([
                'intropage_item_info_id' => $item->id,
                'button_type' => $buttonType,
                'sort_order' => $index,
                'button_display_type' => $displayType,
                'background_color' => $displayType === 'text' ? ($buttonData['background_color'] ?? null) : null,
                'text_color' => $displayType === 'text' ? ($buttonData['text_color'] ?? null) : null,
                'button_image_id' => $displayType === 'image' ? ($buttonData['button_image_id'] ?? null) : null,
                'url' => $buttonType === 'other' ? ($buttonData['url'] ?? null) : null,
                'link_target' => $buttonType === 'other' ? ($buttonData['link_target'] ?? null) : null,
                'texts' => $buttonData['texts'] ?? null,
                'created_by' => $actorId,
            ]);
        }
    }
}
