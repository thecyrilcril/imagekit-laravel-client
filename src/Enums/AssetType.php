<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Enums;

/**
 * The `type` of a listed asset, and the `type` filter of a listing. `All`
 * is a filter value only: it returns files and folders together (never file
 * versions), each answering with its own concrete type.
 */
enum AssetType: string
{
    case File = 'file';
    case FileVersion = 'file-version';
    case Folder = 'folder';
    case All = 'all';
}
