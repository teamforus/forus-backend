<?php

namespace Database\Seeders;

use App\Models\ReimbursementCategory;
use Illuminate\Database\Seeder;

class ReimbursementCategoriesTableSeeder extends Seeder
{
    /**
     * @var array|string[]
     */
    protected array $categories = [
        'Sports (activity)',
        'Sports (product)',
        'Culture',
        'Education',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->categories as $name) {
            ReimbursementCategory::firstOrCreate(compact('name'));
        }
    }
}
