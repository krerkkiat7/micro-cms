<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'titlename' => 'nullable|string|max:30',
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            // email ห้ามซ้ำกับผู้ใช้หลังบ้าน (user_type = back) ที่ยังไม่ถูกลบ
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:150',
                Rule::unique(User::class)->where(fn ($query) => $query
                    ->where('user_type', 'back')
                    ->whereNull('deleted_at')),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'titlename' => $request->titlename,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => 'back',
            'status' => 'Y',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('admin.dashboard', absolute: false));
    }
}
