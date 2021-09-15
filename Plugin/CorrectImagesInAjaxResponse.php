<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Magento\Swatches\Helper\Data;
use Yireo\NextGenImages\Browser\BrowserSupport;
use Yireo\NextGenImages\Image\SourceImageFactory;
use Yireo\NextGenImages\Config\Config;

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
     * @var SourceImageFactory
     */
    private $sourceImageFactory;

    /**
     * CorrectImagesInAjaxResponse constructor.
     *
     * @param BrowserSupport $browserSupport
     * @param Config $config
     * @param SourceImageFactory $sourceImageFactory
     */
    public function __construct(
        BrowserSupport $browserSupport,
        Config $config,
        SourceImageFactory $sourceImageFactory
    ) {
        $this->browserSupport = $browserSupport;
        $this->config = $config;
        $this->sourceImageFactory = $sourceImageFactory;
    }

    /**
     * @param Data $dataHelper
     * @param array $data
     * @return mixed[]
     */
    public function afterGetProductMediaGallery(Data $dataHelper, array $data): array
    {
        if (!$this->config->enabled()) {
            return $data;
        }

        if (!$this->browserSupport->hasWebpSupport()) {
            return $data;
        }

        return $this->replaceUrls($data);
    }

    /**
     * @param mixed[] $dataArray
     * @return mixed[]
     */
    private function replaceUrls(array $dataArray): array
    {
        if (empty($dataArray)) {
            return $dataArray;
        }

        foreach ($dataArray as $name => $value) {
            if (is_array($value)) {
                $dataArray[$name] = $this->replaceUrls($value);
                continue;
            }

            if (!is_string($value)) {
                continue;
            }

            if (!preg_match('/\.(jpg|png)$/', $value, $match)) {
                continue;
            }

            $mimeType = 'image/' . $match[0];
            $sourceImage = $this->sourceImageFactory->create(['url' => $value, 'mimeType' => $mimeType]);
            $dataArray[$name] = $sourceImage->getUrl();
        }

        return $dataArray;
    }
}
