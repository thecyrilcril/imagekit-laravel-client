<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Contracts;

/**
 * The files area of the ImageKit API: upload, delete, list and search.
 *
 * Operations land one at a time, each with its own typed request and result;
 * this interface is the stable seam they attach to.
 */
interface Files {}
