<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\User\Models\GroupMember;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'is_active' => true,
            'group_member_id' => GroupMember::factory(),
        ];
    }
}
