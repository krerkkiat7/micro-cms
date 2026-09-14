<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\LogBackAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            // เงื่อนไขเดียวกับการตั้ง/เปลี่ยนรหัสผ่านในโมดูลจัดการผู้ใช้งาน (StoreUserRequest/UpdateUserPasswordRequest)
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
            'password_changed_by' => $user->id, // เปลี่ยนรหัสผ่านตัวเอง — ผู้เปลี่ยน = ตัวเอง
        ]);

        LogBackAction::record('profile.password', 'update', $user->name, $user->id);

        return back()->with('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
    }
}
