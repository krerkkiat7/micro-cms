<?php

namespace App\Http\Controllers\Admin\Banner;

use App\Http\Controllers\Admin\ItemReportController;
use App\Support\Report\ItemReport;

/**
 * เมนูรายงานการคลิกป้ายโฆษณา (ภาพรวมทั้งโมดูล) — สิทธิ์ banner.report.view ทุกหน้า, log module_code = "banner.report.<แท็บ>"
 * หน้าจอ/การคำนวณทั้งหมดอยู่ที่ ItemReportController + App\Support\Report\ItemReport (ดู docs/PRD-article.md §4)
 */
class BannerReportController extends ItemReportController
{
    protected function config(): array
    {
        return [
            'key' => 'banner',
            'title' => 'รายงานป้ายโฆษณา',
            'item_label' => 'ป้ายโฆษณา',
            'permission' => 'banner.report.view',
            'item_permission' => 'banner.item.view',
            'item_report_route' => 'admin.banner.item.report',
        ];
    }

    protected function report(): ItemReport
    {
        return ItemReport::banner();
    }
}
