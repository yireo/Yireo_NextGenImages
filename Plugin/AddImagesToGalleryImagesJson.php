<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Framework\Serialize\SerializerInterface;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\ImageCollector;

class AddImagesToGalleryImagesJson
{
    /**
     * @var SerializerInterface
     */
    private $serializer;
    
    /**
     * @var ImageCollector
     */
    private $imageCollector;
    /**
     * @var Config
     */
    private $config;

    /**
     * AddImagesToConfigurableJsonConfig constructor.
     * @param SerializerInterface $serializer
     * @param ImageCollector $imageCollector
     * @param Config $config
     */
    public function __construct(
        SerializerInterface $serializer,
        ImageCollector $imageCollector,
        Config $config
    ) {
        $this->serializer = $serializer;
        $this->imageCollector = $imageCollector;
        $this->config = $config;
    }
    
    /**
     * @param Gallery $subject
     * @param string $galleryImagesJson
     * @return string
     */
    public function afterGetGalleryImagesJson(Gallery $subject, string $galleryImagesJson): string
    {
        if (! $this->config->enabled()) {
            return $galleryImagesJson;
        }

        $jsonData = $this->serializer->unserialize($galleryImagesJson);
        $jsonData = $this->appendImages($jsonData);
        return $this->serializer->serialize($jsonData);
    }
    
    /**
     * @param array $images
     * @return array
     */
    private function appendImages(array $images): array
    {
        foreach ($images as $id => $imageData) {
            foreach (['thumb', 'img', 'full'] as $imageType) {
                if (empty($imageData[$imageType])) {
                    continue;
                }
                
                $newImages = $this->imageCollector->collect($imageData[$imageType]);
                foreach ($newImages as $newImage) {
                    $imageData[$imageType . '_' . $newImage->getCode()] = $newImage->getUrl();
                }
            }
            $images[$id] = $imageData;
        }
        
        return $images;
    }
}
