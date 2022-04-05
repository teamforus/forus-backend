<?php

namespace App\Services\BNGService\Responses\Pagination;

use App\Services\BNGService\Responses\Value;

class PaginationLink extends Value
{
    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getHref(): ?string
    {
        return $this->data['href'] ?? null;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getParams(): array
    {
        $query = [];
        parse_str(parse_url($this->getHref() ?: '', PHP_URL_QUERY), $query);

        return $query;
    }
}