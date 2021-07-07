<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Magento\Framework\View\LayoutInterface;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Image\HtmlReplacer;

class ReplaceTagsPlugin
{
    /**
     * @var HtmlReplacer
     */
    private $htmlReplacer;
    /**
     * @var Config
     */
    private $config;

    /**
     * ReplaceTags constructor.
     *
     * @param HtmlReplacer $htmlReplacer
     * @param Config $config
     */
    public function __construct(
        HtmlReplacer $htmlReplacer,
        Config $config
    ) {
        $this->htmlReplacer = $htmlReplacer;
        $this->config = $config;
    }

    /**
     * Interceptor of getOutput()
     *
     * @param LayoutInterface $layout
     * @param string $output
     * @return string
     */
    public function afterGetOutput(LayoutInterface $layout, string $output): string
    {
        if (!$this->config->enabled()) {
            return $output;
        }

        if ($this->shouldModifyOutput($layout) === false) {
            return $output;
        }

        return $this->htmlReplacer->replaceImagesInHtml($layout, $output);
    }

    /**
     * @param LayoutInterface $layout
     * @return bool
     */
    private function shouldModifyOutput(LayoutInterface $layout): bool
    {
        $handles = $layout->getUpdate()->getHandles();
        if (empty($handles)) {
            return false;
        }

        foreach ($handles as $handle) {
            if (strstr($handle, '_email_')) {
                return false;
            }
        }

        $skippedHandles = [
            'webp_skip',
            'nextgenimages_skip',
        ];

        if (array_intersect($skippedHandles, $handles)) {
            return false;
        }

        return true;
    }
}
