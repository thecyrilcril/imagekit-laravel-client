<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Enums;

/**
 * The `sort` of a listing: one documented field, ascending or descending.
 * Relevance only means something with a `searchQuery`.
 */
enum SortOrder: string
{
    case NameAscending = 'ASC_NAME';
    case NameDescending = 'DESC_NAME';
    case CreatedAscending = 'ASC_CREATED';
    case CreatedDescending = 'DESC_CREATED';
    case UpdatedAscending = 'ASC_UPDATED';
    case UpdatedDescending = 'DESC_UPDATED';
    case HeightAscending = 'ASC_HEIGHT';
    case HeightDescending = 'DESC_HEIGHT';
    case WidthAscending = 'ASC_WIDTH';
    case WidthDescending = 'DESC_WIDTH';
    case SizeAscending = 'ASC_SIZE';
    case SizeDescending = 'DESC_SIZE';
    case RelevanceAscending = 'ASC_RELEVANCE';
    case RelevanceDescending = 'DESC_RELEVANCE';
}
