<?php

namespace App\Rules;

use App\Support\MarkdownParser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

class MarkdownTextLengthRule implements ValidationRule
{
    /**
     * @param int|null $minLength
     * @param int|null $maxLength
     */
    public function __construct(
        protected ?int $minLength = null,
        protected ?int $maxLength = null,
    ) {
    }

    /**
     * Validate the given attribute.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $attributeSanitized = preg_replace('/[^a-z0-9_.]/i', '', $attribute);

        try {
            $text = resolve(MarkdownParser::class)->toText((string) $value);
        } catch (Throwable) {
            $fail(__('validation.in', [
                'attribute' => trans_fb("validation.attributes.$attributeSanitized", $attributeSanitized),
            ]));

            return;
        }

        $length = mb_strlen($text);

        if (!is_null($this->minLength) && $length < $this->minLength) {
            $fail(__('validation.min.string', [
                'attribute' => trans_fb("validation.attributes.$attributeSanitized", $attributeSanitized),
                'min' => $this->minLength,
            ]));

            return;
        }

        if (!is_null($this->maxLength) && $length > $this->maxLength) {
            $fail(__('validation.rules.max.string', [
                'attribute' => trans_fb("validation.attributes.$attributeSanitized", $attributeSanitized),
                'max' => $this->maxLength,
                'actual' => $length,
            ]));
        }
    }
}
