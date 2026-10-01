<?php

namespace App\Http\Requests\Admin\Banner\Concerns;

use App\Models\BannerItemInfo;
use App\Rules\SafeUrl;
use App\Support\FrontMenuType;
use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * กฎ validation ที่ใช้ร่วมกันระหว่าง Store/UpdateBannerItemRequest — เทียบเคียง ArticleItemValidationRules
 * แต่สั้นกว่ามากเพราะไม่มี SEO/slug และไม่มีเนื้อหาแบบแบ่ง part
 */
trait BannerItemValidationRules
{
    /**
     * กฎของฟิลด์ข้อมูลร่วม (ไม่แยกภาษา) — $itemId = ป้ายโฆษณาที่แก้ไข (หมวดหมู่เดิมที่ถูกปิดใช้งานไปแล้วยังบันทึกซ้ำได้)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function commonRules(?int $itemId = null): array
    {
        $current = $itemId
            ? BannerItemInfo::query()->whereKey($itemId)->first(['banner_category_info_id', 'front_menu_info_id'])
            : null;
        $currentCategoryId = $current?->banner_category_info_id;
        $currentMenuId = $current?->front_menu_info_id;

        return [
            'banner_category_info_id' => [
                'required', 'integer',
                Rule::exists('banner_category_info', 'id')->where(fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->where(fn ($w) => $w
                        ->where('status', 'Y')
                        ->when($currentCategoryId, fn ($or) => $or->orWhere('id', $currentCategoryId)))),
            ],
            'intro_image_id' => [
                'required', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            // ลิงก์: เลือกประเภทก่อน แล้วตรวจเฉพาะฟิลด์ของประเภทนั้น (exclude_unless — ฟิลด์ของประเภทอื่นไม่ถูกบันทึก ดู BannerItemController::linkPayload)
            'link_type' => ['required', Rule::in(BannerItemInfo::LINK_TYPES)],
            'front_menu_info_id' => [
                'exclude_unless:link_type,'.BannerItemInfo::LINK_MENU, 'required', 'integer',
                // เมนูที่เปิดใช้งานและมีลิงก์ของตัวเอง (ไม่ใช่เมนูหัวข้อ) — เมนูเดิมของรายการนี้ที่ถูกปิดภายหลังยังบันทึกซ้ำได้
                Rule::exists('front_menu_info', 'id')->where(fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->whereIn('menu_type', FrontMenuType::LINKABLE)
                    ->where(fn ($w) => $w
                        ->where('status', 'Y')
                        ->when($currentMenuId, fn ($or) => $or->orWhere('id', $currentMenuId)))),
            ],
            'url' => ['exclude_unless:link_type,'.BannerItemInfo::LINK_CUSTOM, 'required', 'string', 'max:500', new SafeUrl],
            'link_target' => ['exclude_unless:link_type,'.BannerItemInfo::LINK_CUSTOM, 'nullable', Rule::in(['_self', '_blank'])],
            'publish_date' => ['required', 'date'],
            'publish_down' => ['nullable', 'date', 'after:publish_date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['Y', 'N'])],
        ];
    }

    /**
     * กฎของฟิลด์แยกภาษา (detail.<lang>.*) — required เฉพาะ "ชื่อ" ของภาษาหลักเท่านั้น (site.lang_default)
     * ไม่มี slug จึงไม่มีกฎ unique
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function languageDetailRules(): array
    {
        $defaultLang = Setting::defaultLanguage();
        $rules = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["detail.{$lang}.title"] = [$lang === $defaultLang ? 'required' : 'nullable', 'string', 'max:500'];
            $rules["detail.{$lang}.intro_text"] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }
}
