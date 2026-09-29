<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Support\Report\ItemReport;
use App\Support\Report\ViewReport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * รายงานการเข้าชม/คลิกของ "รายการเดียว" (แท็บรายงานในหน้าแก้ไขของบทความ / หน้าเพจ / ป้ายโฆษณา)
 * log module_code = "<โมดูล>.item.report" (view เฉพาะเข้าหน้าครั้งแรกที่ไม่มี query, export ทุกครั้ง)
 * หน้าจอ Pages/Admin/Report/Item.vue
 */
trait RendersItemReport
{
    /**
     * @param  array{
     *     item_label: string,
     *     list_label: string,
     *     tabs: list<array{label: string, route: string}>,
     *     amount: int,
     *     publish_date: string|null
     * }  $meta  tabs = แท็บของหน้าแก้ไข (route รับ id ของรายการ) — แท็บรายงานต่อท้ายให้เอง
     */
    protected function itemReportResponse(Request $request, ItemReport $module, Model $model, array $meta): Response
    {
        $filters = ViewReport::filters($request);
        $info = $module->itemInfo([$model->id])[$model->id] ?? [];

        // บันทึก log เฉพาะการเข้าหน้าจริง ๆ — ไม่บันทึกตอนเปลี่ยนตัวกรอง
        if (count($request->query()) === 0) {
            LogBackAccess::record("รายงาน{$meta['item_label']}");
            LogBackAction::record("{$module->key}.item.report", 'view', $info['title'] ?? null, $model->id);
        }

        return Inertia::render('Admin/Report/Item', [
            'module' => [
                'key' => $module->key,
                'item_label' => $meta['item_label'],
                'list_label' => $meta['list_label'],
                'metric' => $module->metric,
                'has_category' => $module->hasCategory(),
                'tabs' => $meta['tabs'],
            ],
            'item' => [
                'id' => $model->id,
                'title' => $info['title'] ?? null,
                'category_title' => $info['category_title'] ?? null,
                'publish_date' => $meta['publish_date'],
                'amount' => $meta['amount'],
                'status' => $model->status,
            ],
            'filters' => $filters,
            ...$module->dashboard($module->forItem($model->id, $filters), $request),
        ]);
    }

    /**
     * ส่งออกตารางรายช่วงเวลาของรายการนี้เป็น CSV ตามตัวกรอง
     */
    protected function itemReportExport(Request $request, ItemReport $module, Model $model): StreamedResponse
    {
        $filters = ViewReport::filters($request);
        $title = $module->itemInfo([$model->id])[$model->id]['title'] ?? null;
        $terms = $module->terms();

        LogBackAction::record("{$module->key}.item.report", 'export', $title, $model->id);

        $rows = array_map(fn (array $row) => [
            $row['label'], $row['start'], $row['end'], $row['views'], $row['sessions'], $row['ips'],
        ], $module->forItem($model->id, $filters)->series());

        return ViewReport::csv(
            "{$module->key}-{$model->id}-report-{$filters['date_from']}-{$filters['date_to']}.csv",
            ['ช่วงเวลา', 'วันที่เริ่มต้น', 'วันที่สิ้นสุด', $terms['count'], $terms['unique'], $terms['ips']],
            $rows,
        );
    }
}
