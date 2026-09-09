<?php

namespace Database\Factories;

use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserGroup>
 */
class UserGroupFactory extends Factory
{
    protected $model = UserGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle().' Group',
            'description' => fake()->sentence(),
            'status' => 'Y',
            'can_edit' => 'Y',
            'can_delete' => 'Y',
        ];
    }

    /**
     * กลุ่มระบบที่แก้ไข/ลบไม่ได้ (เช่น Super Admin)
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_edit' => 'N',
            'can_delete' => 'N',
        ]);
    }

    /**
     * กลุ่มที่ปิดใช้งาน
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'N',
        ]);
    }
}
