<?php

namespace App\Traits;

use Illuminate\Validation\Rule;

trait ValidatesFaq
{
    /**
     * @return string[]
     */
    protected function getFaqRules(?array $allowedIds = null): array
    {
        return array_merge([
            'faq' => 'nullable|array',
            'faq.*' => 'required|array',
            'faq.*.title' => 'required|string|max:200',
            'faq.*.description' => 'required|string|max:5000',
        ], $allowedIds !== null ? [
            'faq.*.id' => [
                'nullable',
                Rule::exists('faq', 'id'),
                Rule::in($allowedIds),
            ],
        ] : []);
    }

    /**
     * @return array
     */
    protected function getFaqAttributes(): array
    {
        $keys = array_dot(array_keys($this->rules()));

        return array_combine($keys, array_map(static function($key) {
            $value = last(explode('.', $key));
            return trans_fb("validation.attributes." . $value, $value);
        }, $keys));
    }
}