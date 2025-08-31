<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

use GdImage;

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
        return 'image/' . $this->getCode();
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        if (preg_match('/.gif$/i', $this->getPath())) {
            return 'gif';
        }

        if (preg_match('/.(jpeg|jpg)$/i', $this->getPath())) {
            return 'jpeg';
        }

        if (preg_match('/.webp$/i', $this->getPath())) {
            return 'webp';
        }

        return 'png';
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUrl();
    }

    public function getGdImage(): ?GdImage
    {
        if (false === file_exists($this->getPath())) {
            return null;
        }

        if (str_ends_with($this->getPath(), '.jpeg') || str_ends_with($this->getPath(), '.jpg')) {
            return imagecreatefromjpeg($this->getPath());
        }

        if (str_ends_with($this->getPath(), '.png')) {
            return imagecreatefrompng($this->getPath());
        }

        return null;
    }
}
