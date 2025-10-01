<?php

namespace App\Traits;

use App\Models\Faq;
use Illuminate\Validation\Rule;

trait ValidatesFaq
{
    /**
     * @return string[]
     */
    protected function faqRules(?array $allowedIds = null): array
    {
        return array_merge([
            'faq' => 'nullable|array',
            'faq.*' => 'required|array',
            'faq.*.type' => [
                'required',
                'string',
                Rule::in([Faq::TYPE_QUESTION, Faq::TYPE_TITLE]),
            ],
            'faq.*.title' => 'required|string|max:200',
            'faq.*.subtitle' => [
                'nullable',
                'string',
                'max:500',
            ],
            'faq.*.description' => [
                'nullable',
                'required_if:faq.*.type,' . Faq::TYPE_QUESTION,
                ...$this->markdownRules(0, 5000),
            ],
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

        return array_combine($keys, array_map(static function ($key) {
            $value = last(explode('.', $key));

            return trans_fb('validation.attributes.' . $value, $value);
        }, $keys));
    }
}
