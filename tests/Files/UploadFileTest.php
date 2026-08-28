<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Thecyrilcril\ImageKitClient\Enums\ExtensionState;
use Thecyrilcril\ImageKitClient\Enums\ResponseField;
use Thecyrilcril\ImageKitClient\Exceptions\RequestFailed;
use Thecyrilcril\ImageKitClient\Exceptions\TransportError;
use Thecyrilcril\ImageKitClient\Exceptions\UnexpectedResponse;
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;
use Thecyrilcril\ImageKitClient\Files\AITag;
use Thecyrilcril\ImageKitClient\Files\ExtensionStatus;
use Thecyrilcril\ImageKitClient\Files\UploadedFile;
use Thecyrilcril\ImageKitClient\Files\UploadRequest;
use Thecyrilcril\ImageKitClient\Files\UploadSource;
use Thecyrilcril\ImageKitClient\Files\VersionInfo;
use Thecyrilcril\ImageKitClient\Tests\Support\Multipart;

/**
 * The smallest body ImageKit sends back for a successful upload.
 *
 * @return array<string, mixed>
 */
function minimalUploadResponse(): array
{
    return [
        'fileId' => 'file_123',
        'name' => 'photo_abc.jpg',
        'filePath' => '/avatars/photo_abc.jpg',
        'url' => 'https://ik.imagekit.io/test/avatars/photo_abc.jpg',
        'thumbnailUrl' => 'https://ik.imagekit.io/test/tr:n-ik_ml_thumbnail/avatars/photo_abc.jpg',
        'fileType' => 'image',
        'size' => 1024,
    ];
}

it('uploads raw bytes as the multipart file part', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response(minimalUploadResponse())]);

    $uploaded = ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('raw-jpeg-bytes'),
        fileName: 'photo.jpg',
    ));

    expect($uploaded)->toBeInstanceOf(UploadedFile::class)
        ->and($uploaded->fileId)->toBe('file_123')
        ->and($uploaded->name)->toBe('photo_abc.jpg')
        ->and($uploaded->filePath)->toBe('/avatars/photo_abc.jpg')
        ->and($uploaded->url)->toBe('https://ik.imagekit.io/test/avatars/photo_abc.jpg')
        ->and($uploaded->thumbnailUrl)->toBe('https://ik.imagekit.io/test/tr:n-ik_ml_thumbnail/avatars/photo_abc.jpg')
        ->and($uploaded->fileType)->toBe('image')
        ->and($uploaded->size)->toBe(1024);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://upload.imagekit.io/api/v1/files/upload'
        && $request->isMultipart()
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('private_test:'))
        && $request->hasFile('file', 'raw-jpeg-bytes', 'photo.jpg')
        && Multipart::fields($request) === ['fileName' => 'photo.jpg']);
});

it('uploads a base64 data URI as the file text field', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response(minimalUploadResponse())]);

    ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::dataUri('data:image/png;base64,iVBORw0KGgo='),
        fileName: 'photo.png',
    ));

    Http::assertSent(fn (Request $request): bool => $request->isMultipart()
        && ! Multipart::hasFilePart($request, 'file')
        && Multipart::fields($request) === [
            'file' => 'data:image/png;base64,iVBORw0KGgo=',
            'fileName' => 'photo.png',
        ]);
});

it('uploads a public URL as the file text field', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response(minimalUploadResponse())]);

    ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::url('https://example.com/photo.jpg'),
        fileName: 'photo.jpg',
    ));

    Http::assertSent(fn (Request $request): bool => $request->isMultipart()
        && ! Multipart::hasFilePart($request, 'file')
        && Multipart::fields($request) === [
            'file' => 'https://example.com/photo.jpg',
            'fileName' => 'photo.jpg',
        ]);
});

it('serialises each request field to the wire format ImageKit expects', function (array $arguments, array $expectedFields): void {
    Http::fake(['upload.imagekit.io/*' => Http::response(minimalUploadResponse())]);

    ImageKitClient::files()->upload(new UploadRequest(...[
        'source' => UploadSource::bytes('bytes'),
        'fileName' => 'photo.jpg',
        ...$arguments,
    ]));

    Http::assertSent(fn (Request $request): bool => Multipart::fields($request) === ['fileName' => 'photo.jpg'] + $expectedFields);
})->with([
    'useUniqueFileName' => [['useUniqueFileName' => false], ['useUniqueFileName' => 'false']],
    'folder' => [['folder' => '/avatars'], ['folder' => '/avatars']],
    'tags are comma-joined' => [['tags' => ['red', 'summer sale']], ['tags' => 'red,summer sale']],
    'isPrivateFile' => [['isPrivateFile' => true], ['isPrivateFile' => 'true']],
    'isPublished' => [['isPublished' => false], ['isPublished' => 'false']],
    'customCoordinates' => [['customCoordinates' => '10,10,100,100'], ['customCoordinates' => '10,10,100,100']],
    'customMetadata is JSON' => [
        ['customMetadata' => ['brand' => 'Nike', 'sizes' => ['S', 'M'], 'path' => 'a/b', 'name' => 'é']],
        ['customMetadata' => '{"brand":"Nike","sizes":["S","M"],"path":"a/b","name":"é"}'],
    ],
    'empty customMetadata is a JSON object' => [['customMetadata' => []], ['customMetadata' => '{}']],
    'responseFields are comma-joined' => [
        ['responseFields' => [ResponseField::Tags, ResponseField::CustomMetadata]],
        ['responseFields' => 'tags,customMetadata'],
    ],
    'extensions are JSON' => [
        ['extensions' => [['name' => 'remove-bg', 'options' => ['add_shadow' => true]], ['name' => 'google-auto-tagging', 'maxTags' => 5, 'minConfidence' => 95]]],
        ['extensions' => '[{"name":"remove-bg","options":{"add_shadow":true}},{"name":"google-auto-tagging","maxTags":5,"minConfidence":95}]'],
    ],
    'webhookUrl' => [['webhookUrl' => 'https://example.com/hook'], ['webhookUrl' => 'https://example.com/hook']],
    'overwriteFile' => [['overwriteFile' => false], ['overwriteFile' => 'false']],
    'overwriteAITags' => [['overwriteAITags' => true], ['overwriteAITags' => 'true']],
    'overwriteTags' => [['overwriteTags' => false], ['overwriteTags' => 'false']],
    'overwriteCustomMetadata' => [['overwriteCustomMetadata' => true], ['overwriteCustomMetadata' => 'true']],
    'transformation is JSON' => [
        ['transformation' => ['pre' => 'rt-90', 'post' => [['type' => 'transformation', 'value' => 'bg-red']]]],
        ['transformation' => '{"pre":"rt-90","post":[{"type":"transformation","value":"bg-red"}]}'],
    ],
    'checks' => [['checks' => "'file.size' < '1MB'"], ['checks' => "'file.size' < '1MB'"]],
    'description' => [['description' => 'A red dress'], ['description' => 'A red dress']],
]);

it('sends false as the text "false", never as an empty string', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response(minimalUploadResponse())]);

    ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('bytes'),
        fileName: 'photo.jpg',
        useUniqueFileName: false,
        isPrivateFile: false,
        isPublished: false,
        overwriteFile: false,
        overwriteAITags: false,
        overwriteTags: false,
        overwriteCustomMetadata: false,
    ));

    Http::assertSent(fn (Request $request): bool => Multipart::fields($request) === [
        'fileName' => 'photo.jpg',
        'useUniqueFileName' => 'false',
        'isPrivateFile' => 'false',
        'isPublished' => 'false',
        'overwriteFile' => 'false',
        'overwriteAITags' => 'false',
        'overwriteTags' => 'false',
        'overwriteCustomMetadata' => 'false',
    ] && ! str_contains($request->body(), "\r\n\r\n\r\n"));
});

it('sends true as the text "true"', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response(minimalUploadResponse())]);

    ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('bytes'),
        fileName: 'photo.jpg',
        useUniqueFileName: true,
        isPrivateFile: true,
        isPublished: true,
        overwriteFile: true,
        overwriteAITags: true,
        overwriteTags: true,
        overwriteCustomMetadata: true,
    ));

    Http::assertSent(fn (Request $request): bool => Multipart::fields($request) === [
        'fileName' => 'photo.jpg',
        'useUniqueFileName' => 'true',
        'isPrivateFile' => 'true',
        'isPublished' => 'true',
        'overwriteFile' => 'true',
        'overwriteAITags' => 'true',
        'overwriteTags' => 'true',
        'overwriteCustomMetadata' => 'true',
    ]);
});

it('leaves every optional field off the wire when it is not given', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response(minimalUploadResponse())]);

    ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('bytes'),
        fileName: 'photo.jpg',
    ));

    Http::assertSent(fn (Request $request): bool => Multipart::fields($request) === ['fileName' => 'photo.jpg']);
});

it('maps every documented response field, including extension status and the video group', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response([
        'fileId' => '598821f949c0a938d57563bd',
        'name' => 'clip_Xy1.mp4',
        'filePath' => '/videos/clip_Xy1.mp4',
        'url' => 'https://ik.imagekit.io/test/videos/clip_Xy1.mp4',
        'thumbnailUrl' => 'https://ik.imagekit.io/test/videos/clip_Xy1.mp4/ik-thumbnail.jpg',
        'fileType' => 'non-image',
        'size' => 4_194_304,
        'width' => 1920,
        'height' => 1080,
        'tags' => ['promo', 'summer'],
        'AITags' => [
            ['name' => 'Beach', 'confidence' => 97.5, 'source' => 'google-auto-tagging'],
            ['name' => 'Sea', 'confidence' => 88, 'source' => 'aws-auto-tagging'],
        ],
        'customCoordinates' => '10,10,200,200',
        'customMetadata' => ['brand' => 'Nike', 'sizes' => ['S', 'M']],
        'description' => 'A day at the beach',
        'isPrivateFile' => true,
        'isPublished' => false,
        'embeddedMetadata' => ['DateCreated' => '2024-05-01T10:00:00.000Z'],
        'metadata' => ['duration' => 12, 'format' => 'mp4'],
        'selectedFieldsSchema' => ['brand' => ['type' => 'Text']],
        'versionInfo' => ['id' => '598821f949c0a938d57563bd', 'name' => 'Version 1'],
        'extensionStatus' => [
            'ai-auto-description' => 'success',
            'ai-tasks' => 'pending',
            'aws-auto-tagging' => 'failed',
            'google-auto-tagging' => 'success',
            'remove-bg' => 'pending',
        ],
        'duration' => 12,
        'bitRate' => 2_500_000,
        'audioCodec' => 'aac',
        'videoCodec' => 'h264',
        'aFieldAddedNextYear' => 'ignored',
    ])]);

    $uploaded = ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('bytes'),
        fileName: 'clip.mp4',
    ));

    expect($uploaded->fileId)->toBe('598821f949c0a938d57563bd')
        ->and($uploaded->name)->toBe('clip_Xy1.mp4')
        ->and($uploaded->filePath)->toBe('/videos/clip_Xy1.mp4')
        ->and($uploaded->url)->toBe('https://ik.imagekit.io/test/videos/clip_Xy1.mp4')
        ->and($uploaded->thumbnailUrl)->toBe('https://ik.imagekit.io/test/videos/clip_Xy1.mp4/ik-thumbnail.jpg')
        ->and($uploaded->fileType)->toBe('non-image')
        ->and($uploaded->size)->toBe(4_194_304)
        ->and($uploaded->width)->toBe(1920)
        ->and($uploaded->height)->toBe(1080)
        ->and($uploaded->tags)->toBe(['promo', 'summer'])
        ->and($uploaded->aiTags)->toHaveCount(2)
        ->and($uploaded->aiTags[0])->toBeInstanceOf(AITag::class)
        ->and($uploaded->aiTags[0]->name)->toBe('Beach')
        ->and($uploaded->aiTags[0]->confidence)->toBe(97.5)
        ->and($uploaded->aiTags[0]->source)->toBe('google-auto-tagging')
        ->and($uploaded->aiTags[1]->confidence)->toBe(88.0)
        ->and($uploaded->customCoordinates)->toBe('10,10,200,200')
        ->and($uploaded->customMetadata)->toBe(['brand' => 'Nike', 'sizes' => ['S', 'M']])
        ->and($uploaded->description)->toBe('A day at the beach')
        ->and($uploaded->isPrivateFile)->toBeTrue()
        ->and($uploaded->isPublished)->toBeFalse()
        ->and($uploaded->embeddedMetadata)->toBe(['DateCreated' => '2024-05-01T10:00:00.000Z'])
        ->and($uploaded->metadata)->toBe(['duration' => 12, 'format' => 'mp4'])
        ->and($uploaded->selectedFieldsSchema)->toBe(['brand' => ['type' => 'Text']])
        ->and($uploaded->versionInfo)->toBeInstanceOf(VersionInfo::class)
        ->and($uploaded->versionInfo?->id)->toBe('598821f949c0a938d57563bd')
        ->and($uploaded->versionInfo?->name)->toBe('Version 1')
        ->and($uploaded->extensionStatus)->toBeInstanceOf(ExtensionStatus::class)
        ->and($uploaded->extensionStatus?->aiAutoDescription)->toBe(ExtensionState::Success)
        ->and($uploaded->extensionStatus?->aiTasks)->toBe(ExtensionState::Pending)
        ->and($uploaded->extensionStatus?->awsAutoTagging)->toBe(ExtensionState::Failed)
        ->and($uploaded->extensionStatus?->googleAutoTagging)->toBe(ExtensionState::Success)
        ->and($uploaded->extensionStatus?->removeBg)->toBe(ExtensionState::Pending)
        ->and($uploaded->duration)->toBe(12)
        ->and($uploaded->bitRate)->toBe(2_500_000)
        ->and($uploaded->audioCodec)->toBe('aac')
        ->and($uploaded->videoCodec)->toBe('h264');
});

it('reads a minimal response with nulls and empty lists for what ImageKit left out', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response(minimalUploadResponse() + [
        'tags' => null,
        'AITags' => null,
        'extensionStatus' => ['remove-bg' => 'a-state-added-next-year'],
    ])]);

    $uploaded = ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('bytes'),
        fileName: 'photo.jpg',
    ));

    expect($uploaded->width)->toBeNull()
        ->and($uploaded->height)->toBeNull()
        ->and($uploaded->tags)->toBe([])
        ->and($uploaded->aiTags)->toBe([])
        ->and($uploaded->customCoordinates)->toBeNull()
        ->and($uploaded->customMetadata)->toBe([])
        ->and($uploaded->description)->toBeNull()
        ->and($uploaded->isPrivateFile)->toBeNull()
        ->and($uploaded->isPublished)->toBeNull()
        ->and($uploaded->embeddedMetadata)->toBe([])
        ->and($uploaded->metadata)->toBe([])
        ->and($uploaded->selectedFieldsSchema)->toBe([])
        ->and($uploaded->versionInfo)->toBeNull()
        ->and($uploaded->extensionStatus)->toBeInstanceOf(ExtensionStatus::class)
        ->and($uploaded->extensionStatus?->removeBg)->toBeNull()
        ->and($uploaded->extensionStatus?->googleAutoTagging)->toBeNull()
        ->and($uploaded->duration)->toBeNull()
        ->and($uploaded->bitRate)->toBeNull()
        ->and($uploaded->audioCodec)->toBeNull()
        ->and($uploaded->videoCodec)->toBeNull();
});

it('leaves extension status null when no extension ran', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response(minimalUploadResponse())]);

    $uploaded = ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('bytes'),
        fileName: 'photo.jpg',
    ));

    expect($uploaded->extensionStatus)->toBeNull();
});

it('throws request-failed with ImageKit\'s message when the upload is rejected', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response([
        'message' => 'Your account cannot be authenticated.',
        'help' => 'For support kindly contact us at support@imagekit.io .',
    ], 403)]);

    expect(fn () => ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('bytes'),
        fileName: 'photo.jpg',
    )))->toThrow(function (RequestFailed $exception): void {
        expect($exception->status)->toBe(403)
            ->and($exception->imageKitMessage)->toBe('Your account cannot be authenticated.')
            ->and($exception->help)->toBe('For support kindly contact us at support@imagekit.io .')
            ->and($exception->getMessage())->toBe('ImageKit responded with HTTP 403: Your account cannot be authenticated.');
    });
});

it('throws transport when the upload host cannot be reached', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::failedConnection()]);

    expect(fn () => ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('bytes'),
        fileName: 'photo.jpg',
    )))->toThrow(function (TransportError $exception): void {
        expect($exception->getPrevious())->toBeInstanceOf(ConnectionException::class)
            ->and($exception->getMessage())->toContain('Could not resolve host: upload.imagekit.io');
    });
});

it('throws unexpected-response when a 2xx body is not a JSON object', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response('<html>OK</html>', 200)]);

    expect(fn () => ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('bytes'),
        fileName: 'photo.jpg',
    )))->toThrow(UnexpectedResponse::class, 'not a JSON object (UploadedFile expected)');
});

it('throws unexpected-response when a 2xx body lacks a field every upload carries', function (string $field): void {
    $body = minimalUploadResponse();
    unset($body[$field]);
    Http::fake(['upload.imagekit.io/*' => Http::response($body)]);

    expect(fn () => ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('bytes'),
        fileName: 'photo.jpg',
    )))->toThrow(UnexpectedResponse::class, sprintf('"%s"', $field));
})->with(['fileId', 'size']);

it('throws unexpected-response when a 2xx body has the wrong type for a field every upload carries', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response(['size' => '1024'] + minimalUploadResponse())]);

    expect(fn () => ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('bytes'),
        fileName: 'photo.jpg',
    )))->toThrow(UnexpectedResponse::class, 'UploadedFile field "size" is not an integer');
});
