<?php

namespace Database\Seeders\DecisionTrees;

use App\Models\DecisionNode;
use App\Models\DecisionTree;
use App\Models\Implementation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class BaseDecisionTreeSeeder extends Seeder
{
    /**
     * @return void
     * @throws \JsonException
     * @throws \Throwable
     */
    public function run(): void
    {
        $implementation = Implementation::general();
        $path = database_path('seeders/db/decision-trees/' . $this->file());
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data, $implementation) {
            $tree = DecisionTree::updateOrCreate(
                [
                    'slug' => $data['slug'],
                    'implementation_id' => $implementation->id,
                ],
                [
                    'name' => $data['name'],
                    'is_common' => $data['is_common'],
                    'start_node_key' => $data['start'],
                    'application_url' => $data['application_url'] ?? null,
                    'is_active' => false,
                ],
            );

            $keys = [];
            $order = 0;

            foreach ($data['nodes'] as $key => $n) {
                $key = (string) $key;
                $keys[] = $key;

                $node = DecisionNode::updateOrCreate(
                    ['decision_tree_id' => $tree->id, 'key' => $key],
                    [
                        'type' => $n['type'],
                        'label' => $n['label'],
                        'description' => $n['description'] ?? null,
                        'eligible' => $n['eligible'] ?? null,
                        'show_button' => $n['button'] ?? false,
                        'sort_order' => $order++,
                    ],
                );

                $node->options()->delete();

                foreach ($n['options'] ?? [] as $i => $o) {
                    $node->options()->create([
                        'label' => $o['label'],
                        'value' => $o['value'],
                        'next_node_key' => $o['next'],
                        'activates_tree_id' => isset($o['tree']) ? $this->treeId($o['tree']) : null,
                        'sort_order' => $i,
                    ]);
                }
            }

            // Mirror the file: drop nodes that were removed from it (their options cascade).
            $tree->nodes()->whereNotIn('key', $keys)->get()->each->delete();
        });
    }

    /**
     * @return string
     */
    abstract protected function file(): string;

    /**
     * @param string $slug
     * @return int
     */
    private function treeId(string $slug): int
    {
        return DecisionTree::where('slug', $slug)->value('id')
            ?? throw new RuntimeException(
                "Decision tree '$slug' does not exist yet. Seed the organization trees before the common tree (php artisan db:seed --class=DecisionTreeSeeder).",
            );
    }
}
