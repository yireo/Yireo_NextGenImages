<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Convertor;

use Yireo\NextGenImages\Image\SourceImage;
use Yireo\NextGenImages\Image\SourceImageFactory;

class DummyConvertor implements ConvertorInterface
{
    /**
     * @var SourceImageFactory
     */
    private $sourceImageFactory;

    /**
     * DummyConvertor constructor.
     * @param SourceImageFactory $sourceImageFactory
     */
    public function __construct(
        SourceImageFactory $sourceImageFactory
    ) {
        $this->sourceImageFactory = $sourceImageFactory;
    }

    /**
     * @param string $imageUrl
     * @return SourceImage
     */
    public function getSourceImage(string $imageUrl): SourceImage
    {
        return $this->sourceImageFactory->create();
    }

    /**
     * @param string $sourceImageUrl
     * @param string $destinationImageUrl
     * @return bool
     */
    public function convert(string $sourceImageUrl, string $destinationImageUrl): bool
    {
        return false;
    }
}
