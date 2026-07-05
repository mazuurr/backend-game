<?php

declare(strict_types=1);

namespace App\Shared\Application\DTO;

final class PaginatedResult implements \JsonSerializable
{
    public function __construct(
        private readonly array $data,
        private readonly int $page,
        private readonly int $perPage,
        private readonly int $total,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'meta' => [
                'page' => $this->page,
                'per_page' => $this->perPage,
                'total' => $this->total,
                'pages' => $this->perPage > 0 ? (int) ceil($this->total / $this->perPage) : 1,
            ],
        ];
    }
}
