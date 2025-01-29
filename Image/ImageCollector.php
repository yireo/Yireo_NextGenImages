<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

use Magento\Framework\View\Asset\File\NotFoundException;
use Yireo\NextGenImages\Convertor\ConvertorListing;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Logger\Debugger;

class ImageCollector
{
    /**
     * @var ImageFactory
     */
    private $imageFactory;
    
    /**
     * @var ConvertorListing
     */
    private $convertorListing;
    
    /**
     * @var Debugger
     */
    private $debugger;
    
    /**
     * @param ImageFactory $imageFactory
     * @param ConvertorListing $convertorListing
     * @param Debugger $debugger
     */
    public function __construct(
        ImageFactory $imageFactory,
        ConvertorListing $convertorListing,
        Debugger $debugger
    ) {
        $this->imageFactory = $imageFactory;
        $this->convertorListing = $convertorListing;
        $this->debugger = $debugger;
    }
    
    /**
     * @param string|array $imageUrl
     * @return Image[]
     */
    public function collect($imageUrl): array
    {
        try {
            $image = $this->imageFactory->createFromUrl($imageUrl);
        } catch (NotFoundException $exception) {
            return [];
        }
        
        $images = [];
        foreach ($this->convertorListing->getConvertors() as $convertor) {
            try {
                $images[] = $convertor->convertImage($image);
            } catch (ConvertorException $convertorException) {
                $this->debugger->debug($convertorException->getMessage(), ['path' => $image->getPath()]);
                continue;
            }
        }
        
        return $images;
    }
}
