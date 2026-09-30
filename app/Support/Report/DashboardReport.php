<?php

namespace App\Support\Report;

use App\Models\ContactusItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * ข้อมูลของหน้า Dashboard หลังบ้าน (admin.dashboard)
 *
 * แต่ละส่วนคำนวณเฉพาะเมื่อผู้ใช้มีสิทธิ์ของส่วนนั้น — ไม่มีสิทธิ์ = ไม่ query และไม่ส่งข้อมูลไปหน้าจอเลย (ค่า null / ไม่มีรายการ)
 * ส่วนใหม่ที่เพิ่มต้องเช็กสิทธิ์ใน can() ก่อนคำนวณเสมอ ไม่ใช่ส่งไปแล้วซ่อนฝั่งหน้าจอ
 *
 * ตัวเลขการเข้าชมใช้ตัวคำนวณเดียวกับเมนูรายงาน (ViewReport / ItemReport / AccessLogReport) ตัวเลขจึงตรงกับหน้ารายงาน
 */
final class DashboardReport
{
    /** จำนวนวันของการ์ดตัวเลข (เทียบกับช่วงก่อนหน้าที่ยาวเท่ากัน) */
    private const KPI_DAYS = 7;

    /** จำนวนวันของกราฟแนวโน้ม */
    private const TREND_DAYS = 30;

    /** เนื้อหาที่จะหมดเผยแพร่ภายในกี่วัน (รายการที่ควรดำเนินการ) */
    private const EXPIRE_DAYS = 7;

    /** @var array<string, true> */
    private array $permissions;

    private CarbonImmutable $now;

    public function __construct(User $user)
    {
        $this->permissions = array_fill_keys($user->getPermissionsArray(), true);
        $this->now = CarbonImmutable::now();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'attention' => $this->attention(),
            'kpis' => $this->kpis(),
            'trend' => $this->trend(),
            'topArticles' => $this->topArticles(),
            'recentContacts' => $this->recentContacts(),
            'content' => $this->content(),
            'recentActions' => $this->recentActions(),
        ];
    }

    /**
     * ปุ่มลัดที่หัวหน้า (เฉพาะที่มีสิทธิ์เพิ่มข้อมูล)
     *
     * @return list<array{key: string, label: string, href: string}>
     */
    public function shortcuts(): array
    {
        $items = [
            ['article', 'article.item.manage', 'เพิ่มบทความ', 'admin.article.item.add'],
            ['page', 'page.item.manage', 'เพิ่มหน้าเพจ', 'admin.page.item.add'],
            ['banner', 'banner.item.manage', 'เพิ่มป้ายโฆษณา', 'admin.banner.item.add'],
        ];

        $result = [];

        foreach ($items as [$key, $permission, $label, $route]) {
            if ($this->can($permission)) {
                $result[] = ['key' => $key, 'label' => $label, 'href' => route($route)];
            }
        }

        return $result;
    }

    /**
     * รายการที่ควรดำเนินการ — ส่งเฉพาะรายการที่มีจำนวน > 0
     *
     * @return list<array{key: string, label: string, count: int, tone: string, href: string}>
     */
    private function attention(): array
    {
        $items = [];

        if ($this->can('contactus.item.view')) {
            $counts = ContactusItem::query()
                ->whereIn('process_status', ['unread', 'considering'])
                ->selectRaw('process_status, count(*) as total')
                ->groupBy('process_status')
                ->pluck('total', 'process_status');

            $items[] = ['key' => 'contactus_unread', 'label' => 'ข้อความติดต่อที่ยังไม่ได้อ่าน', 'count' => (int) ($counts['unread'] ?? 0),
                'tone' => 'brand', 'href' => route('admin.contactus.item.index', ['process_status' => 'unread'])];
            $items[] = ['key' => 'contactus_considering', 'label' => 'ข้อความติดต่อที่อยู่ระหว่างพิจารณา', 'count' => (int) ($counts['considering'] ?? 0),
                'tone' => 'amber', 'href' => route('admin.contactus.item.index', ['process_status' => 'considering'])];
        }

        if ($this->can('article.item.view')) {
            $items[] = ['key' => 'article_expiring', 'label' => 'บทความที่จะหมดเวลาเผยแพร่ภายใน '.self::EXPIRE_DAYS.' วัน',
                'count' => $this->expiringCount('article_item_info'), 'tone' => 'amber', 'href' => route('admin.article.item.index')];
        }

        if ($this->can('popup.item.view')) {
            $items[] = ['key' => 'popup_expiring', 'label' => 'Popup ที่จะหมดเวลาเผยแพร่ภายใน '.self::EXPIRE_DAYS.' วัน',
                'count' => $this->expiringCount('popup_item_info'), 'tone' => 'amber', 'href' => route('admin.popup.item.index')];
        }

        if ($this->can('system.backlog.login')) {
            $count = DB::table('log_back_login')
                ->whereNull('deleted_at')
                ->whereIn('result', ['fail', 'block'])
                ->where('created_at', '>=', $this->now->subDay())
                ->count();

            $items[] = ['key' => 'login_failed', 'label' => 'การเข้าสู่ระบบไม่สำเร็จ/ถูกบล็อกใน 24 ชั่วโมง', 'count' => $count,
                'tone' => 'red', 'href' => route('admin.system.backlog.login.index', ['result' => 'fail', 'date_from' => $this->now->subDay()->toDateString()])];
        }

        return array_values(array_filter($items, fn (array $item) => $item['count'] > 0));
    }

    /**
     * การ์ดตัวเลข 7 วันล่าสุด + % เปลี่ยนแปลงจาก 7 วันก่อนหน้า (null = ช่วงก่อนหน้าไม่มีข้อมูล)
     *
     * @return list<array{key: string, label: string, value: int, change: float|null, hint: string, href: string}>
     */
    private function kpis(): array
    {
        $filters = $this->filters(self::KPI_DAYS);
        $cards = [];

        if ($this->can('system.frontlog.access')) {
            $summary = AccessLogReport::make(AccessLogReport::FRONT, $filters, excludeRobots: true)->summary();
            $cards[] = ['key' => 'front', 'label' => 'ผู้เข้าชมเว็บไซต์', 'value' => $summary['sessions'], 'change' => $summary['change']['sessions'],
                'hint' => 'เปิดหน้า '.number_format($summary['views']).' ครั้ง', 'href' => route('admin.system.frontlog.access.overview')];
        }

        foreach ($this->itemReports() as $key => [$report, $label, $route]) {
            $summary = $report->forModule($filters)->summary();
            $cards[] = ['key' => $key, 'label' => $label, 'value' => $summary['views'], 'change' => $summary['change']['views'],
                'hint' => number_format($summary['items']).' รายการที่มีการ'.($report->metric === 'click' ? 'คลิก' : 'เข้าชม'), 'href' => route($route)];
        }

        if ($this->can('contactus.item.view')) {
            $from = CarbonImmutable::parse($filters['date_from']);
            $current = ContactusItem::query()->where('created_at', '>=', $from)->count();
            $previous = ContactusItem::query()
                ->where('created_at', '>=', $from->subDays(self::KPI_DAYS))
                ->where('created_at', '<', $from)
                ->count();

            $cards[] = ['key' => 'contactus', 'label' => 'ข้อความติดต่อใหม่', 'value' => $current, 'change' => self::percentChange($previous, $current),
                'hint' => '', 'href' => route('admin.contactus.item.index')];
        }

        return $cards;
    }

    /**
     * กราฟแนวโน้ม 30 วัน — 1 เส้นต่อ metric ที่มีสิทธิ์ (ไม่มีสักเส้น = null)
     *
     * @return array{labels: list<string>, datasets: list<array{key: string, label: string, data: list<int>}>}|null
     */
    private function trend(): ?array
    {
        $filters = $this->filters(self::TREND_DAYS);
        $labels = null;
        $datasets = [];

        if ($this->can('system.frontlog.access')) {
            $series = AccessLogReport::make(AccessLogReport::FRONT, $filters, excludeRobots: true)->series();
            $labels = array_column($series, 'label');
            $datasets[] = ['key' => 'front', 'label' => 'ผู้เข้าชมเว็บไซต์', 'data' => array_column($series, 'sessions')];
        }

        foreach ($this->itemReports() as $key => [$report, $label]) {
            $series = $report->forModule($filters)->series();
            $labels ??= array_column($series, 'label');
            $datasets[] = ['key' => $key, 'label' => $label, 'data' => array_column($series, 'views')];
        }

        return $datasets === [] ? null : ['labels' => $labels, 'datasets' => $datasets];
    }

    /**
     * บทความยอดนิยม 7 วันล่าสุด — ชื่อเป็นลิงก์แก้ไขเฉพาะเมื่อมีสิทธิ์ดูบทความ
     *
     * @return array{items: list<array<string, mixed>>, href: string}|null
     */
    private function topArticles(): ?array
    {
        if (! $this->can('article.report.view')) {
            return null;
        }

        $report = ItemReport::article();
        $canView = $this->can('article.item.view');

        $items = array_map(fn (array $row) => [
            'rank' => $row['rank'],
            'title' => $row['title'],
            'category_title' => $row['category_title'],
            'views' => $row['views'],
            'deleted' => $row['deleted'],
            'href' => $canView && ! $row['deleted'] ? route('admin.article.item.edit', $row['id']) : null,
        ], $report->top($report->forModule($this->filters(self::KPI_DAYS)), 5));

        return ['items' => $items, 'href' => route('admin.article.report.top')];
    }

    /**
     * ข้อความติดต่อล่าสุด
     *
     * @return array{items: list<array<string, mixed>>, href: string}|null
     */
    private function recentContacts(): ?array
    {
        if (! $this->can('contactus.item.view')) {
            return null;
        }

        $items = ContactusItem::query()
            ->latest('created_at')
            ->latest('id')
            ->limit(5)
            ->get(['id', 'fullname', 'subject', 'process_status', 'created_at'])
            ->map(fn (ContactusItem $item) => [
                'id' => $item->id,
                'fullname' => $item->fullname,
                'subject' => $item->subject,
                'process_status' => $item->process_status,
                'process_status_label' => ContactusItem::PROCESS_STATUSES[$item->process_status] ?? $item->process_status,
                'created_at' => $item->created_at?->toIso8601String(),
                'href' => route('admin.contactus.item.edit', $item->id),
            ])
            ->all();

        return ['items' => $items, 'href' => route('admin.contactus.item.index')];
    }

    /**
     * ภาพรวมเนื้อหา — จำนวนที่เผยแพร่อยู่ตอนนี้ / ทั้งหมด ต่อโมดูลที่มีสิทธิ์ดู
     * "เผยแพร่อยู่" ใช้เงื่อนไขเดียวกับหน้าบ้าน (status = Y + อยู่ในช่วงเผยแพร่) — ข้อมูลตัวอย่าง is_temp นับด้วยตามปกติ
     *
     * @return list<array{key: string, label: string, published: int, total: int, href: string}>
     */
    private function content(): array
    {
        $modules = [
            ['article', 'article.item.view', 'บทความ', 'article_item_info', true, 'admin.article.item.index'],
            ['page', 'page.item.view', 'หน้าเพจ', 'page_item_info', false, 'admin.page.item.index'],
            ['banner', 'banner.item.view', 'ป้ายโฆษณา', 'banner_item_info', true, 'admin.banner.item.index'],
            ['popup', 'popup.item.view', 'Popup', 'popup_item_info', true, 'admin.popup.item.index'],
            ['intropage', 'intropage.item.view', 'Intropage', 'intropage_item_info', true, 'admin.intropage.item.index'],
        ];

        $result = [];

        foreach ($modules as [$key, $permission, $label, $table, $hasPublishRange, $route]) {
            if (! $this->can($permission)) {
                continue;
            }

            $published = $hasPublishRange ? $this->publishedExpression($table) : "case when {$table}.status = 'Y' then 1 else 0 end";

            $row = DB::table($table)
                ->whereNull("{$table}.deleted_at")
                ->selectRaw("count(*) as total, sum({$published}) as published")
                ->first();

            $result[] = ['key' => $key, 'label' => $label, 'published' => (int) ($row->published ?? 0), 'total' => (int) ($row->total ?? 0), 'href' => route($route)];
        }

        return $result;
    }

    /**
     * การกระทำล่าสุดของผู้ดูแลในหลังบ้าน
     *
     * @return array{items: list<array<string, mixed>>, href: string}|null
     */
    private function recentActions(): ?array
    {
        if (! $this->can('system.backlog.action')) {
            return null;
        }

        $rows = DB::table('log_back_action')
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'user_id', 'module_code', 'action_type', 'value_string', 'created_at']);

        $users = AccessLogReport::userInfo($rows->pluck('user_id')->filter()->unique()->values()->all());

        $items = $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'user_name' => $row->user_id !== null ? ($users[$row->user_id]['name'] ?? null) : null,
            'module_code' => $row->module_code,
            'action_type' => $row->action_type,
            'value_string' => $row->value_string,
            'created_at' => $row->created_at !== null ? CarbonImmutable::parse($row->created_at)->toIso8601String() : null,
        ])->all();

        return ['items' => $items, 'href' => route('admin.system.backlog.action.index')];
    }

    /**
     * รายงานการเข้าชม/คลิกของโมดูลเนื้อหาที่ผู้ใช้มีสิทธิ์ดูรายงาน
     *
     * @return array<string, array{0: ItemReport, 1: string, 2: string}> key => [report, ชื่อที่แสดง, route ภาพรวมรายงาน]
     */
    private function itemReports(): array
    {
        $reports = [];

        if ($this->can('article.report.view')) {
            $reports['article'] = [ItemReport::article(), 'ยอดเข้าชมบทความ', 'admin.article.report.overview'];
        }

        if ($this->can('page.report.view')) {
            $reports['page'] = [ItemReport::page(), 'ยอดเข้าชมหน้าเพจ', 'admin.page.report.overview'];
        }

        if ($this->can('banner.report.view')) {
            $reports['banner'] = [ItemReport::banner(), 'ยอดคลิกป้ายโฆษณา', 'admin.banner.report.overview'];
        }

        return $reports;
    }

    /** จำนวนรายการที่เผยแพร่อยู่และจะหมดเวลาเผยแพร่ภายใน EXPIRE_DAYS วัน */
    private function expiringCount(string $table): int
    {
        return (int) DB::table($table)
            ->whereNull('deleted_at')
            ->where('status', 'Y')
            ->where(fn (Builder $q) => $q->whereNull('publish_date')->orWhere('publish_date', '<=', $this->now))
            ->where('publish_down', '>', $this->now)
            ->where('publish_down', '<=', $this->now->addDays(self::EXPIRE_DAYS))
            ->count();
    }

    /** นิพจน์ SQL "เผยแพร่อยู่ตอนนี้" (1/0) — เงื่อนไขเดียวกับ Front\ArticleReader */
    private function publishedExpression(string $table): string
    {
        $now = DB::connection()->getPdo()->quote($this->now->toDateTimeString());

        return "case when {$table}.status = 'Y'"
            ." and ({$table}.publish_date is null or {$table}.publish_date <= {$now})"
            ." and ({$table}.publish_down is null or {$table}.publish_down > {$now}) then 1 else 0 end";
    }

    /**
     * ตัวกรองช่วงวันที่ของ ViewReport — N วันล่าสุดถึงวันนี้ จัดกลุ่มรายวัน
     *
     * @return array{date_from: string, date_to: string, period: string}
     */
    private function filters(int $days): array
    {
        $today = $this->now->startOfDay();

        return [
            'date_from' => $today->subDays($days - 1)->toDateString(),
            'date_to' => $today->toDateString(),
            'period' => 'day',
        ];
    }

    private function can(string $permission): bool
    {
        return isset($this->permissions[$permission]);
    }

    private static function percentChange(int $previous, int $current): ?float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }
}
