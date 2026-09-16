<?php

namespace App\Http\Controllers\Admin\Article;

use App\Http\Controllers\Controller;
use App\Models\ArticleTagDetail;
use App\Models\ArticleTagInfo;
use App\Support\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ค้นหา/สร้างแท็กบทความแบบ ajax — ใช้เฉพาะจาก TagPicker.vue ในฟอร์มเพิ่ม/แก้ไขบทความ
 * (ไม่มีหน้าจัดการแท็กแยกในรอบนี้ ดู docs/PRD-article.md §2.1) ตรวจสิทธิ์ article.item.manage เหมือน
 * การเพิ่ม/แก้ไขบทความ เพราะเป็นการกระทำที่เกิดขึ้นจากในฟอร์มบทความเท่านั้น
 */
class ArticleTagController extends Controller
{
    /**
     * ค้นหาแท็กที่เปิดใช้งานจากชื่อภาษาหลัก — คืนไม่เกิน 10 รายการ
     */
    public function search(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('article.item.manage'), 403);

        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json(['data' => []]);
        }

        $defaultLang = Setting::defaultLanguage();

        $tags = ArticleTagInfo::query()
            ->join('article_tag_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'article_tag_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('d.deleted_at')
            ->where('article_tag_info.status', 'Y')
            ->where('d.name', 'like', "%{$term}%")
            ->orderBy('d.name')
            ->limit(10)
            ->get(['article_tag_info.id', 'd.name as name']);

        return response()->json(['data' => $tags]);
    }

    /**
     * สร้างแท็กใหม่ (ใช้ตอนพิมพ์ชื่อแท็กที่ยังไม่มีในระบบ) — ต้องกรอกชื่อครบทุกภาษาที่ระบบเปิดใช้
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('article.item.manage'), 403);

        $rules = [];
        foreach (Setting::selectedLanguages() as $lang) {
            $rules["name.{$lang}"] = ['required', 'string', 'max:100'];
        }
        $rules['name'] = ['required', 'array'];

        $data = $request->validate($rules);
        $actorId = $request->user()->id;

        $tagInfo = ArticleTagInfo::create([
            'status' => 'Y',
            'created_by' => $actorId,
        ]);

        foreach ($data['name'] as $lang => $name) {
            $slugBase = str($name)->slug()->value();

            ArticleTagDetail::create([
                'id' => $tagInfo->id,
                'lang' => $lang,
                'name' => $name,
                'slug' => $this->uniqueSlug($lang, $slugBase !== '' ? $slugBase : "tag-{$tagInfo->id}"),
                'status' => 'Y',
                'created_by' => $actorId,
            ]);
        }

        $defaultLang = Setting::defaultLanguage();

        return response()->json([
            'data' => [
                'id' => $tagInfo->id,
                'name' => $data['name'][$defaultLang] ?? reset($data['name']),
            ],
        ], 201);
    }

    /**
     * กัน slug ชนกันภายในภาษาเดียวกัน (unique(['lang','slug'])) โดยเติมเลขต่อท้ายถ้าจำเป็น
     */
    private function uniqueSlug(string $lang, string $base): string
    {
        $slug = $base;
        $suffix = 1;

        while (ArticleTagDetail::where('lang', $lang)->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
