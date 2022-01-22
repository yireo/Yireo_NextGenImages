<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

class Image
{
    /**
     * @var string
     */
    private $path;

    /**
     * SourceImage constructor.
     * @param string $path
     */
    public function __construct(
        string $path
    ) {
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
        return ''; // @todo
    }

    public function exists(): bool
    {
        return false; // @todo
    }
}
