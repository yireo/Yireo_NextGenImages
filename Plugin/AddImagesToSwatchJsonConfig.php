<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Swatches\Block\Product\Renderer\Configurable;
use Magento\Swatches\Model\Swatch;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Image\ImageCollector;

class AddImagesToSwatchJsonConfig
{
    private const TARGET_CODE = 'webp';

    public function __construct(
        private readonly Config $config,
        private readonly SerializerInterface $serializer,
        private readonly ImageCollector $imageCollector
    ) {
    }

    public function afterGetJsonSwatchConfig(Configurable $subject, string $jsonSwatchConfig): string
    {
        if (!$this->config->enabled()) {
            return $jsonSwatchConfig;
        }

        $swatchConfig = $this->serializer->unserialize($jsonSwatchConfig);
        if (empty($swatchConfig) || !is_array($swatchConfig)) {
            return $jsonSwatchConfig;
        }

        foreach ($swatchConfig as $attributeId => $options) {
            if (!is_array($options)) {
                continue;
            }

            foreach ($options as $optionId => $option) {
                if (!is_array($option) || (int)($option['type'] ?? 0) !== Swatch::SWATCH_TYPE_VISUAL_IMAGE) {
                    continue;
                }

                foreach (['value', 'thumb'] as $key) {
                    if (empty($option[$key]) || !is_string($option[$key])) {
                        continue;
                    }

                    $option[$key] = $this->getTargetUrl($option[$key]);
                }

                $swatchConfig[$attributeId][$optionId] = $option;
            }
        }

        return $this->serializer->serialize($swatchConfig);
    }

    private function getTargetUrl(string $url): string
    {
        foreach ($this->imageCollector->collect($url) as $image) {
            if ($image->getCode() === self::TARGET_CODE) {
                return $image->getUrl();
            }
        }

        return $url;
    }
}
