<?php

use App\Support\Front\HtmlSanitizer;

// การจัดข้อความ / ระยะห่างระหว่างบรรทัดจาก RichTextEditor ต้องผ่านไปถึงหน้าบ้าน แต่ style อื่นถูกตัดทิ้งเสมอ

test('text-align and line-height from the editor are kept on block tags', function () {
    $html = HtmlSanitizer::clean(
        '<p style="text-align: justify; line-height: 2">a</p><h2 style="text-align: center">b</h2><p style="line-height: 1.15">c</p>'
    );

    expect($html)->toContain('<p style="text-align: justify; line-height: 2">a</p>')
        ->and($html)->toContain('<h2 style="text-align: center">b</h2>')
        ->and($html)->toContain('<p style="line-height: 1.15">c</p>');
});

test('any other style, unknown values and styles on inline tags are dropped', function () {
    $html = HtmlSanitizer::clean(
        '<p style="color: red; text-align: middle; line-height: 9; background: url(javascript:alert(1))">a</p>'
        .'<p style="text-align: right; position: fixed">b</p><strong style="text-align: center">c</strong>'
    );

    expect($html)->toContain('<p>a</p>')
        ->and($html)->toContain('<p style="text-align: right">b</p>')
        ->and($html)->toContain('<strong>c</strong>')
        ->and($html)->not->toContain('color')
        ->and($html)->not->toContain('javascript')
        ->and($html)->not->toContain('position');
});
