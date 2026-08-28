<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Urls;

use Thecyrilcril\ImageKitClient\Exceptions\InvalidTransformation;

/**
 * The friendly-alias to ImageKit short-code map, one row per documented
 * transformation. A short code from the map is accepted as a key too. Any
 * other key throws, so a typo fails loudly; syntax the map does not cover
 * (layers, conditionals, a brand-new code) goes through `raw`, verbatim.
 *
 * Source: docs/research/2026-08-28-imagekit-api-vs-sdk.md section 3 in
 * thecyrilcril/laravel-imagekit, which cites the ImageKit docs page by page.
 */
final class TransformationCodes
{
    public const string RAW = 'raw';

    /**
     * @var array<string, string>
     */
    public const array ALIASES = [
        // Resize and crop
        'width' => 'w',
        'height' => 'h',
        'aspectRatio' => 'ar',
        'crop' => 'c',
        'cropMode' => 'cm',
        'focus' => 'fo',
        'zoom' => 'z',
        'x' => 'x',
        'y' => 'y',
        'xCenter' => 'xc',
        'yCenter' => 'yc',
        'dpr' => 'dpr',

        // Optimisation
        'quality' => 'q',
        'format' => 'f',
        'lossless' => 'lo',
        'progressive' => 'pr',
        'metadata' => 'md',
        'colorProfile' => 'cp',
        'density' => 'dn',
        'original' => 'orig',
        'defaultImage' => 'di',
        'named' => 'n',

        // Effects and enhancements
        'radius' => 'r',
        'background' => 'bg',
        'border' => 'b',
        'rotation' => 'rt',
        'flip' => 'fl',
        'blur' => 'bl',
        'trim' => 't',
        'opacity' => 'o',
        'colorReplace' => 'cr',
        'contrastStretch' => 'e-contrast',
        'sharpen' => 'e-sharpen',
        'unsharpMask' => 'e-usm',
        'grayscale' => 'e-grayscale',
        'shadow' => 'e-shadow',
        'gradient' => 'e-gradient',
        'colorize' => 'e-colorize',
        'distort' => 'e-distort',

        // AI
        'aiRemoveBackground' => 'e-bgremove',
        'aiRemoveBackgroundExternal' => 'e-removedotbg',
        'aiChangeBackground' => 'e-changebg',
        'aiEdit' => 'e-edit',
        'aiDropShadow' => 'e-dropshadow',
        'aiRetouch' => 'e-retouch',
        'aiUpscale' => 'e-upscale',
        'aiVariation' => 'e-genvar',

        // Documents and provenance
        'page' => 'pg',
        'contentCredentials' => 'c2pa',

        // Video
        'startOffset' => 'so',
        'endOffset' => 'eo',
        'duration' => 'du',
        'videoCodec' => 'vc',
        'audioCodec' => 'ac',
        'streamingResolutions' => 'sr',

        // Names imagekit/imagekit SDK 4.0.2 accepted, kept so Presets written
        // against it keep rendering byte-identical URLs.
        'rotate' => 'rt',
        'effectSharpen' => 'e-sharpen',
        'effectUSM' => 'e-usm',
        'effectContrast' => 'e-contrast',
        'effectGray' => 'e-grayscale',
        'effectShadow' => 'e-shadow',
        'effectGradient' => 'e-gradient',
    ];

    /**
     * @throws InvalidTransformation
     */
    public static function resolve(string $key): string
    {
        if (isset(self::ALIASES[$key])) {
            return self::ALIASES[$key];
        }

        if (in_array($key, self::ALIASES, true)) {
            return $key;
        }

        throw InvalidTransformation::unknownKey($key);
    }

    /**
     * Effects (`e-…`) take their parameters after a second dash and are
     * documented bare when they have none, so `true` renders the code alone.
     * Every other code spells its boolean out (`lo-true`, `md-false`).
     */
    public static function isBareWhenTrue(string $code): bool
    {
        return str_starts_with($code, 'e-');
    }
}
