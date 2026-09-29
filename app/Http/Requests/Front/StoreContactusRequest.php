<?php

namespace App\Http\Requests\Front;

use App\Support\ClientIp;
use App\Support\ContactusSetting;
use App\Support\Turnstile;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * ส่งแบบฟอร์มติดต่อเราจากหน้าบ้าน — ด่านความปลอดภัย (ตามลำดับ):
 * 1. จำกัดจำนวนครั้งต่อ IP (MAX_ATTEMPTS ครั้ง / DECAY_SECONDS) นอกเหนือจาก throttle ของ route
 * 2. form_token = เวลาที่เปิดฟอร์ม (เข้ารหัส) — ต้องผ่านไปอย่างน้อย MIN_SECONDS และไม่เกิน MAX_SECONDS (กันบอทที่ยิงทันที/ใช้ token เก่า)
 * 3. rules สร้างจากตั้งค่า (ฟิลด์ที่ซ่อนไม่ถูกตรวจ และ controller ไม่บันทึก) + ความยาวสูงสุดทุกฟิลด์
 * 4. Cloudflare Turnstile (fail-closed)
 * honeypot (`website`) ตรวจใน controller — มีค่า = ทิ้งเงียบ ๆ ตอบเหมือนสำเร็จ (ไม่บอกบอทว่าโดนจับได้)
 */
class StoreContactusRequest extends FormRequest
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 600;

    public const MIN_SECONDS = 3;

    public const MAX_SECONDS = 7200;

    /** ความยาวสูงสุดของแต่ละฟิลด์ */
    public const MAX_LENGTHS = [
        'fullname' => 255,
        'position' => 255,
        'company' => 255,
        'phone' => 50,
        'email' => 255,
        'subject' => 255,
        'detail' => 5000,
    ];

    /** @var array<string, bool>|null */
    private ?array $fields = null;

    /**
     * ฟิลด์ที่เปิดรับตามตั้งค่า => บังคับกรอก
     *
     * @return array<string, bool>
     */
    public function formFields(): array
    {
        return $this->fields ??= ContactusSetting::formFields();
    }

    public static function throttleKey(?string $ip): string
    {
        return 'contactus:'.($ip ?? 'unknown');
    }

    /**
     * ตัดช่องว่างหัวท้าย + ตัวอักษรควบคุม (เก็บขึ้นบรรทัดใหม่/แท็บไว้เฉพาะรายละเอียด) ก่อน validate
     */
    protected function prepareForValidation(): void
    {
        // แบบฟอร์มปิดอยู่ (ไม่แสดง/ยังไม่ได้ตั้งค่า Turnstile) = ไม่มี endpoint นี้ — ตอบ 404 ก่อนตรวจอะไรทั้งนั้น
        if (! ContactusSetting::formEnabled()) {
            abort(404);
        }

        $clean = [];

        foreach (array_keys(self::MAX_LENGTHS) as $field) {
            $value = $this->input($field);

            if (! is_string($value)) {
                continue;
            }

            $value = str_replace("\r\n", "\n", $value);
            $value = $field === 'detail'
                ? preg_replace('/[^\P{C}\n\t]/u', '', $value)
                : preg_replace('/\p{C}/u', '', $value);

            $clean[$field] = trim((string) $value);
        }

        $this->merge($clean);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'form_token' => ['required', 'string', 'max:1000'],
            'website' => ['nullable'],
        ];

        foreach ($this->formFields() as $field => $required) {
            $fieldRules = [$required ? 'required' : 'nullable', 'string', 'max:'.self::MAX_LENGTHS[$field]];

            if ($field === 'email') {
                $fieldRules[] = 'email:rfc';
            }

            if ($field === 'phone') {
                $fieldRules[] = 'regex:/^[0-9+\-()#.,\/ ]+$/';
            }

            $rules[$field] = $fieldRules;
        }

        if (Turnstile::configured()) {
            $rules['cf-turnstile-response'] = ['required', 'string', 'max:4096'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => __('front.contactus_form.error_required'),
            'max' => __('front.contactus_form.error_max'),
            'email' => __('front.contactus_form.error_email'),
            'regex' => __('front.contactus_form.error_phone'),
            'string' => __('front.contactus_form.error_invalid'),
            'form_token.required' => __('front.contactus_form.error_expired'),
            'cf-turnstile-response.required' => __('front.contactus_form.error_captcha'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(array_keys(self::MAX_LENGTHS))
            ->mapWithKeys(fn (string $field) => [$field => __("front.contactus_form.fields.{$field}")])
            ->all();
    }

    /**
     * ด่านที่ไม่ใช่รูปแบบข้อมูล (จำนวนครั้ง / เวลาเปิดฟอร์ม / CAPTCHA) — ตรวจหลัง rules ผ่าน
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // จำกัดจำนวนครั้งด้วย $request->ip() (เคารพ TrustProxies) ไม่ใช่ ClientIp ที่อ่าน header ซึ่ง client ปลอมได้
            $key = self::throttleKey($this->ip());
            $ip = ClientIp::from($this);

            if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
                throw ValidationException::withMessages([
                    'form' => __('front.contactus_form.error_too_many', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
                ]);
            }

            RateLimiter::hit($key, self::DECAY_SECONDS);

            if (! $this->validFormToken()) {
                $validator->errors()->add('form', __('front.contactus_form.error_expired'));

                return;
            }

            if (Turnstile::configured() && ! Turnstile::verify($this->input('cf-turnstile-response'), $ip)) {
                $validator->errors()->add('cf-turnstile-response', __('front.contactus_form.error_captcha'));
            }
        });
    }

    /**
     * ข้อมูลที่บันทึกได้ — เฉพาะฟิลด์ที่เปิดรับตามตั้งค่า (ฟิลด์อื่นเป็น null เสมอ แม้ผู้ส่งจะแนบมาเอง)
     *
     * @return array<string, string|null>
     */
    public function contactData(): array
    {
        $data = [];

        foreach (array_keys(self::MAX_LENGTHS) as $field) {
            $value = array_key_exists($field, $this->formFields()) ? $this->validated($field) : null;
            $data[$field] = is_string($value) && $value !== '' ? $value : null;
        }

        return $data;
    }

    public function isHoneypotFilled(): bool
    {
        return trim((string) $this->input('website', '')) !== '';
    }

    public static function issueFormToken(): string
    {
        return Crypt::encryptString((string) time());
    }

    private function validFormToken(): bool
    {
        try {
            $issuedAt = (int) Crypt::decryptString((string) $this->input('form_token'));
        } catch (DecryptException) {
            return false;
        }

        $elapsed = time() - $issuedAt;

        return $elapsed >= self::MIN_SECONDS && $elapsed <= self::MAX_SECONDS;
    }
}
