<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Magento\Swatches\Helper\Data as SwatchesDataHelper;
use Yireo\NextGenImages\Browser\BrowserSupport;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Image\ImageCollector;
use Yireo\NextGenImages\Logger\Debugger;

class CorrectImagesInAjaxResponse
{
    /**
     * @var BrowserSupport
     */
    private $browserSupport;
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var ImageCollector
     */
    private $imageCollector;
    
    /**
     * @var Debugger
     */
    private $debugger;
    
    /**
     * CorrectImagesInAjaxResponse constructor.
     *
     * @param BrowserSupport $browserSupport
     * @param Config $config
     * @param ImageCollector $imageCollector
     * @param Debugger $debugger
     */
    public function __construct(
        BrowserSupport $browserSupport,
        Config $config,
        ImageCollector $imageCollector,
        Debugger $debugger
    ) {
        $this->browserSupport = $browserSupport;
        $this->config = $config;
        $this->debugger = $debugger;
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
    
    /**
     * @param string $url
     * @return bool
     */
    private function isValidUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }
        
        if (!preg_match('/\.(jpg|png)$/', $url)) {
            return false;
        }
        
        return true;
    }
}
