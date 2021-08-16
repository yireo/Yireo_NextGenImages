<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Util;

class ImageGenerator
{
    public function getWhiteOnePixelImage(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAABlBMVEUAAAD///+l2Z/dAAAADUlE
QVQIHQECAP3/AIAAggCBcIj5GQAAAABJRU5ErkJggg==');
    }

    public function getBlackOnePixelImage(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAADUlEQVQIHQECAP3/AAAAAgABzePR
KwAAAABJRU5ErkJggg==');
    }
}
