<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Stub;

use Yireo\NextGenImages\Util\HtmlReplacer;

class WrappingHtmlReplacer extends HtmlReplacer
{
    public int $calls = 0;

    public function __construct()
    {
    }

    public function replace(string $html): string
    {
        $this->calls++;
        return (string)preg_replace('/(<img[^>]*>)/i', '<picture>$1</picture>', $html);
    }
}
