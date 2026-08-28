<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files;

use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\LazyCollection;
use Override;
use Thecyrilcril\ImageKitClient\Contracts\Files;
use Thecyrilcril\ImageKitClient\Enums\AssetType;
use Thecyrilcril\ImageKitClient\Enums\ResponseField;
use Thecyrilcril\ImageKitClient\Exceptions\UnexpectedResponse;
use Thecyrilcril\ImageKitClient\Http\Connection;
use Thecyrilcril\ImageKitClient\Http\Payload;

final readonly class FilesApi implements Files
{
    public function __construct(private Connection $connection) {}

    #[Override]
    public function delete(string $fileId): void
    {
        $this->connection->api(
            fn (PendingRequest $request): Response => $request->delete('/files/'.rawurlencode($fileId)),
        );
    }

    #[Override]
    public function list(ListRequest $request): FileListing
    {
        $response = $this->connection->api(
            fn (PendingRequest $pending): Response => $pending->get('/files', $request->toQuery()),
        );

        $body = $response->json();

        if (! is_array($body) || ! array_is_list($body)) {
            throw UnexpectedResponse::notAList();
        }

        return new FileListing(array_map($this->hydrateAsset(...), array_keys($body), $body));
    }

    #[Override]
    public function lazy(ListRequest $request): LazyCollection
    {
        return new LazyCollection(function () use ($request): Generator {
            $pageSize = $request->limit ?? ListRequest::DEFAULT_PAGE_SIZE;
            $skip = $request->skip ?? 0;

            do {
                $page = $this->list($request->withPage($pageSize, $skip));

                foreach ($page as $item) {
                    yield $item;
                }

                $skip += $pageSize;
            } while ($page->count() === $pageSize);
        });
    }

    /**
     * One decoded listing entry into the typed object its `type` names.
     */
    private function hydrateAsset(int $index, mixed $item): File|Folder
    {
        if (! is_array($item)) {
            throw UnexpectedResponse::itemNotAnObject($index);
        }

        /** @var array<string, mixed> $item */
        $type = is_string($item['type'] ?? null) ? AssetType::tryFrom($item['type']) : null;

        return match ($type) {
            AssetType::File, AssetType::FileVersion => File::fromPayload(new Payload($item, 'File'), $type),
            AssetType::Folder => Folder::fromPayload(new Payload($item, 'Folder')),
            default => throw UnexpectedResponse::unknownAssetType($item['type'] ?? null),
        };
    }
}
