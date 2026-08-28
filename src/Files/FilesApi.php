<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Override;
use Thecyrilcril\ImageKitClient\Contracts\Files;
use Thecyrilcril\ImageKitClient\Http\Connection;

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
}
