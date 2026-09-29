<?php

namespace App\Http\Controllers\Admin\Article;

use App\Http\Controllers\Admin\ItemReportController;
use App\Support\Report\ItemReport;

/**
 * เมนูรายงานการเข้าชมบทความ (ภาพรวมทั้งโมดูล) — สิทธิ์ article.report.view ทุกหน้า, log module_code = "article.report.<แท็บ>"
 * หน้าจอ/การคำนวณทั้งหมดอยู่ที่ ItemReportController + App\Support\Report\ItemReport (ดู docs/PRD-article.md §4)
 */
class ArticleReportController extends ItemReportController
{
    protected function config(): array
    {
        return [
            'key' => 'article',
            'title' => 'รายงานบทความ',
            'item_label' => 'บทความ',
            'permission' => 'article.report.view',
            'item_permission' => 'article.item.view',
            'item_report_route' => 'admin.article.item.report',
        ];
    }

    protected function report(): ItemReport
    {
        return ItemReport::article();
    }
}
