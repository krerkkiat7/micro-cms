<?php

use App\Models\BannerItemInfo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

// การนับคลิกลิงก์ banner (front.banner.click — sendBeacon จาก Slideshow/Slideset/Grid จาก banner) ผ่าน ViewCounter ประเภท banner

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->banner = BannerItemInfo::create(['url' => 'https://example.com/promo', 'link_target' => '_blank', 'status' => 'Y']);
});

test('a banner click is recorded once per session with click_amount summed on banner_item_info', function () {
    $this->post(route('front.banner.click'), ['id' => $this->banner->id, 'lang' => 'en'])->assertNoContent();
    $this->post(route('front.banner.click'), ['id' => $this->banner->id, 'lang' => 'en'])->assertNoContent();

    $row = DB::table('banner_item_click')->where('banner_item_info_id', $this->banner->id)->first();

    expect(DB::table('banner_item_click')->where('banner_item_info_id', $this->banner->id)->count())->toBe(1)
        ->and($row->lang)->toBe('en')
        ->and($row->session_id)->not->toBeNull()
        ->and($row->action_date)->toBe(now()->toDateString())
        ->and($this->banner->fresh()->click_amount)->toBe(1);

    // session ใหม่นับใหม่
    $this->flushSession()->post(route('front.banner.click'), ['id' => $this->banner->id, 'lang' => 'th'])->assertNoContent();

    expect($this->banner->fresh()->click_amount)->toBe(2);
});

test('bots, unknown ids, banners without a link and disabled banners are not counted', function () {
    $noLink = BannerItemInfo::create(['url' => '', 'status' => 'Y']);
    $disabled = BannerItemInfo::create(['url' => 'https://example.com', 'status' => 'N']);

    $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
        ->post(route('front.banner.click'), ['id' => $this->banner->id, 'lang' => 'th'])
        ->assertNoContent();

    $this->withHeader('User-Agent', 'Mozilla/5.0')->post(route('front.banner.click'), ['id' => 999999, 'lang' => 'th'])->assertNoContent();
    $this->post(route('front.banner.click'), ['id' => $noLink->id, 'lang' => 'th'])->assertNoContent();
    $this->post(route('front.banner.click'), ['id' => $disabled->id, 'lang' => 'th'])->assertNoContent();
    $this->post(route('front.banner.click'), ['id' => 'abc', 'lang' => 'th'])->assertNoContent();

    expect(DB::table('banner_item_click')->count())->toBe(0)
        ->and($this->banner->fresh()->click_amount)->toBe(0);
});

test('an unknown language falls back to the default language and the endpoint needs no CSRF token', function () {
    $this->post(route('front.banner.click'), ['id' => $this->banner->id, 'lang' => 'xx'])->assertNoContent();

    expect(DB::table('banner_item_click')->value('lang'))->toBe('th');
});
