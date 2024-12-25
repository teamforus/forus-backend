<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DependencyRule implements Rule
{
    private ?string $responseMessage;
    private ?array $dependencies;

    /**
     * Create a new rule instance.
     *
     * DependencyRule constructor.
     * @param array|null $dependencies
     */
    public function __construct(array $dependencies = null)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $invalidDependencies = [];

        if (!is_array($value)) {
            $this->responseMessage = 'invalid_dependency_format';
            return false;
        }

        if (count($value) === 0) {
            return true;
        }


        foreach ($value as $dependency) {
            if (!in_array($dependency, $this->dependencies, true)) {
                $invalidDependencies[] = $dependency;
            }
        }

        if (count($invalidDependencies) > 0) {
            $this->responseMessage = sprintf(
                'invalid_dependency_item: %s',
                implode(', ', $invalidDependencies)
            );

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->responseMessage ?? 'Invalid input.';
    }
}
