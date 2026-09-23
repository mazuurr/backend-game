<?php

declare(strict_types=1);

namespace App\Tests\Unit\Puzzle\Infrastructure;

use App\Puzzle\Infrastructure\Storage\LocalPuzzleStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocalPuzzleStorage::class)]
final class LocalPuzzleStorageTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/puzzle-storage-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->storagePath)) {
            return;
        }

        foreach (glob($this->storagePath . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->storagePath);
    }

    public function testGetPathJoinsStorageRootAndFilename(): void
    {
        $storage = new LocalPuzzleStorage('/var/puzzles');

        self::assertSame('/var/puzzles/obrazek.jpg', $storage->getPath('obrazek.jpg'));
    }

    public function testGetPathTolerartesATrailingSlash(): void
    {
        $storage = new LocalPuzzleStorage('/var/puzzles/');

        self::assertSame('/var/puzzles/obrazek.jpg', $storage->getPath('obrazek.jpg'));
    }

    public function testStoreCreatesTheDirectoryAndMovesTheFile(): void
    {
        $storage = new LocalPuzzleStorage($this->storagePath);
        $tmp = $this->tempFileWith('zawartosc');

        $storage->store($tmp, 'obrazek.jpg');

        self::assertDirectoryExists($this->storagePath);
        self::assertFileExists($this->storagePath . '/obrazek.jpg');
        self::assertFileDoesNotExist($tmp);
        self::assertSame('zawartosc', file_get_contents($this->storagePath . '/obrazek.jpg'));
    }

    public function testStoreFailsLoudlyWhenSourceIsMissing(): void
    {
        $storage = new LocalPuzzleStorage($this->storagePath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to store file: obrazek.jpg');

        @$storage->store('/sciezka/ktora/nie/istnieje.jpg', 'obrazek.jpg');
    }

    public function testDeleteRemovesTheFile(): void
    {
        $storage = new LocalPuzzleStorage($this->storagePath);
        $storage->store($this->tempFileWith('zawartosc'), 'obrazek.jpg');

        $storage->delete('obrazek.jpg');

        self::assertFileDoesNotExist($this->storagePath . '/obrazek.jpg');
    }

    public function testDeletingAMissingFileIsANoOp(): void
    {
        $storage = new LocalPuzzleStorage($this->storagePath);

        $storage->delete('nie-istnieje.jpg');

        $this->addToAssertionCount(1);
    }

    private function tempFileWith(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'puzzle-src');
        file_put_contents($path, $contents);

        return $path;
    }
}
