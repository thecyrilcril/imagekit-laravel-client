<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Enums;

/**
 * The three forms ImageKit accepts a file in. Bytes travel as the multipart
 * file part; a data URI or a public URL travels as the `file` text field and
 * ImageKit decodes or fetches it.
 */
enum UploadSourceKind
{
    case Bytes;
    case DataUri;
    case Url;
}
