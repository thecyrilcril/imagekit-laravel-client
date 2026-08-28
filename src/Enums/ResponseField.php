<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Enums;

/**
 * The optional fields an upload can ask ImageKit to include in its
 * response. Without a request for them, the result reads them as null, or
 * as an empty list or map.
 */
enum ResponseField: string
{
    case Tags = 'tags';
    case CustomCoordinates = 'customCoordinates';
    case IsPrivateFile = 'isPrivateFile';
    case EmbeddedMetadata = 'embeddedMetadata';
    case IsPublished = 'isPublished';
    case CustomMetadata = 'customMetadata';
    case Metadata = 'metadata';
    case SelectedFieldsSchema = 'selectedFieldsSchema';
}
