<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'titlename' => $request->user()->titlename,
                    'firstname' => $request->user()->firstname,
                    'lastname' => $request->user()->lastname,
                    'name' => $request->user()->name, // accessor: คำนำหน้า + ชื่อ + นามสกุล
                    'email' => $request->user()->email,
                    'mobile' => $request->user()->mobile,
                    'phone' => $request->user()->phone,
                    'line' => $request->user()->line,
                    'facebook' => $request->user()->facebook,
                    // โยน Array ของ Action Codes เช่น ['article.view', 'article.create', 'article.delete'] ไปยัง Vue
                    'permissions' => $request->user()->getPermissionsArray(),
                ] : null,
            ],
        ];
    }
}
