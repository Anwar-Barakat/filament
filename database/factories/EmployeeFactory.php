<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Department;
use App\Models\Team;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $country    = Country::inRandomOrder()->first();
        $country->with(['states', 'cities']);

        $state      = $country->states()->inRandomOrder()->first();
        $city       = $country->cities()->inRandomOrder()->first();
        $department = Department::inRandomOrder()->first();

        return [
            'first_name'        => fake()->firstName(),
            'middle_name'       => fake()->firstName(),
            'last_name'         => fake()->lastName(),
            'address'           => fake()->address(),
            'zip_code'          => fake()->numberBetween(6, 10),
            'date_of_birth'     => fake()->date('Y-m-d', now()->subYears(20)),
            'date_of_hired'     => fake()->date('Y-m-d', now()->subMonths(rand(1, 5))),
            'country_id'        => $country->id,
            'state_id'          => $state->id,
            'city_id'           => $city->id,
            'department_id'     => $department->id,
            'team_id'           => Team::inRandomOrder()->first()->id,
        ];
    }
}
