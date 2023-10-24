<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = ['ReactJS', 'VueJS', 'AngularJS', 'NextJS', 'Laravel'];
        foreach ($departments as $department) {
            if (is_null(Department::where('name', $department)->first())) {
                Department::create(['name' => $department]);
            }
        }
    }
}
