<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = ['Laravel Daily', 'Spatie'];
        $admin = User::where('email', 'admin@example.com')->first();

        foreach ($teams as $team) {
            if (is_null(Team::where('name', $team)->first())) {
                $team = Team::create(['name' => $team, 'slug' => Str::slug($team)]);

                $team->members()->attach($admin);
            }
        }
    }
}
