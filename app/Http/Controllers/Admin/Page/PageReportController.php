<?php

namespace App\Http\Controllers\Admin\Page;

use App\Http\Controllers\Admin\ItemReportController;
use App\Support\Report\ItemReport;

/**
 * เมนูรายงานการเข้าชมหน้าเพจ (ภาพรวมทั้งโมดูล) — สิทธิ์ page.report.view ทุกหน้า, log module_code = "page.report.<แท็บ>"
 * หน้าจอ/การคำนวณทั้งหมดอยู่ที่ ItemReportController + App\Support\Report\ItemReport (ดู docs/PRD-article.md §4)
 */
class PageReportController extends ItemReportController
{
    protected function config(): array
    {
        return [
            'key' => 'page',
            'title' => 'รายงานหน้าเพจ',
            'item_label' => 'หน้าเพจ',
            'permission' => 'page.report.view',
            'item_permission' => 'page.item.view',
            'item_report_route' => 'admin.page.item.report',
        ];
    }

    protected function report(): ItemReport
    {
        return ItemReport::page();
    }
}
