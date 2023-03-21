<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use App\Models\DebitCardTransaction;

class ScheduledRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            // TODO: Complete factory
            'loan_id' => fn () => Loan::factory()->create(),
            'amount' => $this->faker->randomNumber(),
            'currency_code' => $this->faker->randomElement(DebitCardTransaction::CURRENCIES),
            'due_date' => $this->faker->dateTimeBetween('+1 month', '+3 year'),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}
