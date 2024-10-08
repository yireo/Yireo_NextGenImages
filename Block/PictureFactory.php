<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Block;

use Magento\Framework\View\LayoutInterface;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Image\Image;

class PictureFactory
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param LayoutInterface $layout
     * @param Config $config
     */
    public function __construct(
        LayoutInterface $layout,
        Config $config
    ) {
        $this->layout = $layout;
        $this->config = $config;
    }

    /**
     * @param Image $originalImage
     * @param Image[] $images
     * @param string $htmlTag
     * @param string $srcAttribute
     * @return Picture
     */
    public function create(
        Image $originalImage,
        array $images,
        string $htmlTag,
        string $srcAttribute = 'src'
    ): Picture {
        /** @var Picture $picture */
        $picture = $this->layout->createBlock(Picture::class);
        $picture
            ->setOriginalImage($originalImage)
            ->setImages($images)
            ->setTitle($this->getAttributeText($htmlTag, 'title'))
            ->setAltText($this->getAttributeText($htmlTag, 'alt'))
            ->setStyle($this->getAttributeText($htmlTag, 'style'))
            ->setClass($this->getAttributeText($htmlTag, 'class'))
            ->setWidth($this->getAttributeText($htmlTag, 'width'))
            ->setHeight($this->getAttributeText($htmlTag, 'height'))
            ->setOriginalTag($htmlTag)
            ->setLazyLoading($this->getAttributeText($htmlTag, 'loading') == "lazy" ?: $this->config->addLazyLoading())
            ->setSrcAttribute($srcAttribute)
            ->setDebug($this->config->isDebugging());
        return $picture;
    }

    /**
     * @param string $htmlTag
     * @param string $attribute
     * @return string
     */
    private function getAttributeText(string $htmlTag, string $attribute): string
    {
        if (preg_match('/\ ' . $attribute . '=\"([^\"]+)/', $htmlTag, $match)) {
            $altText = $match[1];
            return strtr($altText, ['"' => '', "'" => '']);
        }

        return '';
    }
}
