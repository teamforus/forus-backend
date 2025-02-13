<?php

namespace Tests\Browser\Traits;

use Throwable;

trait RollbackModelsTrait
{
    /**
     * @param array $modelsWithFields
     * @param callable $callback
     * @param callable|null $finalCallback
     * @return mixed
     * @throws Throwable
     */
    public function rollbackModels(array $modelsWithFields, callable $callback, callable $finalCallback = null): mixed
    {
        // Backup original values
        $originalValues = [];

        foreach ($modelsWithFields as [$model, $fields]) {
            $originalValues[] = [$model, $fields];
        }

        try {
            // Execute the main callback
            $result = $callback();

            // Restore original values
            foreach ($originalValues as [$model, $fields]) {
                $model->forceFill($fields)->save();
            }

            // Execute the final callback if provided
            if ($finalCallback) {
                $finalCallback();
            }

            return $result;
        } catch (Throwable $e) {
            // Restore original values even if an exception occurs
            foreach ($originalValues as [$model, $fields]) {
                $model->forceFill($fields)->save();
            }

            // Execute the final callback even on failure
            if ($finalCallback) {
                $finalCallback();
            }

            // Re-throw the exception
            throw $e;
        }
    }
}
