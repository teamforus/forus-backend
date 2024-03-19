<?php

namespace App\Services\TokenGeneratorService;

class TokenGenerator
{
    /**
     * @param int|string $block_length
     * @param int $block_count
     *
     * @return string
     *
     * @psalm-param '62'|int $block_length
     */
    public function generate(string|int $block_length, $block_count = 1): string
    {
        return collect(range(0, $block_count - 1))->map(static function() use ($block_length) {
            return bin2hex(random_bytes($block_length / 2));
        })->implode('-');
    }

    /**
     * @return string
     */
    public function address(): string
    {
        return '0x' . $this->generate(40);
    }
}