<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => 3000,
            'terms' => 3,
            'outstanding_amount' => 3000,
            'currency_code' => Loan::CURRENCY_SGD,
            'processed_at' => now(),
            'status' => Loan::STATUS_DUE,
        ];
    }
}
