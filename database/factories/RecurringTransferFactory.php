<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RecurringTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTransfer>
 */
class RecurringTransferFactory extends Factory
{
    protected $model = RecurringTransfer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $stopDate = $this->faker->boolean(80) 
            ? $this->faker->dateTimeBetween($startDate, '+6 months')
            : null;

        return [
            'user_id' => User::factory(),
            'start_date' => $startDate,
            'stop_date' => $stopDate,
            'frequency' => $this->faker->numberBetween(1, 30), // days
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'reason' => $this->faker->sentence(),
        ];
    }
}

