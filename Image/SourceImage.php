<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

class SourceImage
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $mimeType;

    /**
     * SourceImage constructor.
     * @param string $url
     * @param string $mimeType
     */
    public function __construct(
        string $url,
        string $mimeType
    ) {
        $this->url = $url;
        $this->mimeType = $mimeType;
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
    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}
