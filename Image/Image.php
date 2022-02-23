<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

class Image
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $url;

    /**
     * @param string $path
     * @param string $url
     */
    public function __construct(
        string $path,
        string $url
    ) {
        $this->path = $path;
        $this->url = $url;
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
        return $this->url;
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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUrl();
    }
}
