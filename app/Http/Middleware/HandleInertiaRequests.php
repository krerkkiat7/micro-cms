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
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    // // โยน Array ของ Action Codes เช่น ['article.view', 'article.create', 'article.delete'] ไปยัง Vue
                    // 'permissions' => $request->user() && $request->user()->group 
                    //     ? $request->user()->group->actions->pluck('code')->toArray() 
                    //     : [],
                    'permissions' => $request->user()->getPermissionsArray(),
                ] : null,
            ],
        ];
    }
}
