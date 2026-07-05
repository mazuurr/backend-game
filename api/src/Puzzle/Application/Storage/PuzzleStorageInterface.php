<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Storage;

interface PuzzleStorageInterface
{
    public function store(string $tmpPath, string $targetFilename): void;

    public function delete(string $filename): void;

    public function getPath(string $filename): string;
}
