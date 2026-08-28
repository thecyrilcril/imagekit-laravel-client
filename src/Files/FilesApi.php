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
use Thecyrilcril\ImageKitClient\Enums\UploadSourceKind;
use Thecyrilcril\ImageKitClient\Exceptions\UnexpectedResponse;
use Thecyrilcril\ImageKitClient\Http\Connection;
use Thecyrilcril\ImageKitClient\Http\Payload;

final readonly class FilesApi implements Files
{
    public function __construct(private Connection $connection) {}

    #[Override]
    public function upload(UploadRequest $request): UploadedFile
    {
        $response = $this->connection->upload(
            fn (PendingRequest $pending): Response => $request->source->kind === UploadSourceKind::Bytes
                ? $pending->attach('file', $request->source->value, $request->fileName)
                    ->post('/files/upload', self::uploadFields($request))
                : $pending->asMultipart()
                    ->post('/files/upload', ['file' => $request->source->value] + self::uploadFields($request)),
        );

        $body = $response->json();

        if (! is_array($body)) {
            throw UnexpectedResponse::notAnObject('UploadedFile');
        }

        /** @var array<string, mixed> $body */
        return UploadedFile::fromPayload(new Payload($body, 'UploadedFile'));
    }

    /**
     * The text fields of the upload other than `file`, in ImageKit's wire
     * format: booleans as the words "true"/"false" (a raw false would leave
     * as an empty part), tags and response fields comma-joined, objects as
     * JSON. A null field is not sent at all.
     *
     * @return array<string, string>
     */
    private static function uploadFields(UploadRequest $request): array
    {
        $values = [
            'fileName' => $request->fileName,
            'useUniqueFileName' => self::booleanWord($request->useUniqueFileName),
            'folder' => $request->folder,
            'tags' => $request->tags === null ? null : implode(',', $request->tags),
            'isPrivateFile' => self::booleanWord($request->isPrivateFile),
            'isPublished' => self::booleanWord($request->isPublished),
            'customCoordinates' => $request->customCoordinates,
            'customMetadata' => $request->customMetadata === null ? null : self::jsonObject($request->customMetadata),
            'responseFields' => $request->responseFields === null ? null : implode(',', array_map(
                static fn (ResponseField $field): string => $field->value,
                $request->responseFields,
            )),
            'extensions' => $request->extensions === null ? null : self::json($request->extensions),
            'webhookUrl' => $request->webhookUrl,
            'overwriteFile' => self::booleanWord($request->overwriteFile),
            'overwriteAITags' => self::booleanWord($request->overwriteAITags),
            'overwriteTags' => self::booleanWord($request->overwriteTags),
            'overwriteCustomMetadata' => self::booleanWord($request->overwriteCustomMetadata),
            'transformation' => $request->transformation === null ? null : self::jsonObject($request->transformation),
            'checks' => $request->checks,
            'description' => $request->description,
        ];

        return array_filter($values, static fn (?string $value): bool => $value !== null);
    }

    private static function booleanWord(?bool $value): ?string
    {
        return match ($value) {
            null => null,
            true => 'true',
            false => 'false',
        };
    }

    /**
     * A JSON object even when the map is empty: `{}` where a plain
     * json_encode would give `[]`, which ImageKit rejects for an object.
     *
     * @param  array<string, mixed>  $map
     */
    private static function jsonObject(array $map): string
    {
        return self::json((object) $map);
    }

    /**
     * @param  object|array<array-key, mixed>  $value
     */
    private static function json(object|array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

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
