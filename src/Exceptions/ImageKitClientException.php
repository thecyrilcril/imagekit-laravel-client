<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

use RuntimeException;

/**
 * Base of every exception this package throws. Catch this to handle "anything
 * the Client can fail with"; catch a subclass to tell the failures apart.
 */
abstract class ImageKitClientException extends RuntimeException {}
