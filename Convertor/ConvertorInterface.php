<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Convertor;

use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\SourceImage;

interface ConvertorInterface
{
    /**
     * @param string $imageUrl
     * @return SourceImage
     * @throws ConvertorException
     */
    public function getSourceImage(string $imageUrl): SourceImage;

    /**
     * @param string $sourceImageUri
     * @param string|null $destinationImageUri
     * @return bool
     * @throws ConvertorException
     */
    public function convert(string $sourceImageUri, ?string $destinationImageUri = null): bool;
}
