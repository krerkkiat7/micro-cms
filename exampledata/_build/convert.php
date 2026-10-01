<?php

/**
 * แปลงผลลัพธ์ของ generate.mjs / screenshots.mjs (exampledata/_build/out) → exampledata/images, exampledata/files
 * - PNG ทั่วไป → JPG (quality 82) ลดขนาดไฟล์
 * - site/logo.png คง PNG (พื้นใส — ตั้งค่าระบบรับโลโก้ .png เท่านั้น)
 * - site/logo-mark.png → site/favicon.ico (PNG 16/32/48 ห่อใน ICO — ตั้งค่าระบบรับ favicon .ico เท่านั้น)
 * - PDF คัดลอกตรง ๆ
 *
 * รัน: php exampledata/_build/convert.php
 */

require __DIR__.'/../../vendor/autoload.php';

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

$out = __DIR__.'/out';
$images = dirname(__DIR__).'/images';
$files = dirname(__DIR__).'/files';
$manager = new ImageManager(new Driver);

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($out, FilesystemIterator::SKIP_DOTS));
$count = 0;

foreach ($iterator as $file) {
    $path = str_replace('\\', '/', $file->getPathname());
    $rel = ltrim(substr($path, strlen(str_replace('\\', '/', $out))), '/');

    if (str_starts_with($rel, '_html/')) {
        continue;
    }

    if (str_ends_with($rel, '.pdf')) {
        $target = $files.'/'.basename($rel);
        @mkdir(dirname($target), 0777, true);
        copy($path, $target);
        $count++;

        continue;
    }

    if (! str_ends_with($rel, '.png')) {
        continue;
    }

    if ($rel === 'site/logo-mark.png') {
        writeIco($manager, $path, $images.'/site/favicon.ico', [16, 32, 48]);
        $count++;

        continue;
    }

    $keepPng = $rel === 'site/logo.png';
    $target = $images.'/'.($keepPng ? $rel : substr($rel, 0, -4).'.jpg');
    @mkdir(dirname($target), 0777, true);

    if ($keepPng) {
        $manager->read($path)->toPng()->save($target);
    } else {
        $manager->read($path)->toJpeg(82)->save($target);
    }

    $count++;
}

echo "converted {$count} files\n";

/**
 * ICO แบบ PNG-embedded (รองรับทุก browser สมัยใหม่)
 *
 * @param  list<int>  $sizes
 */
function writeIco(ImageManager $manager, string $source, string $target, array $sizes): void
{
    $pngs = [];
    foreach ($sizes as $size) {
        $pngs[$size] = (string) $manager->read($source)->resize($size, $size)->toPng();
    }

    $header = pack('vvv', 0, 1, count($pngs));
    $dir = '';
    $data = '';
    $offset = 6 + 16 * count($pngs);

    foreach ($pngs as $size => $png) {
        $dir .= pack('CCCCvvVV', $size >= 256 ? 0 : $size, $size >= 256 ? 0 : $size, 0, 0, 1, 32, strlen($png), $offset);
        $data .= $png;
        $offset += strlen($png);
    }

    @mkdir(dirname($target), 0777, true);
    file_put_contents($target, $header.$dir.$data);
}
