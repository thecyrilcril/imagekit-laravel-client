<?php

declare(strict_types=1);

use Thecyrilcril\ImageKitClient\Enums\AssetType;
use Thecyrilcril\ImageKitClient\Enums\FileType;
use Thecyrilcril\ImageKitClient\Enums\SortOrder;
use Thecyrilcril\ImageKitClient\Exceptions\ImageKitClientException;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidListRequest;
use Thecyrilcril\ImageKitClient\Files\ListRequest;

it('rejects a limit outside 1–1000', function (int $limit): void {
    expect(fn () => new ListRequest(limit: $limit))
        ->toThrow(InvalidListRequest::class, sprintf('A ListRequest [limit] must be between 1 and 1000; %d given.', $limit));
})->with(['zero' => [0], 'negative' => [-5], 'above the maximum' => [1001]]);

it('rejects a negative skip', function (): void {
    expect(fn () => new ListRequest(skip: -1))
        ->toThrow(function (InvalidListRequest $exception): void {
            expect($exception)->toBeInstanceOf(ImageKitClientException::class)
                ->and($exception->getMessage())->toBe('A ListRequest [skip] cannot be negative; -1 given.');
        });
});

it('accepts the boundaries', function (): void {
    expect(new ListRequest(limit: 1, skip: 0))->toBeInstanceOf(ListRequest::class)
        ->and(new ListRequest(limit: ListRequest::MAX_LIMIT))->toBeInstanceOf(ListRequest::class);
});

it('copies itself for another page, keeping every other filter', function (): void {
    $request = new ListRequest(
        limit: 10,
        skip: 20,
        path: '/kitwire',
        type: AssetType::Folder,
        fileType: FileType::NonImage,
        sort: SortOrder::SizeDescending,
        tags: ['a'],
        name: 'x',
        searchQuery: 'size > "2mb"',
    );

    $paged = $request->withPage(500, 0);

    expect($paged)->not->toBe($request)
        ->and($request->limit)->toBe(10)
        ->and($request->skip)->toBe(20)
        ->and($paged->toQuery())->toBe([
            'limit' => 500,
            'skip' => 0,
            'path' => '/kitwire',
            'type' => 'folder',
            'fileType' => 'non-image',
            'sort' => 'DESC_SIZE',
            'tags' => 'a',
            'name' => 'x',
            'searchQuery' => 'size > "2mb"',
        ]);
});

it('sends nothing for an empty string or an empty tag list, so ImageKit defaults apply', function (): void {
    $request = new ListRequest(path: '', tags: [], name: '', searchQuery: '', limit: 5);

    expect($request->toQuery())->toBe(['limit' => 5]);
});
