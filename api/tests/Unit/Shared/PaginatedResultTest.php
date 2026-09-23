<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Application\DTO\PaginatedResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaginatedResult::class)]
final class PaginatedResultTest extends TestCase
{
    #[DataProvider('pageCountCases')]
    public function testRoundsPageCountUp(int $total, int $perPage, int $expectedPages): void
    {
        $result = new PaginatedResult([], 1, $perPage, $total);

        self::assertSame($expectedPages, $result->jsonSerialize()['meta']['pages']);
    }

    public static function pageCountCases(): array
    {
        return [
            'exact fit' => [40, 20, 2],
            'partial last page' => [41, 20, 3],
            'single item' => [1, 20, 1],
            'nothing' => [0, 20, 0],
        ];
    }

    public function testGuardsAgainstDivisionByZero(): void
    {
        $result = new PaginatedResult([], 1, 0, 100);

        self::assertSame(1, $result->jsonSerialize()['meta']['pages']);
    }

    public function testPayloadShape(): void
    {
        $payload = (new PaginatedResult(['a', 'b'], 2, 20, 42))->jsonSerialize();

        self::assertSame(['data', 'meta'], array_keys($payload));
        self::assertSame(['a', 'b'], $payload['data']);
        self::assertSame(['page' => 2, 'per_page' => 20, 'total' => 42, 'pages' => 3], $payload['meta']);
    }

    public function testJsonEncodesWithSnakeCaseMeta(): void
    {
        $json = json_encode(new PaginatedResult([], 1, 10, 5), JSON_THROW_ON_ERROR);

        self::assertJsonStringEqualsJsonString(
            '{"data":[],"meta":{"page":1,"per_page":10,"total":5,"pages":1}}',
            $json,
        );
    }
}
