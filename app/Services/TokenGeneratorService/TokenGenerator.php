<?php

namespace App\Services\TokenGeneratorService;

class TokenGenerator
{
    /**
     * @param $block_length
     * @param int $block_count
     * @return string
     */
    public function generate($block_length, $block_count = 1)
    {
        return collect(range(0, $block_count - 1))
        ->map(function() use ($block_length) {
            return bin2hex(openssl_random_pseudo_bytes($block_length / 2));
        })->implode('-');
    }

    /**
     * TODO: temporary placeholder, should be replaced by actual contract address
     *
     * @return string
     */
    public function address(){
        return '0x' . $this->generate(40);
    }
}