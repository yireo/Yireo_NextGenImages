<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Framework\Serialize\SerializerInterface;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\ImageCollector;
use Yireo\NextGenImages\Logger\Debugger;
use Yireo\Webp2\Convertor\Convertor;

class AddImagesToConfigurableJsonConfig
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
     * AddImagesToConfigurableJsonConfig constructor.
     * @param SerializerInterface $serializer
     * @param ImageCollector $imageCollector
     */
    public function __construct(
        SerializerInterface $serializer,
        ImageCollector $imageCollector
    ) {
        $this->serializer = $serializer;
        $this->imageCollector = $imageCollector;
    }
    
    /**
     * @param Configurable $subject
     * @param string $jsonConfig
     * @return string
     */
    public function afterGetJsonConfig(Configurable $subject, string $jsonConfig): string
    {
        $jsonData = $this->serializer->unserialize($jsonConfig);
        
        if (isset($jsonData['images'])) {
            $jsonData['images'] = $this->appendImages($jsonData['images']);
        }
        
        $jsonConfig = $this->serializer->serialize($jsonData);
        return $jsonConfig;
    }
    
    /**
     * @param array $images
     * @return array
     */
    private function appendImages(array $images): array
    {
        foreach ($images as $id => $imagesData) {
            foreach ($imagesData as $imageDataIndex => $imageData) {
                foreach (['thumb', 'img', 'full'] as $imageType) {
                    if (empty($imageData[$imageType])) {
                        continue;
                    }
                    
                    $newImages = $this->imageCollector->collect($imageData[$imageType]);
                    foreach ($newImages as $newImage) {
                        $imageData[$imageType . '_' . $newImage->getCode()] = $newImage->getUrl();
                    }
                }
                
                $imagesData[$imageDataIndex] = $imageData;
            }
            
            $images[$id] = $imagesData;
        }
        
        return $images;
    }
}
