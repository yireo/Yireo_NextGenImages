<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Magento\Swatches\Helper\Data;
use Yireo\NextGenImages\Browser\BrowserSupport;
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
     * CorrectImagesInAjaxResponse constructor.
     *
     * @param BrowserSupport $browserSupport
     * @param Config $config
     */
    public function __construct(
        BrowserSupport $browserSupport,
        Config $config
    ) {
        $this->browserSupport = $browserSupport;
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

            if (!$this->isValidUrl((string)$value)) {
                continue;
            }

            // @todo: Shouldn't this be a nextgen image?
            $dataArray[$name] = $value;
        }

        return $dataArray;
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
