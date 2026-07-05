<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Storage;

use App\Puzzle\Application\Storage\PuzzleStorageInterface;

final class LocalPuzzleStorage implements PuzzleStorageInterface
{
    public function __construct(
        private readonly string $storagePath,
    ) {}

    public function store(string $tmpPath, string $targetFilename): void
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }

        $target = $this->getPath($targetFilename);

        if (!move_uploaded_file($tmpPath, $target) && !rename($tmpPath, $target)) {
            throw new \RuntimeException(sprintf('Failed to store file: %s', $targetFilename));
        }
    }

    public function delete(string $filename): void
    {
        $path = $this->getPath($filename);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function getPath(string $filename): string
    {
        return rtrim($this->storagePath, '/') . '/' . $filename;
    }
}
