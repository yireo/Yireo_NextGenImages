<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Exception;
use Magento\Swatches\Helper\Data;
use Yireo\NextGenImages\Browser\BrowserSupport;
use Yireo\NextGenImages\Image\UrlReplacer;
use Yireo\NextGenImages\Logger\Debugger;
use Yireo\NextGenImages\Config\Config;

class CorrectImagesInAjaxResponse
{
    /**
     * @var BrowserSupport
     */
    private $browserSupport;

    /**
     * @var UrlReplacer
     */
    private $urlReplacer;
    /**
     * @var Debugger
     */
    private $debugger;
    /**
     * @var Config
     */
    private $config;

    /**
     * CorrectImagesInAjaxResponse constructor.
     *
     * @param BrowserSupport $browserSupport
     * @param UrlReplacer $urlReplacer
     * @param Debugger $debugger
     * @param Config $config
     */
    public function __construct(
        BrowserSupport $browserSupport,
        UrlReplacer $urlReplacer,
        Debugger $debugger,
        Config $config
    ) {
        $this->browserSupport = $browserSupport;
        $this->urlReplacer = $urlReplacer;
        $this->debugger = $debugger;
        $this->config = $config;
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

        $data = $this->replaceUrls($data);
        return $data;
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

            if (!preg_match('/\.(jpg|png)$/', $value)) {
                continue;
            }

            try {
                $dataArray[$name] = $this->urlReplacer->getNewImageUrlFromImageUrl($value);
            } catch (Exception $e) {
                $this->debugger->debug($e->getMessage(), [$name => $value]);
                continue;
            }
        }

        return $dataArray;
    }
}
