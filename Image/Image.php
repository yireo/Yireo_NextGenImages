<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

use Yireo\NextGenImages\Util\UrlConvertor;

class Image
{
    /**
     * @var UrlConvertor
     */
    private $urlConvertor;

    /**
     * @var string
     */
    private $path;

    /**
     * @param UrlConvertor $urlConvertor
     * @param string $path
     */
    public function __construct(
        UrlConvertor $urlConvertor,
        string $path
    ) {
        $this->urlConvertor = $urlConvertor;
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->urlConvertor->getUrlFromFilename($this->getPath());
    }

    /**
     * @return string
     */
    public function getMimetype(): string
    {
        if (preg_match('/.gif$/i', $this->getPath())) {
            return 'image/gif';
        }

        if (preg_match('/.(jpeg|jpg)$/i', $this->getPath())) {
            return 'image/jpeg';
        }

        if (preg_match('/.webp$/i', $this->getPath())) {
            return 'image/webp';
        }

        return 'image/png';
    }
}
