<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        LogBackAccess::record('ข้อมูลส่วนตัว');

        $profileImage = $request->user()->profileImage; // อาจเป็น null ทั้งกรณียังไม่ได้เลือก และไฟล์ถูกลบไปแล้ว

        return Inertia::render('Admin/Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'profileImage' => $profileImage ? [
                'id' => $profileImage->id,
                'name' => $profileImage->name,
                'hash_name' => $profileImage->hash_name,
                'extension' => $profileImage->extension,
                'file_size' => $profileImage->file_size,
                'is_image' => $profileImage->isImage(),
                'created_at' => $profileImage->created_at,
            ] : null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->updated_by = $user->id; // แก้โปรไฟล์ตัวเอง — ผู้แก้ไข = ตัวเอง
        $user->save();

        LogBackAction::record('profile', 'update', $user->name, $user->id);

        return Redirect::route('admin.profile.edit')->with('success', 'บันทึกข้อมูลโปรไฟล์เรียบร้อยแล้ว');
    }
}
