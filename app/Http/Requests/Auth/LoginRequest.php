<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * เข้าได้เฉพาะผู้ใช้ user_type = 'back' (หลังบ้าน) และ status = 'Y' เท่านั้น
     * บันทึกสถิติ login สำเร็จ/ไม่สำเร็จ ลง sys_user
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = (string) $this->input('email');
        $password = (string) $this->input('password');

        $credentials = [
            'email' => $email,
            'password' => $password,
            'user_type' => 'back',
            'status' => 'Y',
        ];

        if (Auth::attempt($credentials, $this->boolean('remember'))) {
            // login สำเร็จ — เคลียร์สถิติ login ไม่สำเร็จ และบันทึกเวลา login ล่าสุด
            Auth::user()->forceFill([
                'failed_login_count' => 0,
                'last_failed_login_at' => null,
                'last_login_at' => now(),
            ])->save();

            RateLimiter::clear($this->throttleKey());

            return;
        }

        // login ไม่สำเร็จ — หาผู้ใช้หลังบ้านที่ยังไม่ถูกลบ ด้วย email นี้
        $user = User::where('email', $email)
            ->where('user_type', 'back')
            ->first();

        RateLimiter::hit($this->throttleKey());

        // รหัสผ่านถูกต้องแต่บัญชีถูกระงับ (status = 'N') → แจ้งว่าถูก block (ไม่นับเป็น login ไม่สำเร็จ)
        if ($user && $user->status !== 'Y' && Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ',
            ]);
        }

        // รหัสผ่านผิด → เพิ่มจำนวนครั้งที่ login ไม่สำเร็จ + บันทึกเวลา
        if ($user) {
            $user->forceFill([
                'failed_login_count' => $user->failed_login_count + 1,
                'last_failed_login_at' => now(),
            ])->save();
        }

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
