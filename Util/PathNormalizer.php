<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

class PathNormalizer
{
    /**
     * @param string $path
     * @return string
     */
    public static function normalize(string $path): string
    {
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        return '/'.implode('/', $segments);
    }
}
