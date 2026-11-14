<?php

namespace App\Console\Commands;

use App\Models\DecisionTree;
use Illuminate\Console\Command;

class ValidateDecisionTrees extends Command
{
    protected $signature = 'decision-tree:validate';

    protected $description = 'Check decision trees for missing nodes, malformed nodes and unreachable nodes';

    /**
     * @return int
     */
    public function handle(): int
    {
        $result = DecisionTree::validate();

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }
        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        if ($result['errors']) {
            $this->newLine();
            $this->error(count($result['errors']) . ' error(s) found.');

            return self::FAILURE;
        }

        $this->info('Decision trees are valid.');

        return self::SUCCESS;
    }
}
