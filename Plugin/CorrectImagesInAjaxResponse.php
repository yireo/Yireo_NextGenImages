<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Magento\Swatches\Helper\Data as SwatchesDataHelper;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Image\ImageCollector;

class CorrectImagesInAjaxResponse
{
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var ImageCollector
     */
    private $imageCollector;
    
    /**
     * CorrectImagesInAjaxResponse constructor.
     *
     * @param Config $config
     * @param ImageCollector $imageCollector
     */
    public function __construct(
        Config $config,
        ImageCollector $imageCollector
    ) {
        $this->config = $config;
        $this->imageCollector = $imageCollector;
    }
    
    /**
     * @param SwatchesDataHelper $dataHelper
     * @param array $data
     * @return mixed[]
     */
    public function afterGetProductMediaGallery(SwatchesDataHelper $dataHelper, array $data): array
    {
        if (!$this->config->enabled()) {
            return $data;
        }
        
        return $this->replaceUrls($data);
    }
    
    /**
     * @param mixed[] $data
     * @return mixed[]
     */
    private function replaceUrls(array $data): array
    {
        if (empty($data)) {
            return $data;
        }
        
        $types = ['large', 'medium', 'small'];
        foreach ($types as $type) {
            if (empty($data[$type])) {
                continue;
            }
            
            $images = $this->imageCollector->collect($data[$type]);
            foreach ($images as $image) {
                $data[$type . '_' . $image->getCode()] = $image->getUrl();
            }
        }
        
        if (!isset($data['gallery'])) {
            return $data;
        }
        
        foreach ($data['gallery'] as $galleryIndex => $galleryImages) {
            foreach ($types as $type) {
                if (!isset($data[$type])) {
                    continue;
                }
                
                $images = $this->imageCollector->collect($data[$type]);
                foreach ($images as $image) {
                    $data['gallery'][$galleryIndex][$type . '_' . $image->getCode()] = $image->getUrl();
                }
            }
        }
        
        return $data;
    }
}
