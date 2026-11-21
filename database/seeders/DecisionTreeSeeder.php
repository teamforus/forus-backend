<?php

namespace Database\Seeders;

use Database\Seeders\DecisionTrees\ArnhemDecisionTreeSeeder;
use Database\Seeders\DecisionTrees\CommonDecisionTreeSeeder;
use Database\Seeders\DecisionTrees\DuivenDecisionTreeSeeder;
use Database\Seeders\DecisionTrees\OtherDecisionTreeSeeder;
use Database\Seeders\DecisionTrees\RenkumDecisionTreeSeeder;
use Database\Seeders\DecisionTrees\RhedenDecisionTreeSeeder;
use Database\Seeders\DecisionTrees\RozendaalDecisionTreeSeeder;
use Database\Seeders\DecisionTrees\WestervoortDecisionTreeSeeder;
use Database\Seeders\DecisionTrees\ZevenaarDecisionTreeSeeder;
use Illuminate\Database\Seeder;

class DecisionTreeSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $this->call([
            ArnhemDecisionTreeSeeder::class,
            DuivenDecisionTreeSeeder::class,
            RenkumDecisionTreeSeeder::class,
            RhedenDecisionTreeSeeder::class,
            RozendaalDecisionTreeSeeder::class,
            WestervoortDecisionTreeSeeder::class,
            ZevenaarDecisionTreeSeeder::class,
            OtherDecisionTreeSeeder::class,
            CommonDecisionTreeSeeder::class,
        ]);
    }
}
