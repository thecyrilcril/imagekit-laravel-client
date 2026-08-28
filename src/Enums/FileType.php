<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Enums;

/**
 * The `fileType` filter of a listing. ImageKit sorts every file into
 * `image` or `non-image` (JS, CSS, video, documents); `All` asks for both.
 */
enum FileType: string
{
    case All = 'all';
    case Image = 'image';
    case NonImage = 'non-image';
}
