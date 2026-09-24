<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\Template\StoreTemplateRequest;
use App\Http\Requests\Admin\System\Template\UpdateTemplateCodeRequest;
use App\Http\Requests\Admin\System\Template\UpdateTemplateLayoutRequest;
use App\Http\Requests\Admin\System\Template\UpdateTemplateLoadingRequest;
use App\Http\Requests\Admin\System\Template\UpdateTemplateRequest;
use App\Models\FileInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Models\SysTemplate;
use App\Support\AppAsset;
use App\Support\FrontMenuTree;
use App\Support\PageTextStyle;
use App\Support\Setting;
use App\Support\Template\TemplatePreset;
use App\Support\Template\TemplateZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * จัดการ Template หน้าบ้าน (sys_template + ตั้งค่าโซน sys_template_header/body/footer/aside) — ดู docs/PRD-system-template.md
 * แท็บ: ข้อมูลทั่วไป (edit) / โครงสร้าง (layout) / Custom CSS/JS (code) / หน้า Loading (loading) บันทึกแยกกัน
 * เปิดใช้งานได้ครั้งละ 1 รายการ และต้องมีรายการที่ใช้งานอยู่เสมอ (ปิด/ลบรายการที่ใช้งานอยู่ไม่ได้)
 * log action: module_code = "system.template" ทุกแท็บ
 */
class TemplateController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    private const MODULE = 'system.template';

    /**
     * หน้ารายการ template — ค้นหา / กรอง / แบ่งหน้า / เรียง
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.view')) {
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

        $sortable = ['name', 'updated_at', 'status'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $items = SysTemplate::query()
            ->when($filters['q'] !== null, fn ($query) => $query->where('name', 'like', '%'.$filters['q'].'%'))
            ->when($filters['status'] !== null, fn ($query) => $query->where('status', $filters['status']))
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (SysTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'preset' => TemplatePreset::OPTIONS[$template->preset] ?? null,
                'updated_at' => optional($template->updated_at)->format('Y-m-d H:i:s'),
                'status' => $template->status,
            ]);

        if (count($request->query()) === 0) {
            LogBackAccess::record('รายการ Template');
        }

        return Inertia::render('Admin/System/Template/Index', [
            'items' => $items,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'can' => [
                'manage' => $request->user()->hasPermission('system.template.manage'),
            ],
        ]);
    }

    /**
     * ฟอร์มเพิ่ม template — ชื่อ + เลือกแม่แบบตั้งต้น
     */
    public function add(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.manage')) {
            return redirect()->route('admin.system.template.index');
        }

        LogBackAccess::record('เพิ่ม Template');

        return Inertia::render('Admin/System/Template/Add', [
            // ยังไม่มีรายการที่ใช้งานอยู่ = รายการแรกที่สร้างจะถูกเปิดใช้งานเสมอ
            'hasActive' => SysTemplate::where('status', 'Y')->exists(),
        ]);
    }

    /**
     * สร้าง template + ตั้งค่าทั้ง 4 โซนจากแม่แบบที่เลือก แล้วพาไปแท็บโครงสร้าง
     */
    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.manage')) {
            return redirect()->route('admin.system.template.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        $template = DB::transaction(function () use ($data, $actorId) {
            $activate = $data['status'] === 'Y' || ! SysTemplate::where('status', 'Y')->exists();

            if ($activate) {
                $this->deactivateOthers(null, $actorId);
            }

            $template = SysTemplate::create([
                'name' => $data['name'],
                'preset' => $data['preset'],
                'status' => $activate ? 'Y' : 'N',
                'created_by' => $actorId,
            ]);

            foreach (TemplatePreset::zones($data['preset']) as $zone => $values) {
                $template->{$zone}()->create($values + ['created_by' => $actorId]);
            }

            return $template;
        });

        LogBackAction::record(self::MODULE, 'create', $template->name, $template->id);

        return redirect()
            ->route('admin.system.template.layout', $template->id)
            ->with('success', 'เพิ่ม Template เรียบร้อยแล้ว');
    }

    /**
     * แท็บข้อมูลทั่วไป
     */
    public function edit(Request $request, string $template): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.view')) {
            return redirect()->route('admin.system.template.index');
        }

        $model = SysTemplate::find($template);

        if (! $model) {
            return redirect()->route('admin.system.template.index');
        }

        LogBackAccess::record('แก้ไข Template');
        LogBackAction::record(self::MODULE, 'view', $model->name, $model->id);

        return Inertia::render('Admin/System/Template/Edit', [
            'template' => [
                ...$this->summary($model),
                'preset_label' => TemplatePreset::OPTIONS[$model->preset] ?? null,
            ],
            'can' => [
                'manage' => $request->user()->hasPermission('system.template.manage'),
                'delete' => $request->user()->hasPermission('system.template.delete'),
            ],
        ]);
    }

    /**
     * บันทึกข้อมูลทั่วไป — เปิดใช้งาน = ปิดรายการอื่นทั้งหมด (ปิดรายการที่ใช้งานอยู่ถูกกันที่ UpdateTemplateRequest)
     */
    public function update(UpdateTemplateRequest $request, string $template): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.manage')) {
            return redirect()->route('admin.system.template.index');
        }

        $model = SysTemplate::find($template);

        if (! $model) {
            return redirect()->route('admin.system.template.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        DB::transaction(function () use ($model, $data, $actorId) {
            if ($data['status'] === 'Y') {
                $this->deactivateOthers($model->id, $actorId);
            }

            $model->fill([
                'name' => $data['name'],
                'status' => $data['status'],
                'updated_by' => $actorId,
            ])->save();
        });

        LogBackAction::record(self::MODULE, 'update', $model->name, $model->id);

        return redirect()
            ->route('admin.system.template.edit', $model->id)
            ->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * เปิดใช้งาน template นี้ (ปุ่มในหน้ารายการ) — รายการอื่นถูกปิดอัตโนมัติ
     */
    public function activate(Request $request, string $template): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.manage')) {
            return redirect()->route('admin.system.template.index');
        }

        $model = SysTemplate::find($template);

        if (! $model) {
            return redirect()->route('admin.system.template.index');
        }

        $actorId = $request->user()->id;

        if ($model->status !== 'Y') {
            DB::transaction(function () use ($model, $actorId) {
                $this->deactivateOthers($model->id, $actorId);
                $model->fill(['status' => 'Y', 'updated_by' => $actorId])->save();
            });

            LogBackAction::record(self::MODULE, 'update', $model->name, $model->id);
        }

        return back()->with('success', "เปิดใช้งาน Template \"{$model->name}\" เรียบร้อยแล้ว");
    }

    /**
     * ลบ template (soft delete) — รายการที่กำลังใช้งานลบไม่ได้
     */
    public function destroy(Request $request, string $template): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.delete')) {
            return redirect()->route('admin.system.template.index');
        }

        $model = SysTemplate::find($template);

        if (! $model) {
            return redirect()->route('admin.system.template.index');
        }

        if ($model->status === 'Y') {
            return back()->withErrors(['delete' => 'ลบ Template ที่กำลังใช้งานอยู่ไม่ได้ — เปิดใช้งานรายการอื่นก่อน']);
        }

        $name = $model->name;
        $id = $model->id;

        // บันทึกผู้ลบก่อน soft delete (runSoftDelete ไม่ save attribute อื่น)
        $model->deleted_by = $request->user()->id;
        $model->save();
        $model->delete();

        LogBackAction::record(self::MODULE, 'delete', $name, $id);

        return redirect()
            ->route('admin.system.template.index')
            ->with('success', 'ลบ Template เรียบร้อยแล้ว');
    }

    /**
     * แท็บโครงสร้าง — ตั้งค่า 4 โซนพร้อมตัวอย่าง (preview ใช้ข้อมูลจริงของระบบ ณ ปัจจุบัน)
     */
    public function layout(Request $request, string $template): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.view')) {
            return redirect()->route('admin.system.template.index');
        }

        $model = SysTemplate::with([
            'header.backgroundImage', 'body.backgroundImage', 'footer.backgroundImage', 'aside.backgroundImage',
        ])->find($template);

        if (! $model) {
            return redirect()->route('admin.system.template.index');
        }

        LogBackAccess::record('โครงสร้าง Template');
        LogBackAction::record(self::MODULE, 'view', $model->name, $model->id);

        $zones = [];

        foreach (TemplateZone::ZONES as $zone) {
            $zoneModel = $model->{$zone};
            $image = $zoneModel?->backgroundImage;

            $zones[$zone] = [
                ...TemplateZone::toArray($zone, $zoneModel),
                'background_image' => $image ? $this->fileToArray($image) : null,
            ];
        }

        return Inertia::render('Admin/System/Template/Layout', [
            'template' => [
                ...$this->summary($model),
                'layout_updated_at' => optional($model->layout_updated_at)->format('Y-m-d H:i:s'),
            ],
            'zones' => $zones,
            'preview' => $this->previewData(),
            'fonts' => PageTextStyle::fontNames(),
            'fontsUrl' => PageTextStyle::fontsStylesheetUrl(),
            'can' => [
                'manage' => $request->user()->hasPermission('system.template.manage'),
            ],
        ]);
    }

    /**
     * บันทึกตั้งค่าทั้ง 4 โซน + วัน/ผู้บันทึกโครงสร้าง
     */
    public function layoutUpdate(UpdateTemplateLayoutRequest $request, string $template): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.manage')) {
            return redirect()->route('admin.system.template.index');
        }

        $model = SysTemplate::find($template);

        if (! $model) {
            return redirect()->route('admin.system.template.index');
        }

        $data = $request->validated();
        $actorId = $request->user()->id;

        DB::transaction(function () use ($model, $data, $actorId) {
            foreach (TemplateZone::ZONES as $zone) {
                $values = collect($data[$zone])->only(TemplateZone::columns($zone))->all();
                $zoneModel = $model->{$zone}()->first();

                if ($zoneModel) {
                    $zoneModel->fill($values + ['updated_by' => $actorId])->save();
                } else {
                    $model->{$zone}()->create(array_replace(TemplateZone::defaults($zone), $values) + ['created_by' => $actorId]);
                }
            }

            $model->fill([
                'layout_updated_at' => now(),
                'layout_updated_by' => $actorId,
            ])->save();
        });

        LogBackAction::record(self::MODULE, 'update', $model->name, $model->id);

        return redirect()
            ->route('admin.system.template.layout', $model->id)
            ->with('success', 'บันทึกโครงสร้างเรียบร้อยแล้ว');
    }

    /**
     * แท็บ Custom CSS/JS
     */
    public function code(Request $request, string $template): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.view')) {
            return redirect()->route('admin.system.template.index');
        }

        $model = SysTemplate::find($template);

        if (! $model) {
            return redirect()->route('admin.system.template.index');
        }

        LogBackAccess::record('Custom CSS/JS ของ Template');
        LogBackAction::record(self::MODULE, 'view', $model->name, $model->id);

        return Inertia::render('Admin/System/Template/Code', [
            'template' => [
                ...$this->summary($model),
                'custom_css_status' => $model->custom_css_status,
                'custom_css' => $model->custom_css ?? '',
                'custom_js_status' => $model->custom_js_status,
                'custom_js' => $model->custom_js ?? '',
            ],
            'can' => [
                'manage' => $request->user()->hasPermission('system.template.manage'),
            ],
        ]);
    }

    public function codeUpdate(UpdateTemplateCodeRequest $request, string $template): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.manage')) {
            return redirect()->route('admin.system.template.index');
        }

        $model = SysTemplate::find($template);

        if (! $model) {
            return redirect()->route('admin.system.template.index');
        }

        $model->fill($request->validated() + ['updated_by' => $request->user()->id])->save();

        LogBackAction::record(self::MODULE, 'update', $model->name, $model->id);

        return redirect()
            ->route('admin.system.template.code', $model->id)
            ->with('success', 'บันทึก Custom CSS/JS เรียบร้อยแล้ว');
    }

    /**
     * แท็บหน้า Loading
     */
    public function loading(Request $request, string $template): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.view')) {
            return redirect()->route('admin.system.template.index');
        }

        $model = SysTemplate::with('loadingImage')->find($template);

        if (! $model) {
            return redirect()->route('admin.system.template.index');
        }

        LogBackAccess::record('หน้า Loading ของ Template');
        LogBackAction::record(self::MODULE, 'view', $model->name, $model->id);

        return Inertia::render('Admin/System/Template/Loading', [
            'template' => [
                ...$this->summary($model),
                'loading_status' => $model->loading_status,
                'loading_show_logo' => $model->loading_show_logo,
                'loading_type' => $model->loading_type,
                'loading_spinner' => $model->loading_spinner,
                'loading_color' => $model->loading_color,
                'loading_background_color' => $model->loading_background_color,
                'loading_image' => $model->loadingImage ? $this->fileToArray($model->loadingImage) : null,
            ],
            // โลโก้จากตั้งค่าระบบ สำหรับตัวอย่าง (null = ยังไม่ได้ตั้งค่าโลโก้)
            'logoUrl' => AppAsset::logo() ? route('app.logo') : null,
            'siteName' => Setting::siteName(),
            'can' => [
                'manage' => $request->user()->hasPermission('system.template.manage'),
            ],
        ]);
    }

    public function loadingUpdate(UpdateTemplateLoadingRequest $request, string $template): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.template.manage')) {
            return redirect()->route('admin.system.template.index');
        }

        $model = SysTemplate::find($template);

        if (! $model) {
            return redirect()->route('admin.system.template.index');
        }

        $model->fill($request->validated() + ['updated_by' => $request->user()->id])->save();

        LogBackAction::record(self::MODULE, 'update', $model->name, $model->id);

        return redirect()
            ->route('admin.system.template.loading', $model->id)
            ->with('success', 'บันทึกหน้า Loading เรียบร้อยแล้ว');
    }

    /**
     * ปิดใช้งานทุก template ยกเว้น $exceptId (null = ปิดทั้งหมด) — เรียกภายใน transaction ก่อนเปิดรายการใหม่
     */
    private function deactivateOthers(?int $exceptId, int $actorId): void
    {
        SysTemplate::query()
            ->where('status', 'Y')
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->update(['status' => 'N', 'updated_by' => $actorId]);
    }

    /**
     * ข้อมูลหัวของทุกแท็บ (ชื่อ/สถานะ ใช้กับ breadcrumb/หัวหน้า)
     *
     * @return array<string, mixed>
     */
    private function summary(SysTemplate $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'preset' => $model->preset,
            'status' => $model->status,
        ];
    }

    /**
     * ข้อมูลจริงของระบบสำหรับแสดงตัวอย่างในหน้าโครงสร้าง (ไม่ส่ง URL ปลายทางของเมนู — ตัวอย่างกดไม่ได้)
     *
     * @return array<string, mixed>
     */
    private function previewData(): array
    {
        $defaultLang = Setting::defaultLanguage();
        $social = Setting::group('social');

        return [
            'siteName' => Setting::siteName(),
            'logoUrl' => AppAsset::logo() ? route('app.logo') : null,
            'languages' => Setting::selectedLanguages(),
            'defaultLanguage' => $defaultLang,
            'contact' => [
                // ชื่อเจ้าของไซต์ (ค่าดิบ ไม่ fallback) — แสดงหัวข้อมูลติดต่อใน footer เฉพาะเมื่อตั้งค่าไว้
                'owner' => Setting::get('site', 'copyright_owner'),
                'address' => Setting::get('contact', "address_{$defaultLang}"),
                'phone' => Setting::get('contact', 'phone'),
                'fax' => Setting::get('contact', 'fax'),
                'mobile' => Setting::get('contact', 'mobile'),
                'email' => Setting::get('contact', 'email'),
            ],
            // เฉพาะช่องทางที่ตั้งค่าไว้ (เรียงตามฟอร์มตั้งค่าระบบ)
            'social' => collect(['facebook', 'youtube', 'x', 'instagram', 'tiktok', 'line'])
                ->filter(fn (string $key) => ! empty($social[$key]))
                ->values()
                ->all(),
            'copyright' => [
                'year' => Setting::get('site', 'copyright_year', now()->format('Y')),
                'owner' => Setting::get('site', 'copyright_owner', Setting::siteName()),
            ],
            'menu' => FrontMenuTree::forLanguage($defaultLang),
        ];
    }

    /**
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
