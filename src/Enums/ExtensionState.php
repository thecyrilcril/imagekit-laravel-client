<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Enums;

/**
 * Where one upload extension stands when the response is written. Pending
 * extensions finish later and report through `webhookUrl`.
 */
enum ExtensionState: string
{
    case Success = 'success';
    case Pending = 'pending';
    case Failed = 'failed';
}
