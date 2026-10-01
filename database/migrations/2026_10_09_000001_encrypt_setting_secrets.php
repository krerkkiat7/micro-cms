<?php

use App\Support\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * เข้ารหัสค่าลับที่บันทึกไว้แบบข้อความธรรมดาก่อนหน้านี้ (Setting::SECRETS — smtp.password, turnstile.key_secret)
 * ค่าใหม่ถูกเข้ารหัสอัตโนมัติใน SysSetting::saving อยู่แล้ว — migration นี้จัดการเฉพาะข้อมูลเดิม (ค่าที่เข้ารหัสแล้วข้าม)
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->each(function (object $row) {
            if (Setting::decryptSecret($row->value) === null) {
                $this->update($row, Crypt::encryptString($row->value));
            }
        });

        Setting::forgetAll();
    }

    public function down(): void
    {
        $this->each(function (object $row) {
            $plain = Setting::decryptSecret($row->value);

            if ($plain !== null) {
                $this->update($row, $plain);
            }
        });

        Setting::forgetAll();
    }

    private function each(callable $callback): void
    {
        foreach (Setting::SECRETS as $group => $names) {
            DB::table('sys_setting')
                ->where('group', $group)
                ->whereIn('name', $names)
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->get(['group', 'name', 'value'])
                ->each($callback);
        }
    }

    private function update(object $row, string $value): void
    {
        DB::table('sys_setting')->where('group', $row->group)->where('name', $row->name)->update(['value' => $value]);
    }
};
