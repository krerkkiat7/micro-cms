<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(string $lang): Response
    {
        return Inertia::render('Front/Home', [
            'currentLang' => $lang,
            'title' => $lang === 'th' ? 'ยินดีต้อนรับ' : 'Welcome',
        ]);
    }
}