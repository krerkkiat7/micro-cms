<?php

namespace App\Http\Controllers\Admin\Contactus;

use App\Http\Controllers\Controller;
use App\Models\ContactusItem;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Support\SystemInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ข้อมูลติดต่อเราที่ส่งมาจากหน้าบ้าน — รายการ / รายละเอียด + บันทึกสถานะและหมายเหตุ (ไม่มีเพิ่ม/ลบ)
 * สิทธิ์: contactus.item.view (ดู), contactus.item.manage (บันทึกสถานะ/หมายเหตุ) — log module_code = "contactus.item"
 */
class ContactusItemController extends Controller
{
    /** จำนวนรายการต่อหน้าที่อนุญาต (ตัวแรก = ค่าเริ่มต้น) */
    private const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    /**
     * หน้ารายการ — ค้นหา / กรองช่วงวันที่ส่ง + สถานะ / แบ่งหน้า / เรียง (ค่าเริ่มต้น = ส่งล่าสุดก่อน)
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('contactus.item.view')) {
            return redirect()->route('admin.dashboard');
        }

        $q = trim((string) $request->query('q', ''));
        $processStatus = (string) $request->query('process_status', '');
        $perPage = (int) $request->query('per_page');

        $filters = [
            'q' => $q !== '' ? $q : null,
            'process_status' => array_key_exists($processStatus, ContactusItem::PROCESS_STATUSES) ? $processStatus : null,
            'date_from' => $this->toDate($request->query('date_from')),
            'date_to' => $this->toDate($request->query('date_to')),
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0],
        ];

        $sortable = ['fullname', 'email', 'subject', 'created_at', 'process_status'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $items = ContactusItem::query()
            ->when($filters['q'] !== null, function ($query) use ($filters) {
                $term = '%'.addcslashes($filters['q'], '%_\\').'%';

                $query->where(function ($inner) use ($term) {
                    foreach (['fullname', 'email', 'subject', 'position', 'company', 'phone'] as $column) {
                        $inner->orWhere($column, 'like', $term);
                    }
                });
            })
            ->when($filters['process_status'] !== null, fn ($query) => $query->where('process_status', $filters['process_status']))
            ->when($filters['date_from'] !== null, fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== null, fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc') // tie-breaker ให้ลำดับเสถียร
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (ContactusItem $item) => [
                'id' => $item->id,
                'fullname' => $item->fullname,
                'email' => $item->email,
                'subject' => $item->subject,
                'process_status' => $item->process_status,
                'created_at' => $item->created_at,
            ]);

        // บันทึก log เฉพาะการเข้าหน้ารายการจริง ๆ — ไม่บันทึกตอนค้นหา/กรอง/แบ่งหน้า/เรียง
        if (count($request->query()) === 0) {
            LogBackAccess::record('รายการติดต่อเรา');
        }

        return Inertia::render('Admin/Contactus/Item/Index', [
            'items' => $items,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * หน้ารายละเอียด/แก้ไข — แสดงทุกฟิลด์ของแบบฟอร์ม (แม้ตั้งค่าซ่อนไว้) + บันทึกสถานะ/หมายเหตุ
     * เปิดครั้งแรกของรายการที่ยังไม่ได้อ่าน = เปลี่ยนสถานะเป็น "อ่านแล้ว" อัตโนมัติ
     */
    public function edit(Request $request, string $item): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('contactus.item.view')) {
            return redirect()->route('admin.dashboard');
        }

        // ไม่พบ/ถูกลบไปแล้ว → กลับหน้ารายการ (เหมือนโมดูลอื่น ไม่ใช่หน้า 404)
        $item = ContactusItem::find($item);

        if (! $item) {
            return redirect()->route('admin.contactus.item.index');
        }

        if ($item->process_status === 'unread') {
            $item->process_status = 'read';
            $item->read_at = now();
            $item->read_by = $request->user()->id;
            // ไม่นับเป็นการ "ปรับปรุง" ของผู้ดูแล — ไม่แตะ updated_at/updated_by
            $item->timestamps = false;
            $item->save();
            $item->timestamps = true;
        }

        LogBackAccess::record('รายละเอียดติดต่อเรา');
        LogBackAction::record('contactus.item', 'view', $this->label($item), $item->id);

        // ข้อมูลที่ยังไม่เคยถูกบันทึกสถานะ/หมายเหตุ — ไม่มี "ปรับปรุงล่าสุด" (updated_at ตอนส่งเท่ากับ created_at)
        $audit = SystemInfo::audit($item);

        if ($item->updated_by === null) {
            $audit['updated_at'] = null;
        }

        return Inertia::render('Admin/Contactus/Item/Edit', [
            'item' => [
                'id' => $item->id,
                'fullname' => $item->fullname,
                'position' => $item->position,
                'company' => $item->company,
                'phone' => $item->phone,
                'email' => $item->email,
                'subject' => $item->subject,
                'detail' => $item->detail,
                'process_status' => $item->process_status,
                'note' => $item->note,
            ],
            'systemInfo' => $audit,
            'meta' => [
                'remote_ip' => $item->remote_ip,
                'lang' => $item->lang,
            ],
            'can' => [
                'manage' => $request->user()->hasPermission('contactus.item.manage'),
            ],
        ]);
    }

    /**
     * บันทึกสถานะ/หมายเหตุ
     */
    public function update(Request $request, string $item): RedirectResponse
    {
        if (! $request->user()->hasPermission('contactus.item.manage')) {
            return redirect()->route('admin.contactus.item.index');
        }

        $item = ContactusItem::find($item);

        if (! $item) {
            return redirect()->route('admin.contactus.item.index');
        }

        $data = $request->validate([
            'process_status' => ['required', Rule::in(array_keys(ContactusItem::PROCESS_STATUSES))],
            'note' => ['nullable', 'string', 'max:5000'],
        ], [], [
            'process_status' => 'สถานะ',
            'note' => 'หมายเหตุ',
        ]);

        $item->process_status = $data['process_status'];
        $item->note = $data['note'] ?? null;
        $item->updated_by = $request->user()->id;
        $item->save();

        LogBackAction::record('contactus.item', 'update', $this->label($item), $item->id);

        return back()->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * ชื่อรายการสำหรับ log — "ชื่อ - หัวข้อ"
     */
    private function label(ContactusItem $item): string
    {
        return $item->subject ? "{$item->fullname} - {$item->subject}" : $item->fullname;
    }
}
