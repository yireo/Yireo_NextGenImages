<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Config;

class CacheDirectoryValidator
{
    public const DEFAULT_CACHE_DIRECTORY = 'media/nextgenimages';

    /**
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return (bool)preg_match('#^media(/[a-zA-Z0-9_\-]+)+/?$#D', $value);
    }
}
