<?php

namespace Database\Seeders;

use App\Models\Implementation;
use App\Models\ImplementationPage;
use Illuminate\Database\Seeder;

class ImplementationPagesTableSeeder extends Seeder
{
    /**
     * @var string[][]
     */
    protected array $implementationPages = [];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $implementations = Implementation::get();
        $page_types = ImplementationPage::TYPES;

        foreach ($implementations as $implementation) {
            $implementationPages = $implementation->pages->pluck('page_type')->toArray();

            foreach (array_diff($page_types, $implementationPages) as $page_type) {
                $implementation->pages()->create([
                    'page_type' => $page_type
                ]);
            }
        }
    }
}
