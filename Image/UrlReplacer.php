<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

use Exception as ExceptionAlias;
use Yireo\NextGenImages\Convertor\ConvertorInterface;

class UrlReplacer
{
    /**
     * @var ConvertorInterface
     */
    private $convertor;

    /**
     * ReplaceTags constructor.
     *
     * @param ConvertorInterface $convertor
     */
    public function __construct(
        ConvertorInterface $convertor
    ) {
        $this->convertor = $convertor;
    }

    /**
     * @param string $imageUrl
     * @return string
     * @throws ExceptionAlias
     */
    public function getNewImageUrlFromImageUrl(string $imageUrl): string
    {
        $alternateSourceImage = $this->convertor->getSourceImage($imageUrl);
        return $alternateSourceImage->getUrl();
    }
}
