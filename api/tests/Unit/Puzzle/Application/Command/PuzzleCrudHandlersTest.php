<?php

declare(strict_types=1);

namespace App\Tests\Unit\Puzzle\Application\Command;

use App\Puzzle\Application\Command\DeletePuzzle\DeletePuzzleCommand;
use App\Puzzle\Application\Command\DeletePuzzle\DeletePuzzleHandler;
use App\Puzzle\Application\Command\UpdatePuzzle\UpdatePuzzleCommand;
use App\Puzzle\Application\Command\UpdatePuzzle\UpdatePuzzleHandler;
use App\Puzzle\Application\Command\UploadPuzzle\UploadPuzzleCommand;
use App\Puzzle\Application\Command\UploadPuzzle\UploadPuzzleHandler;
use App\Puzzle\Application\Storage\PuzzleStorageInterface;
use App\Puzzle\Domain\Entity\Puzzle;
use App\Puzzle\Domain\Exception\PuzzleNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(UploadPuzzleHandler::class)]
#[CoversClass(UpdatePuzzleHandler::class)]
#[CoversClass(DeletePuzzleHandler::class)]
final class PuzzleCrudHandlersTest extends TestCase
{
    private PuzzleRepositoryInterface&MockObject $repository;
    private PuzzleStorageInterface&MockObject $storage;
    private PuzzleId $puzzleId;
    private string $tmpImage;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PuzzleRepositoryInterface::class);
        $this->storage = $this->createMock(PuzzleStorageInterface::class);
        $this->puzzleId = PuzzleId::generate();
        $this->tmpImage = '';
    }

    protected function tearDown(): void
    {
        if ($this->tmpImage !== '' && file_exists($this->tmpImage)) {
            unlink($this->tmpImage);
        }
    }

    // --- UploadPuzzle ---

    #[DataProvider('storedFilenameCases')]
    public function testStoredFilenameIsDerivedFromUuidAndExtension(string $originalName, string $suffix): void
    {
        $saved = null;
        $this->repository->method('save')->willReturnCallback(
            static function (Puzzle $puzzle) use (&$saved): void {
                $saved = $puzzle;
            },
        );

        $expected = $this->puzzleId->value() . $suffix;
        $this->storage->expects(self::once())->method('store')->with('/tmp/upload', $expected);

        $this->uploadHandler()(new UploadPuzzleCommand(
            uuid: $this->puzzleId->value(),
            originalName: $originalName,
            tmpPath: '/tmp/upload',
            mimeType: 'image/jpeg',
            size: 1024,
        ));

        self::assertInstanceOf(Puzzle::class, $saved);
        self::assertSame($expected, $saved->getStoredFilename());
        self::assertSame($originalName, $saved->getOriginalName());
    }

    public static function storedFilenameCases(): array
    {
        return [
            'jpg' => ['obrazek.jpg', '.jpg'],
            'png' => ['obrazek.png', '.png'],
            'uppercase extension' => ['OBRAZEK.PNG', '.PNG'],
            'dotted name' => ['moje.zdjecie.jpeg', '.jpeg'],
            'no extension' => ['obrazek', ''],
        ];
    }

    public function testWithoutPieceCountTheGridIsTakenVerbatim(): void
    {
        $saved = null;
        $this->repository->method('save')->willReturnCallback(
            static function (Puzzle $puzzle) use (&$saved): void {
                $saved = $puzzle;
            },
        );

        $this->uploadHandler()(new UploadPuzzleCommand(
            uuid: $this->puzzleId->value(),
            originalName: 'obrazek.jpg',
            tmpPath: '/tmp/upload',
            mimeType: 'image/jpeg',
            size: 1024,
            difficulty: 3,
            totalPieces: null,
            piecesPerFragment: 25,
            piecesX: 8,
            piecesY: 6,
        ));

        self::assertInstanceOf(Puzzle::class, $saved);
        self::assertSame(8, $saved->getPiecesX());
        self::assertSame(6, $saved->getPiecesY());
        self::assertNull($saved->getTotalPieces());
        self::assertSame(3, $saved->getDifficulty());
        self::assertSame(25, $saved->getPiecesPerFragment());
    }

    public function testPieceCountIsRecomputedFromRealImageDimensions(): void
    {
        $this->tmpImage = $this->createImage(1600, 900);

        $saved = null;
        $this->repository->method('save')->willReturnCallback(
            static function (Puzzle $puzzle) use (&$saved): void {
                $saved = $puzzle;
            },
        );

        $this->uploadHandler()(new UploadPuzzleCommand(
            uuid: $this->puzzleId->value(),
            originalName: 'obrazek.png',
            tmpPath: $this->tmpImage,
            mimeType: 'image/png',
            size: 1024,
            totalPieces: 100,
        ));

        self::assertInstanceOf(Puzzle::class, $saved);
        self::assertNotNull($saved->getPiecesX());
        self::assertNotNull($saved->getPiecesY());
        self::assertGreaterThan($saved->getPiecesY(), $saved->getPiecesX());
        self::assertSame($saved->getPiecesX() * $saved->getPiecesY(), $saved->getTotalPieces());
    }

    public function testUnreadableImageFallsBackToASquareGrid(): void
    {
        $saved = null;
        $this->repository->method('save')->willReturnCallback(
            static function (Puzzle $puzzle) use (&$saved): void {
                $saved = $puzzle;
            },
        );

        $this->uploadHandler()(new UploadPuzzleCommand(
            uuid: $this->puzzleId->value(),
            originalName: 'obrazek.jpg',
            tmpPath: '/sciezka/ktora/nie/istnieje.jpg',
            mimeType: 'image/jpeg',
            size: 1024,
            totalPieces: 49,
        ));

        self::assertInstanceOf(Puzzle::class, $saved);
        self::assertSame(7, $saved->getPiecesX());
        self::assertSame(7, $saved->getPiecesY());
        self::assertSame(49, $saved->getTotalPieces());
    }

    public function testFileIsStoredBeforeTheRecordIsPersisted(): void
    {
        $calls = [];
        $this->storage->method('store')->willReturnCallback(static function () use (&$calls): void {
            $calls[] = 'store';
        });
        $this->repository->method('save')->willReturnCallback(static function () use (&$calls): void {
            $calls[] = 'save';
        });

        $this->uploadHandler()(new UploadPuzzleCommand(
            uuid: $this->puzzleId->value(),
            originalName: 'obrazek.jpg',
            tmpPath: '/tmp/upload',
            mimeType: 'image/jpeg',
            size: 1024,
        ));

        self::assertSame(['store', 'save'], $calls);
    }

    // --- UpdatePuzzle ---

    public function testUpdateChangesOnlyExplicitlyFlaggedFields(): void
    {
        $puzzle = $this->puzzle();
        $this->repository->method('findByUuid')->willReturn($puzzle);
        $this->repository->expects(self::once())->method('save')->with($puzzle);

        (new UpdatePuzzleHandler($this->repository))(new UpdatePuzzleCommand(
            uuid: $this->puzzleId->value(),
            hasDifficulty: true,
            difficulty: 5,
        ));

        self::assertSame(5, $puzzle->getDifficulty());
        self::assertSame(100, $puzzle->getTotalPieces());
        self::assertSame(10, $puzzle->getPiecesX());
    }

    public function testUpdateCanExplicitlyClearAField(): void
    {
        $puzzle = $this->puzzle();
        $this->repository->method('findByUuid')->willReturn($puzzle);

        (new UpdatePuzzleHandler($this->repository))(new UpdatePuzzleCommand(
            uuid: $this->puzzleId->value(),
            hasPiecesPerFragment: true,
            piecesPerFragment: null,
        ));

        self::assertNull($puzzle->getPiecesPerFragment());
    }

    public function testUpdateIgnoresValuesWithoutTheirFlag(): void
    {
        $puzzle = $this->puzzle();
        $this->repository->method('findByUuid')->willReturn($puzzle);

        (new UpdatePuzzleHandler($this->repository))(new UpdatePuzzleCommand(
            uuid: $this->puzzleId->value(),
            difficulty: 5,
            totalPieces: 999,
        ));

        self::assertSame(1, $puzzle->getDifficulty());
        self::assertSame(100, $puzzle->getTotalPieces());
    }

    public function testUpdateChangesEveryFlaggedField(): void
    {
        $puzzle = $this->puzzle();
        $this->repository->method('findByUuid')->willReturn($puzzle);

        (new UpdatePuzzleHandler($this->repository))(new UpdatePuzzleCommand(
            uuid: $this->puzzleId->value(),
            hasDifficulty: true,
            difficulty: 4,
            hasTotalPieces: true,
            totalPieces: 64,
            hasPiecesPerFragment: true,
            piecesPerFragment: 16,
            hasPiecesX: true,
            piecesX: 8,
            hasPiecesY: true,
            piecesY: 8,
        ));

        self::assertSame(4, $puzzle->getDifficulty());
        self::assertSame(64, $puzzle->getTotalPieces());
        self::assertSame(16, $puzzle->getPiecesPerFragment());
        self::assertSame(8, $puzzle->getPiecesX());
        self::assertSame(8, $puzzle->getPiecesY());
    }

    public function testUpdateThrowsWhenPuzzleMissing(): void
    {
        $this->repository->method('findByUuid')->willReturn(null);
        $this->repository->expects(self::never())->method('save');

        $this->expectException(PuzzleNotFoundException::class);

        (new UpdatePuzzleHandler($this->repository))(new UpdatePuzzleCommand($this->puzzleId->value()));
    }

    // --- DeletePuzzle ---

    public function testDeleteRemovesTheStoredFileAndTheRecord(): void
    {
        $puzzle = $this->puzzle();
        $this->repository->method('findByUuid')->willReturn($puzzle);
        $this->storage->expects(self::once())->method('delete')->with('stored.jpg');
        $this->repository->expects(self::once())->method('remove')->with($puzzle);

        (new DeletePuzzleHandler($this->repository, $this->storage))(
            new DeletePuzzleCommand($this->puzzleId->value()),
        );
    }

    public function testDeleteThrowsWhenPuzzleMissing(): void
    {
        $this->repository->method('findByUuid')->willReturn(null);
        $this->storage->expects(self::never())->method('delete');
        $this->repository->expects(self::never())->method('remove');

        $this->expectException(PuzzleNotFoundException::class);

        (new DeletePuzzleHandler($this->repository, $this->storage))(
            new DeletePuzzleCommand($this->puzzleId->value()),
        );
    }

    private function uploadHandler(): UploadPuzzleHandler
    {
        return new UploadPuzzleHandler($this->repository, $this->storage);
    }

    private function puzzle(): Puzzle
    {
        return Puzzle::create(
            uuid: $this->puzzleId,
            originalName: 'obrazek.jpg',
            storedFilename: 'stored.jpg',
            mimeType: 'image/jpeg',
            size: 1024,
            difficulty: 1,
            totalPieces: 100,
            piecesPerFragment: 25,
            piecesX: 10,
            piecesY: 10,
        );
    }

    private function createImage(int $width, int $height): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('The GD extension is required to generate a test image.');
        }

        $path = tempnam(sys_get_temp_dir(), 'puzzle') . '.png';
        $image = imagecreatetruecolor($width, $height);
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }
}
